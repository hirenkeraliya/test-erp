<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\VoucherConfiguration;

use App\Domains\VoucherConfiguration\Resources\EcommerceVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class VoucherConfigurationController extends Controller
{
    public function __construct(
        protected VoucherConfigurationQueries $voucherConfigurationQueries
    ) {
    }

    public function getVoucherConfigurations(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherList = $voucherConfigurationQueries->getListForEcommerceWithRelatedData(
            $saleChannel->getCompanyId(),
            $afterUpdatedAt
        );

        return [
            'vouchers' => EcommerceVoucherConfigurationListResource::collection($voucherList),
        ];
    }
}
