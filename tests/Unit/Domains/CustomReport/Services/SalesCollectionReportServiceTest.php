<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SalesCollectionReportTypes;
use App\Domains\Sale\Services\SalesCollectionReportService;
use App\Models\CloseCounterPayment;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Currency;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('renderPreparedSalesByDate function returns expected sales data by date', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'cash',
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'payment_type_id' => 1,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $salePaymentData = [];

    $salePaymentData['location_name'] = $location->name;
    $salePaymentData['payment_details'][] = [
        'orders' => 1,
        $paymentType->name => $salePayment->amount,
    ];

    $salePaymentData['totals']['sales_collection'] = 10;
    $salePaymentData['totals']['orders'] = 10;

    $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
        $mock->shouldReceive('preparedPaymentsByDate')
            ->once()
            ->andReturn([[$salePaymentData], ['Orders', 'Cash'], [now(), now()]]);
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesByDate($filterData, $company, collect([$location]));
    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('by Sales Date');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain(Str::title($paymentType->name));
    expect($result)->toContain((string) $salePayment->amount);
});

it('renderPreparedSalesByDateAndBrand function returns expected sales data by date and brand', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('preparedSalesCollectionByDateAndBrand')
            ->once()
            ->andReturn([[], [], [], [now(), now()]]);
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesByDateAndBrand($filterData, $company);
    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('By Date And Brand');
    expect($result)->toContain('Test Company');
});

it('renderPreparedSalesOnlyTotals function returns expected sales data by date', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'cash',
    ]);

    $closeCounterPayment = CloseCounterPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'payment_type_id' => $paymentType->id,
        'total_amount' => 100,
    ]);

    $counterUpdate->payments = $closeCounterPayment;
    $salePaymentData = [];

    $salePaymentData['details'] = [
        'orders' => 1,
        $paymentType->name => $closeCounterPayment->total_amount,
        'location_name' => $location->name,
    ];
    $salePaymentData['totals'] = [];

    $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
        $mock->shouldReceive('preparedPaymentsByOnlyTotals')
            ->once()
            ->andReturn([$salePaymentData, ['Orders', 'Cash'], [now(), now()]]);
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesOnlyTotals($filterData, $company, collect([$location]));

    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('by summary');
    expect($result)->toContain('Test Company');
    expect($result)->toContain((string) $closeCounterPayment->total_amount);
    expect($result)->toContain(Str::title($paymentType->name));
});

it('renderPreparedSalesByReceipt function returns expected sales data by receipt', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'cash',
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentType->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $salePaymentData = [];

    $salePaymentData['location_name'] = $location->name;
    $salePaymentData['sales'][] = [
        'orders' => 1,
        'remark' => '',
        $paymentType->name => $salePayment->amount,
    ];
    $salePaymentData['roundingAdjust'] = 0;
    $salePaymentData['totalTaxAmount'] = 0;
    $salePaymentData['totals'] = [];
    $salePaymentData['grandTotalCollection'] = 1;
    $salePaymentData['grandTotalReceipt'] = 1;
    $salePaymentData['grandTotalReturnAmount'] = 1;

    $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
        $mock->shouldReceive('preparedSalesByReceipt')
            ->once()
            ->andReturn([[$salePaymentData], ['Orders', 'Cash'], [now(), now()]]);
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesByReceipt($filterData, $company, collect([$location]));

    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('by Receipt No');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain((string) $salePayment->amount);
    expect($result)->toContain(Str::title($paymentType->name));
});

it('renderPreparedSalesByCashier function returns expected sales data by cashier', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'cash',
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentType->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $salePaymentData = [];

    $salePaymentData['location_name'] = $location->name;

    $salePaymentData['payment_details'][] = [
        'orders' => 1,
        'sales_collection' => 10,
        'roundingAdjust' => 0,
        'totalTaxAmount' => 0,
        'totals' => [],
        $paymentType->name => $salePayment->amount,
    ];

    $salePaymentData['totals'] = [
        'orders' => 1,
        'sales_collection' => 10,
        'roundingAdjust' => 0,
        'totalTaxAmount' => 0,
        'return_amount' => 0,
    ];

    $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
        $mock->shouldReceive('preparedSalesByCashier')
            ->once()
            ->andReturn([[$salePaymentData], ['Orders', 'Cash', 'Totals'], [now(), now()]]);
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesByCashier($filterData, $company, collect([$location]));

    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('by Cashier');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain(Str::title($paymentType->name));
});

