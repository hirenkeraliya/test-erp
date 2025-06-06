<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Enums\ModelMapping;
use App\Http\Traits\DiskBasedFirstMediaUrl;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 */
class Model extends EloquentModel
{
    use LogsActivity;
    use CaseSensitiveConditionals;
    use DiskBasedFirstMediaUrl;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity): void
    {
        if ('testing' === app()->environment()) {
            return;
        }

        foreach (ModelMapping::getParentChildModules() as $parentModel => $childModules) {
            if (! is_array($childModules)) {
                continue;
            }

            if (in_array($activity->subject_type, $childModules)) {
                /* @phpstan-ignore-next-line */
                $activity->parent_module_name = $parentModel;

                if (method_exists($activity->subject, 'loadRelationAndGetReferenceNumber')) {
                    /* @phpstan-ignore-next-line */
                    $activity->notes = $activity->subject->loadRelationAndGetReferenceNumber();
                }
            }

            if ($activity->subject_type === $parentModel) {
                /* @phpstan-ignore-next-line */
                $activity->notes = $this->getNotesValue($activity);
            }
        }
    }

    private function getNotesValue(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject instanceof StockTransfer) {
            $subject->refresh();

            return implode('|', array_filter([
                $subject->transfer_order_number,
                $subject->request_order_number,
                $subject->transfer_in_number,
                $subject->transfer_out_number,
            ])
            );
        }

        if ($subject instanceof GoodsReceivedNote) {
            return $subject->grn_reference;
        }

        if ($subject instanceof Sale) {
            return $subject->offline_sale_id;
        }

        if ($subject instanceof SaleReturn) {
            return $subject->offline_sale_return_id;
        }

        return null;
    }
}
