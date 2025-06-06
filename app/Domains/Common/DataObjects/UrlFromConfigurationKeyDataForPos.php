<?php

declare(strict_types=1);

namespace App\Domains\Common\DataObjects;

use Spatie\LaravelData\Data;

class UrlFromConfigurationKeyDataForPos extends Data
{
    public function __construct(
        public string $configuration_key,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'configuration_key' => ['required', 'string'],
        ];
    }
}
