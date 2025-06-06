<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Counter\CounterQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    public function __construct(
        protected CounterQueries $counterQueries
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getLocationCounters(): array
    {
        $counters = $this->counterQueries->getCounterListOfSelectedLocation(
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'counters' => $counters,
        ];
    }

    public function getSpecificLocationsCounters(Request $request): array
    {
        $validatedData = $request->validate([
            'location_ids' => ['required', 'array'],
        ]);

        $locationIds = $validatedData['location_ids'];

        $counters = $this->counterQueries->getCountersOfLocations(
            $locationIds,
            session('store_manager_selected_location_company_id')
        );

        return [
            'counters' => $counters,
        ];
    }
}
