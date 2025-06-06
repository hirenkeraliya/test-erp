<?php

namespace App\Http\Controllers\Front;

use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\SiteConfiguration;
use Illuminate\Contracts\View\View;

class PdpaController extends Controller
{
    public function index(): View
    {
        $companyName = config('app.name');
        $companyEmailAddress = config('app.email');
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

        $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
            $getSiteConfigurationTheme->value
        ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

        return view('front.pdpa.index', [
            'companyName' => $companyName,
            'companyEmailAddress' => $companyEmailAddress,
            'themeColor' => $themeColor,
        ]);
    }
}
