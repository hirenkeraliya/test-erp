<?php

declare(strict_types=1);

namespace App\Domains\PosAdvertisement\Exports;

use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use App\Models\PosAdvertisement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PosAdvertisementExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $posAdvertisements
    ) {
    }

    public function collection(): Collection
    {
        return $this->posAdvertisements->map(function (PosAdvertisement $posAdvertisement): array {
            /** @var Collection $locations */
            $locations = $posAdvertisement->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'name' => $posAdvertisement->name,
                'type' => $posAdvertisement->type_id ? PosAdvertisementTypes::getFormattedCaseName(
                    $posAdvertisement->type_id
                ) : 'N/A',
                'locations' => $locationNames,
                'photo_url' => $posAdvertisement->type_id === PosAdvertisementTypes::IMAGE->value ? $posAdvertisement->getDiskBasedFirstMediaUrl(
                    'photo'
                ) : null,
                'video_url' => $posAdvertisement->type_id === PosAdvertisementTypes::VIDEO->value ? $posAdvertisement->getDiskBasedFirstMediaUrl(
                    'video'
                ) : null,
                'status' => $posAdvertisement->status ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'Type', 'Locations', 'Photo Url', 'Video Url', 'Status'];
    }
}
