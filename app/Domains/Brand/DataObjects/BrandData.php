<?php

declare(strict_types=1);

namespace App\Domains\Brand\DataObjects;

use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class BrandData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
    ) {
    }

    /**
     * @return array<string, array<(Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        $brandId = null;
        if ('super_admin.brands.update_brand' === $request->route()?->getName()) {
            /** @var string $brandId */
            $brandId = $request->route()->parameter('brandId');
        }

        return [
            'name' => ['required', 'string', new Unique('brands', 'name', ignore: $brandId)],
            'code' => ['required', 'string', new Unique('brands', 'code', ignore: $brandId)],
        ];
    }
}
