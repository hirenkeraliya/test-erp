<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Http\Controllers\Controller;

class ExternalLocationController extends Controller
{
    public function __construct(
        protected ExternalLocationQueries $externalLocationQueries
    ) {
    }

    public function getExternalLocations(int $externalCompanyId): array
    {
        $externalLocations = $this->externalLocationQueries->getAll($externalCompanyId);

        return [
            'externalStores' => $externalLocations->where('type_id', LocationTypes::STORE->value)->values(),
            'externalWarehouses' => $externalLocations->where('type_id', LocationTypes::WAREHOUSE->value)->values(),
        ];
    }
}
