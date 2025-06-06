<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderInvoice>
 */
class PurchaseOrderInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_order_id' => fn () => PurchaseOrder::factory()->create()->id,
            'created_by_company_id' => fn () => Company::factory()->create()->id,
            'status' => InvoiceStatuses::DRAFT->value,
        ];
    }
}
