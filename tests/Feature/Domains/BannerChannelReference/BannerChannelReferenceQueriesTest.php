<?php

declare(strict_types=1);

use App\Domains\BannerChannelReference\BannerChannelReferenceQueries;
use App\Models\Banner;
use App\Models\BannerChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->bannerChannelReferenceQueries = new BannerChannelReferenceQueries();
});

test('a banner channel reference can be added', function (): void {
    $banner = Banner::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $bannerChannelReferenceRecord = BannerChannelReference::factory()->make([
        'banner_id' => $banner,
        'sale_channel_id' => $saleChannelId,
        'external_banner_id' => $banner,
    ]);

    $this->bannerChannelReferenceQueries->addNew($bannerChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(BannerChannelReference::class, $bannerChannelReferenceRecord->toArray());
});

test('it calls the getByBannerIdAndSaleChannelId to get the external Banner', function (): void {
    $bannerId = Banner::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $bannerChannelReference = BannerChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'banner_id' => $bannerId,
        'external_banner_id' => 1,
    ]);

    $response = $this->bannerChannelReferenceQueries->getByBannerIdAndSaleChannelId($bannerId, $saleChannelId);

    expect($response)
        ->toHaveKey('id', $bannerChannelReference->getKey())
        ->toHaveKey('banner_id', $bannerId)
        ->toHaveKey('external_banner_id', 1);
});
