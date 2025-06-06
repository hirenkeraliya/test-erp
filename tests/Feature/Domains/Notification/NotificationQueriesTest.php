<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Promoter;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use Carbon\Carbon;

test('fetchMessages gives list of notifications.', function (): void {
    [$admin, $company, $notification] = notificationSeedRecords();

    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->fetchMessages($company->id, $admin->id, ModelMapping::ADMIN->name);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'message', 'created_at']);
});

test('it can be mark as read all', function (): void {
    [$admin, $company, $notification] = notificationSeedRecords();

    $notificationQueries = resolve(NotificationQueries::class);
    $notificationQueries->markAllAsRead($company->id, $admin->id, ModelMapping::ADMIN->name);

    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
        'mark_as_read_at' => null,
        'mark_as_read_by_id' => $admin->id,
        'mark_as_read_by_type' => ModelMapping::ADMIN->name,
    ]);
});

test('new notification can be added.', function (): void {
    $company = Company::factory()->create();
    $employeeOne = Employee::factory()->create([
        'company_id' => $company->id,
    ]);

    $employeeTwo = Employee::factory()->create([
        'company_id' => $company->id,
    ]);

    $storeManagerOne = StoreManager::factory()->create([
        'employee_id' => $employeeOne->id,
    ]);

    $storeManagerTwo = StoreManager::factory()->create([
        'employee_id' => $employeeTwo->id,
    ]);

    $notificationQueries = resolve(NotificationQueries::class);
    $notificationQueries->addNew(
        $company->id,
        ModelMapping::STORE_MANAGER->name,
        $storeManagerOne->id,
        ModelMapping::STORE_MANAGER->name,
        $storeManagerTwo->id,
        'test notification'
    );

    $this->assertDatabaseHas('notifications', [
        'company_id' => $company->id,
        'from_user_id' => $storeManagerOne->id,
        'from_user_type' => ModelMapping::STORE_MANAGER->name,
        'to_user_id' => $storeManagerTwo->id,
        'to_user_type' => ModelMapping::STORE_MANAGER->name,
        'message' => 'test notification',
    ]);
});

test('fetchMessagesByUserIdAndType gives list of notifications.', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();

    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->fetchMessagesByUserIdAndType($superAdmin->id, ModelMapping::SUPER_ADMIN->name);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'message', 'created_at']);
});

test('it can be mark as read', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();

    $notificationQueries = resolve(NotificationQueries::class);
    $notificationQueries->markAllAsReadByUserIdAndType($superAdmin->id, ModelMapping::SUPER_ADMIN->name);

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'mark_as_read_at' => Carbon::now(),
        'mark_as_read_by_id' => $superAdmin->id,
        'mark_as_read_by_type' => ModelMapping::SUPER_ADMIN->name,
    ]);
});

test('new notification can be added with null value.', function (): void {
    $superAdmin = SuperAdmin::factory()->create();

    $notificationQueries = resolve(NotificationQueries::class);
    $notification = $notificationQueries->addNewWithNullValue(
        ModelMapping::SUPER_ADMIN->name,
        $superAdmin->id,
        'test notification'
    );

    expect($notification->first()->toArray())
        ->toHaveKey('to_user_type', ModelMapping::SUPER_ADMIN->name)
        ->toHaveKey('to_user_id', $superAdmin->id)
        ->toHaveKey('message', 'test notification');

    $this->assertDatabaseHas('notifications', [
        'company_id' => null,
        'from_user_id' => null,
        'from_user_type' => null,
        'to_user_id' => $superAdmin->id,
        'to_user_type' => ModelMapping::SUPER_ADMIN->name,
        'message' => 'test notification',
    ]);
});

test('it can update message', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();

    $notificationQueries = resolve(NotificationQueries::class);
    $notificationQueries->updateMessage($notification, 'Update Message');

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'message' => 'Update Message',
    ]);
});

test('getById method return notification.', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();

    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->getById($notification->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $notification->id)
        ->toHaveKey('to_user_id', $notification->to_user_id)
        ->toHaveKey('to_user_type', $notification->to_user_type)
        ->toHaveKey('message', $notification->message);
});

test('markAsReadById method can notification mark as read.', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();

    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->markAsReadById(
        $notification->id,
        $superAdmin->id,
        ModelMapping::SUPER_ADMIN->name,
    );

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'mark_as_read_by_id' => $superAdmin->id,
        'mark_as_read_by_type' => ModelMapping::SUPER_ADMIN->name,
    ]);
});

test('fetchReadMessages gives list of notifications.', function (): void {
    [$admin, $company, $notification] = notificationSeedRecords();
    $notification->mark_as_read_at = Carbon::now()->format('Y-m-d H:i:s');
    $notification->save();
    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->fetchReadMessages($company->id, $admin->id, ModelMapping::ADMIN->name);
    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'message', 'created_at']);
});

