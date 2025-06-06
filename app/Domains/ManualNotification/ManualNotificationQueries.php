<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\ManualNotification\DataObjects\ManualNotificationData;
use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\MembersFilter;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\Enums\Statuses;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Models\ManualNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ManualNotificationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->notificationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(ManualNotificationData $manualNotificationData, int $companyId): ManualNotification
    {
        $manualNotificationDetails = $manualNotificationData->all();

        $manualNotificationDetails['company_id'] = $companyId;
        $manualNotificationDetails['type_id'] = $manualNotificationDetails['notification_type'];
        $manualNotificationDetails['promoter_filter_type_id'] = $manualNotificationDetails['promoter_filter_type'];
        $manualNotificationDetails['member_filter_type_id'] = $manualNotificationDetails['member_filter_type'];

        unset($manualNotificationDetails['location_ids'], $manualNotificationDetails['promoter_ids'], $manualNotificationDetails['promoter_group_ids'], $manualNotificationDetails['notification_type'], $manualNotificationDetails['member_group_ids'], $manualNotificationDetails['member_type_ids'], $manualNotificationDetails['member_ids'], $manualNotificationDetails['promoter_filter_type'], $manualNotificationDetails['member_filter_type']);

        $manualNotification = ManualNotification::create($manualNotificationDetails);

        $this->attachBroadcasters($manualNotification, $manualNotificationData);

        return $manualNotification;
    }

    private function notificationQuery(array $filterData, int $companyId): Builder
    {
        return ManualNotification::query()
            ->select('id', 'title', 'message', 'status', 'type_id')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['title', 'message'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhere(function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw(
                                'status',
                                Statuses::getMatchingCases($filterData['search_text'])
                            )->orWhereIntegerInRaw(
                                'type_id',
                                ManualNotificationTypes::getMatchingCases($filterData['search_text'])
                            );
                        });
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function markAsCompleted(ManualNotification $manualNotification): void
    {
        $manualNotification->status = Statuses::COMPLETED->value;
        $manualNotification->save();
    }

    public function markAsInProgress(ManualNotification $manualNotification): void
    {
        $manualNotification->status = Statuses::IN_PROGRESS->value;
        $manualNotification->save();
    }

    private function attachBroadcasters(
        ManualNotification $manualNotification,
        ManualNotificationData $manualNotificationData
    ): void {
        if ($manualNotificationData->promoter_filter_type === PromotersFilter::PROMOTERS->value && null !== $manualNotificationData->promoter_ids) {
            $manualNotification->promoters()->sync($manualNotificationData->promoter_ids);
        }

        if ($manualNotificationData->promoter_filter_type === PromotersFilter::GROUPS->value && null !== $manualNotificationData->promoter_group_ids) {
            $manualNotification->promoterGroups()->sync($manualNotificationData->promoter_group_ids);
        }

        if ($manualNotificationData->member_filter_type === MembersFilter::GROUPS->value && null !== $manualNotificationData->member_group_ids) {
            $manualNotification->memberGroups()->sync($manualNotificationData->member_group_ids);
        }

        if ($manualNotificationData->member_filter_type === MembersFilter::MEMBERS->value && null !== $manualNotificationData->member_ids) {
            $manualNotification->members()->sync($manualNotificationData->member_ids);
        }

        if ($manualNotificationData->member_filter_type === MembersFilter::TYPES->value && null !== $manualNotificationData->member_type_ids) {
            $memberTypeIds = collect($manualNotificationData->member_type_ids)->map(
                fn ($memberTypeId): array => [
                    'member_type_id' => $memberTypeId,
                ]
            )->toArray();
            $manualNotification->memberTypes()->createMany($memberTypeIds);
        }

        if ($manualNotificationData->promoter_filter_type !== PromotersFilter::LOCATIONS->value && $manualNotificationData->member_filter_type !== MembersFilter::STORES->value) {
            return;
        }

        if (null === $manualNotificationData->location_ids) {
            return;
        }

        $manualNotification->locations()->sync($manualNotificationData->location_ids);
    }

    public function getWithById(int $manualNotificationId, int $companyId): ManualNotification
    {
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return ManualNotification::query()
            ->select(
                'id',
                'title',
                'message',
                'type_id',
                'promoter_filter_type_id',
                'member_filter_type_id',
                'company_id',
                'status'
            )
            ->where('company_id', $companyId)
            ->with(
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'promoterGroups:' . $promoterGroupQueries->getBasicColumnNames(),
                'locations:' . $locationQueries->getNameColumnName(),
            )
            ->findOrFail($manualNotificationId);
    }

    public function updateMemberIdsInManualNotificationMemberPivot(int $oldMemberId, int $newMemberId): void
    {
        DB::table('manual_notification_member')
            ->where('member_id', $oldMemberId)
            ->update([
                'member_id' => $newMemberId,
            ]);
    }
}
