<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\LoyaltyPointUpdate\Resources\MemberAppLoyaltyPointsUpdateListResource;
use App\Domains\LoyaltyPointUpdate\Resources\MemberAppLoyaltyPointUsedListResource;
use App\Domains\Member\DataObjects\AppMemberData;
use App\Domains\Member\DataObjects\RegisterMemberData;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\StaticMembers;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\MemberAppListApiResource;
use App\Domains\Member\Resources\PaginatedVouchersListResource;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MemberController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedVoucherList(Request $request): array
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,number'],
            'sort_direction' => ['sometimes', 'string'],
            'status' => ['sometimes', 'integer'],
        ]);

        $filteredData = [
            'per_page' => $request->per_page,
            'sort_by' => $request->sort_by,
            'sort_direction' => $request->sort_direction,
            'status' => $request->status,
        ];
        /** @var Member $member */
        $member = $request->user();

        // this data is for the static voucher details for static members
        if ($member->mobile_number === StaticMembers::STATIC_MEMBER->value) {
            /** @var string $filePath */
            $filePath = file_get_contents(public_path('files/static_voucher_details.json'));

            return json_decode($filePath, true, 512, JSON_THROW_ON_ERROR);
        }

        $voucherQueries = resolve(VoucherQueries::class);
        $vouchers = $voucherQueries->getPaginatedListForMemberApi($filteredData, $member->id);

        return [
            'vouchers' => PaginatedVouchersListResource::collection($vouchers),
            'total_records' => $vouchers->total(),
            'last_page' => $vouchers->lastPage(),
            'current_page' => $vouchers->currentPage(),
            'per_page' => $vouchers->perPage(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getGenders(): array
    {
        return [
            'genders' => Genders::getList(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getRaces(): array
    {
        return [
            'races' => Races::getList(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getTitles(): array
    {
        return [
            'titles' => Titles::getList(),
        ];
    }

    public function updateProfile(AppMemberData $appMemberData, Request $request): void
    {
        /** @var Member $member */
        $member = $request->user();

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updateMemberProfile($appMemberData, $member);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaginatedTransactionList(Request $request): array
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,number'],
            'sort_direction' => ['sometimes', 'string'],
        ]);

        $filteredData = [
            'per_page' => $request->per_page,
            'sort_by' => $request->sort_by,
            'sort_direction' => $request->sort_direction,
            'status' => $request->status,
        ];

        /** @var Member $member */
        $member = $request->user();

        // this data is for the static voucher details for static members
        if ($member->mobile_number === StaticMembers::STATIC_MEMBER->value) {
            /** @var string $filePath */
            $filePath = file_get_contents(public_path('files/static_loyalty_points_update_details.json'));

            return json_decode($filePath, true, 512, JSON_THROW_ON_ERROR);
        }

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdates = $loyaltyPointUpdateQueries->getPaginatedTransactionListForMemberApi(
            $filteredData,
            $member->id
        );
        $totalPointRewarded = $loyaltyPointUpdateQueries->getTotalPointRewarded($member->id);
        $totalPointsRedeemed = $loyaltyPointUpdateQueries->getTotalPointsRedeemed($member->id);

        return [
            'total_points' => $totalPointRewarded,
            'redeem_points' => $totalPointsRedeemed,
            'available_points' => $member->loyalty_points,
            'loyalty_points' => MemberAppLoyaltyPointsUpdateListResource::collection(
                $loyaltyPointUpdates->getCollection()
            ),
            'total_records' => $loyaltyPointUpdates->total(),
            'last_page' => $loyaltyPointUpdates->lastPage(),
            'current_page' => $loyaltyPointUpdates->currentPage(),
            'per_page' => $loyaltyPointUpdates->perPage(),
        ];
    }

    public function uploadProfilePhoto(Request $request): void
    {
        $filteredData = $request->validate([
            'photo' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                'max:' . config('services.max_upload_size'),
            ],
        ]);

        /** @var Member $member */
        $member = $request->user();

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->uploadProfilePhoto($filteredData, $member);
    }

    /**
     * @return array<string, mixed>
     */
    public function memberDetails(Request $request): array
    {
        $memberQueries = resolve(MemberQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);

        /** @var Member $member */
        $member = $request->user();

        $member = $memberQueries->loadRelations($member);

        $totalActiveVoucher = $voucherQueries->getActiveVoucherCountFor($member->id);

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $member->latestFiveLoyaltyPointUpdates;

        return [
            'member_details' => new MemberAppListApiResource($member),
            'last_transactions_details' => MemberAppLoyaltyPointsUpdateListResource::collection($loyaltyPointUpdates),
            'currently_available_loyalty_points' => $member->loyalty_points,
            'active_voucher_count' => $totalActiveVoucher,
        ];
    }

    public function deleteMember(Request $request): void
    {
        /** @var Member $member */
        $member = $request->user();

        $memberQueries = resolve(MemberQueries::class);

        $memberQueries->deleteMember($member);
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getVoucherStatuses(): array
    {
        return [
            'statuses' => VoucherStatusTypes::getList(),
        ];
    }

    public function registerMember(RegisterMemberData $registerMemberData): void
    {
        $validateData = $registerMemberData->all();

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->addNewOpenMemberRegistration($validateData, MemberChannelEnum::M_COMMERCE->value);
    }

    public function emailVerification(Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $memberQueries = resolve(MemberQueries::class);

        $member = $memberQueries->getByIdForEmailVerification($member->id, $member->company_id);

        if (null === $member->email) {
            abort(412, 'The email is not set.');
        }

        if ($member->is_email_verified) {
            abort(412, 'The email is already verified.');
        }

        EmailVerificationJob::dispatch($member)->delay(now()->addSeconds(5))->onQueue('high');

        return [
            'message' => 'The verification mail sent successfully.',
        ];
    }

    public function getLoyaltyPointUsedDetails(Request $request, int $loyaltyPointUpdateId): array
    {
        /** @var Member $member */
        $member = $request->user();

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdate = $loyaltyPointUpdateQueries->getSalesById($loyaltyPointUpdateId, $member->id);

        return [
            'loyalty_point_used_details' => new MemberAppLoyaltyPointUsedListResource($loyaltyPointUpdate),
        ];
    }
}
