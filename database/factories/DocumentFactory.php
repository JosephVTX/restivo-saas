<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 10, 300);
        $tax = round($total - ($total / 1.18), 2);

        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'payment_id' => null,
            'customer_id' => null,
            'type' => DocumentType::NotaVenta,
            'series' => 'NV01',
            'number' => fake()->unique()->numberBetween(1, 99999),
            'status' => DocumentStatus::Issued,
            'customer_doc_type' => null,
            'customer_doc_number' => null,
            'customer_name' => null,
            'customer_address' => null,
            'currency' => 'PEN',
            'subtotal' => round($total - $tax, 2),
            'tax_total' => $tax,
            'total' => $total,
            'tip' => 0,
            'issue_date' => now()->toDateString(),
            'sent_at' => null,
        ];
    }

    public function boleta(): static
    {
        return $this->state(fn (): array => [
            'type' => DocumentType::Boleta,
            'series' => 'B001',
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => DocumentStatus::Accepted,
            'sunat_code' => '0',
            'sunat_description' => 'La Boleta numero B001-1 ha sido aceptada',
            'sent_at' => now(),
        ]);
    }
}
