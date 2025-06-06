<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\Resources\PosVoucherListResource;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\Resources\MemberAppVoucherUsedListResource;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherController extends Controller
{
    public function generateMemberLoyaltyPointVoucher(
        LoyaltyPointVoucherData $loyaltyPointVoucherData,
        Request $request,
    ): array {
        /** @var Member $member */
        $member = $request->user();

        $companyId = $member->company_id;

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByIdAndCompanyIdWithMembership($loyaltyPointVoucherData->member_id, $companyId);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getById(
            $loyaltyPointVoucherData->voucher_configuration_id,
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
            );

            $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CREATED->value,
                now()->format('Y-m-d H:i:s'),
                null,
                null
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

    public function getVoucherUsedDetails(Request $request, int $voucherId): array
    {
        /** @var Member $member */
        $member = $request->user();

        $voucherQueries = resolve(VoucherQueries::class);
        $voucher = $voucherQueries->getByOnlyId($voucherId);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $voucherCreateTransaction = $voucherTransactionQueries->getSaleByIdAndTypeFirst(
            $voucherId,
            $member->id,
            VoucherTransactionActionTypes::CREATED->value
        );
        $voucherUsedTransaction = $voucherTransactionQueries->getSaleByIdAndTypeFirst(
            $voucherId,
            $member->id,
            VoucherTransactionActionTypes::USED->value
        );

        return [
            'voucher_used_details' => new MemberAppVoucherUsedListResource(
                $voucher,
                $voucherCreateTransaction,
                $voucherUsedTransaction
            ),
        ];
    }
}
