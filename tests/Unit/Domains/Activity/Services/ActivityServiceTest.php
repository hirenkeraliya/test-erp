<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Activity\ActivityLogQueries;
use App\Domains\Activity\Services\ActivityService;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Models\Admin;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

test(
    'It calls activityDataPrint method and returns proper response',
    function (): void {
        $companyId = 1;
        $designationId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => fn (): int => $designationId,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $admin->employee = $employee;

        $this->activityLogQueries = new ActivityLogQueries();

        $activity = Activity::make([
            'id' => 1,
            'log_name' => 'default',
            'subject_type' => ModelMapping::EMPLOYEE->name,
            'subject_id' => $employee->id,
            'causer_type' => ModelMapping::ADMIN->name,
            'causer_id' => $admin->id,
            'created_at' => Carbon::now()->format('Y-m-d h:i:s'),
        ]);

        $activity->causer = $admin;

        $activityService = new ActivityService();
        $response = $activityService->activityDataPrint(
            collect([$activity]),
            ModelMappingTypes::BASE_MODULES->value,
            collect(['module', 'user', 'event', 'description', 'date'])
        );

        /** @var Carbon $createdAt */
        $createdAt = $activity->created_at;

        expect($response->first())
            ->toHaveKey('date', $createdAt->format('d-m-Y H:i:s'))
            ->toHaveKey('module', CommonFunctions::stringTitleLowerCase($activity->subject_type))
            ->toHaveKey(
                'user',
                Str::title(
                    str_replace('_', ' ', $activity->causer_type)
                ) . ' : ' . $employee->first_name . '(' . $employee->staff_id . ')'
            );
    }
);
