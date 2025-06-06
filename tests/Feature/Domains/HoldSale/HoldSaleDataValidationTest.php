<?php

declare(strict_types=1);

use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
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
});

test('Hold Sales validations pass.', function (): void {
    $request = new Request([
        'offline_id' => '1',
        'member_id' => $this->member->id,
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
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
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    HoldSaleData::validate($request);
    $this->assertTrue(true);
});

test('Hold Sales validations fails as expected.', function (): void {
    $request = new Request([
        'offline_sale_id' => null,
        'member_id' => $this->member->id,
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
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
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    HoldSaleData::validate($request);
})->throws(ValidationException::class);
