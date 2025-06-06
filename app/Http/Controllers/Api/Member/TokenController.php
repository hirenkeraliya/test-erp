<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Member\MemberQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function setFcmToken(Request $request): array
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        /** @var Member $member */
        $member = $request->user();

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updateFcmToken($request->token, $member->id, $member->company_id);

        return [
            'message' => 'Fcm Token Set Successfully',
        ];
    }
}
