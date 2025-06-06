<?php

declare(strict_types=1);

use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCard\Jobs\GiftCardExpirationJob;
use App\Models\GiftCard;

test(
    'GiftCardExpirationJob job calls respective methods and expired gift card as expected',
    function (): void {
        $giftCards = [];
        $giftCards[] = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'available_amount' => 10,
        ]);

        $giftCards[] = GiftCard::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'available_amount' => 10,
        ]);

        $this->mock(GiftCardQueries::class, function ($mock): void {
            $mock->shouldReceive('markGiftCardsAsExpired')
                ->once();
        });

        GiftCardExpirationJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
