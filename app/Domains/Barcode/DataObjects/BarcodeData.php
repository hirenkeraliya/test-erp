<?php

declare(strict_types=1);

namespace App\Domains\Barcode\DataObjects;

use App\Domains\Common\Enums\BarcodePrintModuleTypes;
use App\Domains\Common\Enums\BarcodePrintSizes;
use App\Domains\Common\Enums\BarcodePrintTypes;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class BarcodeData extends Data
{
    public function __construct(
        public ?array $print_items,
        public array $print_columns,
        public string $print_size,
        public string $product_price,
        public string $module_type,
        public ?string $reference_number,
        public ?int $selected_module_by,
        public ?string $remark,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $rules = [
            'print_items' => ['required_if:module_type,' . BarcodePrintTypes::MANUAL->value, 'array'],
            'print_items.*.product_id' => ['required', 'integer'],
            'print_items.*.quantity' => ['required', 'string', 'min:1'],

            'print_columns' => ['required', 'array'],

            'print_size' => ['required', 'in:' . BarcodePrintSizes::getValues()],
            'product_price' => ['required', 'string'],
            'module_type' => ['required', 'string', 'in:' . BarcodePrintTypes::getValues()],
            'remark' => ['nullable', 'string'],
        ];

        if ($request->module_type === BarcodePrintTypes::BY_MODULE->value) {
            $rules['reference_number'] = ['required_if:module_type,' . BarcodePrintTypes::BY_MODULE->value, 'string'];
            $rules['selected_module_by'] = [
                'required_if:module_type,' . BarcodePrintTypes::BY_MODULE->value,
                'in:' . BarcodePrintModuleTypes::getValues(),
            ];
        }

        return $rules;
    }
}
