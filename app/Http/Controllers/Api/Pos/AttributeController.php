<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Cashier;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;
        /** @var Cashier $cashier */
        $cashier = $request->user();
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $attributeQueries = resolve(AttributeQueries::class);
        $attributes = $attributeQueries->getByCompanyId($companyId, $afterUpdatedAt);

        $attributes = $attributes->map(fn (Attribute $attribute): array => [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'options' => collect($attribute->options)->map(fn ($option): array => [
                'id' => $option,
                'name' => $option,
            ])->values(),
        ])->values();

        return [
            'attributes' => $attributes,
        ];
    }
}
