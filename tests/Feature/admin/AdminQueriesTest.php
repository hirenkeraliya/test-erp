<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\ChangePasswordData;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->admin = Admin::factory()->create();
    loginAdmin($this->admin);
    $this->adminQueries = new AdminQueries();
});

test('Admin can change password', function (): void {
    $this->adminQueries->changePassword($this->admin, new ChangePasswordData('123456', '111111'));

    $this->admin->refresh();
    $this->assertTrue(Hash::check('111111', $this->admin->password));
});