it('renderPreparedSalesByCounter function returns expected sales data by counter', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'cash',
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentType->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $salePaymentData = [];

    $salePaymentData['location_name'] = $location->name;
    $salePaymentData['payment_details'][] = [
        'orders' => 1,
        'sales_collection' => 10,
        'roundingAdjust' => 0,
        'totalTaxAmount' => 0,
        'totals' => [],
        $paymentType->name => $salePayment->amount,
    ];

    $salePaymentData['totals'] = [
        'orders' => 1,
        'sales_collection' => 10,
        'roundingAdjust' => 0,
        'totalTaxAmount' => 0,
        'return_amount' => 0,
    ];

    $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
        $mock->shouldReceive('preparedSalesByCounter')
            ->once()
            ->andReturn([[$salePaymentData], ['Orders', 'Cash', 'Totals'], [now(), now()]]);
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesByCounter($filterData, $company, collect([$location]));

    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('by Counter');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain(Str::title($paymentType->name));
});

it('renderPreparedSalesByTime function returns expected sales data by time period', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'cash',
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentType->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $salePaymentData = [];

    $salePaymentData['location_name'] = $location->name;
    $salePaymentData['sales'][] = [
        'orders' => 1,
        'total_collection' => 0,
        'roundingAdjust' => 0,
        'totalTaxAmount' => 0,
        'return_amount' => 0,
        'remark' => '',
        $paymentType->name => $salePayment->amount,
    ];

    $salePaymentData['totals'] = [
        'orders' => 0,
        'total_collection' => 0,
        'roundingAdjust' => 0,
        'totalTaxAmount' => 0,
        'return_amount' => 0,
    ];

    $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
        $mock->shouldReceive('preparedSalesByTime')
            ->once()
            ->andReturn([[$salePaymentData], ['Orders', 'Cash'], [now(), now()]]);
    });

    $salesCollectionReportService = new SalesCollectionReportService();
    $result = $salesCollectionReportService->renderPreparedSalesByTime($filterData, $company, collect([$location]));

    expect($result)->toContain('Sales Collection Report');
    expect($result)->toContain('by Time');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain(Str::title($paymentType->name));
});

it(
    'renderPreparedSalesByCounterAndByCashier function returns expected sales data by counter and by cashier',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => [],
            'report_type' => [],
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'cash',
        ]);

        $salePayment = SalePayment::factory()->make([
            'id' => 1,
            'sale_id' => $sale->id,
            'payment_type_id' => $paymentType->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $salePaymentData = [];

        $salePaymentData['location_name'] = $location->name;
        $salePaymentData['payment_details'][] = [
            'orders' => 1,
            'sales_collection' => 10,
            'roundingAdjust' => 0,
            'totalTaxAmount' => 0,
            'totals' => [],
            $paymentType->name => $salePayment->amount,
        ];

        $salePaymentData['totals'] = [
            'orders' => 1,
            'sales_collection' => 10,
            'roundingAdjust' => 0,
            'totalTaxAmount' => 0,
            'return_amount' => 0,
        ];

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getForSalesCollectionByFilterCashier')
            ->once()
           ->andReturn(collect([]));
        });

        $salesCollectionReportService = new SalesCollectionReportService();
        $result = $salesCollectionReportService->renderPreparedSalesByCounterAndByCashier(
            $filterData,
            $company,
            collect([$location])
        );

        expect($result)->toContain('Sales Collection Report');
        expect($result)->toContain('by Counter And by Cashier');
        expect($result)->toContain('Test Company');
        expect($result)->toContain('Test Store');
    }
);

