<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TenantBillingSetting;
use App\Services\Billing\SunatInvoiceBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class SunatInvoiceBuilderTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_it_builds_a_boleta_with_taxed_totals_and_a_soles_legend(): void
    {
        $tenant = $this->createTenant('Invoice Builder');

        $settings = TenantBillingSetting::factory()->create([
            'tenant_id' => $tenant->id,
            'ruc' => '20123456789',
            'business_name' => 'RESTAURANTE DEMO SAC',
            'igv_rate' => 0.18,
        ]);

        $order = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'subtotal' => 100,
            'tax_total' => 18,
            'total' => 118,
        ]);

        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'tax_type' => 'gravado',
            'quantity' => 1,
            'unit_price' => 118,
            'line_total' => 118,
        ]);

        $invoice = app(SunatInvoiceBuilder::class)->build(
            $order,
            $settings,
            DocumentType::Boleta,
            'B001',
            1,
            ['doc_number' => '12345678', 'name' => 'Cliente Demo'],
        );

        $this->assertSame('03', $invoice->getTipoDoc());
        $this->assertSame(100.0, $invoice->getMtoOperGravadas());
        $this->assertSame(18.0, $invoice->getMtoIGV());
        $this->assertSame(118.0, $invoice->getMtoImpVenta());

        $legends = $invoice->getLegends();
        $this->assertNotEmpty($legends);
        $this->assertStringContainsString('SOLES', $legends[0]->getValue());
        $this->assertSame('1000', $legends[0]->getCode());
    }
}
