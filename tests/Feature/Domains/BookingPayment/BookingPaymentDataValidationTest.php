<?php

declare(strict_types=1);

use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('Booking Payment validations pass.', function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->paymentType = PaymentType::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Cash',
    ]);

    $designation = Designation::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'designation_id' => $designation->id,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
        'username' => 'test',
    ]);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn($this->company->id);
    });

    $request = new Request([
        'offline_id' => 'a123',
        'member_id' => $member->id,
        'amount' => 100,
        'remarks' => '',
        'bill_reference_number' => '',
        'payment_type_id' => $this->paymentType->id,
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => $storeManager->passcode,
        'promoter_ids' => [],
        'products' => [
            0 => [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ],
        ],
    ]);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $request->validate(BookingPaymentData::rules($request));
    $this->assertTrue(true);
});

test('Booking Payment throws an validation exception due to product quantity does not specified.', function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
    ]);

    $request = new Request([
        'offline_id' => 'a123',
        'member_id' => $this->member->id,
        'amount' => 100,
        'remarks' => '',
        'payment_type_id' => 1,
        'products' => [
            0 => [
                'product_id' => 1,
            ],
        ],
    ]);

    $cashier = Cashier::factory()->create();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $request->validate(BookingPaymentData::rules($request));
})->throws(ValidationException::class);
