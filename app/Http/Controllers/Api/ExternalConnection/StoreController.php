<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StoreController extends Controller
{
    public function getStores(Request $request): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByCompanyIdAndTypeId((int) $request->company_id, LocationTypes::STORE->value);
    }
}
