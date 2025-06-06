<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountData;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscountTransaction\HappyHourDiscountTransactionQueries;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Admin\HappyHourDiscountController;
use App\Models\Admin;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->companyId = 1;
    $this->happyHourDiscount = HappyHourDiscount::factory()->make([
        'id' => 1,
        'name' => 'abcd',
        'location_id' => 1,
        'company_id' => $this->companyId,
        'product_type_id' => ProductTypes::ALL->value,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-01',
    ]);

    $this->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->make([
        'id' => 1,
        'happy_hour_discount_id' => $this->happyHourDiscount->id,
        'counter_update_id' => null,
        'offline_id' => 123,
        'authorizer_id' => 1,
        'authorizer_type' => 'ADMIN',
        'happened_at' => '2023-01-01',
    ]);

    $this->happyHourDiscount->happyHourDiscountTransaction = $this->happyHourDiscountTransaction;

    $this->happyHourDiscountRecord = [
        'name' => $this->happyHourDiscount->name,
        'new_price' => 200,
        'counter_update_id' => $this->happyHourDiscountTransaction->counter_update_id,
        'offline_id' => $this->happyHourDiscountTransaction->offline_id,
        'location_id' => $this->happyHourDiscount->location_id,
        'company_id' => $this->happyHourDiscount->company_id,
        'product_type_id' => $this->happyHourDiscount->product_type_id,
        'authorizer_id' => $this->happyHourDiscountTransaction->authorizer_id,
        'authorizer_type' => $this->happyHourDiscountTransaction->authorizer_type,
        'start_date' => $this->happyHourDiscount->start_date,
        'end_date' => $this->happyHourDiscount->end_date,
    ];
});

test(
    'It calls the List query method of the happy hour discount queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
        ];

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $requestParameter,
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $this->companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);

        $response = $happyHourDiscountController->fetchHappyHours(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the addNew method of the happy hour discount queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $happyHourDiscountA = HappyHourDiscount::factory()->make([
            'id' => 1,
            'name' => 'efgahdgh',
            'location_id' => 1,
            'company_id' => $this->companyId,
            'product_type_id' => ProductTypes::ALL->value,
            'start_date' => '2023-01-20',
            'end_date' => '2023-01-30',
        ]);

        $happyHourDiscountRecord = [
            'name' => $happyHourDiscountA->name,
            'new_price' => 350.63,
            'counter_update_id' => null,
            'offline_id' => 'ZBJBJA2424',
            'location_id' => $happyHourDiscountA->location_id,
            'company_id' => $happyHourDiscountA->company_id,
            'product_type_id' => $happyHourDiscountA->product_type_id,
            'authorizer_id' => 1,
            'authorizer_type' => 'ADMIN',
            'start_date' => $happyHourDiscountA->start_date,
            'end_date' => $happyHourDiscountA->end_date,
        ];

        unset($happyHourDiscountRecord['company_id']);
        unset($happyHourDiscountRecord['counter_update_id']);
        unset($happyHourDiscountRecord['offline_id']);
        unset($happyHourDiscountRecord['authorizer_id']);
        unset($happyHourDiscountRecord['authorizer_type']);
        unset($happyHourDiscountRecord['happened_at']);

        $happyHourDiscountData = new HappyHourDiscountData(...$happyHourDiscountRecord);

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId,
            $admin,
            $happyHourDiscountA
        ): void {
            $mock->shouldReceive('checkIfExists')
                ->once()
                ->andReturn(null);
            $mock->shouldReceive('addNewForAdmin')
                ->once()
                ->with($happyHourDiscountData, $admin, $companyId)
                ->andReturn($happyHourDiscountA);
            $mock->shouldReceive('prepareHappyHourDiscount')
                ->once()
                ->andReturn($happyHourDiscountData->all());
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $redirectResponse = $happyHourDiscountController->store($happyHourDiscountData, $request);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Happy hour added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/happy-hours', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the store method of the ProductTypes Brand and checkRequest',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->happyHourDiscountRecord['product_type_id'] = ProductTypes::BRAND->value;
        $this->happyHourDiscountRecord['brand_ids'] = [1];

        unset($this->happyHourDiscountRecord['company_id']);
        unset($this->happyHourDiscountRecord['counter_update_id']);
        unset($this->happyHourDiscountRecord['offline_id']);
        unset($this->happyHourDiscountRecord['authorizer_id']);
        unset($this->happyHourDiscountRecord['authorizer_type']);
        unset($this->happyHourDiscountRecord['happened_at']);

        $happyHourDiscountData = new HappyHourDiscountData(...$this->happyHourDiscountRecord);

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId,
            $admin
        ): void {
            $mock->shouldReceive('checkIfExists')
                ->once()
                ->andReturn(null);
            $mock->shouldReceive('addNewForAdmin')
                ->never()
                ->with($happyHourDiscountData, $admin, $companyId);
            $mock->shouldReceive('prepareHappyHourDiscount')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsById')
                ->once()
                ->andReturn(false);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $happyHourDiscountController->store($happyHourDiscountData, $request);
    }
)->throws(HttpException::class, 'Some of the brands are not available in over records');

