<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promoter\DataObjects\PromoterApplicationData;
use App\Domains\StoreManager\DataObjects\StoreManagerApplicationData;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Promoter;
use App\Models\SuperAdmin;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);

    $this->companyB = Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'EFGH',
    ]);

    $this->employeeGroupA = EmployeeGroup::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);

    $membership = Membership::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $this->employeeA = Employee::factory()->create([
        'id' => 1,
        'company_id' => $this->companyA->id,
        'first_name' => 'DEF',
        'last_name' => 'JKL',
        'spent_till_now' => 0,
        'loyalty_points' => 100,
        'group_id' => $this->employeeGroupA->id,
        'created_at' => Carbon::now()->format('Y-m-d'),
        'membership_id' => $membership->id,
        'is_email_verified' => 0,
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->companyB->id,
        'first_name' => 'GHI',
        'last_name' => 'MNO',
        'created_at' => Carbon::now()->format('Y-m-d'),
        'membership_id' => $membership->id,
    ]);

    $this->employeeQueries = new EmployeeQueries();
});

test('Employees can be searched', function (): void {
    $response = $this->employeeQueries->superAdminListQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('company_id', $this->employeeA->company_id)
        ->toHaveKey('first_name', $this->employeeA->first_name)
        ->toHaveKey('company.name', $this->employeeA->company->name);
});

test('Active company employees should list', function (): void {
    $this->companyB->delete();

    $response = $this->employeeQueries->superAdminListQuery([
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('company_id', $this->employeeA->company_id)
        ->toHaveKey('first_name', $this->employeeA->first_name)
        ->toHaveKey('company.name', $this->employeeA->company->name);
});

test('Employees are returned as per page', function (): void {
    $response = $this->employeeQueries->superAdminListQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ]);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('company_id', $this->employeeB->company_id)
        ->toHaveKey('first_name', $this->employeeB->first_name);
});

test('the getColumnsForPanelHeader method call and returns the required columns', function (): void {
    $response = $this->employeeQueries->getColumnsForPanelHeader($this->employeeB->id);

    expect($response->toArray())
        ->toHaveKey('first_name', $this->employeeB->first_name)
        ->toHaveKey('last_name', $this->employeeB->last_name)
        ->toHaveKey('staff_id', $this->employeeB->staff_id);
});

test(
    'the getByIdForEmployeeUpdatePointsAndTotalSalesJob method call and returns the required columns',
    function (): void {
        $response = $this->employeeQueries->getByIdForEmployeeUpdatePointsAndTotalSalesJob($this->employeeB->id);

        expect($response->toArray())
            ->toHaveKey('id', $this->employeeB->id)
            ->toHaveKey('company_id', $this->employeeB->company_id)
            ->toHaveKey('total_earned_points', $this->employeeB->total_earned_points)
            ->toHaveKey('total_expired_points', $this->employeeB->total_expired_points)
            ->toHaveKey('total_redeemed_points', $this->employeeB->total_redeemed_points)
            ->toHaveKey('total_sales', $this->employeeB->total_sales);
    }
);

test("Employees are returned as per admin's company", function (): void {
    $response = $this->employeeQueries->adminListQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('first_name', $this->employeeA->first_name);
});

test('Employees can be fetched by company id', function (): void {
    $response = $this->employeeQueries->getByCompanyId($this->companyA->id);
    expect($response[0])
        ->toHaveKey('id', $this->employeeA->id)
        ->toHaveKey('first_name', $this->employeeA->first_name);
});

test('getFormattedEmployeesOf returns formatted employees', function (): void {
    $response = $this->employeeQueries->getFormattedEmployeesOf($this->companyA->id);
    expect($response[0])
        ->toHaveKey('id', $this->employeeA->id)
        ->toHaveKey(
            'name',
            $this->employeeA->first_name . ' ' . $this->employeeA->last_name . ' (' . $this->employeeA->staff_id . ')'
        );
});

