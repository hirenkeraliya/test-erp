<?php

declare(strict_types=1);

namespace App\Domains\CashMovementReason\DataObjects;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class CashMovementReasonData extends Data
{
    public function __construct(
        public string $reason,
        public int $type_id
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $cashMovementReasonId = null;
        $cashMovementReasonQueries = new CashMovementReasonQueries();

        if ('admin.cash_movement_reasons.update' === $request->route()?->getName()) {
            $cashMovementReasonId = $request->route()->parameter('cashMovementReasonId');
        }

        return [
            'reason' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cash_movement_reasons', 'reason')->ignore($cashMovementReasonId)
                    ->where($cashMovementReasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'type_id' => ['required', 'integer', 'in:' . CashMovementTypes::getValues()],
        ];
    }
}
