<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasureDerivative\DataObjects;

use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class UnitOfMeasureDerivativeData extends Data
{
    public function __construct(
        public string $name,
        public float $ratio,
    ) {
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    public static function rules(Request $request): array
    {
        /** @var ?int $unitOfMeasureId */
        $unitOfMeasureId = $request->route()?->parameter('unitOfMeasureId');

        $decimalQty = false;
        if ($unitOfMeasureId) {
            $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
            $allowDecimalQty = $unitOfMeasureQueries->getAllowDecimalQty(
                (int) $unitOfMeasureId,
                session('admin_company_id')
            );

            $decimalQty = (bool) $allowDecimalQty?->allow_decimal_qty;
        }

        $derivativeId = null;
        $unitOfMeasureDerivativeQueries = new UnitOfMeasureDerivativeQueries();

        if ('admin.unit_of_measure_derivatives.update' === $request->route()?->getName()) {
            $derivativeId = $request->route()->parameter('derivativeId');
        }

        $validationRules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measure_derivatives', 'name')->ignore($derivativeId)
                    ->where(
                        $unitOfMeasureDerivativeQueries->filterByUnitOfMeasure(
                            (int) $request->route()?->originalParameter('unitOfMeasureId')
                        )
                    ),
            ],
            'ratio' => ['required', 'integer', 'min:1', 'max:9999999'],
        ];

        if ($decimalQty) {
            $validationRules['ratio'] = ['required', 'numeric', 'min:0.01', 'max:9999999.99'];
        }

        return $validationRules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'ratio.max' => 'Ratio should not be more than 9 digits.',
        ];
    }
}
