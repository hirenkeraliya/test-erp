<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SiteConfigurationTypes: int
{
    use PrepareEnumDataMethods;

    case THEME = 1;
    case FAVICON_ICON = 2;
    case LOGIN_PAGE_LOGO = 3;
    case LOGIN_PAGE_TAGLINE = 4;
    case LOGIN_PAGE_SUB_TAGLINE = 5;
    case NAVBAR_LOGO = 6;
    case DEFAULT_COMPANY = 7;
    case ECOMMERCE_TYPE = 8;
    case APP_THEME_COLOR = 9;
    case APP_BUTTON_TEXT_COLOR = 10;
    case APP_TITLE_BAR_COLOR = 11;
    case APP_COMPLETE_TEXT = 12;
    case APP_COMPLETE_TEXT_BACKGROUND = 13;
    case APP_TEXT_HINT_COLOR = 14;
    case APP_TEXT_CHANGE_DUE = 15;
    case APP_ALL_TEXT_COLOR = 16;
    case ECOMMERCE_FAVICON = 17;
    case ECOMMERCE_COMPANY_NAME = 18;
    case ECOMMERCE_COMPANY_LOGO = 19;
    case APP_LABEL_COLOR = 20;
    case APP_BUTTON_BACKGROUND_COLOR = 21;
    case APP_ALL_SUB_TITTLE_TEXT_COLOR = 22;
    case APP_SWITCH_ON_COLOR = 23;
    case APP_CHECKBOX_FILL_COLOR = 24;
    case APP_DASHBOARD_SECTION1_COLOR = 25;
    case APP_DASHBOARD_SECTION2_COLOR = 26;
    case APP_DASHBOARD_SECTION3_COLOR = 27;
    case APP_DASHBOARD_SECTION4_COLOR = 28;
    case APP_SCAFFOLD_BACKGROUND_COLOR_FIRST_GRADIENT = 29;
    case APP_SCAFFOLD_BACKGROUND_COLOR_SECOND_GRADIENT = 30;
    case APP_SCAFFOLD_BACKGROUND_COLOR_THIRD_GRADIENT = 31;
}
