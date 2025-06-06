<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Spatie\UrlSigner\Laravel\Facades\UrlSigner;

beforeEach(function (): void {
    $this->admin = Admin::factory()->create();
});

test('admin cannot login with Credentials are incorrect', function (): void {
    $this->post(route('admin.login_user'), [
        'username' => $this->admin->username,
        'password' => '1234567',
    ])
    ->assertStatus(302)
    ->assertRedirect('/');

    $this->assertGuest('admin');
});

test('Admin can login with correct credentials', function (): void {
    $response = $this->post(route('admin.login_user'), [
        'username' => $this->admin->username,
        'password' => '123456',
    ])
    ->assertStatus(302)
    ->assertRedirect(route('admin.dashboard'));

    $this->admin->load('employee:id,company_id', 'employee.company:id');
    $employee = $this->admin->employee;

    $this->assertAuthenticatedAs($this->admin, 'admin');
    $response->assertSessionHas('admin_company_id', $employee->company_id);
});

test('Admin cannot visit the dashboard page without login', function (): void {
    $this->get(route('admin.dashboard'))
    ->assertStatus(302)
    ->assertRedirect('/admin');

    $this->assertGuest('admin');
});

test('Admin cannot visit the login page after login', function (): void {
    $this->post(route('admin.login_user'), [
        'username' => $this->admin->username,
        'password' => '123456',
    ])
    ->assertStatus(302);

    $this->get('/admin')
    ->assertRedirect(route('admin.dashboard'));
});

test('Admin cannot login if employee status is inactive', function (): void {
    $admin = Admin::factory()->create([
        'employee_id' => Employee::factory()->inactive()->create()->id,
    ]);

    $this->post(route('admin.login_user'), [
        'username' => $admin->username,
        'password' => '123456',
    ])
    ->assertStatus(302)
    ->assertRedirect('/');

    $this->assertGuest('admin');
});

test('it throws an error if SSO is requested with invalid url', function (): void {
    $this->get(route('admin.login', [
        'intent' => 'sso',
        'redirectBackTo' => 'https://url-not-in-the-list.com',
    ]))
    ->assertStatus(412);
});

test('it redirects to respective url after login when sso is requested', function (): void {
    $retailPlanningUrl = 'http://retail-planning.local/sso-login';

    $adminDetails = [
        'name' => AdminQueries::getEmployeeFullName($this->admin),
        'ulid' => $this->admin->ulid,
    ];

    Carbon::withTestNow(Carbon::now(), function () use ($retailPlanningUrl, $adminDetails): void {
        $redirectUrl = UrlSigner::sign($retailPlanningUrl . '?' . http_build_query($adminDetails), 15);

        $this->post(route('admin.login_user', [
            'intent' => 'sso',
            'redirectBackTo' => $retailPlanningUrl,
        ]), [
            'username' => $this->admin->username,
            'password' => '123456',
        ])
        ->assertStatus(302)
        ->assertRedirect($redirectUrl);
    });
});

test('if user is logged in, the redirect happens even when sso is requested', function (): void {
    loginAdmin($this->admin);

    $retailPlanningUrl = 'http://retail-planning.local/sso-login';

    $adminDetails = [
        'name' => AdminQueries::getEmployeeFullName($this->admin),
        'ulid' => $this->admin->ulid,
    ];

    $redirectUrl = UrlSigner::sign($retailPlanningUrl . '?' . http_build_query($adminDetails), 15);

    $this->get(route('admin.login', [
        'intent' => 'sso',
        'redirectBackTo' => $retailPlanningUrl,
    ]))
    ->assertStatus(302)
    ->assertRedirect($redirectUrl);
});
