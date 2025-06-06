<?php

declare(strict_types=1);

namespace App\Domains\Courier\DataObjects;

use App\Domains\Common\Enums\CourierWebhookUrls;
use App\Domains\Courier\Enums\CourierTypes;
use Spatie\LaravelData\Data;

class CourierData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public int $type_id,
        public string $url,
        public string $client_id,
        public string $client_secret,
        public array $webhook_urls
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'code' => ['required', 'string'],
            'type_id' => ['required', 'integer', 'in:' . CourierTypes::getValues()],
            'url' => ['required', 'string', 'url'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'webhook_urls' => ['required', 'array'],
            'webhook_urls.*.webhook_url_type_id' => [
                'required',
                'integer',
                'in:' . CourierWebhookUrls::getValues(),
            ],
            'webhook_urls.*.url' => ['required', 'string', 'url'],
            'webhook_urls.*.variance_url' => ['sometimes', 'nullable', 'string', 'url'],
        ];
    }
}
