<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\CounterUpdateEvent\CounterUpdateEventQueries;
use App\Domains\CounterUpdateEvent\DataObjects\CounterUpdateEventData;
use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Api\Pos\CounterUpdateEventController;
use App\Models\Cashier;
use App\Models\CounterUpdateEvent;
use App\Models\Employee;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the get List method and returns list of counter update events', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    CounterUpdateEvent::factory()->make([
        'offline_id' => '1234',
        'counter_update_id' => 1,
        'type_id' => 1,
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CounterUpdateEventQueries::class, function ($mock): void {
        $mock->shouldReceive('getList')
            ->once()
            ->andReturn(collect([]));
    });

    $counterUpdateEventController = new CounterUpdateEventController();
    $response = $counterUpdateEventController->getList($request);

    expect($response['counter_update_events']->resource);
});

test('getStaticDetails method returns the list of counter update event types', function (): void {
    $counterUpdateEventController = new CounterUpdateEventController();
    $response = $counterUpdateEventController->getStaticDetails();
    expect($response)->toHaveKey('types');
});

test(
    'It calls the addNew method of the counter update event queries class and returns proper response',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $counterUpdateEvent = CounterUpdateEvent::factory()->make([
            'offline_id' => '1234',
            'counter_update_id' => 1,
            'type_id' => 1,
            'happened_at' => '2022-01-04 04:20:50',
        ]);

        $preparedArray = [
            'offline_id' => 'A1234',
            'type_id' => 2,
            'happened_at' => '2022-01-04 04:20:50',
            'product_id' => null,
        ];

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CounterUpdateEventQueries::class, function ($mock) use ($counterUpdateEvent): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($counterUpdateEvent);
        });

        $counterUpdateEventController = new CounterUpdateEventController();
        $response = $counterUpdateEventController->store(new CounterUpdateEventData(...$preparedArray), $request);
        expect($response['counter_update_event']->resource);
    }
);

test(
    'addNew method throw exception when provided product id not found',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => 2,
        ]);

        $preparedArray = [
            'offline_id' => 'A1234',
            'type_id' => CounterUpdateEventTypes::PRODUCT_ADDED_TO_CART->value,
            'happened_at' => '2022-01-04 04:20:50',
            'product_id' => $product->id,
        ];

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier, $companyId): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn($companyId);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndCompanyId')
                ->once()
                ->andReturn(false);
        });

        $counterUpdateEventController = new CounterUpdateEventController();
        $counterUpdateEventController->store(new CounterUpdateEventData(...$preparedArray), $request);
    }
)->throws(HttpException::class, 'The provided product is not found in our records.');

test(
    'It calls the addNew method with product and returns proper response',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $counterUpdateEvent = CounterUpdateEvent::factory()->make([
            'offline_id' => '1234',
            'counter_update_id' => 1,
            'type_id' => 1,
            'happened_at' => '2022-01-04 04:20:50',
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
        ]);

        $preparedArray = [
            'offline_id' => 'A1234',
            'type_id' => CounterUpdateEventTypes::PRODUCT_ADDED_TO_CART->value,
            'happened_at' => '2022-01-04 04:20:50',
            'product_id' => $product->id,
        ];

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier, $companyId): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn($companyId);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndCompanyId')
                ->once()
                ->andReturn(true);
        });

        $this->mock(CounterUpdateEventQueries::class, function ($mock) use ($counterUpdateEvent): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($counterUpdateEvent);
        });

        $counterUpdateEventController = new CounterUpdateEventController();
        $response = $counterUpdateEventController->store(new CounterUpdateEventData(...$preparedArray), $request);

        expect($response['counter_update_event']->resource);
    }
);
