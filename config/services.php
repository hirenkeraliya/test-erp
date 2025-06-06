<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'list_of_web_app_urls_and_keys' => env('LIST_OF_WEB_APP_URLS_AND_KEYS', ''),

    'sms_gateway' => [
        'enabled' => env('SMS_GATEWAY_ENABLED', true),
        'url' => env('SMS_GATEWAY_URL'),
        'username' => env('SMS_GATEWAY_USERNAME'),
        'password' => env('SMS_GATEWAY_PASSWORD'),
        'sender_id' => env('SMS_GATEWAY_SENDER_ID'),
    ],

    'celcom_sms' => [
        'enabled' => env('CELCOM_SMS_ENABLED', false),
        'url' => env('CELCOM_SMS_URL'),
        'username' => env('CELCOM_SMS_USERNAME'),
        'password' => env('CELCOM_SMS_PASSWORD'),
        'sender_id' => env('CELCOM_SMS_SENDER_ID'),
    ],

    'automation' => [
        'enabled' => env('AUTOMATION_ENABLED', false),
        'url' => env('AUTOMATION_URL'),
        'token' => env('AUTOMATION_TOKEN'),
    ],

    'pos_admin' => [
        'url' => env('POS_ADMIN_URL'),
        'company_id' => env('POS_ADMIN_COMPANY_ID'),
        'client_id' => env('POS_ADMIN_CLIENT_ID'),
        'client_secret' => env('POS_ADMIN_CLIENT_SECRET'),
    ],

    'firebase' => [
        'enabled' => env('FIREBASE_ENABLED', false),
        'type' => env('FIREBASE_TYPE'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'client_id' => env('FIREBASE_CLIENT_ID'),
        'auth_uri' => env('FIREBASE_AUTH_URI'),
        'token_uri' => env('FIREBASE_TOKEN_URI'),
        'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL'),
        'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
        'universe_domain' => env('FIREBASE_UNIVERSE_DOMAIN'),
    ],

    'retail_planning' => [
        'sso_urls_whitelist' => env('RETAIL_PLANNING_SSO_URLS_WHITELIST', ''),
        'is_enabled' => env('RETAIL_PLANNING_INTEGRATION_ENABLED', false)
    ],

    'pos_modules' => [
        'allow_pos_modules_zip' => env('ALLOW_POS_MODULES_ZIP', false),
    ],

    'max_upload_size' => env('MAX_UPLOAD_SIZE', 20480),

    'http_time_out' => env('HTTP_TIME_OUT', 1500),

    'exchanges_rates' => [
        'exchanges_rate_api_key' => env('EXCHANGE_RATE_API_KEY'),
        'exchanges_rate_api_url' => env('EXCHANGE_RATE_API_URL'),
    ],

    'share_sale_details_to_third_party' => [
        'share_sale_details_to_third_party_enabled' => env('SHARE_SALE_DETAILS_TO_THIRD_PARTY_ENABLED'),
        'share_sale_details_to_third_party_token' => env('SHARE_SALE_DETAILS_TO_THIRD_PARTY_TOKEN'),
        'share_sale_details_to_third_party_key' => env('SHARE_SALE_DETAILS_TO_THIRD_PARTY_KEY'),
        'share_sale_details_to_third_party_url' => env('SHARE_SALE_DETAILS_TO_THIRD_PARTY_URL'),
    ],

    'sftp' => [
        'ip_address' => env('SFTP_IP_ADDRESS'),
        'username' => env('SFTP_USERNAME'),
        'password' => env('SFTP_PASSWORD'),
        'port' => env('SFTP_PORT'),
        'path' => env('SFTP_PATH'),
    ],

    'azentio_integration' => [
        'from_date' => env('AZENTIO_INTEGRATION_FETCH_DATA_FROM_DATE'),
    ],

    'import_product_record' => [
        'create_with_category' => env('IMPORT_PRODUCT_RECORD_CREATE_WITH_CATEGORY', false),
    ],
];
