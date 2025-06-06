<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Resources;

use App\CommonFunctions;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\Employee;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SaleTargetViewResource extends JsonResource
{
    public function __construct(
        $resource,
        protected string $currencySymbol
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleTarget = $this->resource;

        /** @var Collection $saleTargetTimeframes */
        $saleTargetTimeframes = $saleTarget->saleTargetTimeframes;

        /** @var Collection $saleTargetPromoters */
        $saleTargetPromoters = $saleTarget->promoters;

        /** @var Collection $locations */
        $locations = $saleTarget->locations;

        $promoters = $saleTargetPromoters->map(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => trim($employee->getFullName()),
            ];
        });

        return [
            'id' => $saleTarget->id,
            'name' => $saleTarget->name,
            'amount_type' => SaleTargetAmountTypes::getFormattedCaseName($saleTarget->amount_type),
            'target_type' => TargetType::getFormattedCaseName($saleTarget->target_type),
            'time_interval_type' => TimeIntervalType::getFormattedCaseName($saleTarget->time_interval_type),
            'time_interval_value' => $this->preparedData($saleTargetTimeframes, $saleTarget),
            'promoters' => $promoters->count() ? $promoters->implode('name', ', ') : 'N/A',
            'locations' => $locations->count() ? $locations->implode('name', ', ') : 'N/A',
        ];
    }

    public function preparedData(Collection $saleTargetTimeframes, SaleTarget $saleTarget): array|Collection
    {
        $firstSaleTargetTimeframe = $saleTargetTimeframes->sortBy('start_date')->first();

        if ($saleTarget->time_interval_type === TimeIntervalType::CUSTOM_PERIOD->value && $firstSaleTargetTimeframe) {
            return [
                [
                    'value' => $this->getAmount($saleTarget->amount_type, $saleTargetTimeframes->first()),
                    'range' => CommonFunctions::dateFormat(
                        $firstSaleTargetTimeframe->start_date,
                        'd-m-Y'
                    ). ' to ' . CommonFunctions::dateFormat($firstSaleTargetTimeframe->end_date, 'd-m-Y'),
                ],
            ];
        }

        $lastSaleTargetTimeframe = $saleTargetTimeframes->sortBy('start_date')->last();
        if (
            $saleTarget->time_interval_type === TimeIntervalType::DAILY->value
            && $firstSaleTargetTimeframe
            && $lastSaleTargetTimeframe
        ) {
            return [
                [
                    'value' => $this->getAmount($saleTarget->amount_type, $saleTargetTimeframes->first()),
                    'range' => CommonFunctions::dateFormat(
                        $firstSaleTargetTimeframe->start_date,
                        'd-m-Y'
                    ). ' to ' . CommonFunctions::dateFormat($lastSaleTargetTimeframe->end_date, 'd-m-Y'),
                ],
            ];
        }

        if ($saleTarget->time_interval_type === TimeIntervalType::MONTHLY->value) {
            return $saleTargetTimeframes->map(function ($saleTargetTimeframe) use ($saleTarget): array {
                $date = CommonFunctions::dateFormat($saleTargetTimeframe->start_date, 'F Y');

                return [
                    'value' => $this->getAmount($saleTarget->amount_type, $saleTargetTimeframe),
                    'range' => $date,
                ];
            });
        }

        if ($saleTarget->time_interval_type === TimeIntervalType::WEEKLY->value) {
            return $saleTargetTimeframes->map(fn ($saleTargetTimeframe): array => [
                'value' => $this->getAmount($saleTarget->amount_type, $saleTargetTimeframe),
                'range' => CommonFunctions::dateFormat(
                    $saleTargetTimeframe->start_date,
                    'd-m-Y'
                ) . ' to ' . CommonFunctions::dateFormat($saleTargetTimeframe->end_date, 'd-m-Y'),
            ]);
        }

        if ($saleTarget->time_interval_type === TimeIntervalType::YEARLY->value) {
            return [
                [
                    'value' => $this->getAmount($saleTarget->amount_type, $saleTargetTimeframes->first()),
                    'range' => $saleTargetTimeframes->first()->target_label . ' Year',
                ],
            ];
        }

        return [];
    }

    public function getAmount(int $amountType, SaleTargetTimeframe $saleTargetTimeframe): string
    {
        return $amountType === SaleTargetAmountTypes::AMOUNT->value ? $this->currencySymbol .CommonFunctions::numberFormatString(
            (float) $saleTargetTimeframe->amount,
            2
        ) : $saleTargetTimeframe->percentage.'%';
    }
}
