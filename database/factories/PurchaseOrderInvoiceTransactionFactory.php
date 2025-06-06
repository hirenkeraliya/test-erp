<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderInvoiceTransaction>
 */
class PurchaseOrderInvoiceTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_order_invoice_id' => fn () => PurchaseOrderInvoice::factory()->create()->id,
            'old_status' => fake()->boolean(),
            'new_status' => fake()->boolean(),
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
