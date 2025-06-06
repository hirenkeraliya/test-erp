<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\DataObjects;

use App\Domains\Common\Enums\PriceOverrideTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class StoreManagerData extends Data
{
    public function __construct(
        public int $employee_id,
        public string $username,
        public ?string $password,
        public ?string $passcode,
        public int $price_override_type,
        public ?float $price_override_limit_percentage_for_item,
        public ?float $price_override_limit_percentage_for_cart,
        public bool $can_manage_wholesale,
        public array $location_ids,
        public array $role_ids,
        public ?array $brand_ids,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $storeManagerId = null;
        if ('admin.store_managers.update' === $request->route()?->getName()) {
            /** @var string $storeManagerId */
            $storeManagerId = $request->route()->parameter('storeManagerId');
        }

        $rules = [
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
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'brand_ids' => ['sometimes', 'array'],
            'brand_ids.*' => ['required', 'integer'],
            'price_override_type' => ['required', 'integer', 'in:' . PriceOverrideTypes::getValues()],
            'price_override_limit_percentage_for_item' => [
                'required_if:price_override_type,' . PriceOverrideTypes::PERCENTAGE->value,
                'nullable',
                'numeric',
                'between:0,100.00',
            ],
            'price_override_limit_percentage_for_cart' => ['sometimes', 'numeric', 'between:0,100.00'],
            'can_manage_wholesale' => ['required', 'boolean'],
            'role_ids' => ['required', 'array'],
            'two_factor_secret' => ['sometimes', 'string', 'nullable'],
            'two_factor_recovery_codes' => ['sometimes', 'string', 'nullable'],
        ];

        if ('admin.store_managers.store' === $request->route()?->getName()) {
            $rules['password'] = ['required', 'confirmed', 'string', 'max:20', Password::defaults()];
            $rules['passcode'] = ['required', 'string', 'min:4', 'max:10'];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password' => 'A password must be at least 8 characters long and include a combination of uppercase and lowercase letters, numbers, and symbols.',
            'password.confirmed' => 'The confirmed password does not match the original password. Please re-enter your password and confirm it.',
        ];
    }
}
