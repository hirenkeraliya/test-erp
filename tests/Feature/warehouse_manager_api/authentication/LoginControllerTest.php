<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\WarehouseManager;

test('warehouse_manager cannot login with incorrect credentials', function (): void {
    $this->post(route('warehouse_manager.get_token'), [
        'username' => 'test',
        'password' => '1234567',
    ])
    ->assertStatus(404);
});

test('warehouse_manager can login', function (): void {
    $employee = Employee::factory()->create([
        'status' => true,
    ]);

    $promoter = WarehouseManager::factory()->create([
        'username' => 'test',
        'employee_id' => $employee->getKey(),
    ]);

    $this->post(route('warehouse_manager.get_token'), [
        'username' => $promoter->username,
        'password' => '123456',
    ])
    ->assertStatus(200)
    ->assertJsonStructure(['access_token']);
});
