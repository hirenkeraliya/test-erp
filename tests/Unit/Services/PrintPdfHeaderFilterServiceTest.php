<?php

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Counter\CounterQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Counter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Promoter;
use App\Models\PromoterGroup;
use App\Models\Region;
use App\Models\SaleThroughRatio;
use App\Models\Size;
use App\Models\Style;
use App\Models\Tag;

beforeEach(function (): void {
    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $this->brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $this->category = Category::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'test',
    ]);

    $this->color = Color::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->size = Size::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'sort_order' => 1,
    ]);

    $this->productCollection = ProductCollection::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'company_id' => 1,
        'designation_id' => 1,
        'email' => 'employee@test.com',
    ]);

    $this->promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
        'monthly_sales_target' => 100,
    ]);

    $this->promoterGroup = PromoterGroup::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->department = Department::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'Test',
    ]);

    $this->tag = Tag::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->region = Region::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->saleThroughRatio = SaleThroughRatio::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'Test',
    ]);

    $this->style = Style::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'name' => 'CounterA',
    ]);

    $this->locationQueries = $this->getMockBuilder(LocationQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getLocationForFilter'])
        ->getMock();
    $this->locationQueries->method('getLocationForFilter')
        ->willReturn($this->location->name);

    $this->productQueries = $this->getMockBuilder(ProductQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getProductNameForFilter'])
        ->getMock();
    $this->productQueries->method('getProductNameForFilter')
        ->willReturn($this->product->name);

    $this->brandQueries = $this->getMockBuilder(BrandQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getBrandNameForFilter'])
        ->getMock();
    $this->brandQueries->method('getBrandNameForFilter')
        ->willReturn($this->brand->name);

    $this->categoryQueries = $this->getMockBuilder(CategoryQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getCategoryNameForFilter'])
        ->getMock();
    $this->categoryQueries->method('getCategoryNameForFilter')
        ->willReturn($this->category->name);

    $this->colorQueries = $this->getMockBuilder(ColorQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getColorNameForFilter'])
        ->getMock();
    $this->colorQueries->method('getColorNameForFilter')
        ->willReturn($this->color->name);

    $this->sizeQueries = $this->getMockBuilder(SizeQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getSizeNameForFilter'])
        ->getMock();
    $this->sizeQueries->method('getSizeNameForFilter')
        ->willReturn($this->size->name);

    $this->productCollectionQueries = $this->getMockBuilder(ProductCollectionQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getProductCollectionNameForFilter'])
        ->getMock();
    $this->productCollectionQueries->method('getProductCollectionNameForFilter')
        ->willReturn($this->productCollection->name);
    $this->promoterQueries = $this->getMockBuilder(PromoterQueries::class)
    ->disableOriginalConstructor()
    ->onlyMethods(['getByIdsWithName'])
    ->getMock();
    $this->promoterQueries->method('getByIdsWithName')
    ->willReturn($this->employee->first_name .' '.$this->employee->last_name);

    $this->promoterGroupQueries = $this->getMockBuilder(PromoterGroupQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getPromoterGroupNameForFilter'])
        ->getMock();
    $this->promoterGroupQueries->method('getPromoterGroupNameForFilter')
        ->willReturn($this->promoterGroup->name);

    $this->departmentQueries = $this->getMockBuilder(DepartmentQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getDepartmentNameForFilter'])
        ->getMock();
    $this->departmentQueries->method('getDepartmentNameForFilter')
        ->willReturn($this->department->name);

    $this->tagQueries = $this->getMockBuilder(TagQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getTagsNameForFilter'])
        ->getMock();
    $this->tagQueries->method('getTagsNameForFilter')
        ->willReturn($this->tag->name);

    $this->regionQueries = $this->getMockBuilder(RegionQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getRegionNameForFilter'])
        ->getMock();
    $this->regionQueries->method('getRegionNameForFilter')
        ->willReturn($this->region->name);

    $this->employeeQueries = $this->getMockBuilder(EmployeeQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getEmployeeNameForFilter'])
        ->getMock();
    $this->employeeQueries->method('getEmployeeNameForFilter')
        ->willReturn($this->employee->first_name .' '.$this->employee->last_name);

    $this->saleThroughRatioQueries = $this->getMockBuilder(SaleThroughRatioQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getGradeNameForFilter'])
        ->getMock();
    $this->saleThroughRatioQueries->method('getGradeNameForFilter')
        ->willReturn($this->saleThroughRatio->name);

    $this->styleQueries = $this->getMockBuilder(StyleQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getStyleNameForFilter'])
        ->getMock();
    $this->styleQueries->method('getStyleNameForFilter')
        ->willReturn($this->style->name);

    $this->memberQueries = $this->getMockBuilder(MemberQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getMemberNameForFilter'])
        ->getMock();
    $this->memberQueries->method('getMemberNameForFilter')
        ->willReturn($this->employee->first_name .' '.$this->employee->last_name);

    $this->counterQueries = $this->getMockBuilder(CounterQueries::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getCounterNameForFilter'])
        ->getMock();
    $this->counterQueries->method('getCounterNameForFilter')
        ->willReturn($this->counter->name);

    // Initialize the service with mocks
    $this->service = new PrintPdfHeaderFilterService(
        $this->locationQueries,
        $this->productQueries,
        $this->productCollectionQueries,
        $this->categoryQueries,
        $this->brandQueries,
        $this->promoterQueries,
        $this->promoterGroupQueries,
        $this->sizeQueries,
        $this->colorQueries,
        $this->departmentQueries,
        $this->tagQueries,
        $this->counterQueries,
        $this->regionQueries,
        $this->employeeQueries,
        $this->saleThroughRatioQueries,
        $this->styleQueries,
        $this->memberQueries,
    );
});

test('buildFilterData returns correct header data with all filters', function (): void {
    $filterData = [
        'location_ids' => [$this->location->id],
        'product_id' => $this->product->id,
        'brand_ids' => [$this->brand->id],
        'category_ids' => [$this->category->id],
        'color_ids' => [$this->color->id],
        'size_ids' => [$this->size->id],
        'product_collection_id' => $this->productCollection->id,
        'promoter_ids' => [$this->promoter->id],
        'group_ids' => [$this->promoterGroup->id],
        'department_ids' => [$this->department->id],
        'tag_ids' => [$this->tag->id],
        'region_ids' => [$this->region->id],
        'employee_id' => $this->employee->id,
        'counter_ids' => [$this->counter->id],
        'style_ids' => [$this->style->id],
        'grade_filter' => $this->saleThroughRatio->id,
    ];

    $expected = [
        'location_name' => $this->location->name,
        'product_name' => $this->product->name,
        'product_collection' => $this->productCollection->name,
        'category_name' => $this->category->name,
        'brand' => $this->brand->name,
        'promoter_name' => $this->employee->first_name .' '. $this->employee->last_name,
        'promoter_group_name' => $this->promoterGroup->name,
        'size' => $this->size->name,
        'color' => $this->color->name,
        'department' => $this->department->name,
        'tags' => $this->tag->name,
        'counters' => $this->counter->name,
        'regions' => $this->region->name,
        'employee_name' => $this->employee->first_name .' '. $this->employee->last_name,
        'style' => $this->style->name,
        'grade_filters' => $this->saleThroughRatio->name,
    ];

    $result = $this->service->buildFilterData($filterData);
    expect($result)->toBe($expected);
});
