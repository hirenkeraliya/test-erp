<?php

declare(strict_types=1);

use App\Domains\DreamPrice\DataObjects\DreamPriceData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\DreamPrice;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->dreamPriceA = DreamPrice::factory()->create([
        'name' => 'dream_price_one',
        'company_id' => $this->companyId,
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    $this->dreamPriceB = DreamPrice::factory()->create([
        'name' => 'dream_price_two',
        'company_id' => $this->companyId,
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    setCompanyIdInSession($this->companyId);
});

test(
    'same name cannot be added while adding a dream price.',
    function (): void {
        $dreamPriceDetails = DreamPrice::factory()->make([
            'name' => $this->dreamPriceA->name,
            'start_date' => '2022-05-10',
            'end_date' => '2022-05-11',
            'allow_registered_member' => false,
            'allow_employee' => false,
            'allow_walk_in_member' => false,
            'member_group_ids' => null,
        ])->toArray();

        $dreamPriceDetails['location_ids'] = [$this->location->id];

        $request = new Request($dreamPriceDetails);

        $request->validate(DreamPriceData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can add different dream price name while adding.',
    function (): void {
        $dreamPriceDetails = DreamPrice::factory()->make([
            'name' => 'dream_price_three',
            'start_date' => '2022-05-10',
            'end_date' => '2022-05-11',
            'allow_registered_member' => false,
            'allow_employee' => false,
            'allow_walk_in_member' => false,
            'member_group_ids' => null,
        ])->toArray();

        $dreamPriceDetails['location_ids'] = [$this->location->id];

        $request = new Request($dreamPriceDetails);

        $request->validate(DreamPriceData::rules($request));

        $this->assertTrue(true);
    }
);

test(
    'user can add same dream price name for a different company.',
    function (): void {
        $this->companyBId = Company::factory()->create()->id;

        setCompanyIdInSession($this->companyBId);

        $dreamPriceDetails = DreamPrice::factory()->make([
            'name' => $this->dreamPriceA->name,
            'start_date' => '2022-05-10',
            'end_date' => '2022-05-11',
            'allow_registered_member' => false,
            'allow_employee' => false,
            'allow_walk_in_member' => false,
            'member_group_ids' => null,
        ])->toArray();

        $dreamPriceDetails['location_ids'] = [$this->location->id];

        $request = new Request($dreamPriceDetails);

        $request->validate(DreamPriceData::rules($request));

        $this->assertTrue(true);
    }
);