test(
    'It calls the store method of the ProductTypes Category and checkRequest',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->happyHourDiscountRecord['product_type_id'] = ProductTypes::CATEGORY->value;
        $this->happyHourDiscountRecord['category_ids'] = [1];

        unset($this->happyHourDiscountRecord['company_id']);
        unset($this->happyHourDiscountRecord['counter_update_id']);
        unset($this->happyHourDiscountRecord['offline_id']);
        unset($this->happyHourDiscountRecord['authorizer_id']);
        unset($this->happyHourDiscountRecord['authorizer_type']);
        unset($this->happyHourDiscountRecord['happened_at']);
        $happyHourDiscountData = new HappyHourDiscountData(...$this->happyHourDiscountRecord);

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId,
            $admin
        ): void {
            $mock->shouldReceive('checkIfExists')
                ->once()
                ->andReturn(null);
            $mock->shouldReceive('addNewForAdmin')
                ->never()
                ->with($happyHourDiscountData, $admin, $companyId);
            $mock->shouldReceive('prepareHappyHourDiscount')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllCategoriesExist')
                ->once()
                ->andReturn(false);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $happyHourDiscountController->store($happyHourDiscountData, $request);
    }
)->throws(HttpException::class, 'Some of the categories are not available in over records');

test(
    'It calls the store method of the ProductTypes Style and checkRequest',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->happyHourDiscountRecord['product_type_id'] = ProductTypes::STYLE->value;
        $this->happyHourDiscountRecord['style_ids'] = [1];

        unset($this->happyHourDiscountRecord['company_id']);
        unset($this->happyHourDiscountRecord['counter_update_id']);
        unset($this->happyHourDiscountRecord['offline_id']);
        unset($this->happyHourDiscountRecord['authorizer_id']);
        unset($this->happyHourDiscountRecord['authorizer_type']);
        unset($this->happyHourDiscountRecord['happened_at']);
        $happyHourDiscountData = new HappyHourDiscountData(...$this->happyHourDiscountRecord);

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId,
            $admin
        ): void {
            $mock->shouldReceive('checkIfExists')
                ->once()
                ->andReturn(null);
            $mock->shouldReceive('addNewForAdmin')
                ->never()
                ->with($happyHourDiscountData, $admin, $companyId);
            $mock->shouldReceive('prepareHappyHourDiscount')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $this->mock(StyleQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllStylesExist')
                ->once()
                ->andReturn(false);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $happyHourDiscountController->store($happyHourDiscountData, $request);
    }
)->throws(HttpException::class, 'Some of the styles are not available in over records');

