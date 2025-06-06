<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\DataObjects;

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class AutomatedNotificationData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public int $type_id,
        public bool $sent_notification,
        public ?int $timeframe_type_id,
        public ?array $week_days,
        public ?array $month_dates,
        public ?int $low_stock_alert_threshold,
        public ?array $automated_email_recipients = null,
        public ?array $locations = null,
        public ?array $products = null,
        public ?array $product_ids = [],
        public ?array $product_location_ids = [],
        public ?UploadedFile $product_locations_file = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $mimes = 'mimes:xlsx, ods, xls';

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type_id' => ['required', 'integer', 'in:' . AutomatedNotificationTypes::getValues()],
            'sent_notification' => [
                'required_if:type_id,' . AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
                'required_if:type_id,' . AutomatedNotificationTypes::LOW_STOCK_LOCATION->value,
                'required_if:type_id,' . AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value,
                'boolean',
            ],
            'product_location_ids' => ['nullable', 'array'],
            'product_locations_file' => [
                'nullable',
                'file',
                $mimes,
                'required_with:product_location_ids',
                function ($attribute, $value, $fail) use ($request): void {
                    if ($request->hasFile('product_locations_file') && ! $request->product_location_ids) {
                        $fail('At least one product location must be selected if you want to upload a product file.');
                    }
                },
            ],
            'timeframe_type_id' => [
                'required_if:sent_notification,true',
                'required_if:type_id,' . AutomatedNotificationTypes::NO_STOCK->value,
                'required_if:type_id,' . AutomatedNotificationTypes::REQUEST_STOCK->value,
                'required_if:type_id,' . AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value,
                'nullable',
                'integer',
                'in:' . AutomatedNotificationTimeframeTypes::getValues(),
            ],
            'week_days' => [
                'required_if:timeframe_type_id,' . AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
                'nullable',
                'array',
            ],
            'week_days.*' => ['required', 'integer'],
            'month_dates' => [
                'required_if:timeframe_type_id,' . AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
                'nullable',
                'array',
            ],
            'month_dates.*' => ['required', 'integer'],
            'low_stock_alert_threshold' => [
                'required_if:type_id,' . AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
                'nullable',
                'integer',
                'min:1',
            ],
            'automated_email_recipients' => ['nullable', 'array'],
            'automated_email_recipients.*' => ['required', 'integer'],
            'locations' => ['nullable', 'array'],
            'locations.*.id' => ['required', 'integer'],
            'locations.*.low_stock_alert_threshold' => ['required', 'integer'],
            'products' => ['nullable', 'array'],
            'products.*.id' => ['required', 'integer'],
            'products.*.location_id' => ['required', 'integer'],
            'products.*.low_stock_alert_threshold' => ['required', 'integer'],
        ];
    }
}
