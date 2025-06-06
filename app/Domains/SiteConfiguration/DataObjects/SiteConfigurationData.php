<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration\DataObjects;

use App\Domains\SiteConfiguration\Enums\EcommerceType;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class SiteConfigurationData extends Data
{
    public function __construct(
        public int $type_id,
        public ?string $theme_color,
        public ?UploadedFile $favicon_icon,
        public ?UploadedFile $login_page_logo,
        public ?string $login_page_tagline,
        public ?string $login_page_sub_tagline,
        public ?UploadedFile $upload_navbar_logo,
        public ?int $default_company,
        public ?int $ecommerce_type,
        public ?string $app_theme_color,
        public ?string $app_button_text_color,
        public ?string $app_title_bar_color,
        public ?string $app_complete_text,
        public ?string $app_complete_text_background,
        public ?string $app_text_hint_color,
        public ?string $app_text_change_due,
        public ?string $app_all_text_color,
        public ?string $app_label_color,
        public ?string $app_button_background_color,
        public ?string $app_all_sub_tittle_text_color,
        public ?string $app_switch_on_color,
        public ?string $app_checkbox_fill_color,
        public ?string $app_dashboard_section1_color,
        public ?string $app_dashboard_section2_color,
        public ?string $app_dashboard_section3_color,
        public ?string $app_dashboard_section4_color,
        public ?string $app_scaffold_background_color_first_gradient,
        public ?string $app_scaffold_background_color_second_gradient,
        public ?string $app_scaffold_background_color_third_gradient,
        public ?UploadedFile $ecommerce_favicon,
        public ?string $ecommerce_company_name,
        public ?UploadedFile $ecommerce_company_logo,
    ) {
    }

    public static function rules(): array
    {
        return [
            'type_id' => ['required', 'in:' . SiteConfigurationTypes::getValues()],

            'theme_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::THEME->value,
                'nullable',
                'in:' . ThemeColors::getValues(),
            ],

            'favicon_icon' => [
                'required_if:type_id,' . SiteConfigurationTypes::FAVICON_ICON->value,
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(50)->maxHeight(50)),
                'max:' . config('services.max_upload_size'),
            ],

            'ecommerce_favicon' => [
                'required_if:type_id,' . SiteConfigurationTypes::ECOMMERCE_FAVICON->value,
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(45)->maxHeight(40)),
                'max:' . config('services.max_upload_size'),
            ],

            'ecommerce_company_name' => [
                'required_if:type_id,' . SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME->value,
                'nullable',
                'string',
            ],

            'ecommerce_company_logo' => [
                'required_if:type_id,' . SiteConfigurationTypes::ECOMMERCE_COMPANY_LOGO->value,
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(204)->maxHeight(40)),
                'max:' . config('services.max_upload_size'),
            ],

            'login_page_logo' => [
                'required_if:type_id,' . SiteConfigurationTypes::LOGIN_PAGE_LOGO->value,
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(500)->maxHeight(500)),
                'max:' . config('services.max_upload_size'),
            ],

            'login_page_tagline' => [
                'required_if:type_id,' . SiteConfigurationTypes::LOGIN_PAGE_TAGLINE->value,
                'nullable',
                'string',
            ],

            'login_page_sub_tagline' => [
                'required_if:type_id,' . SiteConfigurationTypes::LOGIN_PAGE_SUB_TAGLINE->value,
                'nullable',
                'string',
            ],

            'upload_navbar_logo' => [
                'required_if:type_id,' . SiteConfigurationTypes::NAVBAR_LOGO->value,
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(200)->maxHeight(200)),
                'max:' . config('services.max_upload_size'),
            ],

            'default_company' => [
                'required_if:type_id,' . SiteConfigurationTypes::DEFAULT_COMPANY->value,
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'ecommerce_type' => [
                'required_if:type_id,' . SiteConfigurationTypes::ECOMMERCE_TYPE->value,
                'nullable',
                'in:' . EcommerceType::getValues(),
            ],
            'app_theme_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_THEME_COLOR->value,
                'nullable',
                'string',
            ],
            'app_button_text_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_BUTTON_TEXT_COLOR->value,
                'nullable',
                'string',
            ],
            'app_title_bar_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_TITLE_BAR_COLOR->value,
                'nullable',
                'string',
            ],
            'app_complete_text' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_COMPLETE_TEXT->value,
                'nullable',
                'string',
            ],
            'app_complete_text_background' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_COMPLETE_TEXT_BACKGROUND->value,
                'nullable',
                'string',
            ],
            'app_text_hint_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_TEXT_HINT_COLOR->value,
                'nullable',
                'string',
            ],
            'app_text_change_due' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_TEXT_CHANGE_DUE->value,
                'nullable',
                'string',
            ],
            'app_all_text_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_ALL_TEXT_COLOR->value,
                'nullable',
                'string',
            ],
            'app_label_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_LABEL_COLOR->value,
                'nullable',
                'string',
            ],
            'app_button_background_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_BUTTON_BACKGROUND_COLOR->value,
                'nullable',
                'string',
            ],
            'app_all_sub_tittle_text_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_ALL_SUB_TITTLE_TEXT_COLOR->value,
                'nullable',
                'string',
            ],
            'app_switch_on_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_SWITCH_ON_COLOR->value,
                'nullable',
                'string',
            ],
            'app_checkbox_fill_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_CHECKBOX_FILL_COLOR->value,
                'nullable',
                'string',
            ],
            'app_dashboard_section1_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_DASHBOARD_SECTION1_COLOR->value,
                'nullable',
                'string',
            ],
            'app_dashboard_section2_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_DASHBOARD_SECTION2_COLOR->value,
                'nullable',
                'string',
            ],
            'app_dashboard_section3_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_DASHBOARD_SECTION3_COLOR->value,
                'nullable',
                'string',
            ],
            'app_dashboard_section4_color' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_DASHBOARD_SECTION4_COLOR->value,
                'nullable',
                'string',
            ],
            'app_scaffold_background_color_first_gradient' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_FIRST_GRADIENT->value,
                'nullable',
                'string',
            ],
            'app_scaffold_background_color_second_gradient' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_SECOND_GRADIENT->value,
                'nullable',
                'string',
            ],
            'app_scaffold_background_color_third_gradient' => [
                'required_if:type_id,' . SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_THIRD_GRADIENT->value,
                'nullable',
                'string',
            ],
        ];
    }
}
