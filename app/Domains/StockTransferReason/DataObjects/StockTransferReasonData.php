<?php

declare(strict_types=1);

namespace App\Domains\StockTransferReason\DataObjects;

use App\Domains\StockTransferReason\StockTransferReasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class StockTransferReasonData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $stockTransferReasonId = null;
        $stockTransferReasonQueries = new StockTransferReasonQueries();

        if ('admin.stock_transfer_reasons.update' === $request->route()?->getName()) {
            $stockTransferReasonId = $request->route()->parameter('stockTransferReasonId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stock_transfer_reasons', 'name')->ignore($stockTransferReasonId)
                    ->where($stockTransferReasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stock_transfer_reasons', 'code')->ignore($stockTransferReasonId)
                    ->where($stockTransferReasonQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}
