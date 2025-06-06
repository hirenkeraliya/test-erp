<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Exports;

use App\Domains\HappyHourDiscount\Resources\HappyHourDiscountListResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HappyHourDiscountExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $happyHourDiscounts
    ) {
    }

    public function collection(): Collection
    {
        $data = HappyHourDiscountListResource::collection($this->happyHourDiscounts);
        /** @var array $resourceData */
        $resourceData = $data->toArray(new Request());

        $records = collect($resourceData);

        return $records->transform(function (array $record): array {
            unset($record['id']);
            $record['offline_ids'] = implode(', ', $record['offline_ids']);
            $record['authorizer_names'] = implode(', ', $record['authorizer_names']);
            $record['happened_at_dates'] = implode(', ', $record['happened_at_dates']);

            return $record;
        });
    }

    public function headings(): array
    {
        return [
            'OfflineId',
            'Location',
            'Name',
            'Product Type',
            'New Price',
            'Start Date',
            'End Date',
            'Authorize Name',
            'Happened At',
        ];
    }
}
