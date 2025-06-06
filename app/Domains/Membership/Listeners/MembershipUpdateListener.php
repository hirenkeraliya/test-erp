<?php

declare(strict_types=1);

namespace App\Domains\Membership\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Membership\Events\MembershipUpdateEvent;
use App\Domains\Membership\Services\MembershipService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class MembershipUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MembershipUpdateEvent $membershipUpdateEvent): void
    {
        $membership = $membershipUpdateEvent->membership;
        $membership->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBERSHIP_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $membership->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook membership update started', [
            'start time of the webhook call for the membership update' => Carbon::now()->format('Y-m-d H:i:s'),
            'membership id: ' . $membership->getKey(),
        ]);

        try {
            $membershipService = resolve(MembershipService::class);
            foreach ($saleChannels as $saleChannel) {
                $membershipService->addUpdateDetails($membership, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook membership update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook membership update ended', [
            'end time of the webhook call for membership update' => Carbon::now()->format('Y-m-d H:i:s'),
            'membership id: ' . $membership->getKey(),
        ]);
    }
}
