<?php

declare(strict_types=1);

use App\Domains\SiteConfiguration\DataObjects\SiteConfigurationData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('site configuration validation works', function (): void {
    $request = new Request([
        'type_id' => '',
        'theme_color' => '',
        'favicon_icon' => '',
        'login_page_logo' => '',
        'login_page_tagline' => '',
        'login_page_sub_tagline' => '',
        'upload_navbar_logo' => '',
        'default_company' => '',
        'ecommerce_type' => '',
        'app_theme_color' => '',
        'app_button_text_color' => '',
        'app_title_bar_color' => '',
        'app_complete_text' => '',
        'app_complete_text_background' => '',
        'app_text_hint_color' => '',
        'app_text_change_due' => '',
        'app_all_text_color' => '',
        'ecommerce_favicon' => '',
        'ecommerce_company_name' => '',
        'ecommerce_company_logo' => '',
    ]);

    SiteConfigurationData::validate($request);
})->throws(ValidationException::class);
