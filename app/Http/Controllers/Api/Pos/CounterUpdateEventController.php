<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\Cashier\CashierQueries;
use App\Domains\CounterUpdateEvent\CounterUpdateEventQueries;
use App\Domains\CounterUpdateEvent\DataObjects\CounterUpdateEventData;
use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Domains\CounterUpdateEvent\Resources\PosCounterUpdateEventListResource;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CounterUpdateEventController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $counterUpdateEventQueries = resolve(CounterUpdateEventQueries::class);
        $counterUpdateEventList = $counterUpdateEventQueries->getList($cashier->counter_update_id, $afterUpdatedAt);

        return [
            'counter_update_events' => PosCounterUpdateEventListResource::collection($counterUpdateEventList),
        ];
    }

    /**
     * @return array<string, PosCounterUpdateEventListResource>
     */
    public function store(CounterUpdateEventData $counterUpdateEventData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $this->checkRequest($counterUpdateEventData, $cashier);

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->counter_update_id;

        $counterUpdateEventQueries = resolve(CounterUpdateEventQueries::class);
        DB::beginTransaction();

        try {
            $counterUpdateEventDetails = $counterUpdateEventQueries->addNew($counterUpdateEventData, $counterUpdateId);

            DB::commit();

            return [
                'counter_update_event' => new PosCounterUpdateEventListResource($counterUpdateEventDetails),
            ];
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('POS COunter Update Event', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getStaticDetails(): array
    {
        return [
            'types' => CounterUpdateEventTypes::getList(),
        ];
    }

    private function checkRequest(CounterUpdateEventData $counterUpdateEventData, Cashier $cashier): void
    {
        if (! $counterUpdateEventData->product_id) {
            return;
        }

        $cashierQueries = resolve(CashierQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $companyId = $cashierQueries->getCashierCompanyId($cashier);

        $product = $productQueries->existsByIdAndCompanyId((int) $counterUpdateEventData->product_id, $companyId);
        if ($product) {
            return;
        }

        abort(412, 'The provided product is not found in our records.');
    }
}
