<?php

declare(strict_types=1);

namespace App\Domains\Integration\DataObjects;

use App\Domains\Integration\Enums\IntegrationConnections;
use Spatie\LaravelData\Data;

class IntegrationData extends Data
{
    public function __construct(
        public string $name,
        public int $company_id,
        public int $connection_type,
        public string $url,
        public string $secret,
        public array $webhook_urls,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'company_id' => ['required', 'integer'],
            'connection_type' => ['required', 'integer'],
            'url' => ['required', 'string', 'url'],
            'secret' => ['required', 'string'],
            'webhook_urls' => [
                'required_if:connection_type,' . IntegrationConnections::RETAIL_PLANNING->value,
                'array',
            ],
            'webhook_urls.*.webhook_url_type_id' => ['required', 'integer'],
            'webhook_urls.*.url' => ['required', 'string', 'url'],
        ];
    }
}
