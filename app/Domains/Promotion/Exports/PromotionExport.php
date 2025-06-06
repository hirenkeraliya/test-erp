<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Exports;

use App\CommonFunctions;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromotionExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $promotions
    ) {
    }

    public function collection(): Collection
    {
        return $this->promotions->map(function (Promotion $promotion): array {
            $saleDiscount = $promotion->saleDiscountPromotion;
            $saleItemDiscount = $promotion->saleItemDiscountPromotion;

            $totalUsedCounts = $saleDiscount->count() + $saleItemDiscount->count();
            $totalDiscountAmount = CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            );

            return [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'promotion_applicable_type' => $promotion->promotion_applicable_type_id ? PromotionApplicableTypes::getFormattedCaseName(
                    $promotion->promotion_applicable_type_id
                ) : 'N/A',
                'timeframe_type' => PromotionTimeframeTypes::getFormattedCaseName($promotion->timeframe_type_id),
                'usage' => 'Count: '.$totalUsedCounts. ', Amount: ' .$totalDiscountAmount,
                'status' => $promotion->status ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return ['Id', 'Name', 'Promotion Applicable Type', 'Timeframe', 'Usage', 'Status'];
    }
}
