<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\Billing\DocumentService;
use App\Services\Billing\GreenterGateway;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    private function paidOrder(Tenant $tenant, float $total = 118): Order
    {
        return Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'subtotal' => round($total / 1.18, 2),
            'tax_total' => round($total - $total / 1.18, 2),
            'total' => $total,
            'paid_total' => $total,
        ]);
    }

    private function configureBilling(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        app(DocumentService::class)->updateSettings([
            'enabled' => true,
            'ruc' => '20123456789',
            'business_name' => 'RESTAURANTE DEMO SAC',
            'trade_name' => 'Restaurante Demo',
            'address' => 'Av. Siempre Viva 123, Lima',
            'ubigeo' => '150101',
            'sol_user' => 'MODDATOS',
            'sol_password' => 'moddatos',
            'certificate_path' => '/tmp/demo.pem',
            'mode' => 'beta',
            'boleta_series' => 'B001',
            'igv_rate' => 0.18,
        ]);

        app(TenantContext::class)->clear();
    }

    public function test_emit_a_nota_venta_generates_a_pdf(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Nota');
        $order = $this->paidOrder($tenant);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/documents', ['type' => 'nota_venta'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'nota_venta')
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.has_pdf', true)
            ->assertJsonPath('data.full_number', 'NV01-00000001');

        $this->assertDatabaseHas('documents', [
            'tenant_id' => $tenant->id,
            'type' => 'nota_venta',
            'status' => 'issued',
        ]);

        $document = Document::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertNotNull($document->pdf_path);
        Storage::disk('local')->assertExists($document->pdf_path);
    }

    public function test_emit_a_document_with_a_customer_links_and_exposes_it(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Customer');
        $order = $this->paidOrder($tenant);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/documents', [
            'type' => 'nota_venta',
            'customer_id' => $customer->uuid,
        ])
            ->assertCreated()
            ->assertJsonPath('data.customer_uuid', $customer->uuid);

        $this->assertDatabaseHas('documents', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_document_numbering_is_correlative(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Numbering');
        $first = $this->paidOrder($tenant);
        $second = $this->paidOrder($tenant);

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/orders/'.$first->uuid.'/documents', ['type' => 'nota_venta'])
            ->assertCreated()
            ->assertJsonPath('data.number', 1);

        $this->postJson('/api/v1/orders/'.$second->uuid.'/documents', ['type' => 'nota_venta'])
            ->assertCreated()
            ->assertJsonPath('data.number', 2);
    }

    public function test_a_factura_requires_a_ruc(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Factura');
        $order = $this->paidOrder($tenant);

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/documents', ['type' => 'factura'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_doc_type', 'customer_doc_number']);
    }

    public function test_an_electronic_boleta_is_sent_to_sunat_when_configured(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Electronic');
        $order = $this->paidOrder($tenant);
        $this->configureBilling($tenant);

        $this->mock(GreenterGateway::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('buildPem')->andReturn('PEM-CONTENT');
            $mock->shouldReceive('send')->andReturn([
                'success' => true,
                'code' => '0',
                'description' => 'La Boleta numero B001-1 ha sido aceptada',
                'xml' => '<Invoice/>',
                'cdr' => 'ZIP-CONTENT',
            ]);
        });

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/documents', [
            'type' => 'boleta',
            'customer_doc_number' => '12345678',
            'customer_name' => 'Cliente Demo',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.sunat_code', '0')
            ->assertJsonPath('data.has_xml', true)
            ->assertJsonPath('data.has_cdr', true);

        $document = Document::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertNotNull($document->xml_path);
        $this->assertNotNull($document->cdr_path);
        Storage::disk('local')->assertExists($document->xml_path);
        Storage::disk('local')->assertExists($document->cdr_path);
    }

    public function test_an_electronic_boleta_fails_when_billing_is_not_configured(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Unconfigured');
        $order = $this->paidOrder($tenant);

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/documents', ['type' => 'boleta'])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_annulling_a_non_accepted_document_marks_it_annulled(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Annul');
        $order = $this->paidOrder($tenant);
        $document = Document::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => DocumentStatus::Issued,
        ]);

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/documents/'.$document->uuid.'/annul')
            ->assertOk()
            ->assertJsonPath('data.status', 'annulled');

        $this->assertDatabaseHas('documents', ['id' => $document->id, 'status' => 'annulled']);
    }

    public function test_an_accepted_document_cannot_be_annulled(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Accepted');
        $order = $this->paidOrder($tenant);
        $document = Document::factory()->accepted()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
        ]);

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/documents/'.$document->uuid.'/annul')
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_index_filters_by_type_and_status(): void
    {
        $tenant = $this->createTenant('Document Filter');
        $order = $this->paidOrder($tenant);

        Document::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'type' => DocumentType::NotaVenta,
            'status' => DocumentStatus::Issued,
        ]);
        Document::factory()->boleta()->accepted()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->getJson('/api/v1/documents?filter[type]=boleta')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/documents?filter[status]=accepted')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/documents')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_waiter_cannot_issue_documents(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Document Waiter');
        $order = $this->paidOrder($tenant);

        $this->actingAsMember($tenant, 'waiter');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/documents', ['type' => 'nota_venta'])
            ->assertForbidden();
    }
}
