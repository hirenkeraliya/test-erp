<?php

declare(strict_types=1);

use App\Domains\CashMovement\Services\CashMovementReportService;
use App\Domains\CustomReport\DataObjects\CashMovementCustomReportData;
use App\Domains\CustomReport\DataObjects\DiscountSummaryCustomReportData;
use App\Domains\CustomReport\DataObjects\GeneralSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\GoodReceivedNotesCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleHourCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnAndExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesByPromoterCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesCollectionCustomReportData;
use App\Domains\CustomReport\DataObjects\StockAdjustmentCustomReportData;
use App\Domains\CustomReport\DataObjects\StockCardCustomReportData;
use App\Domains\CustomReport\DataObjects\StockDiscountCustomReportData;
use App\Domains\CustomReport\DataObjects\StockMovementsCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferDiscrepancyCustomReportData;
use App\Domains\CustomReport\DataObjects\SuspendAndResumeCustomReportData;
use App\Domains\CustomReport\DataObjects\TopTwentyCustomReportData;
use App\Domains\CustomReport\DataObjects\VoidReportCustomReportData;
use App\Domains\CustomReport\DataObjects\WorstTwentyCustomReportData;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteReportService;
use App\Domains\HoldSale\Services\SuspendAndResumeReportService;
use App\Domains\InventoryUpdate\Services\StockCardReportService;
use App\Domains\Promoter\Enums\SalesByPromoterFilterTypes;
use App\Domains\Promoter\services\SalesByPromoterReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\Sale\Services\DiscountCustomReportService;
use App\Domains\Sale\Services\DiscountSummaryReportService;
use App\Domains\Sale\Services\GeneralSalesReportService;
use App\Domains\Sale\Services\SaleHourReportService;
use App\Domains\Sale\Services\SaleReturnAndSaleExchangeReportService;
use App\Domains\Sale\Services\SalesCollectionReportService;
use App\Domains\Sale\Services\SalesExchangeReportService;
use App\Domains\Sale\Services\TopTwentyReportService;
use App\Domains\Sale\Services\WorstTwentyReportService;
use App\Domains\SaleReturn\Services\SaleReturnReportService;
use App\Domains\StockAdjustment\Services\StockAdjustmentCustomReportService;
use App\Domains\StockMovement\Services\StockMovementReportService;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Services\StockTransferCustomReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Domains\VoidSale\Services\VoidReportService;
use App\Http\Controllers\StoreManager\CustomReportController;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the printCashMovement method and returns the string',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $filterData = [
            'location_ids' => [$loactionId],
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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the printExchange method and returns the string',
    function (): void {
        $loactionId = 1;

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'location_ids' => [$loactionId],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [$currentDate, $currentDate],
            'filter_by' => [],
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
        $loactionId = 1;

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [$loactionId],
            'counter_ids' => [],
            'date_range' => [],
            'filter_by' => null,
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
        $loactionId = 1;
        setStoreManagerStoreIdInSession($loactionId);
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [$loactionId],
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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession($companyId);

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
    'the exportCashMovementsReport method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [$loactionId],
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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the exportVoidReport method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [$loactionId],
            'counter_ids' => [],
            'date_range' => [],
            'filter_by' => null,
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
        $loactionId = 1;

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [$loactionId],
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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the printGoodsReceivedNote method and returns the string',
    function (): void {
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the exportStockTransfer method and returns the BinaryFileResponse',
    function (): void {
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the exportStockCard method and returns the BinaryFileResponse',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession($companyId);

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
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the exportWorstTwenty method and returns the BinaryFileResponse',
    function (): void {
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
        $loactionId = 1;
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [$loactionId],
            'date_range' => [],
            'filter_by' => SalesByPromoterFilterTypes::BY_CATEGORIES->value,
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
    'the exportTopTwenty method and returns the BinaryFileResponse',
    function (): void {
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

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
    'the exportSaleReturn method and returns the BinaryFileResponse',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();

        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
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
    'the exportExchange method and returns the BinaryFileResponse',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
        ];

        $this->mock(SalesExchangeReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportExchange(new SaleExchangeCustomReportData($filterData), 'demo.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the exportReturnAndExchange method and returns the BinaryFileResponse',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $filterData = [
            'location_ids' => [],
            'counter_ids' => [],
            'cashier_ids' => [],
            'date_range' => [],
        ];

        $this->mock(SaleReturnAndSaleExchangeReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportReturnAndExchange(
            new SaleReturnAndExchangeCustomReportData($filterData),
            'demo.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the printDiscount method and returns the string',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $filterData = [
            'location_ids' => [$loactionId],
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
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $filterData = [
            'location_ids' => [$loactionId],
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
    'the printSaleReturn method and returns the string',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'location_ids' => [$loactionId],
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
    'the printReturnAndExchange method and returns the string',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);
        $filterData = [
            'location_ids' => [$loactionId],
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
    'the printSuspendAndResume method and returns the string',
    function (): void {
        $companyId = 1;
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);
        $filterData = [
            'location_ids' => [$loactionId],
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
    'the printDiscountSummaryReport method and returns the string',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $filterData = [
            'location_ids' => [$loactionId],
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
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $filterData = [
            'location_ids' => [$loactionId],
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
    'the printStockTransferDiscrepancy method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

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
    'the exportStockTransferDiscrepancy method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

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
    'the printInterCompany method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

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
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $this->mock(InterCompanyCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportInterCompany(new InterCompanyCustomReportData(1, true), 'test.csv');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the saleHourPrint method and returns the string',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'date_range' => [$currentDate, $currentDate],
            'location_id' => $loactionId,
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
    'the exportSaleHour method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $currentDate = Carbon::now()->format('Y-m-d');

        $filterData = [
            'date_range' => [$currentDate, $currentDate],
            'location_id' => $loactionId,
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
    'the getStoresAndWareHouses method and returns with proper response',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('getStoresAndWareHousesByCompanyId')
                ->with(1)
                ->once()
                ->andReturn([
                    'stores' => [],
                    'warehouses' => [],
                ]);
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->getStoresAndWareHouses();

        expect($response)->toBeArray()
            ->toHaveKeys(['stores', 'warehouses']);
    }
);

test(
    'the printStockAdjustment method and returns the BinaryFileResponse',
    function (): void {
        $loactionId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreManagerStoreIdInSession($loactionId);

        $this->mock(StockAdjustmentCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printStockAdjustment(new StockAdjustmentCustomReportData(), false);

        expect($response)->toBe('');
    }
);