test('It can store employee', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $newEmployeeRecord = Employee::factory()->make()->toArray();
    $newEmployeeRecord['photo'] = $uploadedFile;
    unset($newEmployeeRecord['card_number']);

    $admin = Admin::factory()->create();

    $this->employeeQueries->addNew(new EmployeeData(...$newEmployeeRecord), $admin);

    unset($newEmployeeRecord['photo']);

    $this->assertDatabaseHas('employees', $newEmployeeRecord);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::EMPLOYEE->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('It can return employee', function (): void {
    $response = $this->employeeQueries->getByIdWithMedia($this->employeeA->id);
    $employee = $this->employeeA->load('media');

    unset($employee['updated_at'], $employee['created_at'], $employee['spent_till_now'], $employee['loyalty_points'], $employee['card_number'], $response['created_at']);

    $this->assertEquals($employee->toArray(), $response->toArray());
});

test('It can update employee', function (): void {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $this->employeeA->status = true;
    $this->employeeA->save();

    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $employeeRecord = Employee::factory()->make([
        'status' => false,
    ])->toArray();

    $employeeRecord['photo'] = $uploadedFile;
    unset($employeeRecord['card_number']);
    $this->employeeQueries->update(new EmployeeData(...$employeeRecord), $admin, $this->employeeA->id);

    unset($employeeRecord['photo']);
    $this->assertDatabaseHas('employees', $employeeRecord);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::EMPLOYEE->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => null,
    ]);
});

test('It can update employee and employee id is set in member table', function (): void {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $this->employeeA->status = false;
    $this->employeeA->save();

    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $employeeRecord = Employee::factory()->make([
        'status' => true,
    ])->toArray();

    $employeeRecord['photo'] = $uploadedFile;
    unset($employeeRecord['card_number']);
    $this->employeeQueries->update(new EmployeeData(...$employeeRecord), $admin, $this->employeeA->id);

    unset($employeeRecord['photo']);
    $this->assertDatabaseHas('employees', $employeeRecord);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);
});

test('It can update employee but employee id is not update in member table when status are same', function (): void {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $this->employeeA->status = false;
    $this->employeeA->save();

    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $employeeRecord = Employee::factory()->make([
        'status' => false,
    ])->toArray();

    $employeeRecord['photo'] = $uploadedFile;
    unset($employeeRecord['card_number']);
    $this->employeeQueries->update(new EmployeeData(...$employeeRecord), $admin, $this->employeeA->id);

    unset($employeeRecord['photo']);
    $this->assertDatabaseHas('employees', $employeeRecord);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);
});

test('A employee can be fetched with membership', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $this->employeeA->membership_id = $membership->id;
    $this->employeeA->save();
    $this->employeeA->membership = $membership;
    $response = $this->employeeQueries->getByIdWithMembership($this->employeeA->id, $this->companyA->id);
    $this->employeeA->load('membership');

    expect($response->toArray())
        ->toHaveKey('id', $this->employeeA->id)
        ->toHaveKey('spent_till_now', $this->employeeA->spent_till_now)
        ->toHaveKey('membership');
});

test('updateSpentTillNow method updates the spent_till_now column', function (): void {
    $this->employeeQueries->updateSpentTillNow(100, $this->employeeA->id);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'spent_till_now' => 100,
    ]);
});

test('setMembershipId method sets the membership_id column', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $this->employeeQueries->setMembershipId($membership->id, $this->employeeA->id);
    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'membership_id' => $membership->id,
    ]);
});

test('decreaseLoyaltyPoints method decrease the employee loyalty points as expected', function (): void {
    $this->employeeA->loyalty_points = 10;
    $this->employeeA->save();

    $this->employeeQueries->decreaseLoyaltyPoints($this->employeeA->id, 5);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'loyalty_points' => $this->employeeA->loyalty_points - 5,
    ]);
});

test(
    'getByIdWithMembershipAndLoyaltyPoints method returns the membership_id and loyalty_points column',
    function (): void {
        $response = $this->employeeQueries->getByIdWithMembershipAndLoyaltyPoints(
            $this->companyA->id,
            $this->employeeA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('membership_id', $this->employeeA->membership_id)
            ->toHaveKey('loyalty_points', $this->employeeA->loyalty_points);
    }
);

test('increaseLoyaltyPoints method updates the loyalty points', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyA->id,
        'first_name' => 'employee',
        'last_name' => 'one',
        'spent_till_now' => 0,
        'loyalty_points' => 10,
    ]);

    $this->employeeQueries->increaseLoyaltyPoints($employee, 10);

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'loyalty_points' => 20,
    ]);
});

test('admin can change the status of the employee', function (): void {
    $admin = Admin::factory()->create();
    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeA->status = true;
    $this->employeeA->save();

    $this->employeeQueries->adminSetStatus($this->employeeA->id, $this->companyA->id, false, $admin);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'status' => false,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => null,
    ]);
});

