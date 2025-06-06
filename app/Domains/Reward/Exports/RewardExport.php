<?php

declare(strict_types=1);

namespace App\Domains\Reward\Exports;

use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Models\Reward;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RewardExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $rewards
    ) {
    }

    public function collection(): Collection
    {
        return $this->rewards->map(function (Reward $reward): array {
            $targetType = $reward->target_type ? RewardTargetTypes::getFormattedCaseName($reward->type) : '';

            return [
                'title' => $reward->title,
                'type' => RewardTypes::getFormattedCaseName($reward->type),
                'target_type' => $targetType,
                'minimum_point' => $reward->minimum_point,
                'maximum_point' => $reward->maximum_point,
                'status' => $reward->status ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return ['Title', 'Type', 'Target Type', 'Minimum Point', 'Maximum Point', 'Status'];
    }
}
