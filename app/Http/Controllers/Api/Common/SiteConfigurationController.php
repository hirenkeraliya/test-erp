<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Common;

use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;

class SiteConfigurationController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getSiteConfiguration(): array
    {
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
        $allSiteConfigurations = $siteConfigurationQueries->getAll();
        $response = $this->setSiteConfiguration($allSiteConfigurations);

        return [
            'data' => $response,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function setSiteConfiguration(Collection $allSiteConfigurations): array
    {
        $siteConfigurationTypes = SiteConfigurationTypes::getList();
        $allSiteConfigurationsResponse = [];

        foreach ($siteConfigurationTypes as $siteConfigurationType) {
            $allSiteConfigurationsResponse[SiteConfigurationTypes::getCaseName($siteConfigurationType['id'])] = '';
        }

        foreach ($allSiteConfigurations as $allSiteConfiguration) {
            $typeValue = $allSiteConfiguration->type_id->value;

            $value = ($typeValue === SiteConfigurationTypes::THEME->value)
                ? ThemeColors::getHexColor($allSiteConfiguration->value)
                : $allSiteConfiguration->value;

            if ($typeValue === SiteConfigurationTypes::FAVICON_ICON->value) {
                $value = $allSiteConfiguration->getDiskBasedFirstMediaUrl('favicon_icon');
            }

            if ($typeValue === SiteConfigurationTypes::LOGIN_PAGE_LOGO->value) {
                $value = $allSiteConfiguration->getDiskBasedFirstMediaUrl('login_page_logo');
            }

            if ($typeValue === SiteConfigurationTypes::NAVBAR_LOGO->value) {
                $value = $allSiteConfiguration->getDiskBasedFirstMediaUrl('navbar_logo');
            }

            $allSiteConfigurationsResponse[SiteConfigurationTypes::getCaseName($typeValue)] = $value;
        }

        $allSiteConfigurationsResponse['e_invoice_url'] = config('app.url').'/front/e-invoice-details';
        $allSiteConfigurationsResponse['types'] = SequenceTypes::formattedForDigitalInvoice();
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $allSiteConfigurationsResponse['is_ecommerce_enabled'] = $saleChannelQueries->isEcommerceEnabled();

        return $allSiteConfigurationsResponse;
    }
}
