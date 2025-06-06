<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Auth;

use App\Domains\Member\MemberQueries;
use App\Http\Controllers\Controller;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function tokenForMemberApplication(Request $request): array
    {
        $validateData = $request->validate([
            'mobile_number' => ['required', 'string', new MobileNumber()],
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByMobileNumber($validateData['mobile_number']);

        if (null === $member) {
            abort(412, 'Apologies, The member not valid');
        }

        $memberQueries->updateLastLoginTime($member);
        $token = $memberQueries->generateToken($member);

        return [
            'token' => $token,
        ];
    }
}
