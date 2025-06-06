<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;

class LocationController extends Controller
{
    public function __construct(
        protected LocationQueries $locationQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getByCompanyId(int $companyId): array
    {
        $locations = $this->locationQueries->getByCompanyIdAndTypeId($companyId, LocationTypes::STORE->value);

        $locations->transform(fn ($location): array => [
            'id' => $location->id,
            'name' => $location->name,
        ]);

        return [
            'data' => $locations,
        ];
    }
}
