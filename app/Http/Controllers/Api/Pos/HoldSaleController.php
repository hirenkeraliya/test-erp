<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\Resources\PosHoldSaleListResource;
use App\Domains\HoldSale\Services\CheckHoldSaleDetailsService;
use App\Domains\HoldSale\Services\SaveHoldSaleDetailsService;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HoldSaleController extends Controller
{
    public function saveDetails(HoldSaleData $holdSaleData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $checkHoldSaleDetailsService = resolve(CheckHoldSaleDetailsService::class);
        $this->checkHoldSaleDetails($checkHoldSaleDetailsService, $cashier, $holdSaleData);
        $checkHoldSaleDetailsService->checkOfflineId();

        DB::beginTransaction();

        try {
            $saveHoldSaleDetailsService = resolve(SaveHoldSaleDetailsService::class);
            $holdSale = $saveHoldSaleDetailsService->saveDetails(
                $cashier,
                $checkHoldSaleDetailsService,
                $request->all(),
            );

            DB::commit();

            return [
                'hold_sale' => $holdSale ? new PosHoldSaleListResource($holdSale) : null,
            ];
        } catch (Throwable $throwable) {
            Log::error('Cashier-Hold-Sales', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function cancelHoldSale(HoldSaleData $holdSaleData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $checkHoldSaleDetailsService = resolve(CheckHoldSaleDetailsService::class);
        $this->checkHoldSaleDetails($checkHoldSaleDetailsService, $cashier, $holdSaleData);

        DB::beginTransaction();

        try {
            $saveHoldSaleDetailsService = resolve(SaveHoldSaleDetailsService::class);
            $holdSale = $saveHoldSaleDetailsService->saveDetails(
                $cashier,
                $checkHoldSaleDetailsService,
                $request->all(),
            );

            DB::commit();

            return [
                'hold_sale' => $holdSale ? new PosHoldSaleListResource($holdSale) : null,
            ];
        } catch (Throwable $throwable) {
            Log::error('Cashier-Cancel-Hold-Sales', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function completeHoldSale(HoldSaleData $holdSaleData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $checkHoldSaleDetailsService = resolve(CheckHoldSaleDetailsService::class);
        $this->checkHoldSaleDetails($checkHoldSaleDetailsService, $cashier, $holdSaleData);

        DB::beginTransaction();

        try {
            $saveHoldSaleDetailsService = resolve(SaveHoldSaleDetailsService::class);
            $holdSale = $saveHoldSaleDetailsService->saveDetails(
                $cashier,
                $checkHoldSaleDetailsService,
                $request->all(),
            );

            DB::commit();

            return [
                'hold_sale' => $holdSale ? new PosHoldSaleListResource($holdSale) : null,
            ];
        } catch (Throwable $throwable) {
            Log::error('Cashier-Complete-Hold-Sales', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function releasedHoldSale(HoldSaleData $holdSaleData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $checkHoldSaleDetailsService = resolve(CheckHoldSaleDetailsService::class);
        $this->checkHoldSaleDetails($checkHoldSaleDetailsService, $cashier, $holdSaleData);

        DB::beginTransaction();

        try {
            $saveHoldSaleDetailsService = resolve(SaveHoldSaleDetailsService::class);
            $holdSale = $saveHoldSaleDetailsService->saveDetails(
                $cashier,
                $checkHoldSaleDetailsService,
                $request->all(),
            );

            DB::commit();

            return [
                'hold_sale' => $holdSale ? new PosHoldSaleListResource($holdSale) : null,
            ];
        } catch (Throwable $throwable) {
            Log::error('Cashier-Hold-Released-Sales', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getTypes(): array
    {
        return [
            'types' => HoldSaleTypes::getList(),
        ];
    }

    private function checkHoldSaleDetails(
        CheckHoldSaleDetailsService $checkHoldSaleDetailsService,
        Cashier $cashier,
        HoldSaleData $holdSaleData
    ): void {
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $items = collect($holdSaleData->items);

        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getByIdsWithBrandAndCategories($items->pluck('id')->toArray(), $companyId);

        $checkHoldSaleDetailsService->setDetails($holdSaleData, $products, $items, $companyId);

        $checkHoldSaleDetailsService->checkRequestDetails();
    }
}
