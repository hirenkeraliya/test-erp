<?php

declare(strict_types=1);

namespace App\Domains\OrderIntegration\Jobs;

use App\Domains\Courier\Enums\CourierTypes;
use App\Domains\OrderIntegration\Enums\IntegrationStatuses;
use App\Domains\OrderIntegration\OrderIntegrationQueries;
use App\Domains\OrderIntegration\Services\NinjaVanService;
use App\Models\Courier;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderIntegrationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $orderIntegrationId,
        protected int $orderId,
    ) {
    }

    public function handle(): void
    {
        Log::channel('order_integration_job')->info('order integration job started', [
            'start time of the call for the order integration' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $this->orderId,
        ]);

        $orderIntegrationQueries = resolve(OrderIntegrationQueries::class);
        $orderIntegration = $orderIntegrationQueries->getByIdAndOrderId($this->orderIntegrationId, $this->orderId);

        /** @var Courier $courier */
        $courier = $orderIntegration->courier;

        try {
            if ($courier->type_id->value === CourierTypes::NINJA_VAN->value) {
                $ninjaVanService = resolve(NinjaVanService::class);

                if ($orderIntegration->status === IntegrationStatuses::CREATE_ORDER->value && null === $orderIntegration->tracking_number) {
                    $ninjaVanService->createOrder($this->orderId, $orderIntegration);

                    $orderIntegration->fresh();
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('order_integration_job')->error('order integration job failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('order_integration_job')->info('order integration job ended', [
            'end time of the call for the order integration' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $this->orderId,
        ]);
    }
}
