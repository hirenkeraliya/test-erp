<?php

declare(strict_types=1);

use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Models\Admin;
use App\Models\PromoterCommissionRegeneration;
use App\Models\SuperAdmin;

beforeEach(function (): void {
    $this->promoterCommissionRegenerationQueries = new PromoterCommissionRegenerationQueries();
});

test('addNew method add new Promoter Commission Regeneration', function (): void {
    $admin = Admin::factory()->create();
    $superAdmin = SuperAdmin::factory()->create();
    $period = now()->format('Y-m-d');

    $response = $this->promoterCommissionRegenerationQueries->addNew([
        'period' => $period,
        'admin_id' => $admin->id,
        'super_admin_id' => $superAdmin->id,
        'started_at' => now()->format('Y-m-d H:i:s'),
    ]);

    expect($response->toArray())
        ->toHaveKey('period', $period)
        ->toHaveKey('admin_id', $admin->id)
        ->toHaveKey('super_admin_id', $superAdmin->id);

    $this->assertDatabaseHas('promoter_commission_regenerations', [
        'period' => $period,
        'admin_id' => $admin->id,
        'super_admin_id' => $superAdmin->id,
    ]);
});

test('markAsCompleted work as expected', function (): void {
    $promoterCommissionRegeneration = PromoterCommissionRegeneration::factory()->create([
        'completed_at' => null,
    ]);

    $this->assertDatabaseHas('promoter_commission_regenerations', [
        'period' => $promoterCommissionRegeneration->period,
        'admin_id' => $promoterCommissionRegeneration->admin_id,
        'super_admin_id' => $promoterCommissionRegeneration->super_admin_id,
        'completed_at' => null,
    ]);

    $completedAt = now()->format('Y-m-d H:i:s');
    $this->promoterCommissionRegenerationQueries->markAsCompleted($completedAt);

    $this->assertDatabaseHas('promoter_commission_regenerations', [
        'period' => $promoterCommissionRegeneration->period,
        'admin_id' => $promoterCommissionRegeneration->admin_id,
        'super_admin_id' => $promoterCommissionRegeneration->super_admin_id,
        'completed_at' => $completedAt,
    ]);
});

test('markAsStarted work as expected', function (): void {
    $promoterCommissionRegeneration = PromoterCommissionRegeneration::factory()->create([
        'started_at' => null,
    ]);

    $this->assertDatabaseHas('promoter_commission_regenerations', [
        'period' => $promoterCommissionRegeneration->period,
        'admin_id' => $promoterCommissionRegeneration->admin_id,
        'super_admin_id' => $promoterCommissionRegeneration->super_admin_id,
        'started_at' => null,
    ]);

    $startedAt = now()->format('Y-m-d H:i:s');
    $this->promoterCommissionRegenerationQueries->markAsStarted($promoterCommissionRegeneration->id, $startedAt);

    $this->assertDatabaseHas('promoter_commission_regenerations', [
        'period' => $promoterCommissionRegeneration->period,
        'admin_id' => $promoterCommissionRegeneration->admin_id,
        'super_admin_id' => $promoterCommissionRegeneration->super_admin_id,
        'started_at' => $startedAt,
    ]);
});

test('entryExistsForPeriod method return as expected', function (): void {
    $currentTime = now();
    PromoterCommissionRegeneration::factory()->create([
        'period' => $currentTime->format('Y-m-d'),
        'completed_at' => null,
    ]);

    $response = $this->promoterCommissionRegenerationQueries->entryExistsForPeriod($currentTime->format('Y-m-d'));

    $this->assertTrue($response);

    $response = $this->promoterCommissionRegenerationQueries->entryExistsForPeriod(
        $currentTime->addDay()->format('Y-m-d')
    );

    $this->assertFalse($response);
});