test('admin can change the status of the employee and set employee id in member table', function (): void {
    $admin = Admin::factory()->create();

    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeA->status = false;
    $this->employeeA->save();

    $this->employeeQueries->adminSetStatus($this->employeeA->id, $this->companyA->id, true, $admin);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'status' => true,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);
});

test(
    'admin can change the status of the employee but not update employee id in member table when status are same',
    function (): void {
        $admin = Admin::factory()->create();

        $member = Member::factory()->create([
            'employee_id' => $this->employeeA->id,
        ]);

        $this->employeeA->status = true;
        $this->employeeA->save();

        $this->employeeQueries->adminSetStatus($this->employeeA->id, $this->companyA->id, true, $admin);

        $this->assertDatabaseHas('employees', [
            'id' => $this->employeeA->id,
            'status' => true,
        ]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'employee_id' => $this->employeeA->id,
        ]);
    }
);

test('super admin can change the status of the employee', function (): void {
    $superAdmin = SuperAdmin::factory()->create();

    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeA->status = true;
    $this->employeeA->save();

    $this->employeeQueries->superAdminSetStatus($this->employeeA->id, false, $superAdmin);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'status' => false,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => null,
    ]);
});

test('super admin can change the status of the employee and set employee id in member table', function (): void {
    $superAdmin = SuperAdmin::factory()->create();

    $member = Member::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeA->status = false;
    $this->employeeA->save();

    $this->employeeQueries->superAdminSetStatus($this->employeeA->id, true, $superAdmin);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'status' => true,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'employee_id' => $this->employeeA->id,
    ]);
});

test(
    'super admin can change the status of the employee but not update employee id in member table when status are same',
    function (): void {
        $superAdmin = SuperAdmin::factory()->create();

        $member = Member::factory()->create([
            'employee_id' => $this->employeeA->id,
        ]);

        $this->employeeA->status = false;
        $this->employeeA->save();

        $this->employeeQueries->superAdminSetStatus($this->employeeA->id, false, $superAdmin);

        $this->assertDatabaseHas('employees', [
            'id' => $this->employeeA->id,
            'status' => false,
        ]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'employee_id' => $this->employeeA->id,
        ]);
    }
);

