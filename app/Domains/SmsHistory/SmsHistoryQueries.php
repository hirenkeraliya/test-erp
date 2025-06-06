<?php

declare(strict_types=1);

namespace App\Domains\SmsHistory;

use App\Domains\SmsHistory\Enums\SmsHistoryStatusTypes;
use App\Models\SmsHistory;
use Carbon\Carbon;

class SmsHistoryQueries
{
    public function addNew(string $mobileNumber, string $message): int
    {
        return SmsHistory::create([
            'mobile_number' => $mobileNumber,
            'message' => $message,
            'sending_date' => Carbon::now(),
        ])->id;
    }

    public function updateById(array $responseData, int $smsHistoryId): void
    {
        $smsHistory = SmsHistory::findOrFail($smsHistoryId);

        $data = array_key_exists('response_data', $responseData) ? $responseData['response_data'] : [];

        $smsHistory->status = SmsHistoryStatusTypes::SUCCESS->value;
        $smsHistory->response_data = $data;
        $smsHistory->save();
    }
}
