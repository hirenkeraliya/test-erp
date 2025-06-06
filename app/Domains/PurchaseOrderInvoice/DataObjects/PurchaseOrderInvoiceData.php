<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\DataObjects;

use Spatie\LaravelData\Data;

class PurchaseOrderInvoiceData extends Data
{
    public function __construct(
        public int $purchase_order_id,
        public ?array $fulfillment_ids,
    ) {
    }

    public static function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer'],
            'fulfillment_ids' => ['array', 'nullable'],
        ];
    }
}
