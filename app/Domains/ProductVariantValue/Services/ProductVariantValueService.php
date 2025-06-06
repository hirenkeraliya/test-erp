<?php

declare(strict_types=1);

namespace App\Domains\ProductVariantValue\Services;

use App\Domains\Color\ColorQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Template\TemplateQueries;
use App\Models\Product;
use App\Models\Template;

class ProductVariantValueService
{
    public function createColorSizeStyleProductVariantValues(Product $product): Template
    {
        $product->productVariantValues()->delete();

        $templateQueries = resolve(TemplateQueries::class);
        $defaultTemplate = $templateQueries->createDefaultTemplateAndAttributes($product->company_id);

        $colorName = 'NO COLOR';
        if ($product->color_id) {
            $colorQueries = resolve(ColorQueries::class);
            $color = $colorQueries->getByOnlyId($product->color_id);
            $colorName = $color->name ?? 'NO COLOR';
        }

        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeColor = $defaultTemplate->attributes->firstWhere('name', 'Color');
        if ($attributeColor) {
            $productVariantValueQueries->addNew($product->id, (int) $attributeColor->id, (string) $colorName);

            $options = $attributeColor->options ?? [];
            if (! in_array($colorName, $options, true)) {
                $options[] = $colorName;
                $attributeColor->options = $options;
                $attributeColor->save();
            }
        }

        $sizeName = 'NO SIZE';
        if ($product->size_id) {
            $sizeQueries = resolve(SizeQueries::class);
            $size = $sizeQueries->getByOnlyId($product->size_id);
            $sizeName = $size->name ?? 'NO SIZE';
        }

        $attributeSize = $defaultTemplate->attributes->firstWhere('name', 'Size');
        if ($attributeSize) {
            $productVariantValueQueries->addNew($product->id, (int) $attributeSize->id, (string) $sizeName);

            $options = $attributeSize->options ?? [];
            if (! in_array($sizeName, $options, true)) {
                $options[] = $sizeName;
                $attributeSize->options = $options;
                $attributeSize->save();
            }
        }

        $styleName = 'NO STYLE';
        if ($product->style_id) {
            $styleQueries = resolve(StyleQueries::class);
            $style = $styleQueries->getByOnlyId($product->style_id);
            $styleName = $style->name ?? 'NO STYLE';
        }

        $attributeStyle = $defaultTemplate->attributes->firstWhere('name', 'Style');
        if ($attributeStyle) {
            $productVariantValueQueries->addNew($product->id, (int) $attributeStyle->id, (string) $styleName);

            $options = $attributeStyle->options ?? [];
            if (! in_array($styleName, $options, true)) {
                $options[] = $styleName;
                $attributeStyle->options = $options;
                $attributeStyle->save();
            }
        }

        return $defaultTemplate;
    }
}
