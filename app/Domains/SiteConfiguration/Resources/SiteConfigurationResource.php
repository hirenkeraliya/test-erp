<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration\Resources;

use App\Domains\Company\CompanyQueries;
use App\Domains\SiteConfiguration\Enums\EcommerceType;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteConfigurationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $siteConfiguration = $this->resource;

        $typeValue = $siteConfiguration->type_id->value;

        $value = ($typeValue === SiteConfigurationTypes::THEME->value)
            ? ThemeColors::getFormattedCaseName($siteConfiguration->value)
            : $siteConfiguration->value;

        $imageUrl = '';

        if ($typeValue === SiteConfigurationTypes::FAVICON_ICON->value) {
            $imageUrl = $siteConfiguration->getDiskBasedFirstMediaUrl('favicon_icon');
        }

        if ($typeValue === SiteConfigurationTypes::LOGIN_PAGE_LOGO->value) {
            $imageUrl = $siteConfiguration->getDiskBasedFirstMediaUrl('login_page_logo');
        }

        if ($typeValue === SiteConfigurationTypes::NAVBAR_LOGO->value) {
            $imageUrl = $siteConfiguration->getDiskBasedFirstMediaUrl('navbar_logo');
        }

        if ($typeValue === SiteConfigurationTypes::DEFAULT_COMPANY->value) {
            $companyQuery = resolve(CompanyQueries::class);
            $value = $companyQuery->getNameAndCodeById((int) $value)->name;
        }

        if ($typeValue === SiteConfigurationTypes::ECOMMERCE_TYPE->value) {
            $value = EcommerceType::getFormattedCaseName((int) $siteConfiguration->value);
        }

        return [
            'id' => $siteConfiguration->id,
            'type' => SiteConfigurationTypes::getFormattedCaseName($siteConfiguration->type_id->value),
            'value' => $value,
            'image_url' => $imageUrl,
        ];
    }
}
