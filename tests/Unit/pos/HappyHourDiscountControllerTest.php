<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountListDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountCheckService;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Http\Controllers\Api\Pos\HappyHourDiscountController;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls getProductTypes method and returns the allow product types list', function (): void {
    $happyHourDiscountController = new HappyHourDiscountController();
    $response = $happyHourDiscountController->getProductTypes();
    expect($response['product_types'][0])
        ->toHaveKeys(['id', 'name', 'key']);
});

function getHappyHourDiscountListDataForPos(): HappyHourDiscountListDataForPos
{
    $happyHourDiscountListData = [
        'product_type_id' => ProductTypes::ALL->value,
        'per_page' => 10,
        'page' => 1,
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'after_updated_at' => null,
    ];

    return new HappyHourDiscountListDataForPos(...$happyHourDiscountListData);
}

beforeEach(function (): void {
    $this->cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $this->company = Company::factory()->make([
        'id' => 1,
        'allow_happy_hour_discount' => 1,
        'default_country_id' => 1,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'test',
    ]);

    $this->company->brands = collect([
        [
            'brand_id' => $this->brand->id,
        ],
    ]);

    $this->company->locations = collect([
        'location_id' => $this->location->id,
    ]);

    $this->happyHourDiscount = HappyHourDiscount::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::BRAND->value,
        'name' => 'abc',
        'start_date' => '2024-01-04 04:25:50',
        'end_date' => '2024-01-04 04:50:50',
    ]);

    $this->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->make([
        'id' => 1,
        'happy_hour_discount_id' => $this->happyHourDiscount->id,
        'counter_update_id' => 1,
        'offline_id' => 123,
        'happened_at' => '2024-01-04 04:20:50',
        'authorizer_id' => 1,
        'authorizer_type' => AuthorizerTypes::STORE_MANAGER->value,
    ]);

    $this->happyHourDiscount->happyHourDiscountTransaction = $this->happyHourDiscountTransaction;

    $this->happyHourDiscount->brands = collect([
        [
            'brand_id' => $this->brand->id,
        ],
    ]);

    $this->happyHourDiscountRequestData = [
        'offline_id' => '123456',
        'product_type_id' => ProductTypes::BRAND->value,
        'brand_ids' => [$this->brand->id],
        'name' => 'abc',
        'new_price' => '500',
        'start_date' => '2024-01-04 04:25:50',
        'end_date' => '2024-01-04 04:50:50',
        'happened_at' => '2024-01-04 04:20:50',
        'store_manager_id' => 1,
        'store_manager_passcode' => '123456',
        'director_id' => null,
        'director_passcode' => null,
    ];

    $this->happyHourDiscountDataForPos = new HappyHourDiscountDataForPos(...$this->happyHourDiscountRequestData);
});

test(
    'it calls the getPaginateHappyHourDiscountList method and returns the paginated list of happy hour discount',
    function (): void {
        $happyHourDiscountListDataForPos = getHappyHourDiscountListDataForPos();

        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $this->cashier);

        $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->shouldReceive('getCurrentLocation')
                ->once()
                ->with($this->cashier)
                ->andReturn($this->location);
        });

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($this->cashier)
                ->andReturn(1);
        });

        $filterData = [
            'per_page' => $happyHourDiscountListDataForPos->per_page,
            'company_id' => $this->company->id,
            'product_type_id' => $happyHourDiscountListDataForPos->product_type_id,
            'search_text' => $happyHourDiscountListDataForPos->search_text,
            'sort_by' => $happyHourDiscountListDataForPos->sort_by,
            'sort_direction' => $happyHourDiscountListDataForPos->sort_direction,
            'location_id' => $this->location->id,
            'after_updated_at' => null,
        ];

        $this->mock(HappyHourDiscountQueries::class, function ($mock) use ($filterData): void {
            $mock->shouldReceive('getPaginatedHappyHourDiscounts')
                ->once()
                ->with($filterData)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $happyHourDiscountController = new HappyHourDiscountController();
        $response = $happyHourDiscountController->getPaginateHappyHourDiscountList(
            $happyHourDiscountListDataForPos,
            $request
        );

        expect($response['happy_hour_discounts']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getPaginateHappyHourDiscountList method throw exception when counter has not been opened',
    function (): void {
        $happyHourDiscountListDataForPos = getHappyHourDiscountListDataForPos();

        $this->cashier->counter_update_id = null;

        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $this->cashier);

        $happyHourDiscountController = new HappyHourDiscountController();
        $happyHourDiscountController->getPaginateHappyHourDiscountList($happyHourDiscountListDataForPos, $request);
    }
)->throws(HttpException::class);

test('it calls store method and save happy hour', function (): void {
    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $this->cashier);

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($this->cashier)
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn($this->location);
    });

    $this->mock(HappyHourDiscountCheckService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
        $mock->shouldReceive('validateHappyHourData')
            ->once()
            ->andReturn(null);
        $mock->happyHourDiscountMismatches = collect([]);
    });

    $this->mock(HappyHourDiscountService::class, function ($mock): void {
        $mock->shouldReceive('addHappyHourDiscount')
            ->once()
            ->andReturn($this->happyHourDiscount);

        $mock->shouldReceive('saveHappyHourDiscountMismatches')
            ->once()
            ->andReturn(null);
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $happyHourDiscountController = new HappyHourDiscountController();
    $happyHourDiscountController->store($this->happyHourDiscountDataForPos, $request);
});

test('store method throw exception when counter has not been opened', function (): void {
    $this->cashier->counter_update_id = null;

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $this->cashier);

    $happyHourDiscountController = new HappyHourDiscountController();
    $happyHourDiscountController->store($this->happyHourDiscountDataForPos, $request);
})->throws(HttpException::class);
