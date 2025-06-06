<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\State\StateQueries;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function __construct(
        protected StateQueries $stateQueries
    ) {
    }

    public function getStatesByCountryId(int $countryId): array
    {
        $states = $this->stateQueries->getByCountryId($countryId);
        $states = $states->map(fn ($state): array => [
            'id' => $state->id,
            'name' => $state->name,
        ]);

        return [
            'states' => $states,
        ];
    }
}
