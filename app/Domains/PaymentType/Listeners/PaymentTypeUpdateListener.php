<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\PaymentType\Events\PaymentTypeUpdateEvent;
use App\Domains\PaymentType\Services\PaymentTypeService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentTypeUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentTypeUpdateEvent $paymentTypeUpdateEvent): void
    {
        $paymentType = $paymentTypeUpdateEvent->paymentType;
        $paymentType->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PAYMENT_TYPE_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $paymentType->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook payment type update started', [
            'start time of the webhook call for the payment type update' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);

        try {
            $paymentTypeService = resolve(PaymentTypeService::class);
            foreach ($saleChannels as $saleChannel) {
                $paymentTypeService->addUpdateDetails($paymentType, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook payment type update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook payment type update ended', [
            'end time of the webhook call for payment type update' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);
    }
}
