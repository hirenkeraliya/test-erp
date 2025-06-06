<?php

declare(strict_types=1);

namespace App\Domains\SaleReturnReason\DataObjects;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class SaleReturnReasonData extends Data
{
    public function __construct(
        public string $reason,
        public bool $put_back_in_inventory,
        public array $type_ids,
        public ?int $location_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $saleReturnReasonId = null;
        $saleReturnReasonQueries = new SaleReturnReasonQueries();

        if ('admin.sale_return_reasons.update' === $request->route()?->getName()) {
            $saleReturnReasonId = $request->route()->parameter('saleReturnReasonId');
        }

        return [
            'reason' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sale_return_reasons', 'reason')->ignore($saleReturnReasonId)
                    ->where($saleReturnReasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'type_ids' => ['required', 'array'],
            'type_ids.*' => ['required', 'integer', 'in:' . SaleReturnOrVoidSaleReasonTypes::getValues()],
            'location_id' => ['required_if:put_back_in_inventory,false', 'nullable', 'integer'],
            'put_back_in_inventory' => ['required', 'boolean'],
        ];
    }
}
