<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\SiteConfiguration;
use Illuminate\View\View;

class SalesReturnsPolicyController extends Controller
{
    public function index(): View
    {
        $companyName = config('app.name');
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

        $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
            $getSiteConfigurationTheme->value
        ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

        return view('front.sales-returns-policy.index', [
            'companyName' => $companyName,
            'themeColor' => $themeColor,
        ]);
    }
}
