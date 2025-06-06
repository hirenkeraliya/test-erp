<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration;

use App\Domains\Media\MediaQueries;
use App\Domains\SiteConfiguration\DataObjects\SiteConfigurationData;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Models\SiteConfiguration;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SiteConfigurationQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return SiteConfiguration::query()
            ->select('id', 'type_id', 'value')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw(
                        'type_id',
                        SiteConfigurationTypes::getMatchingCases($filterData['search_text'])
                    );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getById(int $siteConfigurationId): SiteConfiguration
    {
        return SiteConfiguration::select('id', 'type_id', 'value')->findOrFail($siteConfigurationId);
    }

    public function update(SiteConfigurationData $siteConfigurationData, int $siteConfigurationId): void
    {
        $siteConfigurationRecord = collect($siteConfigurationData)->forget(
            [
                'theme_color',
                'favicon_icon',
                'login_page_logo',
                'login_page_tagline',
                'login_page_sub_tagline',
                'upload_navbar_logo',
                'default_company',
                'ecommerce_type',
                'app_theme_color',
                'app_button_text_color',
                'app_title_bar_color',
                'app_complete_text',
                'app_complete_text_background',
                'app_text_hint_color',
                'app_text_change_due',
                'app_all_text_color',
                'ecommerce_favicon',
                'ecommerce_company_name',
                'ecommerce_company_logo',
                'app_label_color',
                'app_button_background_color',
                'app_all_sub_tittle_text_color',
                'app_switch_on_color',
                'app_checkbox_fill_color',
                'app_dashboard_section1_color',
                'app_dashboard_section2_color',
                'app_dashboard_section3_color',
                'app_dashboard_section4_color',
                'app_scaffold_background_color_first_gradient',
                'app_scaffold_background_color_second_gradient',
                'app_scaffold_background_color_third_gradient',
            ]
        )->toArray();

        Cache::forget('site_configuration_theme');
        Cache::forget('site_configuration_login_page_logo');
        Cache::forget('site_configuration_fav_icon');
        Cache::forget('site_configuration_login_page_tagline');
        Cache::forget('site_configuration_login_page_sub_tagline');
        Cache::forget('site_configuration_navbar_logo');
        $siteConfiguration = $this->getById($siteConfigurationId);

        $siteConfigurationRecord['value'] = $this->getPreparedValue($siteConfigurationData, $siteConfiguration);
        $siteConfiguration->update($siteConfigurationRecord);
    }

    public function getAll(): Collection
    {
        $mediaQueries = resolve(MediaQueries::class);

        return SiteConfiguration::select('id', 'type_id', 'value')
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->get();
    }

    public function getEcommerceData(): Collection
    {
        $mediaQueries = resolve(MediaQueries::class);

        return SiteConfiguration::select('id', 'type_id', 'value')
            ->whereIn('type_id', [
                SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME->value,
                SiteConfigurationTypes::ECOMMERCE_FAVICON->value,
                SiteConfigurationTypes::ECOMMERCE_COMPANY_LOGO->value,
            ])
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->get();
    }

    public function getCachedThemeConfiguration(): ?SiteConfiguration
    {
        return Cache::remember(
            'site_configuration_theme',
            Carbon::now()->addWeek(),
            fn () => SiteConfiguration::select('id', 'type_id', 'value')
                ->where('type_id', SiteConfigurationTypes::THEME->value)
                ->first()
        );
    }

    public function getCachedFavIconConfiguration(): ?SiteConfiguration
    {
        return Cache::remember(
            'site_configuration_fav_icon',
            Carbon::now()->addWeek(),
            fn () => SiteConfiguration::select('id', 'type_id', 'value')
                ->where('type_id', SiteConfigurationTypes::FAVICON_ICON->value)
                ->first()
        );
    }

    public function getCachedLoginPageLogoConfiguration(): ?SiteConfiguration
    {
        return Cache::remember(
            'site_configuration_login_page_logo',
            Carbon::now()->addWeek(),
            fn () => SiteConfiguration::select('id', 'type_id', 'value')
                ->where('type_id', SiteConfigurationTypes::LOGIN_PAGE_LOGO->value)
                ->first()
        );
    }

    public function getCachedLoginPageTaglineConfiguration(): ?SiteConfiguration
    {
        return Cache::remember(
            'site_configuration_login_page_tagline',
            Carbon::now()->addWeek(),
            fn () => SiteConfiguration::select('id', 'type_id', 'value')
                ->where('type_id', SiteConfigurationTypes::LOGIN_PAGE_TAGLINE->value)
                ->first()
        );
    }

    public function getCachedLoginPageSubTaglineConfiguration(): ?SiteConfiguration
    {
        return Cache::remember(
            'site_configuration_login_page_sub_tagline',
            Carbon::now()->addWeek(),
            fn () => SiteConfiguration::select('id', 'type_id', 'value')
                ->where('type_id', SiteConfigurationTypes::LOGIN_PAGE_SUB_TAGLINE->value)
                ->first()
        );
    }

    public function getCachedNavbarLogoConfiguration(): ?SiteConfiguration
    {
        return Cache::remember(
            'site_configuration_navbar_logo',
            Carbon::now()->addWeek(),
            fn () => SiteConfiguration::select('id', 'type_id', 'value')
                ->where('type_id', SiteConfigurationTypes::NAVBAR_LOGO->value)
                ->first()
        );
    }

    public function getDefaultCompany(): ?SiteConfiguration
    {
        return SiteConfiguration::select('id', 'value')->where(
            'type_id',
            SiteConfigurationTypes::DEFAULT_COMPANY->value
        )
            ->first();
    }

    private function uploadIcon(
        SiteConfigurationData $siteConfigurationData,
        SiteConfiguration $siteConfiguration
    ): void {
        $uploadIcon = $siteConfigurationData->favicon_icon;

        if ($uploadIcon instanceof UploadedFile) {
            $siteConfiguration->addMedia($uploadIcon)->toMediaCollection('favicon_icon');
        }
    }

    private function uploadLogo(
        SiteConfigurationData $siteConfigurationData,
        SiteConfiguration $siteConfiguration
    ): void {
        $uploadLogo = $siteConfigurationData->login_page_logo;

        if ($uploadLogo instanceof UploadedFile) {
            $siteConfiguration->addMedia($uploadLogo)->toMediaCollection('login_page_logo');
        }
    }

    private function uploadNavbarLogo(
        SiteConfigurationData $siteConfigurationData,
        SiteConfiguration $siteConfiguration
    ): void {
        $uploadNavbarLogo = $siteConfigurationData->upload_navbar_logo;

        if ($uploadNavbarLogo instanceof UploadedFile) {
            $siteConfiguration->addMedia($uploadNavbarLogo)->toMediaCollection('navbar_logo');
        }
    }

    private function uploadEcommerceAssets(
        SiteConfigurationData $siteConfigurationData,
        SiteConfiguration $siteConfiguration
    ): void {
        if ($siteConfigurationData->ecommerce_favicon instanceof UploadedFile) {
            $siteConfiguration->addMedia($siteConfigurationData->ecommerce_favicon)
                ->toMediaCollection('ecommerce_favicon');
        }

        if ($siteConfigurationData->ecommerce_company_logo instanceof UploadedFile) {
            $siteConfiguration->addMedia($siteConfigurationData->ecommerce_company_logo)
                ->toMediaCollection('ecommerce_company_logo');
        }
    }

    private function getPreparedValue(
        SiteConfigurationData $siteConfigurationData,
        SiteConfiguration $siteConfiguration
    ): string {
        if ($siteConfigurationData->theme_color) {
            return $siteConfigurationData->theme_color;
        }

        if ($siteConfigurationData->login_page_tagline) {
            return $siteConfigurationData->login_page_tagline;
        }

        if ($siteConfigurationData->login_page_sub_tagline) {
            return $siteConfigurationData->login_page_sub_tagline;
        }

        if ($siteConfigurationData->default_company) {
            return (string) $siteConfigurationData->default_company;
        }

        if ($siteConfigurationData->ecommerce_type) {
            return (string) $siteConfigurationData->ecommerce_type;
        }

        if ($siteConfigurationData->favicon_icon instanceof UploadedFile) {
            $this->uploadIcon($siteConfigurationData, $siteConfiguration);
        }

        if ($siteConfigurationData->login_page_logo instanceof UploadedFile) {
            $this->uploadLogo($siteConfigurationData, $siteConfiguration);
        }

        if ($siteConfigurationData->upload_navbar_logo instanceof UploadedFile) {
            $this->uploadNavbarLogo($siteConfigurationData, $siteConfiguration);
        }

        if ($siteConfigurationData->app_theme_color) {
            return $siteConfigurationData->app_theme_color;
        }

        if ($siteConfigurationData->app_button_text_color) {
            return $siteConfigurationData->app_button_text_color;
        }

        if ($siteConfigurationData->app_title_bar_color) {
            return $siteConfigurationData->app_title_bar_color;
        }

        if ($siteConfigurationData->app_complete_text) {
            return $siteConfigurationData->app_complete_text;
        }

        if ($siteConfigurationData->app_complete_text_background) {
            return $siteConfigurationData->app_complete_text_background;
        }

        if ($siteConfigurationData->app_text_hint_color) {
            return $siteConfigurationData->app_text_hint_color;
        }

        if ($siteConfigurationData->app_text_change_due) {
            return $siteConfigurationData->app_text_change_due;
        }

        if ($siteConfigurationData->app_all_text_color) {
            return $siteConfigurationData->app_all_text_color;
        }

        if ($siteConfigurationData->ecommerce_company_name) {
            return $siteConfigurationData->ecommerce_company_name;
        }

        if ($siteConfigurationData->ecommerce_favicon instanceof UploadedFile ||
            $siteConfigurationData->ecommerce_company_logo instanceof UploadedFile) {
            $this->uploadEcommerceAssets($siteConfigurationData, $siteConfiguration);
        }

        if ($siteConfigurationData->app_label_color) {
            return $siteConfigurationData->app_label_color;
        }

        if ($siteConfigurationData->app_button_background_color) {
            return $siteConfigurationData->app_button_background_color;
        }

        if ($siteConfigurationData->app_all_sub_tittle_text_color) {
            return $siteConfigurationData->app_all_sub_tittle_text_color;
        }

        if ($siteConfigurationData->app_switch_on_color) {
            return $siteConfigurationData->app_switch_on_color;
        }

        if ($siteConfigurationData->app_checkbox_fill_color) {
            return $siteConfigurationData->app_checkbox_fill_color;
        }

        if ($siteConfigurationData->app_dashboard_section1_color) {
            return $siteConfigurationData->app_dashboard_section1_color;
        }

        if ($siteConfigurationData->app_dashboard_section2_color) {
            return $siteConfigurationData->app_dashboard_section2_color;
        }

        if ($siteConfigurationData->app_dashboard_section3_color) {
            return $siteConfigurationData->app_dashboard_section3_color;
        }

        if ($siteConfigurationData->app_dashboard_section4_color) {
            return $siteConfigurationData->app_dashboard_section4_color;
        }

        if ($siteConfigurationData->app_scaffold_background_color_first_gradient) {
            return $siteConfigurationData->app_scaffold_background_color_first_gradient;
        }

        if ($siteConfigurationData->app_scaffold_background_color_second_gradient) {
            return $siteConfigurationData->app_scaffold_background_color_second_gradient;
        }

        if ($siteConfigurationData->app_scaffold_background_color_third_gradient) {
            return $siteConfigurationData->app_scaffold_background_color_third_gradient;
        }

        return '';
    }
}
