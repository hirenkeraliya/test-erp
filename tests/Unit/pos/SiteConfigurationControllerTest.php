<?php

declare(strict_types=1);

use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Api\Common\SiteConfigurationController;
use App\Models\SiteConfiguration;
use Illuminate\Support\Collection;

test('it calls the getSiteConfiguration method and returns site configuration record', function (): void {
    $siteConfiguration = SiteConfiguration::factory(2)->make([
        'type_id' => SiteConfigurationTypes::THEME,
        'value' => ThemeColors::DARK_PURPLE->value,
    ]);
    $siteConfigCollection = new Collection($siteConfiguration);
    $siteConfigurationController = new SiteConfigurationController();
    $this->mock(SiteConfigurationQueries::class, function ($mock) use ($siteConfigCollection): void {
        $mock->shouldReceive('getAll')
            ->once()
            ->andReturn($siteConfigCollection);
    });
    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('isEcommerceEnabled')
            ->once()
            ->andReturn(true);
    });
    $response = $siteConfigurationController->getSiteConfiguration();
    expect($response['data'])->toBeArray();
});
