<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\Promoter;

test('promoter cannot login with incorrect credentials', function (): void {
    $this->post(route('promoter.get_token'), [
        'username' => 'test',
        'password' => '1234567',
    ])
    ->assertStatus(404);
});

test('promoter can login', function (): void {
    $employee = Employee::factory()->create([
        'status' => true,
    ]);

    $promoter = Promoter::factory()->create([
        'username' => 'test',
        'employee_id' => $employee->getKey(),
    ]);

    $this->post(route('promoter.get_token'), [
        'username' => $promoter->username,
        'password' => '123456',
    ])
    ->assertStatus(200)
    ->assertJsonStructure(['access_token']);
});
