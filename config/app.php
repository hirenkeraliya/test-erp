<?php

use App\CommonFunctions;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'email' => env('APP_CUSTOMER_CONTACT_EMAIL', 'hello@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    'site_env' => env('SITE_ENV', 'production'),

    'validate_mobile_number' => env('VALIDATE_MOBILE_NUMBER', true),

    'currency_symbol' => env('CURRENCY_SYMBOL', 'RM'),

    'update_unit_of_measure' => env('UPDATE_UNIT_OF_MEASURE', false),

    'product_variant' => env('PRODUCT_VARIANT', false),

    'allow_smart_transfer' => env('ALLOW_SMART_TRANSFER', false),

    'barcode_count_threshold_for_async_print' => env('BARCODE_COUNT_THRESHOLD_FOR_ASYNC_PRINT', 1000),

    'ioi_city_mall_sales_file_notification_email' => env('IOI_CITY_MALL_SALES_FILE_NOTIFICATION_EMAIL'),

    'allow_different_color_in_chart' => env('ALLOW_DIFFERENT_COLOR_IN_CHART'),

    'prevent_backend_access' => env('PREVENT_BACKEND_ACCESS', false),

    'developer_email' => env('DEVELOPER_EMAIL', 'test@gmail.com'),

    'demand_forecasting_dashboard_visibility' => env('DEMAND_FORECASTING_DASHBOARD_VISIBILITY', false),
    'loyalty_campaign_configurations_visibility' => env('LOYALTY_CAMPAIGN_CONFIGURATIONS_VISIBILITY', false),
    'genuine_official_online_store_link' => env('GENUINE_OFFICIAL_ONLINE_STORE_LINK'),

    'enable_json_log' => env('ENABLE_JSON_LOG', false),

    'location_name_code_limit' => env('LOCATION_NAME_CODE_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'site_identifier_key' => env('SITE_IDENTIFIER_KEY', Str::random(10)),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Asia/Kuala_Lumpur',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Webklex\PDFMerger\Providers\PDFMergerServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Mews\Captcha\CaptchaServiceProvider::class,
        App\Providers\HorizonServiceProvider::class,
        Spatie\Permission\PermissionServiceProvider::class,
        Jorenvh\Share\Providers\ShareServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'CommonFunctions' => CommonFunctions::class,
        'Captcha' => Mews\Captcha\Facades\Captcha::class,
        'Share' => Jorenvh\Share\ShareFacade::class,
    ])->toArray(),

    'terms_and_condition_page_url' => env('TERMS_AND_CONDITION_PAGE_URL'),

    'pdp_notice_url' => env('PDP_NOTICE_URL'),

    // Latest version @ https://regex101.com/r/iOkMUr/1
    // Notes @ https://www.notion.so/Members-77d17da2af664ecbad05531ed8210504?pvs=4#ad78765d785c4ab88645cf0b59edf54e
    'mobile_number_regex' => env('MOBILE_NUMBER_REGEX', '#^(601((1\d{8})|([02-46-9]{1}\d{7})))|(65[689]\d{7})|(673\d{7})|(66\d{9,10})|(63\d{10})|(62\d{10,12})|(44\d{10})|(971\d{9})|(61\d{10})|(1\d{10})|(64\d{9})$#'),

    'api_threshold_rate_limit_per_minute' => 1500,
    'web_threshold_rate_limit_per_minute' => env('WEB_THRESHOLD_RATE_LIMIT_PER_MINUTE', 60),
    'excel' => [
        'export' => [
            'job_limit' => env('EXPORT_EXCEL_LIMIT', 15000),
            'initial_row_limit' => env('EXPORT_EXCEL_INITIAL_ROW_LIMIT', 1000),
            'decrement_percentage' => env('EXPORT_EXCEL_DECREMENT_PERCENTAGE', 80),
            'increment_percentage' => env('EXPORT_EXCEL_INCREMENT_PERCENTAGE', 10),
            'job_coverage_percentage' => env('EXPORT_EXCEL_JOB_COVERAGE_PERCENTAGE', 80),
        ]
    ],

    'warn_connection_count' => env('WARN_CONNECTION_COUNT', 20),
    'fail_connection_count' => env('FAIL_CONNECTION_COUNT', 30),
    'redis_memory_usage_check' => env('REDIS_MEMORY_USAGE_CHECK', 2048),
    'database_size_check' => env('DATABASE_SIZE_CHECK', 130),
    'geo_code_api_key' => env('GEO_CODE_API_KEY', null),
];
