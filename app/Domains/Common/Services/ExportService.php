<?php

declare(strict_types=1);

namespace App\Domains\Common\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExportService
{
    public function exportDataMapping(Collection $reportData, Collection $filteredColumns): Collection
    {
        return $reportData->map(
            fn (array $item) => collect($item)->filter(fn ($value, $key) => $filteredColumns->contains($key))
        );
    }

    public function getHeadings(Collection $filteredColumns): array
    {
        return $filteredColumns
            ->reject(fn ($column): bool => in_array($column, ['info', 'action', 'counter_information']))
            ->map(fn ($column) => Str::title(str_replace('_', ' ', $column)))->toArray();
    }

    public function exportData(array $reportData, Collection $filteredColumns): array
    {
        $excludedKeys = ['images', 'thumbnail_url', 'info', 'action', 'counter_information'];
        $filteredColumns = $filteredColumns->reject(fn ($column): bool => in_array($column, $excludedKeys));

        return array_replace(
            array_flip($filteredColumns->toArray()),
            array_filter($reportData, fn ($key) => $filteredColumns->contains($key), ARRAY_FILTER_USE_KEY)
        );
    }
}
