<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Resources;

use App\Models\Employee;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTakeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockTake $stockTake */
        $stockTake = $this;

        /** @var StoreManager|WarehouseManager $requestedManager */
        $requestedManager = $stockTake->requestedBy;

        /** @var StoreManager|WarehouseManager|null $submittedManager */
        $submittedManager = $stockTake->submittedBy;

        /** @var Employee $requestedEmployee */
        $requestedEmployee = $requestedManager->employee;

        /** @var ?Employee $submittedEmployee */
        $submittedEmployee = null !== $submittedManager ? $submittedManager->employee : null;

        /** @var Location $location */
        $location = $stockTake->location;

        /** @var Carbon|string $submittedAt */
        $submittedAt = 'N/A';

        if ($stockTake->submitted_at) {
            /** @var Carbon $submittedAtFormat */
            $submittedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $stockTake->submitted_at);
            $submittedAt = $submittedAtFormat->format('d-m-Y h:i:s A');
        }

        $stockRecordDate = null;

        if ($stockTake->stock_record_date) {
            /** @var Carbon $stockRecordDateFormat */
            $stockRecordDateFormat = Carbon::createFromFormat('Y-m-d', $stockTake->stock_record_date);
            $stockRecordDate = $stockRecordDateFormat->format('d-m-Y');
        }

        return [
            'id' => $stockTake->id,
            'stock_record_date' => $stockRecordDate,
            'requested_manager' => $requestedEmployee->getFullName(),
            'location' => $location->name,
            'submitted_manager' => $submittedEmployee instanceof Employee ? $submittedEmployee->getFullName() : 'N/A',
            'submitted_at' => $submittedAt,
            'compare_stock_date' => $stockTake->getStockCompareDate(),
        ];
    }
}
