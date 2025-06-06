<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration\Resources;

use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteConfigurationEditResource extends JsonResource
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

        $imageUrl = null;

        if ($siteConfiguration->type_id->value === SiteConfigurationTypes::FAVICON_ICON->value) {
            $imageUrl = $siteConfiguration->getDiskBasedFirstMediaUrl('favicon_icon');
        }

        if ($siteConfiguration->type_id->value === SiteConfigurationTypes::LOGIN_PAGE_LOGO->value) {
            $imageUrl = $siteConfiguration->getDiskBasedFirstMediaUrl('login_page_logo');
        }

        if ($siteConfiguration->type_id->value === SiteConfigurationTypes::NAVBAR_LOGO->value) {
            $imageUrl = $siteConfiguration->getDiskBasedFirstMediaUrl('navbar_logo');
        }

        return [
            'id' => $siteConfiguration->id,
            'type_id' => $siteConfiguration->type_id->value,
            'theme_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::THEME->value ? $siteConfiguration->value : null,
            'favicon_icon_url' => $siteConfiguration->type_id->value === SiteConfigurationTypes::FAVICON_ICON->value ? $imageUrl : null,
            'login_page_logo_url' => $siteConfiguration->type_id->value === SiteConfigurationTypes::LOGIN_PAGE_LOGO->value ? $imageUrl : null,
            'upload_navbar_logo_url' => $siteConfiguration->type_id->value === SiteConfigurationTypes::NAVBAR_LOGO->value ? $imageUrl : null,
            'login_page_tagline' => $siteConfiguration->type_id->value === SiteConfigurationTypes::LOGIN_PAGE_TAGLINE->value ? $siteConfiguration->value : null,
            'login_page_sub_tagline' => $siteConfiguration->type_id->value === SiteConfigurationTypes::LOGIN_PAGE_SUB_TAGLINE->value ? $siteConfiguration->value : null,
            'default_company' => $siteConfiguration->type_id->value === SiteConfigurationTypes::DEFAULT_COMPANY->value ? (int) $siteConfiguration->value : null,
            'ecommerce_type' => $siteConfiguration->type_id->value === SiteConfigurationTypes::ECOMMERCE_TYPE->value ? (int) $siteConfiguration->value : null,
            'app_theme_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_THEME_COLOR->value ? $siteConfiguration->value : null,
            'app_button_text_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_BUTTON_TEXT_COLOR->value ? $siteConfiguration->value : null,
            'app_title_bar_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_TITLE_BAR_COLOR->value ? $siteConfiguration->value : null,
            'app_complete_text' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_COMPLETE_TEXT->value ? $siteConfiguration->value : null,
            'app_complete_text_background' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_COMPLETE_TEXT_BACKGROUND->value ? $siteConfiguration->value : null,
            'app_text_hint_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_TEXT_HINT_COLOR->value ? $siteConfiguration->value : null,
            'app_text_change_due' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_TEXT_CHANGE_DUE->value ? $siteConfiguration->value : null,
            'app_all_text_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_ALL_TEXT_COLOR->value ? $siteConfiguration->value : null,
            'ecommerce_favicon_icon_url' => $siteConfiguration->type_id->value === SiteConfigurationTypes::ECOMMERCE_FAVICON->value ? $siteConfiguration->value : null,
            'ecommerce_company_name' => $siteConfiguration->type_id->value === SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME->value ? $siteConfiguration->value : null,
            'ecommerce_company_logo_url' => $siteConfiguration->type_id->value === SiteConfigurationTypes::ECOMMERCE_COMPANY_LOGO->value ? $siteConfiguration->value : null,
            'app_label_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_LABEL_COLOR->value ? $siteConfiguration->value : null,
            'app_button_background_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_BUTTON_BACKGROUND_COLOR->value ? $siteConfiguration->value : null,
            'app_all_sub_tittle_text_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_ALL_SUB_TITTLE_TEXT_COLOR->value ? $siteConfiguration->value : null,
            'app_switch_on_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_SWITCH_ON_COLOR->value ? $siteConfiguration->value : null,
            'app_checkbox_fill_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_CHECKBOX_FILL_COLOR->value ? $siteConfiguration->value : null,
            'app_dashboard_section1_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_DASHBOARD_SECTION1_COLOR->value ? $siteConfiguration->value : null,
            'app_dashboard_section2_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_DASHBOARD_SECTION2_COLOR->value ? $siteConfiguration->value : null,
            'app_dashboard_section3_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_DASHBOARD_SECTION3_COLOR->value ? $siteConfiguration->value : null,
            'app_dashboard_section4_color' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_DASHBOARD_SECTION4_COLOR->value ? $siteConfiguration->value : null,
            'app_scaffold_background_color_first_gradient' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_FIRST_GRADIENT->value ? $siteConfiguration->value : null,
            'app_scaffold_background_color_second_gradient' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_SECOND_GRADIENT->value ? $siteConfiguration->value : null,
            'app_scaffold_background_color_third_gradient' => $siteConfiguration->type_id->value === SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_THIRD_GRADIENT->value ? $siteConfiguration->value : null,
        ];
    }
}
