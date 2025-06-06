<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
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

    $this->promoter = Promoter::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    setCompanyIdInSession($this->companyId);
});

test(
    'same employee cannot be selected while adding a promoter.',
    function (): void {
        $promoterDetails = Promoter::factory()->make([
            'employee_id' => $this->employeeA->id,
            'monthly_sales_target' => 100,
        ])->toArray();

        $promoterDetails['location_ids'] = [$this->location->id];

        $request = new Request($promoterDetails);

        $request->validate(PromoterData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can select different employee while adding.',
    function (): void {
        $promoterDetails = Promoter::factory()->make([
            'employee_id' => $this->employeeB->id,
            'monthly_sales_target' => 100,
        ])->toArray();

        $promoterDetails['location_ids'] = [$this->location->id];

        $request = new Request($promoterDetails);

        $request->validate(PromoterData::rules($request));

        $this->assertTrue(true);
    }
);
