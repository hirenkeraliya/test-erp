<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\DataObjects;

use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class StoreManagerProfileData extends Data
{
    public function __construct(
        public int $employee_id,
        public string $username,
        public ?string $two_factor_secret,
        public ?string $two_factor_recovery_codes,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $storeManagerId = null;
        if ('store_manager.update_profile' === $request->route()?->getName()) {
            /** @var string $storeManagerId */
            $storeManagerId = $request->route()->parameter('storeManagerId');
        }

        return [
            'employee_id' => [
                'required',
                'integer',
                new Unique('store_managers', 'employee_id', ignore: $storeManagerId),
            ],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:255',
                new Unique('store_managers', 'username', ignore: $storeManagerId),
            ],
            'two_factor_secret' => ['sometimes', 'string', 'nullable'],
            'two_factor_recovery_codes' => ['sometimes', 'string', 'nullable'],
        ];
    }
}
