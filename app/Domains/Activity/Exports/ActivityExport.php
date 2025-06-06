<?php

declare(strict_types=1);

namespace App\Domains\Activity\Exports;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Common\Services\ExportService;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Member;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Activitylog\Models\Activity;

class ActivityExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $activities,
        protected int $moduleType,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->activities->map(function (Activity $activity): array {
            $employee = null;

            $name = '';

            /** @var Admin|WarehouseManager|StoreManager|Cashier|SuperAdmin|Member $causer */
            $causer = $activity->causer;

            if (! $causer instanceof SuperAdmin && ! $causer instanceof Member) {
                /** @var Employee $employee */
                $employee = $causer->employee;

                $name = $employee->getFullName() . '(' . $employee->staff_id . ')';
            }

            if ($causer instanceof SuperAdmin) {
                $name = $causer->name;
            }

            if ($causer instanceof Member) {
                $name = $causer->first_name . ' ' . $causer->last_name;
            }

            $causerType = 'N/A';
            if (null !== $activity->causer_type) {
                $causerType = CommonFunctions::stringTitleLowerCase($activity->causer_type);
            }

            $module = 'N/A';
            if ($this->moduleType === ModelMappingTypes::BASE_MODULES->value) {
                /* @phpstan-ignore-next-line */
                if (null !== $activity->parent_module_name) {
                    $module = CommonFunctions::stringTitleLowerCase((string) $activity->parent_module_name);
                }

                if (null === $activity->parent_module_name) {
                    $module = CommonFunctions::stringTitleLowerCase((string) $activity->subject_type);
                }
            }

            if ($this->moduleType === ModelMappingTypes::CHILD_MODULES->value) {
                $module = CommonFunctions::stringTitleLowerCase((string) $activity->subject_type);
            }

            /** @var Carbon $createdAt */
            $createdAt = $activity->created_at;

            $activityData = [
                'date' => $createdAt->format('d-m-Y H:i:s'),
                'module' => $module,
                'user' => $causerType . ' : ' . $name,
                'event' => $activity->event,
                /* @phpstan-ignore-next-line */
                'description' => $activity->notes,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($activityData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
