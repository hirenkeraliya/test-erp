<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\PaymentType\Events\PaymentTypeCreateEvent;
use App\Domains\PaymentType\Services\PaymentTypeService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentTypeCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentTypeCreateEvent $paymentTypeCreateEvent): void
    {
        $paymentType = $paymentTypeCreateEvent->paymentType;
        $paymentType->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PAYMENT_TYPE_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $paymentType->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook payment type create started', [
            'start time of the webhook call for the payment type create' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);

        try {
            $paymentTypeService = resolve(PaymentTypeService::class);
            foreach ($saleChannels as $saleChannel) {
                $paymentTypeService->addUpdateDetails($paymentType, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook payment type create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook payment type create ended', [
            'end time of the webhook call for the payment type create' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);
    }
}
