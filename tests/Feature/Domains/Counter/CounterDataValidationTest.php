<?php

declare(strict_types=1);

use App\Domains\Counter\DataObjects\CounterData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
        'name' => 'CounterA',
    ]);

    setCompanyIdInSession($this->companyId);
});

test(
    'store wise unique name validation works while adding a counter.',
    function (): void {
        $counterDetails = Counter::factory()->make([
            'location_id' => $this->location->id,
            'name' => 'CounterA',
        ])->toArray();

        $request = new Request($counterDetails);

        $request->validate(CounterData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can add different counter name with same store.',
    function (): void {
        $counterDetails = Counter::factory()->make([
            'name' => 'ABC',
            'location_id' => $this->location->id,
        ])->toArray();

        $request = new Request($counterDetails, server: [
            'REQUEST_URI' => 'counters/' . $this->counter->id . '/update',
        ]);
        $request->setRouteResolver(
            fn (): Route => (new Route(
                'Post',
                'counters/{counterId}/update',
                [
                    'as' => 'admin.counters.update',
                    'uses' => [CounterController::class, 'update'],
                ]
            ))->bind($request)
        );

        $request->validate(CounterData::rules($request));
        $this->assertTrue(true);
    }
);

test(
    'user can add same counter name with different location.',
    function (): void {
        $locationA = Location::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'xyz',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counterDetails = Counter::factory()->make([
            'name' => 'CounterA',
            'location_id' => $locationA->id,
        ])->toArray();

        $request = new Request($counterDetails);
        $request->validate(CounterData::rules($request));
        $this->assertTrue(true);
    }
);

test(
    'the counter will not be added when the selected store is from a different company.',
    function (): void {
        $locationA = Location::factory()->create([
            'name' => 'xyz',
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counterDetails = Counter::factory()->make([
            'location_id' => $locationA->id,
            'name' => 'WWW',
        ])->toArray();
        $request = new Request($counterDetails);
        $request->validate(CounterData::rules($request));
    }
)->throws(ValidationException::class);
