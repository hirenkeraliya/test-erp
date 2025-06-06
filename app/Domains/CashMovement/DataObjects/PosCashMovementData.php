<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\DataObjects;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\Common\Enums\AuthorizerTypes;
use Illuminate\Validation\Rules\Exists;
use Spatie\LaravelData\Data;

class PosCashMovementData extends Data
{
    public function __construct(
        public ?string $offline_id,
        public ?string $happened_at,
        public int $cash_movement_type_id,
        public ?int $cash_movement_reason_id,
        public ?string $other_reason,
        public ?string $remarks,
        public int $authorizer_id,
        public string $authorizer_type,
        public float $amount,
        public ?string $store_manager_authorization_code = null,
    ) {
    }

    /**
     * @return array<string, array<(Exists|string)>>
     */
    public static function rules(): array
    {
        return [
            'offline_id' => ['sometimes', 'string', 'unique:cash_movements'],
            'happened_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'cash_movement_reason_id' => ['sometimes', 'nullable', 'required_if:other_reason,null', 'integer'],
            'cash_movement_type_id' => ['required', 'integer', 'in:' . CashMovementTypes::getValues()],
            'authorizer_id' => ['required', 'integer'],
            'authorizer_type' => ['required', 'string', 'in:' . AuthorizerTypes::getValues()],
            'other_reason' => ['sometimes', 'nullable', 'required_if:cash_movement_reason_id,null', 'string'],
            'remarks' => ['sometimes', 'nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
