<?php

use Freshbitsweb\LaravelLogEnhancer\LogEnhancer;
use Monolog\Handler\NullHandler;
use App\Logging\JsonFormatterWithExtraDetails;
use Monolog\Formatter\LineFormatter;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'pos_mismatches_log_channel' => env('POS_MISMATCHES_LOG_CHANNEL', 'pos_mismatches'),

    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'attachment' => false,
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'job_failure_slack' => [
            'driver' => 'slack',
            'url' => env('JOB_FAILURE_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'production_stack' => [
            'driver' => 'stack',
            'tap' => [LogEnhancer::class],
            'channels' => ['daily', 'sentry'],
            'ignore_exceptions' => false,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'sentry' => [
            'driver' => 'sentry',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'pos_negative_inventory' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/pos_negative_inventory/pos-negative-inventory.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'voucher_generation' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/voucher_generation/voucher-generation.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'update_points_and_total_sales' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/update_points_and_total_sales/update-points-and-total-sales.log'),
            'level' => 'debug',
            'days' => 0,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'new_member_benefits' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/new_member_benefits/new-member-benefits.log'),
            'level' => 'debug',
            'days' => 0,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'voucher_expiration' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/voucher_expiration/voucher-expiration.log'),
            'level' => 'debug',
            'days' => 0,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'old_data_migration' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/old_data_migration/old-data-migration.log'),
            'level' => 'debug',
            'days' => 0,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_api' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/pos_api/pos-api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'external_connection_api' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/external_connection_api/external-connection-api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'automatic_day_close' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/automatic_day_close/day_close.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'credit_note_expiration' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/credit_note_expiration/credit-note-expiration.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'loyalty_point_expiration' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/loyalty_point_expiration/loyalty-point-expiration.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_mismatches_production_stack' => [
            'driver' => 'stack',
            'channels' => ['pos_mismatches', 'pos_mismatches_slack', 'pos_application_mismatches_slack'],
            'ignore_exceptions' => false,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_mismatches' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/pos_mismatches/pos-mismatches.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_mismatches_slack' => [
            'driver' => 'slack',
            'url' => env('POS_MISMATCHES_SLACK_WEBHOOK_URL'),
            'username' => 'Mismatch observer',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_application_mismatches_slack' => [
            'driver' => 'slack',
            'url' => env('POS_APPLICATION_MISMATCHES_SLACK_WEBHOOK_URL'),
            'username' => 'Mismatch observer',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'promoter_commission_generation' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/promoter_commission_generation/promoter_commission_generation.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'sale_order_approve_job' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/sale_order_approve_job/sale_order_approve_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'gift_card_expiration' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/gift_card_expiration/gift_card-expiration.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'daily_sales_update' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/daily_sales_update/daily_sales_update.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'daily_store_wise_sales' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/daily_store_wise_sales/daily_store_wise_sales.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'sale_achieved_target_job' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/sale_achieved_target_job/sale-achieved-target-job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'store_manager_authorization_code' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/store_manager_authorization_code/store-manager-authorization-code.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'daily_top_ten_stores_sales' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/daily_top_ten_stores_sales/daily_top_ten_stores_sales.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'currency_rate_update_service' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/currency_rate_update_service/currency_rate_update_service.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'member_app' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/member_app/member-app.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'automation' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/member_app/member-app.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'products_data' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/products_data/products-data.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_modules' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/pos_modules/pos-modules-zip.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'pos_admin' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/pos_admin/pos-admin.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'notifications' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/notifications/notifications.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'export_report_job' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/export_report_job/export_report_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'ioi_city_mall' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/ioi_city_mall/ioi_city_mall.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'trx_mall' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/trx_mall/trx_mall.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'firebase_notification' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/firebase_notification/firebase_notification.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'average_transfer_day' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/average_transfer_day/average_transfer_day.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'e_commerce' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/e_commerce/e_commerce.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'external_product_create_job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/external_product_create_job/external_product_create_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'external_login' => [
            'driver' => 'daily',
            'path' => storage_path('logs/external_login/external_login.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'external_connection' => [
            'driver' => 'daily',
            'path' => storage_path('logs/external_connection/external_connection.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'create_product_from_external_product_job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/create_product_from_external_product_job/create_product_from_external_product_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'daily_total_sales_job' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/daily_total_sales_job/daily_total_sales_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'retail_planning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/retail_planning/retail_planning.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'request_logging' => [
            'driver' => 'daily',
            'path' => storage_path('logs/request_logging/request_logging.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'create_draft_product_transactions_job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/create_draft_product_transactions_job/create_draft_product_transactions_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'daily_top_twenty_report_update_job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/daily_top_twenty_report_update_job/daily_top_twenty_report_update_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'dream_price_product_and_store_duplication' => [
            'driver' => 'daily',
            'path' => storage_path('logs/dream_price_product_and_store_duplication/dream_price_product_and_store_duplication.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'product_ageing_table' => [
            'driver' => 'daily',
            'path' => storage_path('logs/product_ageing_table/product_ageing_table.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'product_channel_reference' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/product_channel_reference/product_channel_reference.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'temporary_data_migration_job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/temporary_data_migration_job/temporary_data_migration_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'category_channel_reference' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/category_channel_reference/category_channel_reference.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'external_categories' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/external_categories/external_categories.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'sale_channel_shipment' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/sale_channel_shipment/sale_channel_shipment.log'),
            'level' => 'debug',
            'days' => 0,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'ninja_van_service' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ninja_van_service/ninja_van_service.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'google_geocoding' => [
            'driver' => 'daily',
            'path' => storage_path('logs/google_geocoding/google_geocoding.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'order_integration_job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/order_integration_job/order_integration_job.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'online_order_status_update' => [
            'driver' => 'daily',
            'path' => storage_path('logs/online_order_status_update/online_order_status_update.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'master_product' => [
            'driver' => 'daily',
            'path' => storage_path('logs/master_product/master_product.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'store_day_close_export' => [
            'driver' => 'daily',
            'path' => storage_path('logs/store_day_close_export/store_day_close_export.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
        ],

        'product_channel_reference_categories' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/product_channel_reference_categories/product_channel_reference_categories.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

        'azentio_oneerp_integration' => [
            'driver' => env('ENABLE_JSON_LOG') == true ? 'single' : 'daily',
            'path' => env('ENABLE_JSON_LOG') == true ? storage_path('logs/laravel.log') : storage_path('logs/azentio_oneerp_integration/azentio_oneerp_integration.log'),
            'level' => 'debug',
            'days' => 0,
            'replace_placeholders' => true,
            'tap' => [JsonFormatterWithExtraDetails::class],
        ],

    ],
];
