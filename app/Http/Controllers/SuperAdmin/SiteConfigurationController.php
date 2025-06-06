<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Company\CompanyQueries;
use App\Domains\SiteConfiguration\DataObjects\SiteConfigurationData;
use App\Domains\SiteConfiguration\Enums\EcommerceType;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\Resources\SiteConfigurationEditResource;
use App\Domains\SiteConfiguration\Resources\SiteConfigurationResource;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteConfigurationController extends Controller
{
    public function __construct(
        protected SiteConfigurationQueries $siteConfigurationQueries
    ) {
    }

    public function fetch(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->siteConfigurationQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SiteConfigurationResource::collection($lengthAwarePaginator),
        ];
    }

    public function edit(int $siteConfigurationId): Response
    {
        $siteConfiguration = $this->siteConfigurationQueries->getById($siteConfigurationId);

        $typeIds = $this->siteConfigurationQueries->getAll()->pluck('type_id.value')->toArray();

        $siteConfigurationEnum = collect(SiteConfigurationTypes::formattedForSelection())
            ->filter(
                fn ($siteConfig): bool => ! (in_array(
                    $siteConfig['id'],
                    $typeIds,
                    true
                ) && $siteConfiguration->type_id->value !== $siteConfig['id'])
            )->values()->toArray();

        $companyQuery = resolve(CompanyQueries::class);
        $companies = $companyQuery->getWithIdAndName();

        return Inertia::render('site_configurations/Manage', [
            'siteConfiguration' => (new SiteConfigurationEditResource($siteConfiguration))->jsonSerialize(),
            'siteConfigurationEnum' => $siteConfigurationEnum,
            'siteConfigurationValues' => [
                'theme' => SiteConfigurationTypes::THEME->value,
                'favicon_icon' => SiteConfigurationTypes::FAVICON_ICON->value,
                'login_page_logo' => SiteConfigurationTypes::LOGIN_PAGE_LOGO->value,
                'login_page_tagline' => SiteConfigurationTypes::LOGIN_PAGE_TAGLINE->value,
                'login_page_sub_tagline' => SiteConfigurationTypes::LOGIN_PAGE_SUB_TAGLINE->value,
                'navbar_logo' => SiteConfigurationTypes::NAVBAR_LOGO->value,
                'default_company' => SiteConfigurationTypes::DEFAULT_COMPANY->value,
                'ecommerce_type' => SiteConfigurationTypes::ECOMMERCE_TYPE->value,
                'app_theme_color' => SiteConfigurationTypes::APP_THEME_COLOR->value,
                'app_button_text_color' => SiteConfigurationTypes::APP_BUTTON_TEXT_COLOR->value,
                'app_title_bar_color' => SiteConfigurationTypes::APP_TITLE_BAR_COLOR->value,
                'app_complete_text' => SiteConfigurationTypes::APP_COMPLETE_TEXT->value,
                'app_complete_text_background' => SiteConfigurationTypes::APP_COMPLETE_TEXT_BACKGROUND->value,
                'app_text_hint_color' => SiteConfigurationTypes::APP_TEXT_HINT_COLOR->value,
                'app_text_change_due' => SiteConfigurationTypes::APP_TEXT_CHANGE_DUE->value,
                'app_all_text_color' => SiteConfigurationTypes::APP_ALL_TEXT_COLOR->value,
                'ecommerce_favicon' => SiteConfigurationTypes::ECOMMERCE_FAVICON->value,
                'ecommerce_company_name' => SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME->value,
                'ecommerce_company_logo' => SiteConfigurationTypes::ECOMMERCE_COMPANY_LOGO->value,
                'app_label_color' => SiteConfigurationTypes::APP_LABEL_COLOR->value,
                'app_button_background_color' => SiteConfigurationTypes::APP_BUTTON_BACKGROUND_COLOR->value,
                'app_all_sub_tittle_text_color' => SiteConfigurationTypes::APP_ALL_SUB_TITTLE_TEXT_COLOR->value,
                'app_switch_on_color' => SiteConfigurationTypes::APP_SWITCH_ON_COLOR->value,
                'app_checkbox_fill_color' => SiteConfigurationTypes::APP_CHECKBOX_FILL_COLOR->value,
                'app_dashboard_section1_color' => SiteConfigurationTypes::APP_DASHBOARD_SECTION1_COLOR->value,
                'app_dashboard_section2_color' => SiteConfigurationTypes::APP_DASHBOARD_SECTION2_COLOR->value,
                'app_dashboard_section3_color' => SiteConfigurationTypes::APP_DASHBOARD_SECTION3_COLOR->value,
                'app_dashboard_section4_color' => SiteConfigurationTypes::APP_DASHBOARD_SECTION4_COLOR->value,
                'app_scaffold_background_color_first_gradient' => SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_FIRST_GRADIENT->value,
                'app_scaffold_background_color_second_gradient' => SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_SECOND_GRADIENT->value,
                'app_scaffold_background_color_third_gradient' => SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_THIRD_GRADIENT->value,
            ],
            'themeColors' => ThemeColors::formattedForSelection(),
            'ecommerceType' => EcommerceType::formattedForSelection(),
            'companies' => $companies,
        ]);
    }

    public function update(SiteConfigurationData $siteConfigurationData, int $siteConfigurationId): RedirectResponse
    {
        $this->siteConfigurationQueries->update($siteConfigurationData, $siteConfigurationId);

        return to_route('super_admin.site_configurations.index')->with(
            'success',
            'Site Configuration updated successfully.'
        );
    }
}
