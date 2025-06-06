<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\PosMismatch\Services\PosMismatchService;
use App\Domains\Voucher\DataObjects\BirthdayVoucherData;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\DataObjects\PaginatedVoucherListDataForPos;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\Voucher\Resources\PosBirthdayVoucherResource;
use App\Domains\Voucher\Resources\PosVoucherListResource;
use App\Domains\Voucher\Services\BirthdayVoucherCheckRequestService;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getPaginatedList(
        Request $request,
        PaginatedVoucherListDataForPos $paginatedVoucherListDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $filterData = [
            'per_page' => $paginatedVoucherListDataForPos->per_page,
            'after_updated_at' => $paginatedVoucherListDataForPos->after_updated_at,
        ];

        $voucherQueries = resolve(VoucherQueries::class);
        $voucherList = $voucherQueries->getPaginatedList($filterData, $companyId);

        return [
            'vouchers' => PosVoucherListResource::collection($voucherList),
            'total_records' => $voucherList->total(),
            'last_page' => $voucherList->lastPage(),
            'current_page' => $voucherList->currentPage(),
            'per_page' => $voucherList->perPage(),
        ];
    }

    public function generateMemberBirthdayVoucher(
        BirthdayVoucherData $birthdayVoucherData,
        Request $request,
        int $memberId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById($companyId, $memberId);

        /** @var Carbon $today */
        $today = Carbon::createFromFormat('Y-m-d H:i:s', $birthdayVoucherData->happened_at);

        $dateOfBirth = '';
        if ($member->date_of_birth) {
            /** @var Carbon $dateOfBirthFormat */
            $dateOfBirthFormat = Carbon::createFromFormat('Y-m-d', $member->date_of_birth);
            $dateOfBirth = $dateOfBirthFormat->format('m');
        }

        if (
            $today->format('m') !== $dateOfBirth
        ) {
            abort(412, 'The specified member`s birthday is not in the current month.');
        }

        $birthdayVoucherLastGeneratedAt = '';
        if ($member->birthday_voucher_last_generated_at) {
            /** @var Carbon $birthdayVoucherLastGeneratedAtFormat */
            $birthdayVoucherLastGeneratedAtFormat = Carbon::createFromFormat(
                'Y-m-d',
                $member->birthday_voucher_last_generated_at
            );
            $birthdayVoucherLastGeneratedAt = $birthdayVoucherLastGeneratedAtFormat->format('Y-m');
        }

        if (
            $today->format('Y-m') === $birthdayVoucherLastGeneratedAt
        ) {
            abort(412, 'The member`s birthday voucher has already been generated');
        }

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getByIdForBirthdayVoucher(
            $birthdayVoucherData->voucher_configuration_id,
            $companyId,
            $today
        );

        $birthdayVoucherCheckRequestService = resolve(BirthdayVoucherCheckRequestService::class);
        $birthdayVoucherCheckRequestService->setDetails();
        $birthdayVoucherCheckRequestService->checkRequestDetails(
            $birthdayVoucherData,
            $voucherConfiguration,
            $companyId
        );
        $expiryDate = null;
        if ($voucherConfiguration->validity_days) {
            $expiryDate = $today->addDays($voucherConfiguration->validity_days);
        }

        $posMismatchQueries = resolve(PosMismatchQueries::class);

        $voucherQueries = resolve(VoucherQueries::class);

        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);

        DB::beginTransaction();

        try {
            $voucher = $voucherQueries->addNew(
                $voucherConfiguration,
                (float) $voucherConfiguration->get_value,
                $voucherConfiguration->discount_type,
                $expiryDate,
                $member->id,
            );

            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CREATED->value,
                $birthdayVoucherData->happened_at,
                null,
                $location->id
            );

            $memberQueries->updateBirthdayVoucherDetails($member, $voucher->id);

            foreach ($birthdayVoucherCheckRequestService->birthdayVoucherMismatches as $birthdayVoucherMismatch) {
                $posMismatchQueries->addNew($voucher, $birthdayVoucherMismatch);
            }

            DB::commit();

            $voucher = $voucherQueries->loadVoucherWithMismatchesRelations($voucher);

            if ($voucher->mismatches->isNotEmpty()) {
                $messages = $voucher->mismatches->pluck('message')->toArray();

                $posMismatchService = resolve(PosMismatchService::class);
                $posMismatchService->logMismatchEntries(
                    'Birthday Voucher Generate Mismatches',
                    $voucher->id,
                    $messages,
                    null
                );
            }

            return [
                'birthday_voucher' => new PosBirthdayVoucherResource($voucher),
            ];
        } catch (Throwable $throwable) {
            Log::error('Birthday Voucher Generate', [
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

    /**
     * @return array<string, PosBirthdayVoucherResource>|null[]
     */
    public function getActiveBirthdayVoucher(Request $request, int $memberId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $memberQueries = resolve(MemberQueries::class);

        $birthdayVoucherDetails = $memberQueries->getActiveBirthdayVoucher($companyId, $memberId);

        return [
            'birthday_voucher' => $birthdayVoucherDetails ? new PosBirthdayVoucherResource(
                $birthdayVoucherDetails->birthdayVoucher
            ) : null,
        ];
    }

    public function generateMemberLoyaltyPointVoucher(
        LoyaltyPointVoucherData $loyaltyPointVoucherData,
        Request $request,
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

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
                null,
                null,
                $location->id,
            );

            $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CREATED->value,
                now()->format('Y-m-d H:i:s'),
                null,
                $location->id
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

    public function getStatuses(): array
    {
        return [
            'statuses' => VoucherStatusTypes::getList(),
        ];
    }
}
