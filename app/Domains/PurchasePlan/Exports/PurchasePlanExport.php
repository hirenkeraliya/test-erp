<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Exports;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Models\Location;
use App\Models\PurchasePlan;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchasePlanExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $purchasePlans,
    ) {
    }

    public function collection(): Collection
    {
        return $this->purchasePlans->map(function (PurchasePlan $purchasePlan): array {
            /** @var string $createdAt */
            $createdAt = $purchasePlan->created_at;

            /** @var Carbon $createdAtFormat */
            $createdAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $createdAt);

            /** @var Vendor $vendor */
            $vendor = $purchasePlan->vendor;

            return [
                'created_at' => $createdAtFormat->format('d-m-y H:i:s A'),
                'from' => $vendor->name,
                'to' => $this->getToLocation($purchasePlan),
                'plan_number' => $purchasePlan->plan_number,
                'reference_number' => $purchasePlan->reference_number,
                'total_amount' => $purchasePlan->total_amount ?? 0.0,
                'remarks' => $purchasePlan->remarks,
                'status' => Statuses::getFormattedCaseName($purchasePlan->status),
            ];
        });
    }

    public function headings(): array
    {
        return ['Date', 'From', 'To', 'Plan Number', 'Reference Number', 'Total Amount', 'Remarks', 'Status'];
    }

    public function getToLocation(PurchasePlan $purchasePlan): string
    {
        /** @var Location $location */
        $location = $purchasePlan->location;

        $locationType = LocationTypes::getFormattedCaseName($location->type_id);

        return $location->name . ' (' . $locationType . ')';
    }
}
