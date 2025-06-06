<?php

declare(strict_types=1);

namespace App\Domains\Activity\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Common\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivityService
{
    public function activityDataPrint(Collection $activities, int $moduleType, Collection $filteredColumns): Collection
    {
        $activityData = $activities->transform(function ($activity) use ($moduleType): array {
            $user = '';
            $staffId = '';

            $causer = $activity->causer;

            if (null !== $causer) {
                $employee = $causer->employee ?? null;
                if (null !== $employee) {
                    $user = $employee->first_name;
                    $staffId = $employee->staff_id;
                }
            }

            $causerType = 'N/A';
            if (null !== $activity->causer_type) {
                $causerType = CommonFunctions::stringTitleLowerCase($activity->causer_type);
            }

            $module = 'N/A';

            if ($moduleType === ModelMappingTypes::BASE_MODULES->value) {
                if (null !== $activity->parent_module_name) {
                    $module = CommonFunctions::stringTitleLowerCase((string) $activity->parent_module_name);
                }

                if (null === $activity->parent_module_name) {
                    $module = CommonFunctions::stringTitleLowerCase((string) $activity->subject_type);
                }
            }

            if ($moduleType === ModelMappingTypes::CHILD_MODULES->value) {
                $module = CommonFunctions::stringTitleLowerCase((string) $activity->subject_type);
            }

            /** @var Carbon $createdAt */
            $createdAt = $activity->created_at;

            return [
                'date' => $createdAt->format('d-m-Y H:i:s'),
                'module' => $module,
                'user' => $causerType . ' : ' . $user . '(' . $staffId . ')',
                'event' => $activity->event,
                'description' => $activity->notes,
            ];
        });
        $exportService = resolve(ExportService::class);

        return $exportService->exportDataMapping($activityData, $filteredColumns);
    }
}
