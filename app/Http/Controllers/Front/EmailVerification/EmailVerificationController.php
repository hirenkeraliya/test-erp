<?php

namespace App\Http\Controllers\Front\EmailVerification;

use App\Domains\Member\MemberQueries;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Region;
use App\Models\SiteConfiguration;
use App\Models\SuperAdmin;
use App\Models\Vendor;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    public function verify(string $token): View
    {
        try {
            // Decrypt token and decode JSON
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (Exception) {
            abort(403, 'Invalid or expired verification link.');
        }

        if (
            ! isset($data['model']) || ! array_key_exists('model', $data) ||
            ! isset($data['id']) || ! array_key_exists('id', $data) ||
            ! isset($data['hash']) || ! array_key_exists('hash', $data)
        ) {
            abort(403, 'Invalid verification data.');
        }

        /** @var Employee|Member|Vendor|Region|Location|Company|SuperAdmin|EmailRecipient $model */
        $model = $data['model'];

        /** @var Employee|Member|Vendor|Region|Location|Company|SuperAdmin|EmailRecipient $entity */
        $entity = $model::findOrFail($data['id']);

        /** @var string $email */
        $email = $entity->email;

        if (sha1($email) !== $data['hash']) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $entity->markEmailAsVerified();

            if ($entity instanceof Employee) {
                $memberQueries = resolve(MemberQueries::class);
                $member = $memberQueries->getMemberByEmployeeId($entity->id, $entity->company_id);
                if ($member) {
                    $member->updateQuietly([
                        'is_email_verified' => true,
                    ]);
                }
            }

            $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

            $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

            $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
                $getSiteConfigurationTheme->value
            ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

            DB::commit();

            return view('front/email_verify/email_verify', [
                'themeColor' => $themeColor,
            ]);
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Email-Verification', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }
}
