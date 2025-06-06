<?php

declare(strict_types=1);

use App\Domains\Activity\ActivityLogQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->adminId = Admin::factory()->create([
        'employee_id' => $this->employee->id,
    ])->id;

    $this->activityLogQueries = new ActivityLogQueries();
});

test(
    'the getActivitiesForExport method returns activity as expected',
    function (): void {
        $activity = Activity::create([
            'log_name' => 'default',
            'subject_type' => ModelMapping::EMPLOYEE->name,
            'subject_id' => $this->employee->id,
            'causer_type' => ModelMapping::ADMIN->name,
            'causer_id' => $this->adminId,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 10,
            'date_range' => null,
            'employee_id' => null,
            'module_type' => ModelMappingTypes::BASE_MODULES->value,
        ];

        $response = $this->activityLogQueries->getActivitiesForExport($filterData, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $activity->id)
            ->toHaveKey('subject_type', $activity->subject_type)
            ->toHaveKey('causer_type', $activity->causer_type)
            ->toHaveKey('causer_id', $activity->causer_id);
    }
);