test('fetchReadMessagesByUserIdAndType gives list of notifications.', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();
    $notification->mark_as_read_at = Carbon::now()->format('Y-m-d H:i:s');
    $notification->save();
    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->fetchReadMessagesByUserIdAndType(
        $superAdmin->id,
        ModelMapping::SUPER_ADMIN->name
    );

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'message', 'created_at']);
});

test('markAsUnReadByIds method can notification mark as unread.', function (): void {
    [$superAdmin, $notification] = notificationSeedRecordsWithoutCompanyId();

    $notificationQueries = resolve(NotificationQueries::class);
    $notificationQueries->markAsUnReadByIds([$notification->id], $superAdmin->id, ModelMapping::SUPER_ADMIN->name);

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'mark_as_read_at' => null,
        'mark_as_read_by_id' => null,
        'mark_as_read_by_type' => null,
    ]);
});

test('getUnReadNotifications method can notification mark as unread.', function (): void {
    $company = Company::factory()->create();
    $employeeOne = Employee::factory()->create([
        'company_id' => $company->id,
    ]);
    $admin = Admin::factory()->create([
        'employee_id' => $employeeOne->id,
    ]);
    $promoter = Promoter::factory()->create([
        'employee_id' => $employeeOne->id,
    ]);

    $notification = Notification::factory()->create([
        'id' => 1,
        'company_id' => $company->id,
        'from_user_id' => $admin->id,
        'from_user_type' => ModelMapping::ADMIN->name,
        'to_user_id' => $promoter->id,
        'to_user_type' => ModelMapping::PROMOTER->name,
        'mark_as_read_at' => null,
        'message' => 'first notification',
        'created_at' => Carbon::now(),
    ]);

    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->getUnReadNotifications([
        'per_page' => 1,
    ], $promoter->id, ModelMapping::PROMOTER->name);
    expect($response->first()->toArray())
        ->toHaveKey('id', $notification->id)
        ->toHaveKey('title', $notification->title)
        ->toHaveKey('message', $notification->message)
        ->toHaveKey('created_at');
});

test('getArchivedNotifications method can notification mark as unread.', function (): void {
    $company = Company::factory()->create();
    $employeeOne = Employee::factory()->create([
        'company_id' => $company->id,
    ]);
    $admin = Admin::factory()->create([
        'employee_id' => $employeeOne->id,
    ]);
    $promoter = Promoter::factory()->create([
        'employee_id' => $employeeOne->id,
    ]);

    $notification = Notification::factory()->create([
        'id' => 1,
        'company_id' => $company->id,
        'from_user_id' => $admin->id,
        'from_user_type' => ModelMapping::ADMIN->name,
        'to_user_id' => $promoter->id,
        'to_user_type' => ModelMapping::PROMOTER->name,
        'mark_as_read_at' => Carbon::now()->format('Y-m-d H:i:s'),
        'message' => 'first notification',
        'created_at' => Carbon::now(),
    ]);

    $notificationQueries = resolve(NotificationQueries::class);
    $response = $notificationQueries->getArchivedNotifications([
        'per_page' => 1,
    ], $promoter->id, ModelMapping::PROMOTER->name);
    expect($response->first()->toArray())
        ->toHaveKey('id', $notification->id)
        ->toHaveKey('title', $notification->title)
        ->toHaveKey('message', $notification->message)
        ->toHaveKey('mark_as_read_at', $notification->mark_as_read_at)
        ->toHaveKey('created_at');
});

function notificationSeedRecordsWithoutCompanyId(): array
{
    $superAdmin = SuperAdmin::factory()->create();

    $notification = Notification::factory()->create([
        'id' => 1,
        'to_user_id' => $superAdmin->id,
        'to_user_type' => ModelMapping::SUPER_ADMIN->name,
        'message' => 'first notification',
        'created_at' => Carbon::now(),
    ]);

    return [$superAdmin, $notification];
}

function notificationSeedRecords(): array
{
    $company = Company::factory()->create();
    $employeeOne = Employee::factory()->create([
        'company_id' => $company->id,
    ]);
    $admin = Admin::factory()->create([
        'employee_id' => $employeeOne->id,
    ]);

    $employeeTwo = Employee::factory()->create([
        'company_id' => $company->id,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employeeTwo->id,
    ]);

    $notification = Notification::factory()->create([
        'id' => 1,
        'company_id' => $company->id,
        'from_user_id' => $storeManager->id,
        'from_user_type' => ModelMapping::STORE_MANAGER->name,
        'to_user_id' => $admin->id,
        'to_user_type' => ModelMapping::ADMIN->name,
        'message' => 'first notification',
        'created_at' => Carbon::now(),
    ]);

    return [$admin, $company, $notification];
}
