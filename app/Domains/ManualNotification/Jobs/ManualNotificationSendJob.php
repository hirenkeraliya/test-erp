<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\MembersFilter;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\ManualNotificationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Models\ManualNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManualNotificationSendJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $manualNotificationId,
        private readonly int $companyId,
        private readonly int $userId,
    ) {
    }

    public function handle(): void
    {
        $notificationQueries = resolve(NotificationQueries::class);
        $manualNotificationQueries = resolve(ManualNotificationQueries::class);

        $manualNotification = $manualNotificationQueries->getWithById($this->manualNotificationId, $this->companyId);

        try {
            $manualNotificationQueries->markAsInProgress($manualNotification);

            if ($manualNotification->type_id === ManualNotificationTypes::PROMOTERS->value) {
                $promoterIds = $this->getPromoterIds($manualNotification);

                foreach ($promoterIds as $promoterId) {
                    $notificationQueries->addNew(
                        $manualNotification->company_id,
                        ModelMapping::ADMIN->name,
                        $this->userId,
                        ModelMapping::PROMOTER->name,
                        $promoterId,
                        $manualNotification->message,
                        $manualNotification->title,
                        $manualNotification->message,
                        null,
                    );
                }
            }

            if ($manualNotification->type_id === ManualNotificationTypes::MEMBERS->value) {
                $memberIds = $this->getMemberIds($manualNotification);

                foreach ($memberIds as $memberId) {
                    $notificationQueries->addNew(
                        $manualNotification->company_id,
                        ModelMapping::ADMIN->name,
                        $this->userId,
                        ModelMapping::MEMBER->name,
                        $memberId,
                        $manualNotification->message,
                        $manualNotification->title
                    );
                }
            }

            $manualNotificationQueries->markAsCompleted($manualNotification);
        } catch (Throwable $throwable) {
            Log::error('Manual NOtification Send Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }

    private function getPromoterIds(ManualNotification $manualNotification): array
    {
        $promoterQueries = resolve(PromoterQueries::class);
        if ($manualNotification->promoter_filter_type_id === PromotersFilter::PROMOTERS->value) {
            return $manualNotification->promoters->pluck('id')->toArray();
        }

        if ($manualNotification->promoter_filter_type_id === PromotersFilter::GROUPS->value) {
            $promoterGroupIds = $manualNotification->promoterGroups->pluck('id')->toArray();

            return $promoterQueries->getPromoterByPromoterGroup($promoterGroupIds)->pluck('id')->toArray();
        }

        if ($manualNotification->promoter_filter_type_id === PromotersFilter::LOCATIONS->value) {
            $locationIds = $manualNotification->locations->pluck('id')->toArray();

            return $promoterQueries->getPromoterByLocations($locationIds)->pluck('id')->toArray();
        }

        return [];
    }

    private function getMemberIds(ManualNotification $manualNotification): array
    {
        /** @var int $companyId */
        $companyId = $manualNotification->company_id;

        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        if ($manualNotification->member_filter_type_id === MembersFilter::GROUPS->value) {
            $memberGroupIds = $manualNotification->memberGroups->pluck('id')->toArray();

            return $memberGroupMemberQueries->getMembersByMemberGroupIds($memberGroupIds, $companyId)->pluck(
                'id'
            )->toArray();
        }

        if ($manualNotification->member_filter_type_id === MembersFilter::TYPES->value) {
            $memberTypeIds = $manualNotification->memberTypes->pluck('member_type_id')->toArray();

            return $memberQueries->getMembersByMemberTypeIds($memberTypeIds, $companyId)->pluck('id')->toArray();
        }

        if ($manualNotification->member_filter_type_id === MembersFilter::STORES->value) {
            $locationIds = $manualNotification->locations->pluck('id')->toArray();

            return $memberQueries->getMembersByStoreIds($locationIds, $companyId)->pluck('id')->toArray();
        }

        if ($manualNotification->member_filter_type_id === MembersFilter::MEMBERS->value) {
            return $manualNotification->members->pluck('id')->toArray();
        }

        return [];
    }
}
