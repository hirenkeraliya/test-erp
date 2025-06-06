<?php

declare(strict_types=1);

namespace App\Domains\Azentio\DataObjects;

use App\Domains\Color\ColorQueries;
use App\Domains\Size\SizeQueries;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class AzentioItemData extends Data
{
    public function __construct(
        #[MapName('ITEM_CODE')]
        public string $itemCode,
        #[MapName('COMPANY_ID')]
        public int $companyId,
        #[MapName('BRAND_ID')]
        public int $brandId,
        #[MapName('ITEM_NAME')]
        public string $itemName,
        #[MapName('GRADE1_DEF_VAL')]
        public mixed $gradeCode1 = null,
        #[MapName('GRADE2_DEF_VAL')]
        public mixed $gradeCode2 = null,
    ) {
        if (null !== $this->gradeCode1) {
            /** @var ColorQueries $colorQueries */
            $colorQueries = resolve(ColorQueries::class);
            $this->gradeCode1 = $colorQueries->firstOrCreate($this->gradeCode1, $this->companyId)->getKey();
        }

        if (null !== $this->gradeCode2) {
            /** @var SizeQueries $sizeQueries */
            $sizeQueries = resolve(SizeQueries::class);
            $this->gradeCode2 = $sizeQueries->firstOrCreate($this->gradeCode2, $this->companyId)->getKey();
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'itemCode' => ['required', 'string'],
            'companyId' => ['required', 'integer'],
            'brandId' => ['required', 'integer'],
            'itemName' => ['required', 'string'],
            'gradeCode_1' => ['sometimes', 'nullable', 'string'],
            'gradeCode_2' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'upc' => $this->itemCode,
            'company_id' => $this->companyId,
            'brand_id' => $this->brandId,
            'name' => $this->itemName,
            'color_id' => $this->gradeCode1,
            'size_id' => $this->gradeCode2,
        ];
    }
}
