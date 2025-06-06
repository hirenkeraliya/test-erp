<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Resources;

use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\MembersFilter;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\Member\Enums\Types;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualNotificationDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     */
    public function toArray($request): array
    {
        $manualNotificationDetails = $this->resource;
        $return = [];
        if ($manualNotificationDetails->type_id === ManualNotificationTypes::PROMOTERS->value && $manualNotificationDetails->promoter_filter_type_id === PromotersFilter::PROMOTERS->value) {
            $promoters = $manualNotificationDetails->promoters;
            foreach ($promoters as $promoter) {
                $employee = $promoter->employee;
                $return[] = [
                    'name' => $employee->getFullName(),
                ];
            }
        }

        if ($manualNotificationDetails->type_id === ManualNotificationTypes::PROMOTERS->value && $manualNotificationDetails->promoter_filter_type_id === PromotersFilter::GROUPS->value) {
            $promoterGroups = $manualNotificationDetails->promoterGroups;
            foreach ($promoterGroups as $promoterGroup) {
                $return[] = [
                    'name' => $promoterGroup->name,
                ];
            }
        }

        if ($manualNotificationDetails->promoter_filter_type_id === PromotersFilter::LOCATIONS->value || $manualNotificationDetails->member_filter_type_id === MembersFilter::STORES->value) {
            $locations = $manualNotificationDetails->locations;
            foreach ($locations as $location) {
                $return[] = [
                    'name' => $location->name,
                ];
            }
        }

        if ($manualNotificationDetails->type_id === ManualNotificationTypes::MEMBERS->value && $manualNotificationDetails->member_filter_type_id === MembersFilter::GROUPS->value) {
            $memberGroups = $manualNotificationDetails->memberGroups;
            foreach ($memberGroups as $memberGroup) {
                $return[] = [
                    'name' => $memberGroup->name,
                ];
            }
        }

        if ($manualNotificationDetails->type_id === ManualNotificationTypes::MEMBERS->value && $manualNotificationDetails->member_filter_type_id === MembersFilter::TYPES->value) {
            $memberTypes = $manualNotificationDetails->memberTypes;
            foreach ($memberTypes as $memberType) {
                $return[] = [
                    'name' => Types::getFormattedCaseName($memberType->member_type_id),
                ];
            }
        }

        if ($manualNotificationDetails->type_id === ManualNotificationTypes::MEMBERS->value && $manualNotificationDetails->member_filter_type_id === MembersFilter::MEMBERS->value) {
            $members = $manualNotificationDetails->members;
            foreach ($members as $member) {
                $employee = $member->employee;
                $return[] = [
                    'name' => $employee->getFullName(),
                ];
            }
        }

        return $return;
    }
}
