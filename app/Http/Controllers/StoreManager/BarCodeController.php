<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\CommonFunctions;
use App\Domains\Barcode\DataObjects\BarcodeData;
use App\Domains\Barcode\Exports\BarcodeRecordExport;
use App\Domains\Barcode\Services\BarcodeServices;
use App\Domains\Common\Enums\BarcodePrintColumns;
use App\Domains\Common\Enums\BarcodePrintModuleTypes;
use App\Domains\Common\Enums\BarcodePrintSizes;
use App\Domains\Common\Enums\BarcodePrintTypes;
use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportRecordJob;
use App\Domains\ExportRecord\Resources\ExportRecordListResource;
use App\Domains\ExportRecordTransaction\ExportRecordTransactionQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductPrices;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\Storage\Services\StorageService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BarCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('bar_codes/Index', [
            'exportRecordStatuses' => ExportRecordStatuses::getList(),
            'staticExportRecordStatuses' => ExportRecordStatuses::generateStaticCasesArray(),
            'exportPermission' => PermissionList::getExportPermissionName('barcode'),
            'helpCenterMessages' => BarcodeServices::helpCenterMessages(),
        ]);
    }

    public function create(): Response
    {
        $printColumns = collect(BarcodePrintColumns::cases())->map(function ($type) {
            if (config('app.product_variant')) {
                if ($type->name !== BarcodePrintColumns::COLOR->name && $type->name !== BarcodePrintColumns::SIZE->name && $type->name !== BarcodePrintColumns::STYLE->name) {
                    return [
                        'id' => $type->value,
                        'name' => CommonFunctions::stringTitleLowerCase($type->name),
                        'check' => false,
                    ];
                }
            } elseif ($type->name !== BarcodePrintColumns::STYLE->name && $type->name !== BarcodePrintColumns::ATTRIBUTES->name) {
                return [
                    'id' => $type->value,
                    'name' => CommonFunctions::stringTitleLowerCase($type->name),
                    'check' => false,
                ];
            }
        })->filter();

        $printSizes = collect(BarcodePrintSizes::cases())->map(fn ($type, $key): array => [
            'id' => $key + 1,
            'name' => $type->value,
            'check' => false,
        ])->toArray();

        return Inertia::render('bar_codes/Manage', [
            'printColumns' => array_values($printColumns->toArray()),
            'styleColumns' => [
                'id' => BarcodePrintColumns::STYLE->value,
                'name' => CommonFunctions::stringTitleLowerCase(BarcodePrintColumns::STYLE->name),
                'check' => false,
            ],
            'printSizes' => $printSizes,
            'styleDisplayForPrintSizeName' => BarcodePrintSizes::PRINT_SIZE_TWO->value,
            'defaultPrintColumns' => array_values($printColumns->pluck('id')->toArray()),
            'productPrices' => ProductPrices::getList(),
            'originalCapitalPriceStaticValue' => ProductPrices::ORIGINAL_CAPITAL_PRICE->value,
            'grnStockTransferStatus' => BarcodePrintModuleTypes::formattedForSelection(),
            'moduleTypeStaticEnum' => [
                'manual' => BarcodePrintTypes::MANUAL->value,
                'byModule' => BarcodePrintTypes::BY_MODULE->value,
            ],
            'grnStockTransferStaticStatus' => [
                'goodReceivedNote' => BarcodePrintModuleTypes::GOODS_RECEIVED_NOTES->value,
                'transferOrder' => BarcodePrintModuleTypes::TRANSFER_ORDER->value,
                'requestOrder' => BarcodePrintModuleTypes::REQUEST_ORDER->value,
                'transferIn' => BarcodePrintModuleTypes::TRANSFER_IN->value,
                'transferOut' => BarcodePrintModuleTypes::TRANSFER_OUT->value,
            ],
            'exportPermission' => PermissionList::getExportPermissionName('barcode'),
            'helpCenterMessages' => BarcodeServices::helpCenterMessages(),
        ]);
    }

    public function productsBarcodePrint(BarcodeData $barcodeData, Request $request): RedirectResponse
    {
        /** @var StoreManager $user */
        $user = $request->user();

        $companyId = session('store_manager_selected_location_company_id');
        if ($barcodeData->module_type === BarcodePrintTypes::BY_MODULE->value) {
            if ($barcodeData->selected_module_by === BarcodePrintModuleTypes::GOODS_RECEIVED_NOTES->value) {
                $goodReceiveNoteQueries = resolve(GoodsReceivedNoteQueries::class);

                if (! $goodReceiveNoteQueries->isReferenceNumberExists(
                    (string) $barcodeData->reference_number,
                    $companyId
                )) {
                    throw new RedirectBackWithErrorException('There is no reference number available.');
                }
            } else {
                $stockTransferQueries = resolve(StockTransferQueries::class);

                $stockTransfersCount = $stockTransferQueries->getCountByReferenceNumber(
                    (string) $barcodeData->reference_number,
                    (int) $barcodeData->selected_module_by,
                    $companyId
                );

                if (0 === $stockTransfersCount) {
                    throw new RedirectBackWithErrorException('There is no reference number available.');
                }

                if ($stockTransfersCount > 1) {
                    throw new RedirectBackWithErrorException(
                        'There are multiple stock transfers with specified reference number. can you please try with different reference number like transfer in or transfer out number.'
                    );
                }
            }
        }

        $filters = [
            'print_columns' => $barcodeData->print_columns,
            'print_size' => $barcodeData->print_size,
            'product_price' => $barcodeData->product_price,
            'module_type' => $barcodeData->module_type,
            'reference_number' => $barcodeData->reference_number ?? null,
            'selected_module_by' => $barcodeData->selected_module_by ?? null,
            'print_items' => $barcodeData->print_items ?? null,
            'remark' => $barcodeData->remark,
        ];

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $exportRecord = $exportRecordQueries->addNew($user, $filters, $companyId, ExportRecordTypes::BARCODE->value);

        ExportRecordJob::dispatch($exportRecord->id, $companyId)->onQueue('medium');

        return to_route('store_manager.barcode_prints.index')
            ->with(
                'success',
                'Barcode printing is currently in progress. Please wait for a moment, and you will find the file on the list page.'
            );
    }

    public function printTheBarcodeByManualProcess(BarcodeData $barcodeData): array
    {
        if (count((array) $barcodeData->print_items) <= 0) {
            throw new RedirectBackWithErrorException('there are no items available.');
        }

        $companyId = session('store_manager_selected_location_company_id');
        $requestProducts = collect($barcodeData->print_items);

        $barcodeServices = resolve(BarcodeServices::class);
        $products = $barcodeServices->prepareProductsPrint(
            $companyId,
            $requestProducts->pluck('product_id')->toArray(),
            $barcodeData->print_columns,
            $requestProducts,
            $barcodeData->product_price
        );

        $filePath = 'barcode_print/barcode-' . now()->format('Y-m-d-h-i-s') . '.pdf';

        $storageService = resolve(StorageService::class);

        if ($barcodeData->print_size === BarcodePrintSizes::PRINT_SIZE_ONE->value) {
            $barcodeServices->generatePrintSizeOnePdf($filePath, $products, $barcodeData->remark);

            return [
                'url' => $storageService->getPublicUrl($filePath),
            ];
        }

        if ($barcodeData->print_size === BarcodePrintSizes::PRINT_SIZE_TWO->value) {
            $barcodeServices->generatePrintSizeTwoPdf($filePath, $products, $barcodeData->remark);

            return [
                'url' => $storageService->getPublicUrl($filePath),
            ];
        }

        if (BarcodePrintSizes::PRINT_SIZE_THREE->value === $barcodeData->print_size) {
            $barcodeServices->generatePrintSizeThreePdf($filePath, $products, $barcodeData->remark);

            return [
                'url' => $storageService->getPublicUrl($filePath),
            ];
        }

        if (BarcodePrintSizes::PRINT_SIZE_FOUR->value === $barcodeData->print_size) {
            $barcodeServices->generatePrintSizeFourPdf($filePath, $products, $barcodeData->remark);

            return [
                'url' => $storageService->getPublicUrl($filePath),
            ];
        }

        abort(
            412,
            'There was an error while attempting to locate the file for this print size. Please get in touch with support.'
        );
    }

    public function viewPdf(string $fileName): BinaryFileResponse
    {
        return response()->file(storage_path('app/barcode_print/' . $fileName));
    }

    public function isPDFFileExists(string $fileName): array
    {
        if (Storage::fileExists('barcode_print/' . $fileName)) {
            return [
                'is_file_exists' => true,
            ];
        }

        abort(412, 'File Does Not Exist Please Re-Generate Again.');
    }

    public function fetchBarcodeRecords(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range'),
        ];

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $lengthAwarePaginator = $exportRecordQueries->getPaginatedExportListForBarcode(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ExportRecordListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function downloadPdfEntry(Request $request): void
    {
        DB::beginTransaction();

        try {
            /** @var StoreManager $user */
            $user = $request->user();

            $exportRecordTransactionQueries = resolve(ExportRecordTransactionQueries::class);
            $exportRecordTransactionQueries->addNew($user, session('store_manager_selected_location_company_id'));
            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Download barcode pdf', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function ExportBarcodeRecords(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range'),
        ];

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $companyId = session('store_manager_selected_location_company_id');

        $barcodeRecords = $exportRecordQueries->getExportListForBarcodeRecords($filterData, $companyId);

        return Excel::download(new BarcodeRecordExport($barcodeRecords), $filename);
    }

    public function getPendingExportRecordCount(): array
    {
        $exportRecordQueries = resolve(ExportRecordQueries::class);

        return [
            'pending_counts' => $exportRecordQueries->getPendingBarcodeExportRecordPendingCount(
                session('store_manager_selected_location_company_id')
            ),
        ];
    }
}
