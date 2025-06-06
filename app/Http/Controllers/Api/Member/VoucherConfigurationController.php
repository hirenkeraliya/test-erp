<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\VoucherConfiguration\Resources\PosLoyaltyPointVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class VoucherConfigurationController extends Controller
{
    public function getLoyaltyPointVoucherConfiguration(Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $companyId = $member->company_id;

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $listLoyaltyPointVoucher = $voucherConfigurationQueries->getListLoyaltyPointForPosWithRelatedData($companyId);

        return [
            'vouchers' => PosLoyaltyPointVoucherConfigurationListResource::collection($listLoyaltyPointVoucher),
        ];
    }
}
