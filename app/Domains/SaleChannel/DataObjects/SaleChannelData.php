<?php

declare(strict_types=1);

namespace App\Domains\SaleChannel\DataObjects;

use Spatie\LaravelData\Data;

class SaleChannelData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public int $company_id,
        public int $default_location_id,
        public int $type_id,
        public int $inventory_deduct_order_status,
        public string $url,
        public string $secret,
        public array $inventory_rollback_order_status,
        public array $webhook_urls,
        public bool $display_variants,
        public bool $display_dynamic_menus,
        public string $round_off_configuration,
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
            'company_id' => ['required', 'integer'],
            'default_location_id' => ['required', 'integer'],
            'type_id' => ['required', 'integer'],
            'inventory_deduct_order_status' => ['required', 'integer'],
            'url' => ['required', 'string', 'url'],
            'secret' => ['required', 'string'],
            'inventory_rollback_order_status' => ['required', 'array'],
            'inventory_rollback_order_status.*' => ['required', 'integer'],
            'webhook_urls' => ['required', 'array'],
            'webhook_urls.*.webhook_url_type_id' => ['required', 'integer'],
            'webhook_urls.*.url' => ['required', 'string', 'url'],
            'webhook_urls.*.variance_url' => ['sometimes', 'nullable', 'string', 'url'],
            'display_variants' => ['required', 'boolean'],
            'display_dynamic_menus' => ['required', 'boolean'],
            'round_off_configuration' => ['required', 'string'],
        ];
    }
}
