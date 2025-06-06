<?php

declare(strict_types=1);

namespace App\Domains\ExternalConnection\DataObjects;

use App\Rules\NoTrailingSlashUrls;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class ExternalConnectionData extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public ?int $create_by_super_admin_id = null,
        public ?int $approve_by_super_admin_id = null,
        public ?string $approved_at = null,
        public ?string $rejected_at = null,
        public ?string $token = null,
    ) {
    }

    public static function rules(Request $request): array
    {
        $externalConnectionId = null;
        if ('super_admin.external_connections.update' === $request->route()?->getName()) {
            $externalConnectionId = $request->route()->parameter('externalConnectionId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('external_connections', 'name')->ignore($externalConnectionId),
            ],
            'url' => [
                'required',
                'url',
                'max:255',
                new NoTrailingSlashUrls(),
                Rule::unique('external_connections', 'url')->ignore($externalConnectionId),
            ],
        ];
    }
}
