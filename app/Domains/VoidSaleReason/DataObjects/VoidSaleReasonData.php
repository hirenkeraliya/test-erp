<?php

declare(strict_types=1);

namespace App\Domains\VoidSaleReason\DataObjects;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class VoidSaleReasonData extends Data
{
    public function __construct(
        public string $reason,
        public array $type_ids,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $voidSaleReasonId = null;
        $voidSaleReasonQueries = new VoidSaleReasonQueries();

        if ('admin.void_sale_reasons.update' === $request->route()?->getName()) {
            $voidSaleReasonId = $request->route()->parameter('voidSaleReasonId');
        }

        return [
            'reason' => [
                'required',
                'string',
                'max:255',
                Rule::unique('void_sale_reasons', 'reason')
                    ->ignore($voidSaleReasonId)
                    ->where($voidSaleReasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'type_ids' => ['required', 'array'],
            'type_ids.*' => ['required', 'integer', 'in:' . SaleReturnOrVoidSaleReasonTypes::getValues()],
        ];
    }
}
