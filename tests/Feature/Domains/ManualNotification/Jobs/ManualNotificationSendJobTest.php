<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\MembersFilter;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\Enums\Statuses;
use App\Domains\ManualNotification\Jobs\ManualNotificationSendJob;
use App\Domains\Member\Enums\Types;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Location;
use App\Models\ManualNotification;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\MemberGroupMember;
use App\Models\Promoter;
use App\Models\PromoterGroup;
use Illuminate\Support\Str;

test(
    'notification send by type_id promoters',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $promoter = Promoter::factory()->create();

        $manualNotification = ManualNotification::factory()->create([
            'type_id' => ManualNotificationTypes::PROMOTERS->value,
            'promoter_filter_type_id' => PromotersFilter::PROMOTERS->value,
            'member_filter_type_id' => null,
            'company_id' => $companyId,
        ]);

        $manualNotification->promoters()->attach([$promoter->id]);

        $admin = Admin::factory()->create();

        ManualNotificationSendJob::dispatch($manualNotification->id, $companyId, $admin->id)->onQueue(
            config('horizon.default_queue_name')
        );

        $this->assertDatabaseHas('notifications', [
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('manual_notifications', [
            'status' => Statuses::COMPLETED->value,
        ]);
    }
);

test(
    'notification send by type_id promoter groups',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $promoterGroup = PromoterGroup::factory()->create();

        Promoter::factory()->create([
            'group_id' => $promoterGroup->id,
        ]);

        $manualNotification = ManualNotification::factory()->create([
            'type_id' => ManualNotificationTypes::PROMOTERS->value,
            'promoter_filter_type_id' => PromotersFilter::GROUPS->value,
            'member_filter_type_id' => null,
            'company_id' => $companyId,
        ]);

        $manualNotification->promoterGroups()->attach([$promoterGroup->id]);

        $admin = Admin::factory()->create();

        ManualNotificationSendJob::dispatch($manualNotification->id, $companyId, $admin->id)->onQueue(
            config('horizon.default_queue_name')
        );

        $this->assertDatabaseHas('notifications', [
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('manual_notifications', [
            'status' => Statuses::COMPLETED->value,
        ]);
    }
);

test(
    'notification send by type_id stores promoter',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'type_id' => LocationTypes::STORE->value,
        ]);

        $promoter = Promoter::factory()->create();
        $promoter->locations()->attach([$location->id]);

        $manualNotification = ManualNotification::factory()->create([
            'type_id' => ManualNotificationTypes::PROMOTERS->value,
            'promoter_filter_type_id' => PromotersFilter::LOCATIONS->value,
            'member_filter_type_id' => null,
            'company_id' => $companyId,
        ]);

        $manualNotification->locations()->attach([$location->id]);

        $admin = Admin::factory()->create();

        ManualNotificationSendJob::dispatch($manualNotification->id, $companyId, $admin->id)->onQueue(
            config('horizon.default_queue_name')
        );

        $this->assertDatabaseHas('notifications', [
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('manual_notifications', [
            'status' => Statuses::COMPLETED->value,
        ]);
    }
);

test(
    'notification send by type_id member groups',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $memberGroup = MemberGroup::factory()->create();

        $member = Member::factory()->create([
            'company_id' => $companyId,
            'fcm_token' => Str::random(12),
        ]);

        MemberGroupMember::factory()->create([
            'member_id' => $member->id,
            'member_group_id' => $memberGroup->id,
        ]);

        $manualNotification = ManualNotification::factory()->create([
            'type_id' => ManualNotificationTypes::MEMBERS->value,
            'member_filter_type_id' => MembersFilter::GROUPS->value,
            'promoter_filter_type_id' => null,
            'company_id' => $companyId,
        ]);

        $manualNotification->memberGroups()->attach([$memberGroup->id]);

        $admin = Admin::factory()->create();

        ManualNotificationSendJob::dispatch($manualNotification->id, $companyId, $admin->id)->onQueue(
            config('horizon.default_queue_name')
        );

        $this->assertDatabaseHas('notifications', [
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('manual_notifications', [
            'status' => Statuses::COMPLETED->value,
        ]);
    }
);

test(
    'notification send by type_id stores member',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'type_id' => LocationTypes::STORE->value,
        ]);

        Member::factory()->create([
            'company_id' => $companyId,
            'created_location_id' => $location->id,
            'fcm_token' => Str::random(12),
        ]);

        $manualNotification = ManualNotification::factory()->create([
            'type_id' => ManualNotificationTypes::MEMBERS->value,
            'member_filter_type_id' => MembersFilter::STORES->value,
            'promoter_filter_type_id' => null,
            'company_id' => $companyId,
        ]);

        $manualNotification->locations()->attach([$location->id]);

        $admin = Admin::factory()->create();

        ManualNotificationSendJob::dispatch($manualNotification->id, $companyId, $admin->id)->onQueue(
            config('horizon.default_queue_name')
        );

        $this->assertDatabaseHas('notifications', [
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('manual_notifications', [
            'status' => Statuses::COMPLETED->value,
        ]);
    }
);

test(
    'notification send by type_id member types',
    function (): void {
        $companyId = Company::factory()->create()->id;

        Member::factory()->create([
            'company_id' => $companyId,
            'type_id' => Types::VIP->value,
            'fcm_token' => Str::random(12),
        ]);

        $TypeIds = Types::VIP->value;

        $manualNotification = ManualNotification::factory()->create([
            'type_id' => ManualNotificationTypes::MEMBERS->value,
            'member_filter_type_id' => MembersFilter::TYPES->value,
            'promoter_filter_type_id' => null,
            'company_id' => $companyId,
        ]);

        $memberTypeIds = collect($TypeIds)->map(fn ($TypeId): array => [
            'member_type_id' => $TypeId,
        ])->toArray();

        $manualNotification->memberTypes()->createMany($memberTypeIds);

        $admin = Admin::factory()->create();

        ManualNotificationSendJob::dispatch($manualNotification->id, $companyId, $admin->id)->onQueue(
            config('horizon.default_queue_name')
        );

        $this->assertDatabaseHas('notifications', [
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('manual_notifications', [
            'status' => Statuses::COMPLETED->value,
        ]);
    }
);
