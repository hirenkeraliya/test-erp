<?php

declare(strict_types=1);

namespace App\Domains\Member;

use App\CommonFunctions;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Interfaces\LoyaltyPointsInterface;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Member\DataObjects\AppMemberData;
use App\Domains\Member\DataObjects\MemberData;
use App\Domains\Member\DataObjects\OrderMemberData;
use App\Domains\Member\DataObjects\PosMemberData;
use App\Domains\Member\DataObjects\UpdateMemberAddressData;
use App\Domains\Member\Enums\ConditionOperatorTypes;
use App\Domains\Member\Enums\FilterStatus;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Preferences;
use App\Domains\Member\Enums\PurchaseFilterTypes;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Services\MemberService;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\MemberGroup\Enums\DateConditionTypes;
use App\Domains\MemberGroup\Enums\ElementConditionTypes;
use App\Domains\MemberGroup\Enums\NumberConditionTypes;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\MembershipAssignment\MembershipAssignmentQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Member;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Data;

class MemberQueries implements LoyaltyPointsInterface
{
    public function listQueryForMembers(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->listQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getMembersForExport(array $filterData, int $companyId): Collection
    {
        return $this->listQuery($filterData, $companyId)->get();
    }

    private function getCommonListQuery(array $filterData, int $companyId): Builder
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->when(array_key_exists('status', $filterData) && $filterData['status'], function ($query) use (
                $filterData
            ): void {
                $query->when($filterData['status'] === FilterStatus::ALL->value, function ($query): void {
                    $query->whereIntegerInRaw('status', [Status::ACTIVE->value, Status::INACTIVE->value]);
                });
                $query->when($filterData['status'] === Status::ACTIVE->value, function ($query): void {
                    $query->where('status', Status::ACTIVE->value);
                });
                $query->when($filterData['status'] === Status::INACTIVE->value, function ($query): void {
                    $query->where('status', Status::INACTIVE->value);
                });
            }, function ($query): void {
                $query->where('status', Status::ACTIVE->value);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny([
                            'email',
                            'first_name',
                            'mobile_number',
                            'card_number',
                        ], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('created_location_id', $filterData['location_ids']);
            })
            ->when($filterData['member_group_ids'], function ($query) use ($filterData): void {
                $query->whereHas('memberGroupMembers', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('member_group_id', $filterData['member_group_ids']);
                });
            })
            ->when($filterData['membership_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('membership_id', $filterData['membership_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when(
                $filterData['preference_id'] === Preferences::PREFERRED_COLOR->value && $filterData['color_id'],
                function ($query) use ($filterData): void {
                    $query->where('preferred_color_id', $filterData['color_id']);
                }
            )
            ->when(
                $filterData['preference_id'] === Preferences::PREFERRED_SIZE->value && $filterData['size_id'],
                function ($query) use ($filterData): void {
                    $query->where('preferred_size_id', $filterData['size_id']);
                }
            )
            ->when(
                $filterData['preference_id'] === Preferences::PREFERRED_CATEGORY->value && $filterData['category_id'],
                function ($query) use ($filterData): void {
                    $query->where('preferred_category_id', $filterData['category_id']);
                }
            )
            ->when(
                $filterData['preference_id'] === Preferences::PREFERRED_DATE->value && $filterData['preferred_date'],
                function ($query) use ($filterData): void {
                    $query->where('preferred_date', $filterData['preferred_date']);
                }
            )
            ->when(
                $filterData['preference_id'] === Preferences::PREFERRED_DAY->value && $filterData['preferred_day'],
                function ($query) use ($filterData): void {
                    $query->where('preferred_day', $filterData['preferred_day']);
                }
            )
            ->when(
                $filterData['purchase_filter_type_id'] === PurchaseFilterTypes::UNITS_PURCHASED->value,
                function ($query) use ($filterData): void {
                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::LESS_THAN->value) {
                        $query->where('total_sale_qty', '<', (float) $filterData['purchase_value']);
                    }

                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::GREATER_THAN->value) {
                        $query->where('total_sale_qty', '>', (float) $filterData['purchase_value']);
                    }

                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::EQUAL->value) {
                        $query->where('total_sale_qty', (float) $filterData['purchase_value']);
                    }
                }
            )
            ->when(
                $filterData['purchase_filter_type_id'] === PurchaseFilterTypes::PURCHASES->value,
                function ($query) use ($filterData): void {
                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::LESS_THAN->value) {
                        $query->where('total_sales', '<', (int) $filterData['purchase_value']);
                    }

                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::GREATER_THAN->value) {
                        $query->where('total_sales', '>', (int) $filterData['purchase_value']);
                    }

                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::EQUAL->value) {
                        $query->where('total_sales', (int) $filterData['purchase_value']);
                    }
                }
            )
            ->when(
                $filterData['purchase_filter_type_id'] === PurchaseFilterTypes::LIFT_TIME_VALUE->value,
                function ($query) use ($filterData): void {
                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::LESS_THAN->value) {
                        $query->where('spent_till_now', '<', (float) $filterData['purchase_value']);
                    }

                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::GREATER_THAN->value) {
                        $query->where('spent_till_now', '>', (float) $filterData['purchase_value']);
                    }

                    if ($filterData['condition_operator_type_id'] === ConditionOperatorTypes::EQUAL->value) {
                        $query->where('spent_till_now', (float) $filterData['purchase_value']);
                    }
                }
            )
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function listQuery(array $filterData, int $companyId): Builder
    {
        $membershipQueries = new MembershipQueries();
        $loyaltyPointUpdateQueries = new LoyaltyPointUpdateQueries();
        $saleQueries = resolve(SaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return $this->getCommonListQuery($filterData, $companyId)
            ->select(
                'id',
                'type_id',
                'title_id',
                'race_id',
                'first_name',
                'last_name',
                'gender_id',
                'date_of_birth',
                'mobile_number',
                'email',
                'card_number',
                'last_purchase_date',
                'membership_id',
                'loyalty_points',
                'created_at',
                'updated_at',
                'employee_id',
                'status',
                'is_email_verified',
                'company_name',
                'company_registration_number',
                'company_tax_number',
                'company_address',
                'company_phone',
                'created_location_id',
                'notes',
            )
            ->with([
                'membership:' . $membershipQueries->getColumnNamesForMemberApi(),
                'lastManualUpdateLoyaltyPoint:' . $loyaltyPointUpdateQueries->getBasicColumnsForManualUpdate(),
                'sales:' . $saleQueries->getBasicColumnNames(),
                'sales.saleItems:' . $saleItemQueries->getBasicColumnNames(),
                'createdInLocation:' . $locationQueries->getBasicColumnNames(),
            ])
            ->when(
                isset($filterData['product_id']) && null != $filterData['product_id'],
                function ($query) use ($filterData): void {
                    $query->whereHas('sales.saleItems', static function ($query) use ($filterData): void {
                        $query->select('id')
                            ->where('product_id', $filterData['product_id']);
                    });
                }
            );
    }

    public function changeStatus(int $memberId): void
    {
        $member = Member::select('id', 'status', 'company_id')->findOrFail($memberId);
        $status = $member->status === Status::ACTIVE->value ? Status::INACTIVE->value : Status::ACTIVE->value;
        $member->status = $status;

        $member->save();
    }

    public function getPaginatedMemberReport(array $filterData, int $companyId): array
    {
        $memberCounts = $this->getMemberReport($filterData, $companyId);

        return [
            'total_members' => $this->getTotalMembers($filterData, $companyId),
            'member_data' => $memberCounts->paginate($filterData['per_page']),
        ];
    }

    public function getMembersReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->getMemberReport($filterData, $companyId)->get();
    }

    public function getMemberReport(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);

        return Member::query()
            ->select(
                'created_at',
                'created_location_id',
                DB::raw('count(*) as members_count'),
                DB::raw('DATE(created_at) as date')
            )
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->with(['createdInLocation:' . $locationQueries->getNameColumnName()])
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('created_location_id', $filterData['location_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->groupBy('date', 'created_location_id');
    }

    public function getTotalMembers(array $filterData, int $companyId): int
    {
        $locationQueries = resolve(LocationQueries::class);

        return Member::query()
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->with(['createdInLocation:' . $locationQueries->getNameColumnName()])
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('created_location_id', $filterData['location_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })->count();
    }

    public function getPaginatedMemberReportForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId,
    ): array {
        $memberCounts = $this->getMemberReportForStoreManager($filterData, $companyId, $locationId);

        return [
            'total_members' => $memberCounts->get()->sum('members_count'),
            'member_data' => $memberCounts->paginate($filterData['per_page']),
        ];
    }

    public function getMembersReportForExportForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId,
    ): Collection {
        return $this->getMemberReportForStoreManager($filterData, $companyId, $locationId)->get();
    }

    public function getMemberReportForStoreManager(array $filterData, int $companyId, int $locationId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);

        return Member::query()
            ->select(
                'created_at',
                'created_location_id',
                DB::raw('count(*) as members_count'),
                DB::raw('DATE(created_at) as date')
            )
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->with(['createdInLocation:' . $locationQueries->getNameColumnName()])
            ->where('created_location_id', $locationId)
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->groupBy('date', 'created_location_id');
    }

    public function fetchMemberDetails(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        /** @var Carbon $date */
        $date = Carbon::createFromFormat('d-m-Y', $filterData['select_date']);

        return Member::query()
            ->select(
                'id',
                'type_id',
                'title_id',
                'first_name',
                'mobile_number',
                'email',
                'card_number',
                'last_purchase_date',
                'loyalty_points',
                'created_at',
                'updated_at',
                'created_location_id',
            )
            ->where('company_id', $companyId)
            ->with(['createdInLocation:' . $locationQueries->getNameColumnName()])
            ->where('created_location_id', $filterData['location_id'])
            ->where('status', Status::ACTIVE->value)
            ->where('created_at', '>=', CommonFunctions::addStartTime($date->format('Y-m-d')))
            ->where('created_at', '<=', CommonFunctions::addEndTime($date->format('Y-m-d')))
            ->get();
    }

    public function addNew(Data $memberData, int $companyId, User $user, int $channelId): Member
    {
        $memberRecord = collect($memberData)->forget('photo')->toArray();

        $memberRecord['created_by_type'] = ModelMapping::getCaseName($user::class);
        $memberRecord['created_by_id'] = $user->id;
        $memberRecord['company_id'] = $companyId;
        $memberRecord['channel_id'] = $channelId;

        if (! $memberRecord['card_number']) {
            $memberRecord['card_number'] = $this->generateUniqueCardNumber();
        }

        unset($memberRecord['address_line_1'], $memberRecord['address_line_2'], $memberRecord['city_name'], $memberRecord['area_code']);

        $member = $this->create($memberRecord);

        $this->uploadPhoto($member, $memberData);
        if ($memberData instanceof MemberData && $memberData->member_addresses) {
            $this->addMemberAddresses($member, $memberData->member_addresses);
        }

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        return $member;
    }

    public function addNewForAdminAndStoreManager(Data $memberData, int $companyId, User $user, int $channelId): Member
    {
        $memberRecord = collect($memberData)->forget('photo')->toArray();

        $memberRecord['created_by_type'] = ModelMapping::getCaseName($user::class);
        $memberRecord['created_by_id'] = $user->id;
        $memberRecord['company_id'] = $companyId;
        $memberRecord['channel_id'] = $channelId;

        if (! $memberRecord['card_number']) {
            $memberRecord['card_number'] = $this->generateUniqueCardNumber();
        }

        if (array_key_exists('member_addresses', $memberRecord)) {
            unset($memberRecord['member_addresses']);
        }

        $member = $this->create($memberRecord);

        $this->uploadPhoto($member, $memberData);

        if ($memberData instanceof MemberData && $memberData->member_addresses) {
            $this->addMemberAddresses($member, $memberData->member_addresses);
        }

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        return $member;
    }

    public function addNewFromEcommerceOrder(OrderMemberData $orderMemberData, int $companyId): Member
    {
        $memberRecord = collect($orderMemberData)->toArray();
        $memberRecord['company_id'] = $companyId;

        if (! $memberRecord['card_number']) {
            $memberRecord['card_number'] = $this->generateUniqueCardNumber();
        }

        $member = $this->create($memberRecord);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        return $member;
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getByIdWithMedia(int $memberId, int $companyId): Member
    {
        $mediaQueries = resolve(MediaQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        return Member::select(
            'id',
            'company_id',
            'title_id',
            'type_id',
            'gender_id',
            'race_id',
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'date_of_birth',
            'company_name',
            'company_registration_number',
            'company_tax_number',
            'company_address',
            'company_phone',
            'created_location_id',
            'card_number',
            'notes',
            'pic_name',
            'pic_contact',
            'is_email_verified'
        )
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'createdInLocation:' . $locationQueries->getNameColumnName(),
                'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)->findOrFail($memberId);
    }

    public function getById(int $memberId, int $companyId): Member
    {
        return Member::where('company_id', $companyId)->where('status', Status::ACTIVE->value)->findOrFail($memberId);
    }

    public function getByEmailWithCompanyMedia(string $email): ?Member
    {
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Member::with([
            'company:' . $companyQueries->getBasicColumnNames(),
            'company.media:' . $mediaQueries->getBasicColumnNames(),
        ])
            ->where('status', Status::ACTIVE->value)
            ->where('email', $email)
            ->first();
    }

    public function updateFcmToken(string $fcmToken, int $memberId, int $companyId): void
    {
        $member = $this->getById($memberId, $companyId);
        $member->fcm_token = $fcmToken;
        $member->save();
    }

    public function getByIdForNewMemberBenefitsJob(int $memberId): Member
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Member::select(
            'id',
            'company_id',
            'created_location_id',
            'loyalty_points',
            'membership_id',
            'welcome_member_voucher_id',
            'welcome_member_voucher_generated_at'
        )
            ->with(['company:' . $companyQueries->getBasicColumnNamesForNewMemberBenefitsJob()])
            ->where('status', Status::ACTIVE->value)
            ->findOrFail($memberId);
    }

    public function getByIdWithMembership(int $memberId): Member
    {
        return Member::with('membership')->findOrFail($memberId);
    }

    public function getByIdCompanyIdWithMembership(int $memberId, int $companyId): Member
    {
        return Member::with('membership')
            ->where('company_id', $companyId)
            ->findOrFail($memberId);
    }

    public function getByEmployeeIdAndCompanyIdWithMembership(int $employeeId, int $companyId): Member
    {
        return Member::with('membership')
            ->where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    public function getByIdAndCompanyIdWithMembership(int $memberId, int $companyId): Member
    {
        return Member::with('membership')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->findOrFail($memberId);
    }

    public function getByIdForMemberUpdatePointsAndTotalSalesJob(int $memberId): Member
    {
        return Member::select(
            'id',
            'company_id',
            'total_redeemed_points',
            'total_earned_points',
            'total_expired_points',
            'total_sales',
            'created_location_id'
        )
            ->findOrFail($memberId);
    }

    public function updatePointsAndTotalSales(
        Member $member,
        int $totalEarnedPoints,
        int $totalRedeemedPoints,
        int $totalSales,
        array $preferredItems,
    ): void {
        $member->total_earned_points = $totalEarnedPoints;
        $member->total_redeemed_points = $totalRedeemedPoints;
        $member->total_sales = $totalSales;
        $member->preferred_product_id = empty($preferredItems['preferences_products']) ? null : $preferredItems['preferences_products'][0]['id'];
        $member->preferred_color_id = empty($preferredItems['preferences_color']) ? null : $preferredItems['preferences_color']['id'];
        $member->preferred_size_id = empty($preferredItems['preferences_size']) ? null : $preferredItems['preferences_size']['id'];
        $member->preferred_category_id = empty($preferredItems['preferences_category']) ? null : $preferredItems['preferences_category']['id'];
        $member->preferred_date = empty($preferredItems['preferred_date']) ? null : $preferredItems['preferred_date']['date'];
        $member->preferred_day = empty($preferredItems['preferred_day']) ? null : $preferredItems['preferred_day']['day'];
        $member->save();
    }

    public function update(Data $memberData, int $memberId, int $companyId): void
    {
        $memberRecord = collect($memberData)->forget('photo');
        $memberRecord->forget('loyalty_points');

        if ($memberData instanceof PosMemberData) {
            $memberRecord->forget('created_location_id');
        }

        $memberRecord = $memberRecord->toArray();

        $member = $this->getById($memberId, $companyId);

        $memberRecord['card_number'] ??= $member->card_number;
        $createdLocationId = $member->created_location_id;

        unset($memberRecord['member_addresses']);

        $member->update($memberRecord);

        if (null === $createdLocationId && null !== $memberRecord['created_location_id']) {
            $memberService = resolve(MemberService::class);
            $memberService->addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers($member);
        }

        $this->updateMemberAddresses($member, $memberData);
        $this->uploadPhoto($member, $memberData);
        $this->setUpdatedAt($member);
    }

    public function setUpdatedAt(Member $member): void
    {
        $member->touch();
    }

    public function updatePosMember(Data $memberData, int $memberId, int $companyId): void
    {
        $memberRecord = collect($memberData)->forget('photo');
        $memberRecord->forget('loyalty_points');

        if ($memberData instanceof PosMemberData) {
            $memberRecord->forget('created_location_id');
        }

        $memberRecord = $memberRecord->toArray();

        $member = $this->getById($memberId, $companyId);

        $memberRecord['card_number'] ??= $member->card_number;
        $createdLocationId = $member->created_location_id;

        unset($memberRecord['address_line_1'], $memberRecord['address_line_2'], $memberRecord['city_name'], $memberRecord['area_code']);

        $member->update($memberRecord);
        $this->memberAddressUpdateForPos($member, $memberData->all());

        if (
            null === $createdLocationId &&
            array_key_exists('created_location_id', $memberRecord) &&
            null !== $memberRecord['created_location_id']
        ) {
            $memberService = resolve(MemberService::class);
            $memberService->addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers($member);
        }

        $this->uploadPhoto($member, $memberData);
    }

    public function updateMemberProfile(AppMemberData $appMemberData, Member $member): void
    {
        $data = $appMemberData->all();
        unset($data['address_line_1'], $data['address_line_2'], $data['city_name'], $data['area_code']);
        $member->update($data);

        $this->memberAddressUpdateForPos($member, $appMemberData->all());
    }

    public function getPaginatedListForPos(array $filteredData, int $companyId): LengthAwarePaginator
    {
        return $this->fetchMemberListData($filteredData, $companyId)
            ->paginate($filteredData['per_page']);
    }

    public function fetchMembersListForPos(array $filteredData, int $companyId): LengthAwarePaginator
    {
        return $this->fetchMemberListData($filteredData, $companyId)
            ->whereNotNull('employee_id')
            ->paginate($filteredData['per_page']);
    }

    public function getMemberDetailsForPos(int $memberId, int $companyId): Member
    {
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        return Member::select(...$this->getColumnNames())
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'vouchers' => $voucherQueries->filterByActiveVouchers(),
                'vouchers.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'vouchers.voucherTransactions.sale:' . $saleQueries->getOfflineSaleIdWithStatus(),
                'vouchers.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'vouchers.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'vouchers.voucherConfiguration.products:' . $productQueries->getProductColumnsForPosMemberApi(),
                'vouchers.voucherConfiguration.categories:' . $categoryQueries->getBasicColumnNamesForPosMemberApi(),
                'birthdayVoucher:' . $voucherQueries->getBasicColumnForMemberPos(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
                'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
                'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->findOrFail($memberId);
    }

    public function emailTakenByAnotherMember(string $email, int $companyId, string $mobileNumber): bool
    {
        return Member::whereNot('mobile_number', $mobileNumber)
            ->whereCaseSensitive('email', $email)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function memberExistsById(int $companyId, ?int $memberId): ?Member
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        return Member::select('id', 'status', 'employee_id', 'loyalty_points', 'membership_id')
            ->with([
                'employee:' . $employeeQueries->getBasicColumnWithGroup(),
                'employee.employeeGroup:' . $employeeGroupQueries->getBasicColumnNames(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
                'membership:' . $membershipQueries->getBasicColumnNames(),
            ])
            ->where('id', $memberId)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getByEmployeeIdWithEmployee(int $companyId, int $employeeId): ?Member
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return Member::select('id', 'status', 'employee_id')
            ->with([
                'employee:' . $employeeQueries->getBasicColumnWithGroup(),
                'employee.employeeGroup:' . $employeeGroupQueries->getBasicColumnNames(),
            ])
            ->where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->first();
    }

    public function memberExistsByMobileNumber(int $companyId, string $mobileNumber): bool
    {
        return Member::where('mobile_number', $mobileNumber)->where('company_id', $companyId)->exists();
    }

    public function getMemberWithStore(int $companyId): Collection
    {
        return Member::select(
            'id',
            'title_id',
            'type_id',
            'gender_id',
            'race_id',
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'date_of_birth',
            'last_purchase_date',
            'created_at',
            'created_location_id',
            'company_name',
            'company_registration_number',
            'company_tax_number',
            'company_phone',
            'company_address',
            'pic_name',
            'pic_contact',
        )
            ->with('createdInLocation:id,name')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->get();
    }

    public function updateByMobileNumber(array $memberData, int $companyId, string $mobileNumber): void
    {
        /** @var Member $member */
        $member = Member::where('mobile_number', $mobileNumber)->where('company_id', $companyId)->where(
            'status',
            Status::ACTIVE->value
        )->first();
        $member->update($memberData);
    }

    public function existsByMobileNumber(string $mobileNumber, int $companyId): bool
    {
        return Member::where('mobile_number', $mobileNumber)->where('company_id', $companyId)->exists();
    }

    public function existsByCardNumber(string $cardNumber, int $companyId): bool
    {
        return Member::where('card_number', $cardNumber)->where('company_id', $companyId)->exists();
    }

    public function existsByEmail(string $email, int $companyId): bool
    {
        return Member::where('email', $email)->where('company_id', $companyId)->exists();
    }

    public function create(array $memberData): Member
    {
        return Member::create($memberData);
    }

    public function updateLastPurchaseDate(int $companyId, int $memberId): void
    {
        $member = Member::query()
            ->where('company_id', $companyId)
            ->findOrFail($memberId);
        $member->last_purchase_date = now();
        $member->save();
    }

    public function updateSpentTillNow(float $amount, int $memberId): void
    {
        $member = Member::query()
            ->findOrFail($memberId);
        $member->spent_till_now += $amount;
        $member->save();
    }

    public function updateSalesQuantity(float $totalSaleQty, int $memberId): void
    {
        $member = Member::query()
            ->findOrFail($memberId);
        $member->total_sale_qty += $totalSaleQty;
        $member->save();
    }

    public function updateBirthdayVoucherDetails(Member $member, int $voucherId): void
    {
        $member->update([
            'birthday_voucher_last_generated_at' => now()->format('Y-m-d'),
            'last_birthday_voucher_id' => $voucherId,
        ]);
    }

    public function updateWelcomeMemberVoucherDetails(Member $member, int $voucherId): void
    {
        $member->welcome_member_voucher_generated_at = now()->format('Y-m-d H:i:s');
        $member->welcome_member_voucher_id = $voucherId;
        $member->save();
    }

    public function setMembershipId(int $membershipId, int $memberId): void
    {
        $member = Member::query()
            ->findOrFail($memberId);
        $member->membership_id = $membershipId;
        $member->save();
    }

    public function getMembersByBirthDate(Carbon $date, array $companyIds): Collection
    {
        return Member::query()
            ->select('id', 'company_id', 'date_of_birth', 'birthday_voucher_last_generated_at')
            ->whereIntegerInRaw('company_id', $companyIds)
            ->whereMonth('date_of_birth', '=', $date->format('m'))
            ->whereDay('date_of_birth', '=', $date->format('d'))
            ->where('status', Status::ACTIVE->value)
            ->get();
    }

    public function getMemberByMobileNumber(string $mobileNumber, int $companyId): ?Member
    {
        return Member::select(...$this->getSpecifiedColumnNamesOnly('mobile_number'))
            ->where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getMemberByCardNumber(string $cardNumber, int $companyId): ?Member
    {
        return Member::select(...$this->getSpecifiedColumnNamesOnly('card_number'))
            ->where('card_number', $cardNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getMemberByEmails(string $email, int $companyId): ?Member
    {
        return Member::select(...$this->getSpecifiedColumnNamesOnly('email'))
            ->where('email', $email)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getCompanyIdColumn(): string
    {
        return 'id,company_id';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,first_name,last_name,mobile_number,company_id,email,loyalty_points';
    }

    public function getBasicColumnNamesForSale(): string
    {
        return 'id,first_name,last_name,mobile_number,company_id,email,loyalty_points,employee_id,membership_id';
    }

    public function getBasicColumnNamesForOrderReport(): string
    {
        return 'id,first_name,last_name,mobile_number,company_id,email,company_name,company_address';
    }

    public function getBasicColumnNamesForPosSale(): string
    {
        return 'id,first_name,last_name,employee_id,mobile_number,email,loyalty_points,card_number,company_id,membership_id';
    }

    public function getBasicColumnNamesForPrintReport(): string
    {
        return 'id,first_name,last_name';
    }

    public function getBasicColumnNamesForEmployee(): string
    {
        return 'id,employee_id,spent_till_now,loyalty_points,total_redeemed_points,total_earned_points,total_expired_points,total_sales';
    }

    public function getBasicColumnNamesForSaleChannel(): string
    {
        return 'id,first_name,last_name,gender_id,mobile_number,email,company_id,date_of_birth,status';
    }

    public function getBasicColumnNamesForSaleChannelInArray(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'gender_id',
            'mobile_number',
            'email',
            'company_id',
            'date_of_birth',
            'status',
        ];
    }

    public function decreaseLoyaltyPoints(int $memberId, int $loyaltyPoints): void
    {
        $member = Member::query()
            ->where('id', $memberId)
            ->where('status', Status::ACTIVE->value)
            ->findOrFail($memberId);

        $member->loyalty_points -= $loyaltyPoints;
        $member->save();
    }

    public function decreaseExpiredLoyaltyPoints(Member $member, int $loyaltyPoints): void
    {
        $member->loyalty_points -= $loyaltyPoints;
        $member->total_expired_points += $loyaltyPoints;
        $member->save();
    }

    public function getByIdWithMembershipAndLoyaltyPoints(int $companyId, int $memberId): Member
    {
        return Member::select('id', 'loyalty_points', 'membership_id', 'company_id')
            ->where('company_id', $companyId)
            ->findOrFail($memberId);
    }

    public function getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById(int $companyId, int $memberId): Member
    {
        return Member::select('id', 'date_of_birth', 'birthday_voucher_last_generated_at', 'company_id')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->findOrFail($memberId);
    }

    public function increaseLoyaltyPoints(EloquentModel $memberRecord, int $loyaltyPoints): void
    {
        /** @var Member $member */
        $member = $memberRecord;

        $member->loyalty_points += $loyaltyPoints;

        $member->save();
    }

    public function increaseLoyaltyPointsAndTotalEarnedPoints(Member $member, int $loyaltyPoints): void
    {
        $member->loyalty_points += $loyaltyPoints;
        $member->total_earned_points += $loyaltyPoints;
        $member->save();
    }

    public function addLoyaltyPoints(Member $member, int $loyaltyPoints): void
    {
        $member->loyalty_points = $loyaltyPoints;
        $member->total_earned_points = $loyaltyPoints;
        $member->save();
    }

    public function storeUpdate(Member $member, int $locationId): void
    {
        $member->created_location_id = $locationId;
        $member->save();
    }

    public function assignMembershipToMember(int $memberId, int $companyId): void
    {
        $membershipQueries = resolve(MembershipQueries::class);
        $membership = $membershipQueries->getMembershipWhereLifetimeValueIsZero($companyId);

        if ($membership) {
            $this->setMembershipId($membership->getKey(), $memberId);

            $membershipAssignmentQueries = resolve(MembershipAssignmentQueries::class);
            $membershipAssignmentQueries->addNew(
                $membership->getKey(),
                $memberId,
                Carbon::now()->format('Y-m-d H:i:s')
            );
        }
    }

    public function getColumnNamesForMemberSalesReport(): string
    {
        return 'id,first_name,last_name,mobile_number,last_purchase_date,loyalty_points,employee_id';
    }

    public function generateUniqueCardNumber(): string
    {
        $cardNumber = CommonFunctions::getTwelveDigitNumber();

        $existCardNumbers = Member::whereCaseSensitive('card_number', $cardNumber)->exists();

        if ($existCardNumbers) {
            return $this->generateUniqueCardNumber();
        }

        return $cardNumber;
    }

    public function searchMembersForFilter(array $filterData, int $companyId): Collection
    {
        return Member::select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny([
                            'first_name',
                            'last_name',
                            'card_number',
                            'mobile_number',
                            'email',
                        ], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['number_of_records'], function ($query) use ($filterData): void {
                $query->limit($filterData['number_of_records']);
            })
            ->orderByRaw(
                sprintf("CASE WHEN first_name LIKE '%s%%' THEN 1 ELSE 2 END, first_name", $filterData['search_text'])
            )
            ->get();
    }

    public function searchByBasicColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('status', Status::ACTIVE->value)
            ->whereAny(['first_name', 'last_name', 'mobile_number', 'card_number'], 'LIKE', '%' . $searchText . '%');
    }

    public function filterById(int $memberId): Closure
    {
        return fn ($query) => $query->select('id')->where('status', Status::ACTIVE->value)->where('id', $memberId);
    }

    public function existsGloballyByMobileNumber(string $mobileNumber): bool
    {
        return Member::where('mobile_number', $mobileNumber)->exists();
    }

    public function addNewMemberForRegistration(
        array $memberData,
        int $locationId,
        int $companyId,
        int $channelId,
    ): void {
        $locationQueries = resolve(LocationQueries::class);

        $location = $locationQueries->getLoyaltyPointExpirationDaysById($locationId, $companyId);

        $memberData['company_id'] = $companyId;
        $memberData['channel_id'] = $channelId;
        $memberData['created_location_id'] = $locationId;
        $memberData['card_number'] = $this->generateUniqueCardNumber();
        $memberData['notes'] = 'Generated by QR-code on ' . $location->name;

        $member = $this->create($memberData);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);
    }

    public function addNewMemberAndReturnId(
        array $memberData,
        int $locationId,
        int $companyId,
        int $channelId,
    ): ?int {
        $locationQueries = resolve(LocationQueries::class);

        $location = $locationQueries->getLoyaltyPointExpirationDaysById($locationId, $companyId);

        $memberData['company_id'] = $companyId;
        $memberData['channel_id'] = $channelId;
        $memberData['created_location_id'] = $locationId;
        $memberData['card_number'] = $this->generateUniqueCardNumber();
        $memberData['notes'] = 'Generated by QR-code on ' . $location->name;

        $member = $this->create($memberData);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        return $member->id;
    }

    public function checkEmailExists(string $email): bool
    {
        return Member::where('email', $email)
            ->where('status', Status::ACTIVE->value)
            ->exists();
    }

    public function checkMobileNumberExists(string $mobileNumber): bool
    {
        return Member::where('mobile_number', $mobileNumber)
            ->where('status', Status::ACTIVE->value)
            ->exists();
    }

    public function checkCompanyDelete(string $column, string $data): bool
    {
        return Member::with('company')
            ->where($column, $data)
            ->where('status', Status::ACTIVE->value)
            ->whereHas('company', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->exists();
    }

    public function checkCardNumberExists(string $cardNumber): bool
    {
        return Member::where('card_number', $cardNumber)
            ->where('status', Status::ACTIVE->value)
            ->exists();
    }

    public function checkMobileNumberOrEmailExists(string $username): bool
    {
        return Member::where('mobile_number', $username)
            ->orWhere('email', $username)
            ->exists();
    }

    public function updateOtpBasedOnMobileNumber(string $mobileNumber, string $otp): void
    {
        Member::query()
            ->where(function ($query) use ($mobileNumber): void {
                $query->where('mobile_number', $mobileNumber);
            })
            ->update([
                'otp' => $otp,
                'otp_expire_date' => Carbon::now()->addMinutes(10),
            ]);
    }

    public function updateOtpBasedOnEmail(string $email, string $otp): void
    {
        Member::query()
            ->where(function ($query) use ($email): void {
                $query->where('email', 'like', '%' . $email . '%');
            })
            ->update([
                'otp' => $otp,
                'otp_expire_date' => Carbon::now()->addMinutes(10),
            ]);
    }

    public function validateEmailOtp(array $data, string $email): ?Member
    {
        $membershipQueries = resolve(MembershipQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        return Member::select(...$this->getColumnNamesForValidationMemberApp())
            ->with([
                'createdInLocation:' . $locationQueries->getNameColumnName(),
                'membership:' . $membershipQueries->getColumnNamesForMemberApi(),
                'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
                'primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
                'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
            ])
            ->where('status', Status::ACTIVE->value)
            ->where('email', 'like', '%' . $email . '%')
            ->where('otp', $data['otp'])
            ->first();
    }

    public function validateMobileOtp(array $data, string $mobileNumber): ?Member
    {
        $membershipQueries = resolve(MembershipQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        return Member::select(...$this->getColumnNamesForValidationMemberApp())
            ->with([
                'createdInLocation:' . $locationQueries->getNameColumnName(),
                'membership:' . $membershipQueries->getColumnNamesForMemberApi(),
                'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
                'primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
                'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
            ])
            ->where('status', Status::ACTIVE->value)
            ->where('mobile_number', $mobileNumber)
            ->where('otp', $data['otp'])
            ->first();
    }

    public function updateLastLoginTime(Member $member): void
    {
        $member->last_login_at = Carbon::now()->format('Y-m-d H:i:s');
        $member->save();
    }

    public function generateToken(Member $member): string
    {
        $newAccessToken = $member->createToken('member-mobile-application', ['member_scope']);

        return $newAccessToken->plainTextToken;
    }

    public function loadRelations(Member $member): Member
    {
        $membershipQueries = resolve(MembershipQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $loyaltyPointsUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        return $member->load([
            'createdInLocation:' . $locationQueries->getNameColumnName(),
            'membership:' . $membershipQueries->getColumnNamesForMemberApi(),
            'company:' . $companyQueries->getBasicColumnNamesWithCode(),
            'media:' . $mediaQueries->getBasicColumnNames(),
            'latestFiveLoyaltyPointUpdates:' . $loyaltyPointsUpdateQueries->getBasicColumns(),
            'latestFiveLoyaltyPointUpdates.affectedBy' => $loyaltyPointsUpdateQueries->getAffectedBy(),
            'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
            'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
            'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
            'primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
        ]);
    }

    public function loadRelationsForPos(Member $member): Member
    {
        $locationQueries = resolve(LocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        $member->refresh();

        return $member->load([
            'media:' . $mediaQueries->getBasicColumnNames(),
            'vouchers' => $voucherQueries->filterByActiveVouchers(),
            'vouchers.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
            'vouchers.voucherTransactions.sale:' . $saleQueries->getOfflineSaleIdWithStatus(),
            'vouchers.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            'vouchers.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
            'vouchers.voucherConfiguration.products:' . $productQueries->getProductColumnsForPosMemberApi(),
            'vouchers.voucherConfiguration.categories:' . $categoryQueries->getBasicColumnNamesForPosMemberApi(),
            'birthdayVoucher:' . $voucherQueries->getBasicColumnForMemberPos(),
            'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
            'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
            'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
            'primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
        ]);
    }

    public function uploadProfilePhoto(array $filterData, Member $member): void
    {
        $member->addMedia($filterData['photo'])->toMediaCollection('photo');
    }

    public function getActiveBirthdayVoucher(int $companyId, int $memberId): ?Member
    {
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        return Member::query()
            ->with([
                'birthdayVoucher:' . $voucherQueries->getColumnNames(),
                'birthdayVoucher.mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'birthdayVoucher.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'birthdayVoucher.voucherTransactions.sale:' . $saleQueries->getOfflineSaleIdWithStatus(),
                'birthdayVoucher.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            ])
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->whereHas('birthdayVoucher', function ($query): void {
                $query->select('id')
                    ->from('vouchers')
                    ->whereNull('cancelled_at')
                    ->whereNull('used_at')
                    ->where('expiry_date', '>', Carbon::now());
            })
            ->find($memberId);
    }

    public function getSpecifiedColumnNamesOnly(string $name): array
    {
        return ['id', 'loyalty_points', 'email', $name];
    }

    public function getMemberWithVoucherById(int $memberId): Member
    {
        $voucherQueries = resolve(VoucherQueries::class);

        return Member::select('id', 'first_name', 'last_name', 'email', 'loyalty_points', 'card_number')
            ->with([
                'vouchers' => $voucherQueries->getActiveVoucher(),
            ])
            ->findOrFail($memberId);
    }

    public function deleteMember(Member $member): void
    {
        $member->email = $member->id . $member->email;
        $member->mobile_number = $member->id . $member->mobile_number;
        $member->card_number = $member->id . $member->card_number;
        $member->status = Status::DELETED_BY_USER->value;
        $member->notes = $member->notes . ' ' . now()->format('d-m-Y H:i:s') . ':' . Status::getFormattedCaseName(
            Status::DELETED_BY_USER->value
        );
        $member->save();

        $member->tokens()->delete();
    }

    public function deleteMemberByAdmin(Member $member, int $adminId): void
    {
        $member->email = $member->id . $member->email;
        $member->mobile_number = $member->id . $member->mobile_number;
        $member->card_number = $member->id . $member->card_number;
        $member->status = Status::DELETED_BY_ADMIN->value;
        $member->notes = $member->notes . ' ' . now()->format('d-m-Y H:i:s') . ':' . Status::getFormattedCaseName(
            Status::DELETED_BY_ADMIN->value
        ).':'.$adminId;
        $member->save();
    }

    public function filterByActive(): Closure
    {
        return fn ($query) => $query->where('status', Status::ACTIVE->value);
    }

    public function memberById(int $memberId): Member
    {
        return Member::select('id', 'status')->findOrFail($memberId);
    }

    public function checkMemberByMobileNumber(int $companyId, string $mobileNumber): ?Member
    {
        return Member::select('id', 'status')->where('mobile_number', $mobileNumber)->where(
            'company_id',
            $companyId
        )->first();
    }

    public function addNewMemberByPromoter(
        array $promoterMemberData,
        int $locationId,
        int $companyId,
        User $user,
        int $channelId,
    ): Member {
        $promoterMemberData['created_by_type'] = ModelMapping::getCaseName($user::class);
        $promoterMemberData['created_by_id'] = $user->id;
        $promoterMemberData['company_id'] = $companyId;
        $promoterMemberData['channel_id'] = $channelId;
        $promoterMemberData['created_location_id'] = $locationId;
        $promoterMemberData['card_number'] = $this->generateUniqueCardNumber();
        $promoterMemberData['notes'] = 'Register by Promoter';

        $member = $this->create($promoterMemberData);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        $member->refresh();

        return $member;
    }

    public function getPaginatedListForStoreManagerAndPromoterApp(
        array $filteredData,
        int $companyId,
    ): LengthAwarePaginator {
        $voucherQueries = resolve(VoucherQueries::class);

        return Member::select(
            'id',
            'first_name',
            'last_name',
            'mobile_number',
            'loyalty_points',
            'email',
            'company_id',
            'status'
        )
            ->withCount([
                'vouchers' => $voucherQueries->filterByOnlyActiveQuery(),
            ])
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->when($filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where(function ($query) use ($filteredData): void {
                    $query
                        ->whereAny(
                            ['first_name', 'last_name', 'mobile_number', 'email'],
                            'LIKE',
                            '%' . $filteredData['search_text'] . '%'
                        );
                });
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function addNewOpenMemberRegistration(array $memberData, int $channelId): void
    {
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
        $siteConfigData = $siteConfigurationQueries->getDefaultCompany();

        if (! $siteConfigData) {
            abort(412, 'The default company was not found');
        }

        $companyId = (int) $siteConfigData->value;

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getWithLocationAssignmentTypeById($companyId);

        if (! $company) {
            abort(412, 'The company was not found');
        }

        $memberData['company_id'] = $companyId;
        $memberData['channel_id'] = $channelId;
        $memberData['card_number'] = $this->generateUniqueCardNumber();

        if ($company->location_assignment_type === LocationAssignmentTypes::DEFAULT_LOCATION->value && null !== $company->default_location_id) {
            $locationId = $company->default_location_id;

            $memberData['created_location_id'] = $locationId;
        }

        $member = $this->create($memberData);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);
    }

    public function addNewEmployeeMember(array $memberData, Company $company): void
    {
        if (
            $company->location_assignment_type === LocationAssignmentTypes::DEFAULT_LOCATION->value
            && null !== $company->default_location_id
        ) {
            $memberData['created_location_id'] = $company->default_location_id;
        }

        $addressData = [
            'address_line_1' => $memberData['address_line_1'],
            'address_line_2' => $memberData['address_line_2'],
            'city_name' => $memberData['city_name'],
            'area_code' => $memberData['area_code'],
            'contact_mobile_number' => $memberData['mobile_number'],
            'contact_email' => $memberData['email'],
            'is_primary' => true,
        ];

        unset($memberData['address_line_1'], $memberData['address_line_2'], $memberData['city_name'], $memberData['area_code']);

        $member = $this->create($memberData);
        $this->addMemberAddresses($member, [$addressData]);
        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);
    }

    public function addNewMemberRegistrationForEcommerce(array $memberData, int $channelId, int $saleChannelId): Member
    {
        $memberData['channel_id'] = $channelId;
        $memberData['card_number'] = $this->generateUniqueCardNumber();

        $externalMemberId = null;
        $imageUrl = null;
        if (array_key_exists('id', $memberData)) {
            $externalMemberId = $memberData['id'];
            $imageUrl = $memberData['image_url'];
            unset($memberData['id']);
            unset($memberData['image_url']);
            unset($memberData['gender']);
        }

        $member = $this->create($memberData);

        if (null !== $imageUrl) {
            $member->addMediaFromUrl($imageUrl)
                ->toMediaCollection('photo');
        }

        if ($externalMemberId) {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
            $memberChannelReferenceQueries->updateOrCreate([
                'sale_channel_id' => $saleChannelId,
                'member_id' => $member->id,
                'external_member_id' => $externalMemberId,
            ]);
        }

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        return $member->refresh();
    }

    public function updateMemberForEcommerce(int $memberId, array $updateMemberEcommerceRecord): void
    {
        /** @var Member $member */
        $member = Member::findOrFail($memberId);

        $member->first_name = $updateMemberEcommerceRecord['first_name'];
        $member->last_name = $updateMemberEcommerceRecord['last_name'];
        $member->email = $updateMemberEcommerceRecord['email'];

        if ($updateMemberEcommerceRecord['mobile_number']) {
            $member->mobile_number = $updateMemberEcommerceRecord['mobile_number'];
        }

        if ($updateMemberEcommerceRecord['date_of_birth']) {
            $member->date_of_birth = $updateMemberEcommerceRecord['date_of_birth'];
        }

        if (array_key_exists('gender_id', $updateMemberEcommerceRecord)) {
            $member->gender_id = $updateMemberEcommerceRecord['gender_id'];
        }

        $member->save();
        if ($updateMemberEcommerceRecord['image_url']) {
            $member->addMediaFromUrl($updateMemberEcommerceRecord['image_url'])
                ->toMediaCollection('photo');
        }
    }

    public function addNewMemberForEcommerce(array $memberData): Member
    {
        $memberData['card_number'] = $this->generateUniqueCardNumber();
        $member = $this->create($memberData);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        $member->refresh();

        return $member;
    }

    public function refresh(Member $member): Member
    {
        return $member->refresh();
    }

    public function getTotalRegisteredMembersForStoreManagerDashboard(int $locationId, int $companyId): int
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->where('created_location_id', $locationId)
            ->count();
    }

    public function getTotalMembersRegisteredThisMonthForStoreManagerDashboard(
        int $locationId,
        int $companyId,
        array $date,
    ): int {
        return Member::query()
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->where('created_location_id', $locationId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($date[1]))
            ->count();
    }

    public function removeEmployeeId(int $employeeId): void
    {
        $member = Member::select('id', 'employee_id', 'company_id')
            ->where('employee_id', $employeeId)
            ->first();

        if ($member) {
            $member->employee_id = null;
            $member->save();
        }
    }

    public function isMemberExistsByEmployee(Employee $employee): bool
    {
        return Member::select('id')
            ->where('company_id', $employee->company_id)
            ->where(function ($query) use ($employee): void {
                $query->where('mobile_number', $employee->mobile_number)
                    ->when($employee->email, function ($query) use ($employee): void {
                        $query->orWhere('email', $employee->email);
                    });
            })
            ->exists();
    }

    public function getByEmployee(Employee $employee): ?Member
    {
        return Member::select('id', 'email', 'employee_id', 'company_id')
            ->where('company_id', $employee->company_id)
            ->where(function ($query) use ($employee): void {
                $query->where('mobile_number', $employee->mobile_number)
                    ->when($employee->email, function ($query) use ($employee): void {
                        $query->orWhere('email', $employee->email);
                    });
            })
            ->first();
    }

    public function addEmployeeId(Employee $employee): void
    {
        $member = $this->getByEmployee($employee);

        if ($member instanceof Member) {
            $member->employee_id = $employee->id;
            $member->save();
        }
    }

    public function getPaginatedListForEcommerce(array $filteredData, int $companyId): LengthAwarePaginator
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        return Member::select(
            'id',
            'first_name',
            'mobile_number',
            'email',
            'date_of_birth',
            'created_at',
            'updated_at',
            'status'
        )
            ->with(['memberAddresses:' . $memberAddressQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->when($filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where(function ($query) use ($filteredData): void {
                    $query->whereAny(
                        ['mobile_number', 'email', 'first_name'],
                        'LIKE',
                        '%' . $filteredData['search_text'] . '%'
                    );
                });
            })
            ->when(
                array_key_exists('after_updated_at', $filteredData) && $filteredData['after_updated_at'],
                function ($query) use ($filteredData): void {
                    $query->where('updated_at', '=', $filteredData['after_updated_at']);
                }
            )
            ->when(
                array_key_exists('mobile_number', $filteredData) && $filteredData['mobile_number'],
                function ($query) use ($filteredData): void {
                    $query->where('mobile_number', $filteredData['mobile_number']);
                }
            )
            ->when(
                array_key_exists('email', $filteredData) && $filteredData['email'],
                function ($query) use ($filteredData): void {
                    $query->where('email', $filteredData['email']);
                }
            )
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    private function fetchMemberListData(array $filteredData, int $companyId): Builder
    {
        $voucherQueries = resolve(VoucherQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return Member::select(...$this->getColumnNames())
            ->with([
                'employee:' .$employeeQueries->getBasicColumnsForEmployeeMember(),
                'employee.employeeGroup:' .$employeeGroupQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'vouchers' => $voucherQueries->filterByActiveVouchers(),
                'vouchers.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'vouchers.voucherTransactions.sale:' . $saleQueries->getOfflineSaleIdWithStatus(),
                'vouchers.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'vouchers.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'vouchers.voucherConfiguration.products:' . $productQueries->getProductColumnsForPosMemberApi(),
                'vouchers.voucherConfiguration.categories:' . $categoryQueries->getBasicColumnNamesForPosMemberApi(),
                'birthdayVoucher:' . $voucherQueries->getBasicColumnForMemberPos(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
                'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
                'memberAddresses:' .$memberAddressQueries->getBasicColumnNames(),
                'primaryMemberAddress:' .$memberAddressQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where(function ($query) use ($filteredData): void {
                    $query->whereAny(
                        ['email', 'first_name', 'card_number', 'mobile_number'],
                        'LIKE',
                        '%' . $filteredData['search_text'] . '%'
                    );
                });
            })
            ->when(
                array_key_exists('after_updated_at', $filteredData) && $filteredData['after_updated_at'],
                function ($query) use ($filteredData): void {
                    $query->where('updated_at', '>=', $filteredData['after_updated_at']);
                },
                function ($query): void {
                    $query->where('status', Status::ACTIVE->value);
                }
            )
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getColumnNamesForValidationMemberApp(): array
    {
        return [
            'id', 'title_id', 'type_id', 'gender_id', 'race_id', 'first_name', 'last_name', 'email', 'mobile_number', 'date_of_birth', 'total_orders', 'company_id', 'company_name', 'company_registration_number', 'company_tax_number', 'company_phone', 'notes', 'spent_till_now', 'last_purchase_date', 'loyalty_points', 'membership_id', 'card_number', 'created_location_id', 'created_at', 'otp', 'otp_expire_date', 'employee_id',
        ];
    }

    private function getColumnNames(): array
    {
        return [
            'id', 'employee_id', 'title_id', 'type_id', 'gender_id', 'race_id', 'status', 'first_name', 'last_name', 'email', 'mobile_number', 'date_of_birth', 'total_orders', 'company_id', 'company_name', 'company_registration_number', 'company_tax_number', 'company_phone', 'notes', 'spent_till_now', 'last_purchase_date', 'loyalty_points', 'membership_id', 'card_number', 'last_birthday_voucher_id', 'created_location_id', 'total_redeemed_points', 'total_earned_points', 'total_expired_points', 'total_sales', 'created_at',
        ];
    }

    public function getColumnNamesForOrderReport(): string
    {
        return 'id,first_name,last_name,email,mobile_number,company_id';
    }

    public function getLoyaltyPointsById(int $memberId): Member
    {
        return Member::select('id', 'loyalty_points', 'total_expired_points', 'company_id')
            ->findOrFail($memberId);
    }

    private function uploadPhoto(Member $member, Data $memberData): void
    {
        $memberRecord = collect($memberData)->toArray();
        if (! array_key_exists('photo', $memberRecord)) {
            return;
        }

        if (null === $memberRecord['photo']) {
            return;
        }

        $member->addMedia($memberRecord['photo'])->toMediaCollection('photo');
    }

    public function getTokenById(int $memberId): Member
    {
        return Member::query()
            ->select('id', 'fcm_token')
            ->whereNotNull('fcm_token')
            ->findOrFail($memberId);
    }

    public function getMembersByMemberTypeIds(array $memberTypeIds, int $companyId): Collection
    {
        return Member::query()
            ->select('id')
            ->whereIntegerInRaw('type_id', $memberTypeIds)
            ->where('company_id', $companyId)
            ->where($this->filterByActive())
            ->get();
    }

    public function getMembersByStoreIds(array $locationIds, int $companyId): Collection
    {
        return Member::query()
            ->select('id')
            ->whereIntegerInRaw('created_location_id', $locationIds)
            ->where('company_id', $companyId)
            ->where($this->filterByActive())
            ->get();
    }

    public function findMemberByMobileNumber(string $mobileNumber, int $companyId): ?Member
    {
        return Member::select(
            'id',
            'mobile_number',
            'first_name',
            'email',
            'date_of_birth',
            'status',
            'updated_at',
            'created_at'
        )
            ->where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function findMemberByMobileNumberOrCardNumber(array $memberData, int $companyId): ?Member
    {
        return Member::select(
            'id',
            'mobile_number',
            'first_name',
            'email',
            'date_of_birth',
            'status',
            'updated_at',
            'created_at'
        )
            ->where('company_id', $companyId)
            ->where(function ($query) use ($memberData): void {
                $query->where('mobile_number', $memberData['mobile_number'])
                    ->orWhere('card_number', $memberData['card_number']);
            })
            ->first();
    }

    public function findMemberByEmail(string $email, int $companyId): ?Member
    {
        return Member::select(
            'id',
            'mobile_number',
            'first_name',
            'email',
            'date_of_birth',
            'status',
            'created_at',
            'updated_at'
        )
            ->where('email', $email)
            ->where('company_id', $companyId)
            ->first();
    }

    public function exportMemberRecords(array $filterData, int $companyId, int $skip, int $limit): Collection
    {
        $locationQueries = new LocationQueries();

        return $this->getCommonListQuery($filterData, $companyId)
                ->select(
                    'id',
                    'type_id',
                    'title_id',
                    'race_id',
                    'first_name',
                    'last_name',
                    'gender_id',
                    'date_of_birth',
                    'mobile_number',
                    'email',
                    'company_name',
                    'company_registration_number',
                    'company_tax_number',
                    'company_address',
                    'company_phone',
                    'notes',
                    'card_number',
                    'last_purchase_date',
                    'membership_id',
                    'loyalty_points',
                    'created_at',
                    'updated_at',
                    'created_location_id'
                )
                ->with(['createdInLocation:' . $locationQueries->getBasicColumnNames()])
                ->skip($skip)
                ->limit($limit)
                ->get();
    }

    public function getMembersExportCount(array $filterData, int $companyId): int
    {
        return $this->listQuery($filterData, $companyId)->select('id')->count();
    }

    public function getIdByName(string $firstName, string $mobileNumber, int $companyId): int
    {
        return Member::select('id', 'first_name', 'mobile_number')
            ->whereCaseSensitive('first_name', $firstName)
            ->where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->firstOrFail()
            ->id;
    }

    public function getSeasonalMemberData(array $filterData, int $companyId): Collection
    {
        return Member::select('id', DB::raw('count(*) as members_count'), DB::raw('DATE(created_at) as date'))
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                $query->where('created_location_id', $filterData['location_id']);
            })
            ->whereNotNull('created_location_id')
            ->orderBy('date')
            ->groupBy('date')
            ->get();
    }

    public function updateEmployeeMember(array $memberData, int $memberId): void
    {
        $member = $this->getById($memberId, $memberData['company_id']);
        $member->update($memberData);
    }

    public function existsByMobileOrEmail(?string $mobile, ?string $email, int $companyId): bool
    {
        return Member::where('company_id', $companyId)
            ->when($mobile, function ($query) use ($mobile): void {
                $query->where('mobile_number', $mobile);
            })
            ->when($email, function ($query) use ($email): void {
                $query->where('email', $email);
            })
            ->exists();
    }

    public function getMemberIdsForSalesChannel(array $filteredData): LengthAwarePaginator
    {
        return Member::query()
            ->select('id')
            ->whereHas('memberGroupMembers', function ($query) use ($filteredData): void {
                $query->select('id')
                    ->where('member_group_id', $filteredData['member_group_id']);
            })
            ->when($filteredData['after_updated_at'], function ($query) use ($filteredData): void {
                $query->where('updated_at', '>=', $filteredData['after_updated_at']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function getActiveMemberDetailsById(int $memberId, int $companyId): Member
    {
        $membershipQueries = resolve(MembershipQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        return Member::select(
            'id',
            'company_id',
            'first_name',
            'mobile_number',
            'created_location_id',
            'pic_name',
            'pic_contact',
            'membership_id',
            'spent_till_now',
            'total_redeemed_points',
            'loyalty_points',
            'channel_id',
            'created_at',
            'updated_at',
            'created_location_id'
        )
            ->with([
                'membership:' . $membershipQueries->getBasicColumnNames(),
                'createdInLocation:' . $locationQueries->getNameColumnName(),
                'primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'sales:' . $saleQueries->getTotalDiscountAmountColumn(),
            ])
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)->findOrFail($memberId);
    }

    public function updateMemberAddresses(Member $member, Data|UpdateMemberAddressData $memberData): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        $memberRecord = collect($memberData)->toArray();

        if ($memberRecord['member_addresses']) {
            foreach ($memberRecord['member_addresses'] as $memberAddress) {
                if (! array_key_exists('id', $memberAddress)) {
                    $this->addMemberAddresses($member, [$memberAddress]);
                    continue;
                }

                $memberAddressQueries->updateAddress(
                    (int) $memberAddress['id'],
                    [
                        'member_id' => $member->id,
                        'name' => $memberAddress['name'],
                        'first_name' => $memberAddress['first_name'] ?? null,
                        'last_name' => $memberAddress['last_name'] ?? null,
                        'contact_mobile_number' => $memberAddress['contact_mobile_number'],
                        'contact_email' => $memberAddress['contact_email'],
                        'address_line_1' => $memberAddress['address_line_1'],
                        'address_line_2' => $memberAddress['address_line_2'],
                        'city_name' => $memberAddress['city_name'],
                        'country_id' => $memberAddress['country_id'],
                        'state_id' => $memberAddress['state_id'],
                        'city_id' => $memberAddress['city_id'],
                        'area_code' => $memberAddress['area_code'],
                        'is_primary' => $memberAddress['is_primary'],
                    ]
                );
            }
        }
    }

    private function addMemberAddresses(Member $member, array $memberAddresses): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        foreach ($memberAddresses as $memberAddress) {
            $memberAddressQueries->addNew([
                'member_id' => $member->id,
                'name' => 'Primary',
                'first_name' => $memberAddress['first_name'] ?? null,
                'last_name' => $memberAddress['last_name'] ?? null,
                'contact_mobile_number' => $memberAddress['contact_mobile_number'],
                'contact_email' => $memberAddress['contact_email'],
                'address_line_1' => $memberAddress['address_line_1'],
                'address_line_2' => $memberAddress['address_line_2'],
                'city_name' => $memberAddress['city_name'],
                'country_id' => $memberAddress['country_id'],
                'state_id' => $memberAddress['state_id'],
                'city_id' => $memberAddress['city_id'],
                'area_code' => $memberAddress['area_code'],
                'is_primary' => $memberAddress['is_primary'],
            ]);
        }
    }

    private function memberAddressUpdateForPos(Member $member, array $memberRecord): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressQueries->updateForPos($member->id, [
            'address_line_1' => $memberRecord['address_line_1'],
            'address_line_2' => $memberRecord['address_line_2'],
            'city_name' => $memberRecord['city_name'],
            'area_code' => $memberRecord['area_code'],
        ]);
    }

    public function getByIdForEcommerce(int $memberId, int $companyId): Member
    {
        return Member::where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->where('channel_id', MemberChannelEnum::E_COMMERCE->value)
            ->findOrFail($memberId);
    }

    public function getByIdForEmailVerification(int $memberId, int $companyId): Member
    {
        return Member::select('id', 'email', 'is_email_verified')
            ->where('company_id', $companyId)
            ->findOrFail($memberId);
    }

    public function getByMemberForGenuineReceipt(int $memberId, int $companyId): Member
    {
        return Member::select('id', 'email', 'first_name', 'mobile_number')
            ->where('company_id', $companyId)
            ->findOrFail($memberId);
    }

    public function getMemberByMobileNumberForEcommerce(string $mobileNumber, int $companyId): Collection
    {
        return Member::select(
            'id',
            'mobile_number',
            'first_name',
            'email',
            'date_of_birth',
            'created_at',
            'updated_at',
            'status'
        )
            ->where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getMembersByMobileNumberForEcommerce(string $mobileNumber, int $companyId): Collection
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        return Member::select(
            'id',
            'mobile_number',
            'first_name',
            'email',
            'date_of_birth',
            'created_at',
            'updated_at',
            'status'
        )
            ->with(['memberAddresses:'.$memberAddressQueries->getBasicColumnNames()])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(mobile_number, '-', ''), ' ', ''), '+', '') LIKE ?",
                [sprintf('%%%s%%', $mobileNumber)]
            )
            ->where('company_id', $companyId)
            ->get();
    }

    public function getByIdForMergeDetails(int $memberId, int $companyId): Member
    {
        $membershipQueries = resolve(MembershipQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);

        return Member::query()
            ->select(
                'id',
                'company_id',
                'type_id',
                'title_id',
                'race_id',
                'channel_id',
                'first_name',
                'last_name',
                'gender_id',
                'date_of_birth',
                'mobile_number',
                'email',
                'company_name',
                'company_registration_number',
                'company_tax_number',
                'company_address',
                'company_phone',
                'created_by_id',
                'created_by_type',
                'created_location_id',
                'last_purchase_date',
                'notes',
                'spent_till_now',
                'loyalty_points',
                'membership_id',
                'card_number',
                'birthday_voucher_last_generated_at',
                'last_birthday_voucher_id',
                'welcome_member_voucher_generated_at',
                'welcome_member_voucher_id',
                'otp',
                'otp_expire_date',
                'total_redeemed_points',
                'total_earned_points',
                'total_expired_points',
                'total_sales',
                'status',
                'employee_id',
                'fcm_token',
                'pic_name',
                'pic_contact'
            )
            ->with([
                'membership:' . $membershipQueries->getBasicColumnNames(),
                'createdInLocation:' . $locationQueries->getNameColumnName(),
                'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getBasicColumnNames(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
                'memberGroupMembers.memberGroup:' . $memberGroupQueries->getBasicColumnNames(),
                'vouchers' => $voucherQueries->filterByActiveVouchers(),
                'birthdayVoucher:' . $voucherQueries->getColumnNames(),
            ])
            ->where('status', Status::ACTIVE->value)
            ->where('company_id', $companyId)
            ->findOrFail($memberId);
    }

    public function checkMemberIsActive(int $companyId, int $memberId): ?int
    {
        $member = Member::select('id', 'status')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->find($memberId);

        return $member?->status;
    }

    public function markAsInActive(int $memberId, int $companyId): void
    {
        $member = Member::select('id', 'status', 'company_id')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->find($memberId);

        if (! $member instanceof Member) {
            return;
        }

        $member->status = Status::INACTIVE->value;
        $member->save();
    }

    public function getMemberEmployee(int $memberId, int $companyId): ?Member
    {
        return Member::select('id', 'employee_id', 'company_id')
            ->where('company_id', $companyId)
            ->where('status', Status::ACTIVE->value)
            ->find($memberId);
    }

    public function updateNewMemberDetailsAndDeleteOldMember(
        int $companyId,
        int $oldMemberId,
        int $newMemberId,
        int $userId,
    ): void {
        /** @var Member $oldMember */
        $oldMember = Member::query()
            ->where('company_id', $companyId)
            ->find($oldMemberId);

        /** @var Member $newMember */
        $newMember = Member::query()
            ->where('company_id', $companyId)
            ->find($newMemberId);

        $newMember->last_purchase_date = max($oldMember->last_purchase_date, $newMember->last_purchase_date);
        $newMember->last_login_at = max($oldMember->last_login_at, $newMember->last_login_at);
        $newMember->spent_till_now += $oldMember->spent_till_now;
        $newMember->loyalty_points += $oldMember->loyalty_points;
        $newMember->total_orders += $oldMember->total_orders;
        $newMember->total_sales += $oldMember->total_sales;
        $newMember->total_earned_points += $oldMember->total_earned_points;
        $newMember->total_redeemed_points += $oldMember->total_redeemed_points;
        $newMember->total_expired_points += $oldMember->total_expired_points;
        $newMember->type_id = $oldMember->type_id;
        $newMember->company_name = $oldMember->company_name;
        $newMember->company_registration_number = $oldMember->company_registration_number;
        $newMember->company_tax_number = $oldMember->company_tax_number;
        $newMember->company_address = $oldMember->company_address;
        $newMember->company_phone = $oldMember->company_phone;
        $newMember->employee_id = $oldMember->employee_id;
        $newMember->status = Status::ACTIVE->value;
        $newMember->save();

        $oldMember->notes .= ' This member is merged now. new member is :' . $newMemberId;
        $oldMember->save();

        $this->deleteMemberByAdmin($oldMember, $userId);
    }

    public function getLatestSpentTillNow(int $companyId, int $memberId): float
    {
        /** @var Member $member */
        $member = Member::query()
            ->select('id', 'spent_till_now')
            ->where('company_id', $companyId)
            ->where('id', $memberId)
            ->first();

        return (float) $member->spent_till_now;
    }

    public function getAllWithFullName(int $companyId): Collection
    {
        return Member::select('id', DB::raw("CONCAT(first_name,' ',last_name) AS name"))->where(
            'company_id',
            $companyId
        )->get();
    }

    public function getPurchaseCountByMemberCount(float $amount, float $maxAmount, int $numberCondition): int
    {
        return $this->getPurchaseCountByMemberCommonQuery($amount, $maxAmount, $numberCondition, null)
            ->count('id');
    }

    public function getPurchaseCountByMembers(
        float $amount,
        float $maxAmount,
        int $numberCondition,
        ?int $memberId,
    ): Collection {
        return $this->getPurchaseCountByMemberCommonQuery($amount, $maxAmount, $numberCondition, $memberId)
            ->get();
    }

    private function getPurchaseCountByMemberCommonQuery(
        float $amount,
        float $maxAmount,
        int $numberCondition,
        ?int $memberId,
    ): Builder {
        return Member::select('id')
            ->when($memberId, function ($query) use ($memberId): void {
                $query->where('id', $memberId);
            })
            ->when($numberCondition == NumberConditionTypes::GREATER_THAN->value, function ($query) use (
                $amount
            ): void {
                $query->where('total_orders', '>', $amount);
            })
            ->when($numberCondition == NumberConditionTypes::LESS_THAN->value, function ($query) use ($amount): void {
                $query->where('total_orders', '<', $amount);
            })
            ->when($numberCondition == NumberConditionTypes::BETWEEN->value, function ($query) use (
                $amount,
                $maxAmount
            ): void {
                $query->whereBetween('total_orders', [$amount, $maxAmount]);
            })
            ->when($numberCondition == NumberConditionTypes::EXACTLY_TO->value, function ($query) use ($amount): void {
                $query->where('total_orders', '=', $amount);
            });
    }

    public function getTotalSpentByMemberCount(float $amount, float $maxAmount, int $numberCondition): int
    {
        return $this->getTotalSpentByMemberCommonQuery($amount, $maxAmount, $numberCondition, null)
            ->count('id');
    }

    public function getTotalSpentByMembers(
        float $amount,
        float $maxAmount,
        int $numberCondition,
        ?int $memberId,
    ): Collection {
        return $this->getTotalSpentByMemberCommonQuery($amount, $maxAmount, $numberCondition, $memberId)
            ->get();
    }

    private function getTotalSpentByMemberCommonQuery(
        float $amount,
        float $maxAmount,
        int $numberCondition,
        ?int $memberId,
    ): Builder {
        return Member::select('id')
            ->when($memberId, function ($query) use ($memberId): void {
                $query->where('id', $memberId);
            })
            ->when($numberCondition == NumberConditionTypes::GREATER_THAN->value, function ($query) use (
                $amount
            ): void {
                $query->where('spent_till_now', '>', $amount);
            })
            ->when($numberCondition == NumberConditionTypes::LESS_THAN->value, function ($query) use ($amount): void {
                $query->where('spent_till_now', '<', $amount);
            })
            ->when($numberCondition == NumberConditionTypes::BETWEEN->value, function ($query) use (
                $amount,
                $maxAmount
            ): void {
                $query->whereBetween('spent_till_now', [$amount, $maxAmount]);
            })
            ->when($numberCondition == NumberConditionTypes::EXACTLY_TO->value, function ($query) use ($amount): void {
                $query->where('spent_till_now', '=', $amount);
            });
    }

    public function getProductsIdByMemberCount(array $productsId, int $elementCondition): int
    {
        return $this->getProductsIdByMemberCommonQuery($productsId, $elementCondition, null)->count('id');
    }

    public function getProductsIdByMembers(array $productsId, int $elementCondition, ?int $memberId): Collection
    {
        return $this->getProductsIdByMemberCommonQuery($productsId, $elementCondition, $memberId)->get();
    }

    private function getProductsIdByMemberCommonQuery(array $productsId, int $elementCondition, ?int $memberId): Builder
    {
        return Member::select('id')
            ->when($memberId, function ($query) use ($memberId): void {
                $query->where('id', $memberId);
            })
            ->whereHas('sales.saleItems', function ($query) use ($productsId, $elementCondition): void {
                $query->when($elementCondition == ElementConditionTypes::WAS->value, function ($query) use (
                    $productsId
                ): void {
                    $query->whereIn('product_id', $productsId);
                })->when($elementCondition == ElementConditionTypes::WAS_NOT->value, function ($query) use (
                    $productsId
                ): void {
                    $query->whereNotIn('product_id', $productsId);
                });
            });
    }

    public function getCategoriesIdByMemberCount(array $categoriesId, int $elementCondition): int
    {
        return $this->getCategoriesIdByMemberCommonQuery($categoriesId, $elementCondition, null)->count('id');
    }

    public function getCategoriesIdByMembers(array $categoriesId, int $elementCondition, ?int $memberId): Collection
    {
        return $this->getCategoriesIdByMemberCommonQuery($categoriesId, $elementCondition, $memberId)->get();
    }

    private function getCategoriesIdByMemberCommonQuery(
        array $categoriesId,
        int $elementCondition,
        ?int $memberId,
    ): Builder {
        return Member::select('id')
        ->when($memberId, function ($query) use ($memberId): void {
            $query->where('id', $memberId);
        })
        ->whereHas('sales', function ($query) use ($categoriesId, $elementCondition): void {
            $query->whereHas('saleItems', function ($query) use ($categoriesId, $elementCondition): void {
                $query->whereHas('product', function ($query) use ($categoriesId, $elementCondition): void {
                    $query->whereHas('categories', function ($query) use ($categoriesId, $elementCondition): void {
                        $query->when($elementCondition == ElementConditionTypes::WAS->value, function ($query) use (
                            $categoriesId
                        ): void {
                            $query->whereIn('id', $categoriesId);
                        })->when($elementCondition == ElementConditionTypes::WAS_NOT->value, function ($query) use (
                            $categoriesId
                        ): void {
                            $query->whereNotIn('id', $categoriesId);
                        });
                    });
                });
            });
        });
    }

    public function getPurchaseDateByMemberCount(string $date, ?string $maxDate, int $dateCondition): int
    {
        return $this->getPurchaseDateByMemberCommonQuery($date, $maxDate, $dateCondition, null)
            ->count('id');
    }

    public function getPurchaseDateByMembers(
        string $date,
        ?string $maxDate,
        int $dateCondition,
        ?int $memberId,
    ): Collection {
        return $this->getPurchaseDateByMemberCommonQuery($date, $maxDate, $dateCondition, $memberId)
            ->get();
    }

    private function getPurchaseDateByMemberCommonQuery(
        string $date,
        ?string $maxDate,
        int $dateCondition,
        ?int $memberId,
    ): Builder {
        return Member::select('id')
            ->when($memberId, function ($query) use ($memberId): void {
                $query->where('id', $memberId);
            })
            ->whereHas('sales', function ($query) use ($date, $maxDate, $dateCondition): void {
                $query->when($dateCondition == DateConditionTypes::MORE_THAN->value, function ($query) use (
                    $date
                ): void {
                    $query->whereDate('created_at', '>', $date);
                })
                    ->when($dateCondition == DateConditionTypes::LESS_THAN->value, function ($query) use ($date): void {
                        $query->whereDate('created_at', '<', $date);
                    })
                    ->when($dateCondition == DateConditionTypes::BETWEEN->value && $maxDate, function ($query) use (
                        $date,
                        $maxDate
                    ): void {
                        $query->whereBetween('created_at', [$date, $maxDate]);
                    })
                    ->when($dateCondition == DateConditionTypes::EXACTLY_ON->value, function ($query) use (
                        $date
                    ): void {
                        $query->whereDate('created_at', '=', $date);
                    });
            });
    }

    public function getFirstVisitDateByMemberCount(string $date, ?string $maxDate, int $dateCondition): int
    {
        return $this->getFirstVisitDateByMemberCommonQuery($date, $maxDate, $dateCondition, null)
            ->count('id');
    }

    public function getFirstVisitDateByMembers(
        string $date,
        ?string $maxDate,
        int $dateCondition,
        ?int $memberId,
    ): Collection {
        return $this->getFirstVisitDateByMemberCommonQuery($date, $maxDate, $dateCondition, $memberId)
            ->get();
    }

    private function getFirstVisitDateByMemberCommonQuery(
        string $date,
        ?string $maxDate,
        int $dateCondition,
        ?int $memberId,
    ): Builder {
        return Member::query()
            ->when($memberId, function ($query) use ($memberId): void {
                $query->where('id', $memberId);
            })
            ->when($dateCondition == DateConditionTypes::MORE_THAN->value, function ($query) use ($date): void {
                $query->whereDate('first_purchase_date', '>', $date);
            })
            ->when($dateCondition == DateConditionTypes::LESS_THAN->value, function ($query) use ($date): void {
                $query->whereDate('first_purchase_date', '<', $date);
            })
            ->when($dateCondition == DateConditionTypes::BETWEEN->value, function ($query) use ($date, $maxDate): void {
                $query->whereDateBetween('first_purchase_date', $date, $maxDate);
            })
            ->when($dateCondition == DateConditionTypes::EXACTLY_ON->value, function ($query) use ($date): void {
                $query->whereDate('first_purchase_date', '=', $date);
            });
    }

    public function getLastVisitDateByMemberCount(string $date, ?string $maxDate, int $dateCondition): int
    {
        return $this->getLastVisitDateByMemberCommonQuery($date, $maxDate, $dateCondition, null)
            ->count('id');
    }

    public function getLastVisitDateByMembers(
        string $date,
        ?string $maxDate,
        int $dateCondition,
        ?int $memberId,
    ): Collection {
        return $this->getLastVisitDateByMemberCommonQuery($date, $maxDate, $dateCondition, $memberId)
            ->get();
    }

    private function getLastVisitDateByMemberCommonQuery(
        string $date,
        ?string $maxDate,
        int $dateCondition,
        ?int $memberId,
    ): Builder {
        return Member::select('id')
            ->when($memberId, function ($query) use ($memberId): void {
                $query->where('id', $memberId);
            })
            ->when($dateCondition == DateConditionTypes::MORE_THAN->value, function ($query) use ($date): void {
                $query->whereDate('last_purchase_date', '>', $date);
            })
            ->when($dateCondition == DateConditionTypes::LESS_THAN->value, function ($query) use ($date): void {
                $query->whereDate('last_purchase_date', '<', $date);
            })
            ->when($dateCondition == DateConditionTypes::BETWEEN->value, function ($query) use ($date, $maxDate): void {
                $query->whereDateBetween('last_purchase_date', $date, $maxDate);
            })
            ->when($dateCondition == DateConditionTypes::EXACTLY_ON->value, function ($query) use ($date): void {
                $query->whereDate('last_purchase_date', '=', $date);
            });
    }

    public static function getTodayMemberCount(int $companyId, string $date, int $locationId): int
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where('created_location_id', $locationId);
            })
            ->count();
    }

    public static function getThisWeekMemberCount(array $weekDate, int $companyId, int $locationId): int
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', '>=', $weekDate[0])
            ->whereDate('created_at', '<=', $weekDate[1])
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where('created_location_id', $locationId);
            })
            ->count();
    }

    public static function getThisMonthMemberCount(array $monthDate, int $companyId, int $locationId): int
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', '>=', $monthDate[0])
            ->whereDate('created_at', '<=', $monthDate[1])
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where('created_location_id', $locationId);
            })
            ->count();
    }

    public static function getThisYearMemberCount(int $yearDate, int $companyId, int $locationId): int
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $yearDate)
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where('created_location_id', $locationId);
            })
            ->count();
    }

    public static function getLastYearMemberCount(int $yearDate, int $companyId, int $locationId): int
    {
        return Member::query()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $yearDate)
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where('created_location_id', $locationId);
            })
            ->count();
    }

    public function getNewAndExistingMembers(int $currentYear, int $companyId, int $locationId): Collection
    {
        return Member::query()
            ->leftJoin('sales', 'members.id', '=', 'sales.member_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'locations.id', '=', 'counters.location_id')
            ->whereNotNull('sales.id')
            ->selectRaw('
                DATE_FORMAT(sales.happened_at, "%M") as month,
                DATE_FORMAT(sales.happened_at, "%Y-%m-01") as sale_month,
                COUNT(DISTINCT CASE
                    WHEN DATE(members.created_at) = DATE(sales.happened_at)
                    THEN members.id
                END) as new_members,
                COUNT(DISTINCT CASE
                    WHEN DATE(members.created_at) < DATE(sales.happened_at)
                    THEN members.id
                END) as existing_members
            ')
            ->where('members.company_id', $companyId)
            ->where('locations.company_id', $companyId)
            ->whereYear('sales.happened_at', $currentYear)
            ->whereYear('members.created_at', $currentYear)
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where(function ($q) use ($locationId): void {
                    $q->where('counters.location_id', $locationId)
                    ->orWhere('members.created_location_id', $locationId);
                });
            })
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->get();
    }

    public function topTenSellingMembers(
        int $companyId,
        ?int $locationId,
        string $fromDate,
        string $toDate,
    ): Collection {
        return Cache::remember(
            'top-ten-selling-members' . $companyId . $locationId . $fromDate . $toDate,
            900,
            fn (): Collection => Member::query()
                    ->select(
                        'members.id',
                        'members.first_name',
                        'members.last_name',
                        DB::raw(
                            '(COALESCE(member_sale_total.total_paid_amount, 0) - COALESCE(member_return_total.return_amount, 0)) as total_sales'
                        ),
                    )
                    ->leftJoinSub(
                        DB::table('sales')
                            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                            ->join('locations', 'counters.location_id', '=', 'locations.id')
                            ->join('members', 'sales.member_id', '=', 'members.id')
                            ->where('locations.company_id', $companyId)
                            ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                                $query->where('counters.location_id', $locationId);
                            })
                            ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                            ->select(
                                'members.id as member_id',
                                DB::raw('SUM(sales.total_amount_paid) as total_paid_amount'),
                            )
                            ->groupBy('member_id'),
                        'member_sale_total',
                        'member_sale_total.member_id',
                        '=',
                        'members.id'
                    )
                    ->leftJoinSub(
                        DB::table('sale_returns')
                            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                            ->join('locations', 'counters.location_id', '=', 'locations.id')
                            ->join('members', 'sale_returns.member_id', '=', 'members.id')
                            ->where('locations.company_id', $companyId)
                            ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                                $query->where('counters.location_id', $locationId);
                            })
                            ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                            ->select(
                                'members.id as member_id',
                                DB::raw('SUM(sale_returns.total_price_paid) as return_amount'),
                            )
                            ->groupBy('member_id'),
                        'member_return_total',
                        'member_return_total.member_id',
                        '=',
                        'members.id'
                    )
                    ->whereNotNull('member_sale_total.total_paid_amount')
                    ->orWhereNotNull('member_return_total.return_amount')
                    ->orderByDesc('total_sales')
                    ->limit(10)
                    ->get()
        );
    }

    public function getByOnlyId(int $memberId): Member
    {
        $mediaQueries = resolve(MediaQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);

        return Member::select(
            'id',
            'title_id',
            'type_id',
            'gender_id',
            'first_name',
            'last_name',
            'mobile_number',
            'email',
            'company_id',
            'date_of_birth',
            'company_name',
            'notes',
            'membership_id',
            'card_number',
            'loyalty_points',
            'otp',
            'otp_expire_date',
            'status',
        )
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
            ])
            ->findOrFail($memberId);
    }

    public function getByMobileNumber(string $mobileNumber): ?Member
    {
        return Member::select('id', 'last_login_at', 'company_id')
            ->where('status', Status::ACTIVE->value)
            ->where('mobile_number', $mobileNumber)
            ->first();
    }

    public function getActiveAndInActiveByMobileNumber(string $mobileNumber): ?Member
    {
        return Member::select('id', 'last_login_at', 'company_id')
            ->where('mobile_number', $mobileNumber)
            ->first();
    }

    public function getCompanyId(int $memberId): int
    {
        /** @var Member $member */
        $member = Member::query()
            ->select('id', 'company_id')
            ->where('id', $memberId)
            ->first();

        return (int) $member->company_id;
    }

    public function checkUniqueEmailAndMobileNumber(int $memberId, string $email, ?string $mobileNumber): bool
    {
        return Member::select('id')
            ->when($mobileNumber, function ($query) use ($email, $mobileNumber): void {
                $query->where(function ($query) use ($email, $mobileNumber): void {
                    $query->whereCaseSensitive('email', $email)
                        ->orWhere('mobile_number', $mobileNumber);
                });
            }, function ($query) use ($email): void {
                $query->whereCaseSensitive('email', $email);
            })
            ->whereNot('id', $memberId)
            ->exists();
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?Member
    {
        return Member::select('id')
            ->whereDoesntHave('memberChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?Member
    {
        return Member::select('id')
            ->whereDoesntHave('memberChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getMemberEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId,
    ): Collection {
        $mediaQueries = resolve(MediaQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);

        return Member::select(
            'id',
            'gender_id',
            'first_name',
            'last_name',
            'gender_id',
            'mobile_number',
            'email',
            'company_id',
            'date_of_birth',
            'membership_id',
            'notes',
            'company_name',
            'title_id',
            'status',
            'loyalty_points',
            'otp_expire_date',
            'otp',
            'card_number',
            'phone',
        )->with([
            'media:' . $mediaQueries->getBasicColumnNames(),
            'memberAddresses:' . $memberAddressQueries->getBasicColumnNames(),
            'memberGroupMembers:' . $memberGroupMemberQueries->getBasicColumnNames(),
        ])
            ->whereDoesntHave('memberChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getMemberNameForFilter(int $id): ?string
    {
        $member = Member::where('id', $id)->first();
        if ($member) {
            return sprintf('%s  %s', $member->first_name, $member->last_name);
        }

        return null;
    }

    public function getMemberByEmployeeId(int $employeeId, int $companyId): ?Member
    {
        return Member::select('id', 'is_email_verified')
            ->where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->first();
    }

    public function createOrUpdateMemberFromAzentioMember(array $memberData): void
    {
        Member::updateOrCreate([
            'mobile_number' => $memberData['mobile_number'],
            'company_id' => $memberData['company_id'],
        ], [
            'first_name' => $memberData['name'],
            'email' => $memberData['email'],
            'card_number' => $memberData['card_number'],
            'is_azentio_member' => true,
        ]);
    }
}
