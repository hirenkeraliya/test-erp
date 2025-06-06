<?php

declare(strict_types=1);

namespace App\Domains\PromoterGroup\DataObjects;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class PromoterGroupData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public int $type_id,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $promoterGroupId = null;
        $promoterGroupQueries = new PromoterGroupQueries();
        $companyId = session('admin_company_id');

        if ('admin.promoter_groups.update' === $request->route()?->getName()) {
            $promoterGroupId = $request->route()->parameter('promoterGroupId');
        }

        if ('store_manager.promoter_groups.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.promoter_groups.update' === $request->route()?->getName()) {
            $promoterGroupId = $request->route()->parameter('promoterGroupId');
            $companyId = session('store_manager_selected_location_company_id');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('promoter_groups', 'name')->ignore($promoterGroupId)
                    ->where($promoterGroupQueries->filterByCompany($companyId)),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('promoter_groups', 'code')->ignore($promoterGroupId)
                    ->where($promoterGroupQueries->filterByCompany($companyId)),
            ],
            'type_id' => ['required', 'integer', 'in:' . SaleReturnOrVoidSaleReasonTypes::getValues()],
        ];
    }
}
