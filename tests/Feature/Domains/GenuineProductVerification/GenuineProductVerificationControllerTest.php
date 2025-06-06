<?php

declare(strict_types=1);

use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Front\GenuineProductVerification\GenuineProductVerificationController;
use App\Models\SiteConfiguration;
use Illuminate\Support\Facades\Cookie;

test('It shows the verification page with the correct theme color', function (): void {
    $siteConfigurationQueries = $this->mock(SiteConfigurationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedThemeConfiguration')
            ->once()
            ->andReturn(new SiteConfiguration([
                'value' => ThemeColors::AMARANTH_DEEP_PURPLE->value,
            ]));
    });

    $controller = new GenuineProductVerificationController();
    $response = $controller->index();

    $this->assertEquals('front.verify-product.verify-product', $response->name());
    $this->assertEquals(
        ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value),
        $response->getData()['themeColor']
    );
    $this->assertEquals(Cookie::get('verify-product'), $response->getData()['cookieValue']);
});
