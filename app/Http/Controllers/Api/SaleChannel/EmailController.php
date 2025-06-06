<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel;

use App\Domains\Member\Jobs\SendConfirmationEmailJob;
use App\Domains\Member\MemberQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function sendEmailOtp(Request $request): void
    {
        if (! $request->user()) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'email' => ['required', 'email'],
            'message' => ['required', 'string'],
        ]);

        $memberQueries = resolve(MemberQueries::class);
        if (! $memberQueries->checkEmailExists($validatedData['email'])) {
            abort(404, 'Member not found.');
        }

        SendConfirmationEmailJob::dispatch($validatedData['email'], $validatedData['message'])
            ->onQueue(config('horizon.default_queue_name'));
    }
}
