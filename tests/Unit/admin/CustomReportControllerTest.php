<?php

declare(strict_types=1);

use App\Domains\CashMovement\Services\CashMovementReportService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\DataObjects\CashMovementCustomReportData;
use App\Domains\CustomReport\DataObjects\DiscountSummaryCustomReportData;
use App\Domains\CustomReport\DataObjects\GeneralSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\GoodReceivedNotesCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyCustomReportData;
use App\Domains\CustomReport\DataObjects\PromoterCommissionCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleHourCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleOverallByStoreCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnAndExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesByPromoterCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesCollectionCustomReportData;
use App\Domains\CustomReport\DataObjects\SeasonalSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\StockCardCustomReportData;
use App\Domains\CustomReport\DataObjects\StockDiscountCustomReportData;
use App\Domains\CustomReport\DataObjects\StockMovementsCustomReportData;
use App\Domains\CustomReport\DataObjects\StockSummaryByModuleReportData;
use App\Domains\CustomReport\DataObjects\StockTransferCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferDiscrepancyCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferStatusSummaryCustomReportData;
use App\Domains\CustomReport\DataObjects\SuspendAndResumeCustomReportData;
use App\Domains\CustomReport\DataObjects\TopTwentyCustomReportData;
use App\Domains\CustomReport\DataObjects\VoidReportCustomReportData;
use App\Domains\CustomReport\DataObjects\WorstTwentyCustomReportData;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteReportService;
use App\Domains\HoldSale\Services\SuspendAndResumeReportService;
use App\Domains\InventoryUpdate\Services\StockCardReportService;
use App\Domains\Promoter\services\SalesByPromoterReportService;
use App\Domains\PromoterCommissionUpdate\Services\PromoterCommissionUpdateReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\DiscountCustomReportService;
use App\Domains\Sale\Services\DiscountSummaryReportService;
use App\Domains\Sale\Services\GeneralSalesReportService;
use App\Domains\Sale\Services\SaleHourReportService;
use App\Domains\Sale\Services\SaleReturnAndSaleExchangeReportService;
use App\Domains\Sale\Services\SalesCollectionReportService;
use App\Domains\Sale\Services\SalesExchangeReportService;
use App\Domains\Sale\Services\SalesOverallReportService;
use App\Domains\Sale\Services\TopTwentyReportService;
use App\Domains\Sale\Services\WorstTwentyReportService;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturn\Services\SaleReturnReportService;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Domains\StockMovement\Services\StockMovementReportService;
use App\Domains\StockSummary\Services\StockSummaryByModuleReportService;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Services\StockTransferCustomReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\VoidSale\Services\VoidReportService;
use App\Http\Controllers\Admin\CustomReportController;
use App\Models\Company;
use App\Models\Currency;
use App\Models\SaleSeason;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the printCashMovement method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => null,
            'date_range' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'filter_by' => null,
        ];

        $this->mock(CashMovementReportService::class, function ($mock): void {
            $mock->shouldReceive('printCashMovement')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printCashMovement(new CashMovementCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the stockMovementReportPrint method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(StockMovementReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->stockMovementReportPrint(new StockMovementsCustomReportData());

        expect($response)->toBeString();
    }
);

test(
    'the saleHourPrint method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'date_range' => [$currentDate, $currentDate],
        ];

        $this->mock(SaleHourReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->saleHourPrint(new SaleHourCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the printExchange method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'store_ids' => 'Store',
            'counter_ids' => [],
            'cashier_ids' => [],
            'filter_by' => [],
            'date_range' => [$currentDate, $currentDate],
        ];

        $this->mock(SalesExchangeReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printExchange(new SaleExchangeCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the printVoidReport method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'counter_ids' => [],
            'date_range' => [],
            'filter_by' => [],
        ];

        $this->mock(VoidReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printVoidReport(new VoidReportCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the print method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'report_type' => null,
            'e_invoice_submitted' => null,
        ];

        $this->mock(SalesCollectionReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->print(new SalesCollectionCustomReportData(...$filterData));

        expect($response)->toBeString();
    }
);

test(
    'the printGeneralSale method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(GeneralSalesReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printGeneralSale(new GeneralSalesCustomReportData(''));

        expect($response)->toBeString();
    }
);

test(
    'the printTopTwenty method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(TopTwentyReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once()
                ->andReturn('');
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printTopTwenty(new TopTwentyCustomReportData(false));

        expect($response)->toBeString();
    }
);

test(
    'the printWorstTwenty method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(WorstTwentyReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printWorstTwenty(new WorstTwentyCustomReportData(false));

        expect($response)->toBeString();
    }
);

test(
    'the printStockCard method and returns the string',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $this->mock(StockCardReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printStockCard(new StockCardCustomReportData(null));

        expect($response)->toBeString();
    }
);

test(
    'the printPromoterCommission method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'location_ids' => null,
            'month_range' => null,
            'filter_by' => null,
            'brand_ids' => [],
            'department_ids' => [],
            'group_ids' => [],
            'report_type' => null,
        ];

        $this->mock(PromoterCommissionUpdateReportService::class, function ($mock): void {
            $mock->shouldReceive('printPromoterCommission')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printPromoterCommission(
            new PromoterCommissionCustomReportData($filterData)
        );

        expect($response)->toBeString();
    }
);

test(
    'the exportCashMovementsReport method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'location_ids' => null,
            'date_range' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'filter_by' => null,
        ];

        $this->mock(CashMovementReportService::class, function ($mock): void {
            $mock->shouldReceive('exportCashMovement')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportCashMovementsReport(
            new CashMovementCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportStockMovementReport method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(StockMovementReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockMovementReport')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportStockMovementReport(
            new StockMovementsCustomReportData(null),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportSaleHour method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'date_range' => [$currentDate, $currentDate],
        ];

        $this->mock(SaleHourReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSaleHour(new SaleHourCustomReportData($filterData), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportVoidReport method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'counter_ids' => [],
            'date_range' => [],
        ];

        $this->mock(VoidReportService::class, function ($mock): void {
            $mock->shouldReceive('exportVoidSaleReport')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportVoidReport(new VoidReportCustomReportData($filterData), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportSaleCollection method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'report_type' => null,
            'e_invoice_submitted' => null,
        ];

        $this->mock(SalesCollectionReportService::class, function ($mock): void {
            $mock->shouldReceive('exportSaleCollection')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSaleCollection(
            new SalesCollectionCustomReportData(...$filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportGeneralSalesReport method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(GeneralSalesReportService::class, function ($mock): void {
            $mock->shouldReceive('exportGeneralSalesReport')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportGeneralSalesReport(new GeneralSalesCustomReportData(''), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printSaleReturn method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'store_ids' => 'Store',
            'counter_ids' => [],
            'cashier_ids' => [],
            'filter_by' => null,
            'date_range' => [$currentDate, $currentDate],
        ];

        $this->mock(SaleReturnReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printSaleReturn(new SaleReturnCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the printGoodsReceivedNote method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(GoodsReceivedNoteReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printGoodsReceivedNote(new GoodReceivedNotesCustomReportData());

        expect($response)->toBeString();
    }
);

test(
    'the printStockTransfer method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(StockTransferCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printStockTransfer(
            new StockTransferCustomReportData(
                1,
                '',
                StockTransferCustomReportDateTypes::CREATED_AT->value,
                StockTransferCustomReportDateTypes::CREATED_AT->value
            ),
            false
        );

        expect($response)->toBe('');
    }
);

test(
    'the printStockTransferDiscrepancy method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession(1);

        $this->mock(StockTransferDiscrepancyCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printStockTransferDiscrepancy(
            new StockTransferDiscrepancyCustomReportData(
                1,
                StockTransferCustomReportDateTypes::CREATED_AT->value,
                StockTransferCustomReportDateTypes::CREATED_AT->value
            )
        );

        expect($response)->toBe('');
    }
);

test(
    'the exportStockTransfer method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(StockTransferCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportStockTransfer(
            new StockTransferCustomReportData(
                1,
                'true',
                StockTransferCustomReportDateTypes::CREATED_AT->value,
                StockTransferCustomReportDateTypes::CREATED_AT->value
            ),
            'test.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportStockTransferDiscrepancy method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(StockTransferDiscrepancyCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportStockTransferDiscrepancy(
            new StockTransferDiscrepancyCustomReportData(
                1,
                StockTransferCustomReportDateTypes::CREATED_AT->value,
                StockTransferCustomReportDateTypes::CREATED_AT->value
            ),
            'test.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printReturnAndExchange method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => null,
        ];

        $this->mock(SaleReturnAndSaleExchangeReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printReturnAndExchange(
            new SaleReturnAndExchangeCustomReportData($filterData)
        );

        expect($response)->toBeString();
    }
);

test(
    'the exportStockCard method and returns the BinaryFileResponse',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $this->mock(StockCardReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockCard')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportStockCard(new StockCardCustomReportData(null), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportGoodsReceivedNote method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(GoodsReceivedNoteReportService::class, function ($mock): void {
            $mock->shouldReceive('exportGoodsReceivedNote')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportGoodsReceivedNote(
            new GoodReceivedNotesCustomReportData(null),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportTopTwenty method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(TopTwentyReportService::class, function ($mock): void {
            $mock->shouldReceive('exportTopTwenty')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportTopTwenty(new TopTwentyCustomReportData(false), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportWorstTwenty method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(WorstTwentyReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportWorstTwenty(new WorstTwentyCustomReportData(false), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportSalesByPromoter method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $filterData = [
            'store_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'category_ids' => null,
            'promoter_ids' => null,
            'group_ids' => null,
            'report_type' => null,
        ];

        $this->mock(SalesByPromoterReportService::class, function ($mock): void {
            $mock->shouldReceive('exportSalesByPromoter')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSalesByPromoter(
            new SalesByPromoterCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportPromoterCommission method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => null,
            'month_range' => null,
            'filter_by' => null,
            'brand_ids' => [],
            'department_ids' => [],
            'group_ids' => [],
            'report_type' => null,
        ];

        $this->mock(PromoterCommissionUpdateReportService::class, function ($mock): void {
            $mock->shouldReceive('exportPromoterCommissionData')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportPromoterCommission(
            new PromoterCommissionCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printSuspendAndResume method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => null,
        ];

        $this->mock(SuspendAndResumeReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printSuspendAndResume(new SuspendAndResumeCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the exportSuspendAndResume method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => null,
        ];

        $this->mock(SuspendAndResumeReportService::class, function ($mock): void {
            $mock->shouldReceive('exportSuspendAndResume')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSuspendAndResume(
            new SuspendAndResumeCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportSaleReturn method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
            'filter_by' => null,
        ];

        $this->mock(SaleReturnReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSaleReturn(new SaleReturnCustomReportData($filterData), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printDiscount method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'product_id' => null,
            'article_number' => null,
            'style_ids' => null,
            'report_type' => null,
        ];

        $this->mock(DiscountCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });
        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printDiscount(new StockDiscountCustomReportData($filterData));

        expect($response)->toBeString();
    }
);

test(
    'the exportDiscountReport method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'product_id' => null,
            'article_number' => null,
            'style_ids' => null,
            'report_type' => null,
        ];

        $this->mock(DiscountCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportDiscountReport(
            new StockDiscountCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printDiscountSummaryReport method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'product_id' => null,
            'article_number' => null,
            'style_ids' => null,
            'report_type' => null,
        ];

        $this->mock(DiscountSummaryReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });
        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printDiscountSummaryReport(
            new DiscountSummaryCustomReportData($filterData)
        );

        expect($response)->toBeString();
    }
);

test(
    'the exportDiscountSummaryReport method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'store_ids' => [],
            'date_range' => [],
            'filter_by' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'product_id' => null,
            'article_number' => null,
            'style_ids' => null,
            'report_type' => null,
        ];

        $this->mock(DiscountSummaryReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportDiscountSummaryReport(
            new DiscountSummaryCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printSaleOverallByStore method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'date_range' => [],
            'report_by' => null,
        ];

        $this->mock(SalesOverallReportService::class, function ($mock): void {
            $mock->shouldReceive('printSaleOverall')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printSaleOverallByStore(new SaleOverallByStoreCustomReportData(1));

        expect($response)->toBeString();
    }
);

test(
    'the exportSaleOverallByStore method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(SalesOverallReportService::class, function ($mock): void {
            $mock->shouldReceive('exportSaleOverall')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSaleOverallByStore(
            new SaleOverallByStoreCustomReportData(1),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the getDiscountTypeReports method returns as accepted',
    function (): void {
        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->getDiscountTypeReports();
        expect($response['discountTypeReports']->first())
            ->toHaveKey('id', 0)
            ->toHaveKey('name', 'All Discount');
    }
);

test(
    'the printInterCompany method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(InterCompanyCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printInterCompany(new InterCompanyCustomReportData(1, false), false);

        expect($response)->toBe('');
    }
);

test(
    'the exportInterCompany method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $this->mock(InterCompanyCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportInterCompany(new InterCompanyCustomReportData(1, false), 'test.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the seasonalSalesPrint method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'ABCD',
            'default_country_id' => 1,
        ]);

        $saleSeason = SaleSeason::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->andReturn($company);
        });

        $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
            $mock->shouldReceive('getById')
                ->andReturn($saleSeason);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getSeasonalSalesData')
                ->andReturn(collect());
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('getSeasonalSaleReturnsData')
                ->andReturn(collect());
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->seasonalSalesPrint(new SeasonalSalesCustomReportData(1, 1, 1));

        expect($response)->toBeString();
    }
);

test(
    'the exportSeasonalSales method and returns the BinaryFileResponse',
    function (): void {
        setCompanyIdInSession();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'ABCD',
            'default_country_id' => 1,
        ]);

        $saleSeason = SaleSeason::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->andReturn($company);
        });

        $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
            $mock->shouldReceive('getById')
                ->andReturn($saleSeason);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getSeasonalSalesData')
                ->andReturn(collect());
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('getSeasonalSaleReturnsData')
                ->andReturn(collect());
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportSeasonalSales(
            new SeasonalSalesCustomReportData(
                1,
                1,
                1,
                null,
                null,
                [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')],
                [
                    Carbon::now()->subYear()->startOfMonth()->format('Y-m-d'),
                    Carbon::now()->subYear()->endOfMonth()->format('Y-m-d'),
                ],
            ),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'stock transfer status summary report method call printStockTransfersStatusSummary and returns the string',
    function (): void {
        setCompanyIdInSession();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'ABCD',
            'default_country_id' => 1,
        ]);

        $validateData = [
            'location_ids' => [],
            'date_range' => ['2024-01-01', '2024-01-01'],
            'status' => 2,
            'report_type' => 1,
        ];

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->andReturn($company);
        });

        $this->mock(StockTransferQueries::class, function ($mock): void {
            $mock->shouldReceive('getStockTransferByStatusSummary')
                ->andReturn(collect([]));
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printStockTransfersStatusSummary(
            new StockTransferStatusSummaryCustomReportData(...$validateData)
        );

        expect($response)->toBeString();
    }
);

test(
    'stock transfer status summary report method call exportStockTransfersStatusSummary and returns file',
    function (): void {
        setCompanyIdInSession();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'ABCD',
            'default_country_id' => 1,
        ]);

        $validateData = [
            'location_ids' => [],
            'date_range' => ['2024-01-01', '2024-01-01'],
            'status' => 2,
            'report_type' => 1,
        ];

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->andReturn($company);
        });

        $this->mock(StockTransferQueries::class, function ($mock): void {
            $mock->shouldReceive('getStockTransferByStatusSummary')
                ->andReturn(collect([]));
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportStockTransfersStatusSummary(
            new StockTransferStatusSummaryCustomReportData(...$validateData),
            'demo.xlsx'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

it('can print stock summary by module', function (): void {
    $this->mock(StockSummaryByModuleReportService::class, function ($mock): void {
        $mock->shouldReceive('print')
            ->once()
            ->andReturn('Printed Report');
    });

    Session::shouldReceive('get')
        ->with('admin_company_id', null)
        ->andReturn(1);

    $locationIds = [1, 2];
    $reportBy = 1;
    $reportType = 1;
    $dateRange = ['2024-03-01', '2024-03-30'];
    $articleNumber = ['ABC123'];
    $brandIds = [1, 2];
    $departmentIds = [];

    $request = new StockSummaryByModuleReportData(
        $reportBy,
        $reportType,
        $locationIds,
        $dateRange,
        $articleNumber,
        $brandIds,
        $departmentIds,
    );

    $controller = new CustomReportController();
    $response = $controller->printStockSummaryByModule($request);

    expect($response)->toBe('Printed Report');
});
