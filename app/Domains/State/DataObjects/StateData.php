<?php

declare(strict_types=1);

namespace App\Domains\State\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class StateData extends Data
{
    public function __construct(
        public int $country_id,
        public string $name,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $stateId = null;

        if ('admin.states.update' === $request->route()?->getName()) {
            $stateId = $request->route()->parameter('stateId');
        }

        return [
            'country_id' => ['integer', Rule::exists('countries', 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique('states', 'name')->ignore($stateId)],
        ];
    }
}
