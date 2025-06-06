<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Common;

use App\Domains\State\StateQueries;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function getAllStates(): array
    {
        $stateQueries = resolve(StateQueries::class);

        return [
            'states' => $stateQueries->getAllStates(),
        ];
    }
}
