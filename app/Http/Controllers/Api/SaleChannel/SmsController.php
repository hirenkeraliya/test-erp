<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel;

use App\Http\Controllers\Controller;
use App\Rules\MobileNumber;
use App\Services\CelcomSmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function sendMessage(Request $request): ?array
    {
        if (! $request->user()) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'mobile_number' => ['required', new MobileNumber()],
            'message' => ['required', 'string'],
        ]);

        $celcomService = resolve(CelcomSmsService::class);
        $response = $celcomService->sendSms($validatedData['mobile_number'], $validatedData['message']);

        return $response['response_data'];
    }
}
