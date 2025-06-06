<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Domains\Color\ColorQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\Enums\Statuses;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Template\TemplateQueries;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\Template;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductCreateMasterProductJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $startId,
        private readonly int $endId
    ) {
    }

    public function handle(): void
    {
        $templateQueries = resolve(TemplateQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $products = Product::query()
            ->with(['media', 'categories', 'tags', 'attachedTemplates'])
            ->where('id', '>=', $this->startId)
            ->where('id', '<=', $this->endId)
            ->get();

        try {
            foreach ($products as $product) {
                $categoryIds = $product->categories->pluck('id')->toArray();
                $tagIds = $product->tags->pluck('id')->toArray();

                $defaultTemplate = $templateQueries->getDefaultTemplateWithAttribute($product->company_id);

                $thumbnailUrl = $product->getDiskBasedFirstMediaUrl('thumbnail');
                $images = $product->getDiskBasedMediaUrls('images');
                $videos = $product->getDiskBasedMediaUrls('videos');

                if ($product->master_product_id) {
                    $masterProduct = $masterProductQueries->getById($product->master_product_id, $product->company_id);
                } else {
                    $masterProduct = $product->article_number && trim(
                        $product->article_number
                    ) !== '' ? $masterProductQueries->getByArticleNumberAndCompanyId(
                        $product->article_number,
                        $product->company_id
                    ) : null;
                }

                $masterProductData = [
                    'company_id' => $product->company_id,
                    'brand_id' => $product->brand_id,
                    'variant_template_id' => $defaultTemplate?->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'description' => $product->description,
                    'department_id' => $product->department_id,
                    'vendor_id' => $product->vendor_id,
                    'unit_of_measure_id' => $product->unit_of_measure_id,
                    'article_number' => $product->article_number,
                    'type_id' => $product->type_id,
                    'has_batch' => $product->has_batch,
                    'is_non_inventory' => $product->is_non_inventory,
                    'is_non_selling_item' => $product->is_non_selling_item,
                    'created_by_id' => $product->created_by_id,
                    'created_by_type' => $product->created_by_type,
                    'status' => Statuses::ACTIVE->value,
                ];

                if (! $masterProduct instanceof MasterProduct) {
                    $masterProduct = MasterProduct::create($masterProductData);

                    $this->updateCategories($masterProduct, $categoryIds);
                    $this->updateTags($masterProduct, (array) $tagIds);
                    $this->uploadPhoto($masterProduct, $images);
                    $this->uploadVideo($masterProduct, $videos);
                    $this->uploadOtherImages($masterProduct, $thumbnailUrl);
                    $this->updateProductVariantValueByAttribute($product, $defaultTemplate);

                    $product->master_product_id = $masterProduct->id ?? null;
                    $product->save();
                }
            }
        } catch (Exception) {
        }
    }

    private function updateCategories(MasterProduct $masterProduct, array $categoryIds): void
    {
        $masterProduct->categories()->detach();
        $categoryIds = collect($categoryIds)->unique();
        foreach ($categoryIds as $key => $categoryId) {
            if ($categoryId) {
                $masterProduct->categories()->attach([
                    $categoryId => [
                        'sort_order' => $key,
                    ],
                ]);
            }
        }
    }

    private function updateTags(MasterProduct $masterProduct, ?array $tagIds): void
    {
        if (null !== $tagIds) {
            $masterProduct->tags()->sync($tagIds);
        }
    }

    private function uploadPhoto(MasterProduct $masterProduct, array $urls = []): void
    {
        foreach ($urls as $url) {
            $masterProduct->addMediaFromUrl($url)->toMediaCollection('images');
        }
    }

    private function uploadVideo(MasterProduct $masterProduct, array $urls = []): void
    {
        foreach ($urls as $url) {
            $masterProduct->addMediaFromUrl($url)->toMediaCollection('videos');
        }
    }

    private function uploadOtherImages(MasterProduct $masterProduct, string $url = ''): void
    {
        if ('' !== $url) {
            $masterProduct->addMediaFromUrl($url)->toMediaCollection('thumbnail');
        }
    }

    private function updateProductVariantValueByAttribute(
        Product $productVariant,
        ?Template $defaultTemplate
    ): void {
        $productVariant->productVariantValues()->delete();

        if (! $defaultTemplate instanceof Template) {
            return;
        }

        $colorName = 'NO COLOR';
        if ($productVariant->color_id) {
            $colorQueries = resolve(ColorQueries::class);
            $color = $colorQueries->getByOnlyId($productVariant->color_id);
            $colorName = $color->name ?? 'NO COLOR';
        }

        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeColor = $defaultTemplate->attributes->firstWhere('name', 'Color');
        if ($attributeColor) {
            $productVariantValueQueries->addNew(
                $productVariant->id,
                (int) $attributeColor->id,
                (string) $colorName,
            );
        }

        $sizeName = 'NO SIZE';
        if ($productVariant->size_id) {
            $sizeQueries = resolve(SizeQueries::class);
            $size = $sizeQueries->getByOnlyId($productVariant->size_id);
            $sizeName = $size->name ?? 'NO COLOR';
        }

        $attributeSize = $defaultTemplate->attributes->firstWhere('name', 'Size');
        if ($attributeSize) {
            $productVariantValueQueries->addNew($productVariant->id, (int) $attributeSize->id, (string) $sizeName);
        }
    }
}
