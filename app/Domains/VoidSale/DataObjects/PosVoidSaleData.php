<?php

declare(strict_types=1);

namespace App\Domains\VoidSale\DataObjects;

use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PosVoidSaleData extends Data
{
    public function __construct(
        public int $void_sale_reason_id,
        public int $voided_by_store_manager_id,
        public string $passcode,
        public ?string $store_manager_authorization_code = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(int $companyId): array
    {
        $voidSaleReasonQueries = new VoidSaleReasonQueries();

        return [
            'voided_by_store_manager_id' => ['required', 'integer'],
            'passcode' => ['required', 'string'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
            'void_sale_reason_id' => [
                'required',
                'integer',
                Rule::exists('void_sale_reasons', 'id')
                    ->where($voidSaleReasonQueries->filterByCompany($companyId)),
            ],
        ];
    }
}
