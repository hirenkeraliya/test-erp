<?php

declare(strict_types=1);

use App\Domains\CustomReport\DataObjects\GoodReceivedNotesCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyCustomReportData;
use App\Domains\CustomReport\DataObjects\StockAdjustmentCustomReportData;
use App\Domains\CustomReport\DataObjects\StockCardCustomReportData;
use App\Domains\CustomReport\DataObjects\StockMovementsCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferDiscrepancyCustomReportData;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteReportService;
use App\Domains\InventoryUpdate\Services\StockCardReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\StockAdjustment\Services\StockAdjustmentCustomReportService;
use App\Domains\StockMovement\Services\StockMovementReportService;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Services\StockTransferCustomReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Http\Controllers\WarehouseManager\CustomReportController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the stockMovementReportPrint method and returns the string',
    function (): void {
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
    'the printStockCard method and returns the string',
    function (): void {
        $companyId = 1;
        $locationId = 1;

        setWarehouseManagerWarehouseIdInSession($locationId);
        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

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
    'the exportStockMovementReport method and returns the BinaryFileResponse',
    function (): void {
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
    'the printGoodsReceivedNote method and returns the string',
    function (): void {
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

        $this->mock(StockTransferCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->exportStockTransfer(
            new StockTransferCustomReportData(
                1,
                '',
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
        $locationId = 1;

        setWarehouseManagerWarehouseIdInSession($locationId);
        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
    'the printStockTransferDiscrepancy method and returns the BinaryFileResponse',
    function (): void {
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

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
    'the getStoresAndWareHouses method and returns with proper response',
    function (): void {
        setWarehouseManagerWarehouseCompanyIdInSession();

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
        setWarehouseManagerWarehouseIdInSession();
        setWarehouseManagerWarehouseCompanyIdInSession();

        $this->mock(StockAdjustmentCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $customReportController = resolve(CustomReportController::class);
        $response = $customReportController->printStockAdjustment(new StockAdjustmentCustomReportData(), false);

        expect($response)->toBe('');
    }
);
