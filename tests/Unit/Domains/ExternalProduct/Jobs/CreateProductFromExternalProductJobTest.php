<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Company;
use App\Models\Department;
use App\Models\ExternalProduct;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\Season;
use App\Models\Size;
use App\Models\Style;
use App\Models\Tag;
use App\Models\Template;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Facades\Config;

test(
    'PrepareExternalProductsJob Calls then prepare Supplier Catalog and create product when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $company = Company::factory()->make([
            'id' => 1,
            'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
            'default_country_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => true,
        ]);

        $tag = Tag::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
        ]);

        $category = Category::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
        ]);

        $department = Department::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
                'article_number' => '123456',
                'brand_id' => 1,
                'department_id' => 1,
                'unit_of_measure_id' => 1,
                'variant_template_id' => 1,
                'vendor_id' => null,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);

            $attributeSize = Attribute::factory()->make([
                'id' => 1,
                'name' => 'size',
                'company_id' => $company->id,
            ]);

            $attributeColor = Attribute::factory()->make([
                'id' => 2,
                'name' => 'color',
                'company_id' => $company->id,
            ]);

            $productVariantValue1 = ProductVariantValue::factory()->make([
                'id' => 1,
                'product_id' => $product->id,
                'attribute_id' => $attributeSize->id,
                'value' => 'sizeA',
            ]);

            $productVariantValue2 = ProductVariantValue::factory()->make([
                'id' => 2,
                'product_id' => $product->id,
                'attribute_id' => $attributeColor->id,
                'value' => 'colorA',
            ]);

            $productVariantValue1->attribute = $attributeSize;
            $productVariantValue2->attribute = $attributeColor;

            $masterProduct->tags = [$tag];

            $masterProduct->categories = [$category];

            $masterProduct->department = $department;

            $masterProduct->brand = Brand::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $masterProduct->unit_of_measure = UnitOfMeasure::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $masterProduct->variant_template = Template::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
                'is_variant' => true,
            ]);

            $product->master_product_id = $masterProduct->id;
            $product->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);
            $product->master_product = $masterProduct;
        } else {
            $product->color = Color::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $product->brand = Brand::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $product->size = Size::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $product->style = Style::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $product->season = Season::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $product->unit_of_measure = UnitOfMeasure::factory()->make([
                'id' => 1,
                'company_id' => $company->id,
            ]);

            $product->tags = [$tag];

            $product->categories = [$category];

            $product->department = $department;
        }

        $externalProduct = ExternalProduct::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'external_company_id' => 1,
            'approved_by_id' => $admin->id,
            'approved_by_type' => ModelMapping::ADMIN->name,
            'product_details' => $product->toArray(),
        ]);

        if ($productVariant) {
            $this->mock(ProductQueries::class, function ($mock): void {
                $mock->shouldReceive('addNewFromExternalProductForVariant')
                    ->once();
            });

            $this->mock(TemplateQueries::class, function ($mock): void {
                $mock->shouldReceive('getIdByNameAndCompanyId')
                    ->once();
            });
        } else {
            $this->mock(ProductQueries::class, function ($mock): void {
                $mock->shouldReceive('addNewFromExternalProduct')
                    ->once();
            });

            $this->mock(ColorQueries::class, function ($mock): void {
                $mock->shouldReceive('getIdByName')
                    ->once()
                    ->andReturn(1);
            });

            $this->mock(SizeQueries::class, function ($mock): void {
                $mock->shouldReceive('getIdByName')
                    ->once()
                    ->andReturn(1);
            });

            $this->mock(StyleQueries::class, function ($mock): void {
                $mock->shouldReceive('getIdByName')
                    ->once()
                    ->andReturn(1);
            });

            $this->mock(SeasonQueries::class, function ($mock): void {
                $mock->shouldReceive('getIdByName')
                    ->once()
                    ->andReturn(1);
            });
        }

        $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldReceive('firstOrCreateByName')
                ->once()
                ->andReturn(1);
        });

        $this->mock(DepartmentQueries::class, function ($mock): void {
            $mock->shouldReceive('getIdByName')
                ->once()
                ->andReturn(1);
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getIdByNameAndCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(TagQueries::class, function ($mock): void {
            $mock->shouldReceive('getIdByNameAndCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(UnitOfMeasureQueries::class, function ($mock): void {
            $mock->shouldReceive('getIdByNameAndCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(ExternalProductQueries::class, function ($mock) use ($externalProduct): void {
            $mock->shouldReceive('changeStatus')
                ->once();

            $mock->shouldReceive('getExternalProductByIdAndCompanyId')
                ->once()
                ->andReturn($externalProduct);
        });

        CreateProductFromExternalProductJob::dispatch(
            $externalProduct->id,
            $externalProduct->company_id,
            $admin
        )->onQueue(config('horizon.default_queue_name'));
    }
)->with([[true], [false]]);
