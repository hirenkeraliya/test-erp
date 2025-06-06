<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PosAdmin;

use App\Domains\Counter\CounterQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    public function updateCounterByName(Request $request): void
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string'],
        ]);

        $counterQueries = resolve(CounterQueries::class);
        $counterQueries->updateByName([
            'app_version' => null,
            'app_version_updated_at' => null,
        ], $validatedData['name']);
    }
}
