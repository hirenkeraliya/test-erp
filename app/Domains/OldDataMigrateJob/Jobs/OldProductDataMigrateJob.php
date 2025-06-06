<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Company;
use App\Models\Department;
use App\Models\Product;
use App\Models\Size;
use App\Models\Style;
use App\Models\UnitOfMeasure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OldProductDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $startId,
        private readonly int $endId,
    ) {
        $this->companyId = Company::query()->firstOrFail()->id;
    }

    public function handle(): void
    {
        $unitOfMeasures = UnitOfMeasure::query()
            ->where('company_id', $this->companyId)
            ->get();

        $departments = Department::query()
            ->where('company_id', $this->companyId)
            ->whereNotNUll('code')
            ->get();

        $colors = Color::query()
            ->where('company_id', $this->companyId)
            ->whereNotNUll('code')
            ->get();

        $sizes = Size::query()
            ->where('company_id', $this->companyId)
            ->whereNotNUll('code')
            ->get();

        $styles = Style::query()
            ->where('company_id', $this->companyId)
            ->whereNotNUll('code')
            ->get();

        $brands = Brand::query()
            ->get();

        $categories = Category::query()
            ->where('company_id', $this->companyId)
            ->whereNotNUll('code')
            ->get();

        $oldInventories = DB::connection('old_data_mysql')
            ->table('tblDistinctInventory')
            ->select('id', 'inventory_id', 'ColorCode', 'sizecode', 'DesignCode', 'Inventorycode')
            ->where('id', '>=', $this->startId)
            ->where('id', '<=', $this->endId)
            ->get();

        $oldProducts = DB::connection('old_data_mysql')
            ->table('tblinventory')
            ->select(
                'id',
                'InventoryCode',
                'Description',
                'RetailPrice',
                'AllowBatchStock',
                'AllowBatchStock',
                'CategoryCode',
                'UOM',
                'BrandCode',
                'DepartmentCode',
                'CatalogueNo',
                'CreateDate',
                'ModifyDate',
            )
            ->whereIntegerInRaw('id', $oldInventories->pluck('inventory_id')->filter()->unique()->toArray())
            ->get();

        foreach ($oldInventories as $oldInventory) {
            $oldProduct = $oldProducts->firstWhere('id', $oldInventory->inventory_id);

            $oldProductInventoryCode = trim((string) $oldProduct->InventoryCode) . '-' . $oldInventory->id;

            if ($this->existsByCode($oldProductInventoryCode)) {
                continue;
            }

            $brandId = $this->getBrandId($brands, $oldProduct);

            if (! $brandId) {
                continue;
            }

            $createDate = $oldProduct->CreateDate;
            if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                $createDate = now()->format('Y-m-d H:i:s');
            }

            $modifyDate = $oldProduct->ModifyDate;
            if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                $modifyDate = now()->format('Y-m-d H:i:s');
            }

            $productData = [
                'company_id' => $this->companyId,
                'brand_id' => $brandId,
                'name' => trim((string) $oldProduct->Description),
                'compound_product_name' => trim((string) $oldProduct->Description),
                'description' => trim((string) $oldProduct->Description),
                'unit_of_measure_id' => $this->getUnitOfMeasureId($unitOfMeasures, $oldProduct),
                'department_id' => $this->getDepartmentId($departments, $oldProduct),
                'retail_price' => trim((string) $oldProduct->RetailPrice),
                'has_batch' => $oldProduct->AllowBatchStock,
                'is_non_inventory' => $oldProduct->AllowBatchStock,
                'status' => Statuses::ACTIVE->value,
                'is_non_selling_item' => 0,
                'is_available_in_pos' => 1,
                'is_available_in_ecommerce' => 0,
                'is_temporarily_unavailable' => 0,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
                'article_number' => $oldProduct->CatalogueNo,
                'created_at' => $createDate,
                'updated_at' => $modifyDate,
                'upc' => $this->getNewUpc(),
                'code' => $oldProductInventoryCode,
                'color_id' => $this->getColorId($colors, $oldInventory),
                'size_id' => $this->getSizeId($sizes, $oldInventory),
                'style_id' => $this->getStyleId($styles, $oldInventory),
            ];

            DB::table('products')->insert($productData);

            $this->addProductCategory($productData['upc'], $oldProduct, $categories);
        }
    }

    public function existsByCode(string $code): bool
    {
        return Product::select('id')
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $this->companyId)
            ->exists();
    }

    public function addProductCategory(string $upc, object $oldProduct, Collection $categories): void
    {
        /* @phpstan-ignore-next-line */
        if (! $oldProduct->CategoryCode) {
            return;
        }

        $product = $this->getProductByUpc($upc);
        $category = $categories
            ->first(
                fn ($category): bool => strcasecmp($category->code, trim((string) $oldProduct->CategoryCode)) === 0
            );

        if (! $category) {
            return;
        }

        $product->categories()->attach([
            $category->id => [
                'sort_order' => 0,
            ],
        ]);
    }

    public function getProductByUpc(string $upc): Product
    {
        return Product::query()
                ->where('upc', $upc)
                ->firstOrFail();
    }

    public function getUnitOfMeasureId(Collection $unitOfMeasures, object $oldProduct): ?int
    {
        $oldUMO = DB::connection('old_data_mysql')
            ->table('tblinvuom')
            /* @phpstan-ignore-next-line */
            ->where('UOMCode', trim((string) $oldProduct->UOM))
            ->first();

        if (! $oldUMO) {
            Log::channel('old_data_migration')->error('old_data_migration', [
                /* @phpstan-ignore-next-line */
                'Umo not match in old data umo Code: ' . $oldProduct->UOM . ', trim code:' . trim(
                    /* @phpstan-ignore-next-line */
                    (string) $oldProduct->UOM
                    /* @phpstan-ignore-next-line */
                ) . ', Product Name: ' . $oldProduct->InventoryCode,
            ]);

            return null;
        }

        $unitOfMeasure = $unitOfMeasures
            ->first(
                fn ($unitOfMeasure): bool => strcasecmp(
                    trim((string) $unitOfMeasure->name),
                    /* @phpstan-ignore-next-line */
                    trim((string) $oldUMO->UOMDescription)
                ) === 0
            );

        if ($unitOfMeasure) {
            return $unitOfMeasure->id;
        }

        return UnitOfMeasure::create([
            'company_id' => $this->companyId,
            /* @phpstan-ignore-next-line */
            'name' => $oldUMO->UOMDescription,
        ])->id;
    }

    public function getBrandId(Collection $brands, object $oldProduct): ?int
    {
        $brand = $brands
        /* @phpstan-ignore-next-line */
            ->first(fn ($brand): bool => strcasecmp($brand->code, trim((string) $oldProduct->BrandCode)) === 0);

        if ($brand) {
            return $brand->id;
        }

        $brand = $brands
        /* @phpstan-ignore-next-line */
            ->first(fn ($brand): bool => strcasecmp($brand->name, trim((string) $oldProduct->BrandCode)) === 0);

        if ($brand) {
            return $brand->id;
        }

        $oldBrand = DB::connection('old_data_mysql')
            ->table('tblinvbrand')
            /* @phpstan-ignore-next-line */
            ->where('BrandCode', $oldProduct->BrandCode)
            ->first();

        if (! $oldBrand) {
            Log::channel('old_data_migration')->error('old_data_migration', [
                /* @phpstan-ignore-next-line */
                'Brand Not Match in old data Brand Code: ' . $oldProduct->BrandCode . ', trim:' . trim(
                    /* @phpstan-ignore-next-line */
                    (string) $oldProduct->BrandCode
                    /* @phpstan-ignore-next-line */
                ) . ', Product Name: ' . $oldProduct->InventoryCode,
            ]);

            return null;
        }

        $brand = $brands
        /* @phpstan-ignore-next-line */
            ->first(fn ($brand): bool => strcasecmp($brand->name, trim((string) $oldBrand->BrandName)) === 0);

        if ($brand) {
            return $brand->id;
        }

        return Brand::create([
            /* @phpstan-ignore-next-line */
            'name' => trim((string) $oldBrand->BrandName),
            /* @phpstan-ignore-next-line */
            'code' => trim((string) $oldBrand->BrandCode),
        ])->id;
    }

    public function getDepartmentId(Collection $departments, object $oldProduct): ?int
    {
        /* @phpstan-ignore-next-line */
        if (! $oldProduct->DepartmentCode) {
            return null;
        }

        $department = $departments
            ->first(
                fn ($department): bool => strcasecmp(
                    $department->code,
                    trim((string) $oldProduct->DepartmentCode)
                ) === 0
            );

        if ($department) {
            return $department->id;
        }

        Log::channel('old_data_migration')->error('old_data_migration', [
            'Department Not Match in old data department Code: ' . $oldProduct->DepartmentCode . ', trim Code: ' . trim(
                (string) $oldProduct->DepartmentCode
                /* @phpstan-ignore-next-line */
            ) . ', Product Name: ' . $oldProduct->InventoryCode,
        ]);

        return null;
    }

    public function getColorId(Collection $colors, object $oldInventory): ?int
    {
        /* @phpstan-ignore-next-line */
        if (! $oldInventory->ColorCode) {
            return null;
        }

        $color = $colors
            ->first(fn ($color): bool => strcasecmp($color->code, trim((string) $oldInventory->ColorCode)) === 0);

        if ($color) {
            return $color->id;
        }

        Log::channel('old_data_migration')->error('old_data_migration', [
            'color Not Match in old data Color Code: ' . $oldInventory->ColorCode . ', trim code:' . trim(
                (string) $oldInventory->ColorCode
                /* @phpstan-ignore-next-line */
            ) . ' Product Name: ' . $oldInventory->Inventorycode,
        ]);

        return null;
    }

    public function getSizeId(Collection $sizes, object $oldInventory): ?int
    {
        /* @phpstan-ignore-next-line */
        if (! $oldInventory->sizecode) {
            return null;
        }

        $size = $sizes
            ->first(fn ($size): bool => strcasecmp($size->code, trim((string) $oldInventory->sizecode)) === 0);

        if ($size) {
            return $size->id;
        }

        $size = Size::query()->where('code', trim((string) $oldInventory->sizecode))->first();

        if ($size) {
            return $size->id;
        }

        Log::channel('old_data_migration')->error('old_data_migration', [
            'Size Not Match in old data size Code: ' . $oldInventory->sizecode . ', trim code:' . trim(
                (string) $oldInventory->sizecode
                /* @phpstan-ignore-next-line */
            ) . ', Product Code: ' . $oldInventory->Inventorycode,
        ]);

        return null;
    }

    public function getStyleId(Collection $styles, object $oldInventory): ?int
    {
        /* @phpstan-ignore-next-line */
        if (! $oldInventory->DesignCode) {
            return null;
        }

        $style = $styles
            ->first(fn ($style): bool => strcasecmp($style->code, trim((string) $oldInventory->DesignCode)) === 0);

        if ($style) {
            return $style->id;
        }

        Log::channel('old_data_migration')->error('old_data_migration', [
            'Style Not Match in old data style Code: ' . $oldInventory->DesignCode . ', trim code:' . trim(
                (string) $oldInventory->DesignCode
                /* @phpstan-ignore-next-line */
            ) . ', Product Name: ' . $oldInventory->Inventorycode,
        ]);

        return null;
    }

    public function generateRandomLetters(int $length = 4): string
    {
        return strtoupper(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, $length));
    }

    public function generateRandomNumber(int $min = 11111, int $max = 99999): int
    {
        return random_int($min, $max);
    }

    public function generateUPC(): string
    {
        $randomLetters1 = $this->generateRandomLetters();
        $randomNumber = $this->generateRandomNumber();
        $randomLetters2 = $this->generateRandomLetters();

        return $randomLetters1 . $randomNumber . $randomLetters2;
    }

    public function getNewUpc(): string
    {
        $generatedUpc = $this->generateUPC();
        if ($this->existsByUpc($generatedUpc)) {
            return $this->getNewUpc();
        }

        return $generatedUpc;
    }

    public function existsByUpc(string $generatedUpc): bool
    {
        return Product::select('id')
            ->whereCaseSensitive('upc', $generatedUpc)
            ->where('company_id', $this->companyId)
            ->exists();
    }

    public function generateArticleNumber(): string
    {
        $randomLetters1 = $this->generateRandomLetters();
        $randomNumber = $this->generateRandomNumber();
        $randomLetters2 = $this->generateRandomLetters();

        return $randomLetters1 . $randomNumber . $randomLetters2;
    }

    public function getArticleNumber(): string
    {
        $generatedArticleNumber = $this->generateArticleNumber();
        if ($this->existsByArticleNumber($generatedArticleNumber)) {
            return $this->getArticleNumber();
        }

        return $generatedArticleNumber;
    }

    public function existsByArticleNumber(string $generatedArticleNumber): bool
    {
        return Product::select('id')
            ->whereCaseSensitive('article_number', $generatedArticleNumber)
            ->where('company_id', $this->companyId)
            ->exists();
    }
}
