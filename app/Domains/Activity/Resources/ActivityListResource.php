<?php

declare(strict_types=1);

namespace App\Domains\Activity\Resources;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Member;
use App\Models\SaleChannel;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

class ActivityListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $employee = null;

        /** @var Activity $activity */
        $activity = $this;

        $name = '';

        /** @var Admin|WarehouseManager|StoreManager|Cashier|SuperAdmin|Member|SaleChannel $causer */
        $causer = $activity->causer;

        if (! $causer instanceof SuperAdmin && ! $causer instanceof Member && ! $causer instanceof SaleChannel) {
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

        if ((int) $request->module_type === ModelMappingTypes::BASE_MODULES->value) {
            /* @phpstan-ignore-next-line */
            if (null !== $activity->parent_module_name) {
                $module = CommonFunctions::stringTitleLowerCase((string) $activity->parent_module_name);
            }

            if (null === $activity->parent_module_name) {
                $module = CommonFunctions::stringTitleLowerCase((string) $activity->subject_type);
            }
        }

        if ((int) $request->module_type === ModelMappingTypes::CHILD_MODULES->value) {
            $module = CommonFunctions::stringTitleLowerCase((string) $activity->subject_type);
        }

        /** @var Carbon $createdAt */
        $createdAt = $activity->created_at;

        return [
            'id' => $activity->id,
            'date' => $createdAt->format('d-m-Y H:i:s'),
            'module' => $module,
            'user' => $causerType . ' : ' . $name,
            'event' => $activity->event,
            /* @phpstan-ignore-next-line */
            'description' => $activity->notes,
        ];
    }
}
