<?php

declare(strict_types=1);

use App\Domains\Sale\DataObjects\SaleData;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('Sales validations pass.', function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
    ]);

    $request = new Request([
        'offline_sale_id' => '1',
        'member_id' => $this->member->id,
        'items' => [
            '0' => [
                'id' => 1,
                'price' => '42.54',
                'quantity' => '1',
            ],
            '1' => [
                'id' => 2,
                'price' => '75.94',
                'quantity' => '1',
            ],
        ],
        'payments' => [
            0 => [
                'type_id' => 1,
                'amount' => '300',
                'currency_id' => 1,
                'current_currency_rate' => 1,
                'currency_amount' => 300,
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    SaleData::validate($request);
    $this->assertTrue(true);
});

test('Sales validations fails as expected.', function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
    ]);

    $request = new Request([
        'offline_sale_id' => null,
        'member_id' => $this->member->id,
        'items' => [
            '0' => [
                'id' => 1,
                'price' => '42.54',
                'quantity' => '1',
            ],
            '1' => [
                'id' => 2,
                'price' => '75.94',
                'quantity' => '1',
            ],
        ],
        'payments' => [
            0 => [
                'type_id' => 1,
                'amount' => '300',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    SaleData::validate($request);
})->throws(ValidationException::class);
