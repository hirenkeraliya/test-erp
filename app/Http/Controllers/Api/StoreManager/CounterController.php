<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\Resources\StoreManagerAppCounterResource;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CounterController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getCounters(Request $request): array
    {
        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        /** @var ?string $searchText */
        $searchText = $validatedData['search_text'] ?? null;

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $counters = $counterQueries->getCounterListOfSelectedLocation((int) $locationId, $companyId, $searchText);

        return [
            'counters' => StoreManagerAppCounterResource::collection($counters),
        ];
    }
}
