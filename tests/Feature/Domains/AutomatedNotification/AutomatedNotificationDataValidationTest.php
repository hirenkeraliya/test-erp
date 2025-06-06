<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\DataObjects\AutomatedNotificationData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('automated notification validation works', function (): void {
    $request = new Request([
        'type_id' => '',
        'timeframe_type_id' => '',
        'week_days' => [],
        'month_dates' => [],
        'sent_notification' => true,
        'low_stock_alert_threshold' => '',
        'stores' => [],
        'products' => [],
    ]);

    AutomatedNotificationData::validate($request);
})->throws(ValidationException::class);
