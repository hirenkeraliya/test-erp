<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Voucher;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\Resources\EcommerceVoucherListResource;
use App\Domains\Voucher\Resources\PosVoucherListResource;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherConfigurationChannelReference\VoucherConfigurationChannelReferenceQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherController extends Controller
{
    public function getVouchers(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $voucherQueries = resolve(VoucherQueries::class);
        $voucher = $voucherQueries->getListForEcommerceWithRelatedData($saleChannel->getCompanyId(), $afterUpdatedAt);

        return [
            'vouchers' => EcommerceVoucherListResource::collection($voucher),
        ];
    }

    public function generateMemberLoyaltyPointVoucher(
        LoyaltyPointVoucherData $loyaltyPointVoucherData,
        Request $request,
    ): array {
        $saleChannel = $request->user();
        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $locationId = $saleChannel->default_location_id;
        $companyId = $saleChannel->getCompanyId();

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberChannelReference = $memberChannelReferenceQueries->getByExternalMemberIdAndSaleChannelId(
            $loyaltyPointVoucherData->member_id,
            $saleChannel->id
        );

        if (! $memberChannelReference) {
            abort(412, 'Member channel reference not found for the provided external member ID.');
        }

        $voucherConfigurationChannelReferenceQueries = resolve(VoucherConfigurationChannelReferenceQueries::class);
        $voucherConfigurationChannelReference = $voucherConfigurationChannelReferenceQueries->getByExternalVoucherConfigurationIdAndSaleChannelId(
            $loyaltyPointVoucherData->voucher_configuration_id,
            $saleChannel->id
        );

        if (! $voucherConfigurationChannelReference) {
            abort(
                412,
                'Voucher configuration channel reference not found for the provided external voucher configuration ID.'
            );
        }

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByIdAndCompanyIdWithMembership($memberChannelReference->member_id, $companyId);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getById(
            $voucherConfigurationChannelReference->voucher_configuration_id,
            $companyId
        );

        $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
        $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);

        DB::beginTransaction();

        try {
            $getValue = $loyaltyPointVoucherService->getVoucherTierValue(
                $loyaltyPointVoucherData->loyalty_points,
                $voucherConfiguration
            );

            $expiryDate = now()->addDays($voucherConfiguration->validity_days);

            $voucherQueries = resolve(VoucherQueries::class);
            $voucher = $voucherQueries->addNew(
                $voucherConfiguration,
                $getValue,
                $voucherConfiguration->discount_type,
                $expiryDate,
                $member->id,
                null,
                null,
                $locationId,
            );

            $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CREATED->value,
                now()->format('Y-m-d H:i:s'),
                null,
                $locationId
            );

            $loyaltyPointService = resolve(LoyaltyPointService::class);
            $loyaltyPointService->decreaseLoyaltyPoints(
                $member,
                $loyaltyPointVoucherData->loyalty_points,
                LoyaltyPointUpdateTypes::VOUCHER->value,
                $voucher->id,
                ModelMapping::VOUCHER->name,
                now()->format('Y-m-d H:i:s')
            );

            DB::commit();

            $voucher = $voucherQueries->loadVoucherWithMismatchesRelations($voucher);

            return [
                'voucher' => new PosVoucherListResource($voucher),
            ];
        } catch (Throwable $throwable) {
            Log::error('Loyalty Point Voucher Generate', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }
}