test(
    'It calls the store method of the ProductTypes Departments and checkRequest',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->happyHourDiscountRecord['product_type_id'] = ProductTypes::DEPARTMENTS->value;
        $this->happyHourDiscountRecord['department_ids'] = [1];

        unset($this->happyHourDiscountRecord['company_id']);
        unset($this->happyHourDiscountRecord['counter_update_id']);
        unset($this->happyHourDiscountRecord['offline_id']);
        unset($this->happyHourDiscountRecord['authorizer_id']);
        unset($this->happyHourDiscountRecord['authorizer_type']);
        unset($this->happyHourDiscountRecord['happened_at']);
        $happyHourDiscountData = new HappyHourDiscountData(...$this->happyHourDiscountRecord);

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId,
            $admin
        ): void {
            $mock->shouldReceive('checkIfExists')
                ->once()
                ->andReturn(null);
            $mock->shouldReceive('addNewForAdmin')
                ->never()
                ->with($happyHourDiscountData, $admin, $companyId);
            $mock->shouldReceive('prepareHappyHourDiscount')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $this->mock(DepartmentQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllDepartmentExist')
                ->once()
                ->andReturn(false);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $happyHourDiscountController->store($happyHourDiscountData, $request);
    }
)->throws(HttpException::class, 'Some of the departments are not available in over records');

test(
    'It calls the update method of the happy hour discount queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $this->happyHourDiscountRecord['category_ids'] = [];
        $this->happyHourDiscountRecord['brand_ids'] = [];
        $this->happyHourDiscountRecord['department_ids'] = [];
        $this->happyHourDiscountRecord['style_ids'] = [];

        unset($this->happyHourDiscountRecord['company_id']);
        unset($this->happyHourDiscountRecord['counter_update_id']);
        unset($this->happyHourDiscountRecord['offline_id']);
        unset($this->happyHourDiscountRecord['authorizer_id']);
        unset($this->happyHourDiscountRecord['authorizer_type']);
        unset($this->happyHourDiscountRecord['happened_at']);

        $happyHourDiscountData = new HappyHourDiscountData(...$this->happyHourDiscountRecord);

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId
        ): void {
            $mock->shouldReceive('update')
                ->once()
                ->with($happyHourDiscountData, 1, $companyId);
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $redirectResponse = $happyHourDiscountController->update($happyHourDiscountData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Happy hour updated successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/happy-hours', $redirectResponse->getTargetUrl());
    }
);

test('It calls the exportHappyHours method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('happyHourDiscountExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new HappyHourDiscount()));
    });

    $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);

    $response = $happyHourDiscountController->exportHappyHours('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the store method of checkRequest throw exception when already exists happy hour discount',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->happyHourDiscountRecord['product_type_id'] = ProductTypes::ALL->value;

        unset($this->happyHourDiscountRecord['company_id']);
        unset($this->happyHourDiscountRecord['brand_id']);
        unset($this->happyHourDiscountRecord['counter_update_id']);
        unset($this->happyHourDiscountRecord['offline_id']);
        unset($this->happyHourDiscountRecord['authorizer_id']);
        unset($this->happyHourDiscountRecord['authorizer_type']);
        unset($this->happyHourDiscountRecord['happened_at']);

        $happyHourDiscountData = new HappyHourDiscountData(...$this->happyHourDiscountRecord);

        $this->mock(HappyHourDiscountTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $happyDiscountQueries = $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountData,
            $companyId,
            $admin
        ): void {
            $mock->shouldReceive('checkIfExists')
                ->once()
                ->andReturn($this->happyHourDiscount);

            $mock->shouldReceive('addNewForAdmin')
                ->never()
                ->with($happyHourDiscountData, $admin, $companyId);

            $mock->shouldReceive('prepareHappyHourDiscount')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $happyHourDiscountController = new HappyHourDiscountController($happyDiscountQueries);
        $happyHourDiscountController->store($happyHourDiscountData, $request);
    }
)->throws(HttpException::class, 'Similar Happy Hour discount is already available with us.');
