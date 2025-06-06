<?php

declare(strict_types=1);

namespace App\Domains\Director\DataObjects;

use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class ChangePasscodeData extends Data
{
    public function __construct(
        public string $new_passcode
    ) {
    }

    /**
     * @return array<string, array<(Unique|string)>>
     */
    public static function rules(): array
    {
        return [
            'new_passcode' => ['required', 'string', 'confirmed', 'min:4', 'max:10'],
        ];
    }
}
