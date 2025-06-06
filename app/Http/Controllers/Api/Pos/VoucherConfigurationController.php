<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\VoucherConfiguration\Resources\PosBirthdayVoucherConfigurationResource;
use App\Domains\VoucherConfiguration\Resources\PosLoyaltyPointVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\Resources\PosVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VoucherConfigurationController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherList = $voucherConfigurationQueries->getListForPosWithRelatedData($companyId, $afterUpdatedAt);

        return [
            'vouchers' => PosVoucherConfigurationListResource::collection($voucherList),
        ];
    }

    /**
     * @return array<PosBirthdayVoucherConfigurationResource>|null[]
     */
    public function getBirthdayVoucherConfiguration(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $birthdayVoucher = $voucherConfigurationQueries->getBirthDayVoucherConfigurationByCompanyId($companyId);

        return [
            'birthday_voucher_configuration' => $birthdayVoucher ? new PosBirthdayVoucherConfigurationResource(
                $birthdayVoucher
            ) : null,
        ];
    }

    public function getLoyaltyPointVoucherConfiguration(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $listLoyaltyPointVoucher = $voucherConfigurationQueries->getListLoyaltyPointForPosWithRelatedData(
            $companyId,
            $afterUpdatedAt
        );

        return [
            'vouchers' => PosLoyaltyPointVoucherConfigurationListResource::collection($listLoyaltyPointVoucher),
        ];
    }
}