it('exportSaleCollection function exports sale data to expected format', function (
    int $reportType,
    string $functionName
): void {
    $filterData = [
        'location_ids' => [1],
        'counter_ids' => [],
        'cashier_ids' => [],
        'date_range' => [],
        'filter_by' => [],
        'report_type' => $reportType,
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });
    if ($reportType === SalesCollectionReportTypes::BY_SUMMARY->value) {
        $salePaymentData['details'][] = [
            'orders' => 1,
            'location_name' => $location->name,
        ];
        $salePaymentData['grand_totals'] = [];
        $this->mock(CustomReportService::class, function ($mock) use ($salePaymentData): void {
            $mock->shouldReceive('preparedPaymentsByOnlyTotals')
                ->once()
                ->andReturn([$salePaymentData, ['Orders', 'Cash'], [now(), now()]]);
        });
    } elseif ($reportType === SalesCollectionReportTypes::BY_DATE_AND_BRAND->value) {
        $this->mock(CustomReportService::class, function ($mock) use ($functionName): void {
            $mock->shouldReceive($functionName)
            ->once()
            ->andReturn([[], [], [], [now(), now()]]);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
        });
    } else {
        $this->mock(CustomReportService::class, function ($mock) use ($functionName): void {
            $mock->shouldReceive($functionName)
            ->once()
            ->andReturn([[], [], [now(), now()]]);
        });
    }

    $salesCollectionReportService = new SalesCollectionReportService();

    $result = $salesCollectionReportService->exportSaleCollection(1, $filterData, 'demo.csv');

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
})->with([
    [SalesCollectionReportTypes::BY_DATE->value, 'preparedPaymentsByDate'],
    [SalesCollectionReportTypes::BY_RECEIPT->value, 'preparedSalesByReceipt'],
    [SalesCollectionReportTypes::BY_CASHIER->value, 'preparedSalesByCashier'],
    [SalesCollectionReportTypes::BY_COUNTER->value, 'preparedSalesByCounter'],
    [SalesCollectionReportTypes::BY_TIME->value, 'preparedSalesByTime'],
    [SalesCollectionReportTypes::BY_SUMMARY->value, 'preparedPaymentsByOnlyTotals'],
    [SalesCollectionReportTypes::BY_DATE_AND_BRAND->value, 'preparedSalesCollectionByDateAndBrand'],
]);

it(
    'renderPreparedSalesForCurrentAndPreviousDay function returns expected sales data by date and previous date',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'date' => now()->format('Y-m-d'),
            'filter_by' => [],
            'report_type' => [],
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'cash',
        ]);

        $closeCounterPayment = CloseCounterPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'payment_type_id' => $paymentType->id,
            'total_amount' => 100,
        ]);

        $counterUpdate->payments = $closeCounterPayment;

        $brandWiseRegionWiseData = [
            (object) [
                'brand_name' => 'ARIANI GALLERY',
                'location_name' => 'HOUSE OF ARIANI',
                'region_name' => 'CENTRAL',
                'code' => 'ARAHOA',
                'date' => '30-04-2024',
                'total' => '100',
            ],
            (object) [
                'brand_name' => 'ARIANI GALLERY',
                'location_name' => 'HOUSE OF ARIANI',
                'region_name' => 'CENTRAL',
                'code' => 'ARAHOA',
                'date' => '30-04-2024',
                'total' => '200',
            ],
            (object) [
                'brand_name' => 'ARIANI GALLERY',
                'location_name' => 'HOUSE OF ARIANI',
                'region_name' => 'CENTRAL',
                'code' => 'ARAHOA',
                'date' => '30-04-2024',
                'total' => '300',
            ],
        ];

        $this->mock(CounterUpdateQueries::class, function ($mock) use ($brandWiseRegionWiseData): void {
            $mock->shouldReceive('getSalesAndReturnDataByDate')
                ->once()
                ->andReturn(collect($brandWiseRegionWiseData));
        });

        $salesCollectionReportService = new SalesCollectionReportService();
        $result = $salesCollectionReportService->renderPreparedSalesForCurrentAndPreviousDay($company, $filterData);

        expect($result)->toContain('Sales Collection Report');
        expect($result)->toContain('By Current Day Vs Previous Day');
        expect($result)->toContain('Test Company');
    }
);