test('getAdminEmployeesExport method returns employee as expected', function (): void {
    $response = $this->employeeQueries->getAdminEmployeesExport([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('first_name', $this->employeeA->first_name);
});

test('mobileNumberExist method returns boolean as expected', function (): void {
    $response = $this->employeeQueries->mobileNumberExist('aaaa', $this->companyA->id);
    $this->assertFalse($response);

    $response = $this->employeeQueries->mobileNumberExist($this->employeeA->mobile_number, $this->companyA->id);
    $this->assertTrue($response);
});

test('emailExist method returns boolean as expected', function (): void {
    $response = $this->employeeQueries->emailExist('bbbb', $this->companyA->id);
    $this->assertFalse($response);

    $response = $this->employeeQueries->emailExist($this->employeeA->email, $this->companyA->id);
    $this->assertTrue($response);
});

test('searchEmployeesForFilter can search employee', function (): void {
    $response = $this->employeeQueries->searchEmployeesForFilter($this->employeeA->first_name, $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('first_name', $this->employeeA->first_name);
});

test('employeeExistsById method returns boolean as expected', function (): void {
    $response = $this->employeeQueries->employeeExistsById($this->companyA->id, $this->employeeB->id);
    $this->assertFalse($response);

    $response = $this->employeeQueries->employeeExistsById($this->companyA->id, $this->employeeA->id);
    $this->assertTrue($response);
});

test('A promoter app can be updated profile', function (): void {
    $employee = Employee::factory()->create();
    $promoter = Promoter::factory()->create();

    $this->employeeQueries->updateProfile(new PromoterApplicationData(
        username: $promoter->username,
        first_name: $employee->first_name,
        last_name: $employee->last_name,
        email: $employee->email,
        mobile_number: $employee->mobile_number,
        home_contact: $employee->home_contact,
        address_line_1: $employee->address_line_1,
        address_line_2: $employee->address_line_2,
        city: $employee->city,
        area_code: $employee->area_code,
        primary_contact_name: $employee->primary_contact_name,
        primary_contact_phone: $employee->primary_contact_phone,
        photo: null,
    ), $employee->id);

    $this->assertDatabaseHas('employees', [
        'first_name' => $employee->first_name,
        'last_name' => $employee->last_name,
        'email' => $employee->email,
    ]);
});

test('emailTakenByAnotherEmployee method returns boolean as expected', function (): void {
    $response = $this->employeeQueries->emailTakenByAnotherEmployee(
        $this->employeeA->email,
        $this->companyA->id,
        $this->employeeA->mobile_number
    );
    $this->assertFalse($response);

    $response = $this->employeeQueries->emailTakenByAnotherEmployee(
        $this->employeeA->email,
        $this->companyA->id,
        $this->employeeB->mobile_number
    );
    $this->assertTrue($response);
});

test(
    'getEmployeeForBulkUpdate method return the list',
    function (): void {
        $response = $this->employeeQueries->getEmployeeForBulkUpdate($this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->employeeA->first_name)
            ->toHaveKey('email', $this->employeeA->email);
    }
);

test('updateByMobileNumber method update the employee data', function (): void {
    $employeeRecord = Employee::factory()->make()->toArray();

    $this->employeeQueries->updateByMobileNumber($employeeRecord, $this->employeeA->mobile_number, $this->companyA->id);

    $this->assertDatabaseHas('employees', $employeeRecord);
});

test('A store manager app can update profile', function (): void {
    $employee = Employee::factory()->create();

    $this->employeeQueries->updateProfile(new StoreManagerApplicationData(
        username: $employee->first_name,
        first_name: $employee->first_name,
        last_name: $employee->last_name,
        email: $employee->email,
        mobile_number: $employee->mobile_number,
        home_contact: $employee->home_contact,
        address_line_1: $employee->address_line_1,
        address_line_2: $employee->address_line_2,
        city: $employee->city,
        area_code: $employee->area_code,
        primary_contact_name: $employee->primary_contact_name,
        primary_contact_phone: $employee->primary_contact_phone,
        photo: null,
    ), $employee->id);

    $this->assertDatabaseHas('employees', [
        'first_name' => $employee->first_name,
        'last_name' => $employee->last_name,
        'email' => $employee->email,
    ]);
});

test('getPaginatedListForStoreManagerApp method returns paginated employees by company', function (): void {
    $filteredData = [
        'per_page' => 1,
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
    ];

    $response = $this->employeeQueries->getPaginatedListForStoreManagerApp($filteredData, $this->companyA->id);

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->employeeA->id)
        ->toHaveKey('first_name', $this->employeeA->first_name);
});

test('updatePointsAndTotalSales method update member data', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyA->id,
        'total_earned_points' => 100,
        'total_expired_points' => 100,
        'total_redeemed_points' => 150,
        'total_sales' => 200,
    ]);

    $this->assertDatabaseHas('employees', [
        'total_earned_points' => 100,
        'total_redeemed_points' => 150,
        'total_sales' => 200,
    ]);

    $this->employeeQueries->updatePointsAndTotalSales($employee, 200, 300, 400);

    $this->assertDatabaseHas('employees', [
        'total_earned_points' => 200,
        'total_redeemed_points' => 300,
        'total_sales' => 400,
    ]);
});

test(
    'getLoyaltyPointsById method returns the as expected',
    function (): void {
        $response = $this->employeeQueries->getLoyaltyPointsById($this->employeeA->id);

        expect($response->toArray())
            ->toHaveKey('loyalty_points', $this->employeeA->loyalty_points);
    }
);

test('decreaseExpiredLoyaltyPoints method decrease the employee loyalty points as expected', function (): void {
    $this->employeeA->loyalty_points = 10;
    $this->employeeA->total_expired_points = 10;
    $this->employeeA->save();

    $this->employeeQueries->decreaseExpiredLoyaltyPoints($this->employeeA, 5);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'loyalty_points' => 5,
        'total_expired_points' => 15,
    ]);
});

test('statusChange method is change the employee status', function (): void {
    $this->employeeQueries->statusChange($this->employeeA, true);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employeeA->id,
        'status' => true,
    ]);
});

test('Get Employee name for export PDF headers', function (): void {
    $response = $this->employeeQueries->getEmployeeNameForFilter($this->employeeA->id);

    $this->assertIsString($response);
});
