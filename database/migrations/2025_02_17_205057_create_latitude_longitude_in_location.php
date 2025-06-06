<?php

declare(strict_types=1);

use App\Models\Location;
use App\Services\GoogleGeocodingService;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $googleService = resolve(GoogleGeocodingService::class);

        $locations = Location::select(
            'id',
            'address_line_1',
            'address_line_2',
            'city_id',
            'state_id',
            'country_id',
            'area_code'
        )
            ->with(['city:id,name', 'state:id,name', 'country:id,name'])
            ->get();

        foreach ($locations as $location) {
            $address = $location->address_line_1 . ' ' . $location->address_line_2 . ' ' . $location->city?->name . ' ' . $location->state?->name . ' ' . $location->country?->name . ' ' . $location->area_code;
            $coordinates = $googleService->getCoordinates($address);

            if ($coordinates) {
                $location->latitude = $coordinates['lat'];
                $location->longitude = $coordinates['lng'];
                $location->save();
            }
        }
    }
};
