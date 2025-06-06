<?php

declare(strict_types=1);

use App\Domains\Director\DataObjects\DirectorData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->locationA = Location::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->employeeA = Employee::factory()->create([
        'first_name' => 'employee_one',
    ]);

    $this->employeeB = Employee::factory()->create([
        'first_name' => 'employee_Two',
        'company_id' => $this->companyId,
    ]);

    $this->director = Director::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    setCompanyIdInSession($this->companyId);
});

test(
    'same employee cannot be selected while adding a director.',
    function (): void {
        $directorDetails = Director::factory()->make([
            'employee_id' => $this->employeeA->id,
        ])->toArray();

        $directorDetails['location_ids'] = [$this->locationA->id];

        $request = new Request($directorDetails);

        $request->validate(DirectorData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can select different employee while adding.',
    function (): void {
        $directorDetails = Director::factory()->make([
            'employee_id' => $this->employeeB->id,
        ])->toArray();

        $directorDetails['location_ids'] = [$this->locationA->id];

        $request = new Request($directorDetails);

        $request->validate(DirectorData::rules($request));

        $this->assertTrue(true);
    }
);
