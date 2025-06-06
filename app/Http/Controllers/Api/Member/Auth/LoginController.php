<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member\Auth;

use App\Domains\Common\Enums\LogoutEnums;
use App\Domains\Member\Enums\StaticMembers;
use App\Domains\Member\Jobs\SendConfirmationEmailJob;
use App\Domains\Member\Jobs\SendConfirmationSmsJob;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\MemberAppListApiResource;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SmsHistory\SmsHistoryQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\SaleChannel;
use App\Rules\MobileNumber;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class LoginController extends Controller
{
    /**
     * @return array<string, bool>|array<string, string>
     */
    public function sendOtp(Request $request): array
    {
        $validateData = $request->validate([
            'mobile_number' => ['sometimes', 'nullable', new MobileNumber()],
            'email' => ['sometimes', 'nullable', 'email'],
        ]);

        $validationResult = $this->validateContactInformation($validateData);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $memberQueries = resolve(MemberQueries::class);

        if (array_key_exists('email', $validateData) && null !== $validateData['email']) {
            $checkEmailExists = $memberQueries->checkEmailExists($validateData['email']);

            if (! $checkEmailExists) {
                return [
                    'message' => 'Apologies, but the member does not exist.',
                    'status' => false,
                ];
            }

            $checkCompanyDelete = $memberQueries->checkCompanyDelete('email', $validateData['email']);

            if (! $checkCompanyDelete) {
                return [
                    'message' => 'Your account is inactive. Please contact Admin.',
                    'status' => false,
                ];
            }
        }

        if (
            array_key_exists('mobile_number', $validateData) &&
            null !== $validateData['mobile_number'] &&
            $validateData['mobile_number'] !== StaticMembers::STATIC_MEMBER->value
        ) {
            $checkMobileNumberExists = $memberQueries->checkMobileNumberExists($validateData['mobile_number']);

            if (! $checkMobileNumberExists) {
                return [
                    'message' => 'Apologies, but the member does not exist.',
                    'status' => false,
                ];
            }

            $checkCompanyDelete = $memberQueries->checkCompanyDelete('mobile_number', $validateData['mobile_number']);

            if (! $checkCompanyDelete) {
                return [
                    'message' => 'Your account is inactive. Please contact Admin.',
                    'status' => false,
                ];
            }
        }

        if (array_key_exists(
            'mobile_number',
            $validateData
        ) && $validateData['mobile_number'] === StaticMembers::STATIC_MEMBER->value) {
            return $this->generateStaticOTPForTesting($validateData['mobile_number']);
        }

        if (config('app.env') === 'staging') {
            if (array_key_exists('mobile_number', $validateData) && null !== $validateData['mobile_number']) {
                return $this->generateStaticOTPForTesting($validateData['mobile_number']);
            }

            if (array_key_exists('email', $validateData) && null !== $validateData['email']) {
                return $this->generateStaticEmailOTPForTesting($validateData['email']);
            }
        }

        DB::beginTransaction();

        try {
            $smsHistoryQueries = resolve(SmsHistoryQueries::class);

            $otp = (string) random_int(1000, 9999);

            $message = config('app.name') . ': Your OTP is ' . $otp . '. Valid for only 10 minutes.';
            $smsHistoryId = null;

            if (array_key_exists('email', $validateData) && null !== $validateData['email']) {
                $memberQueries->updateOtpBasedOnEmail($validateData['email'], $otp);
                $smsHistoryId = $smsHistoryQueries->addNew($validateData['email'], $message);
            }

            if (array_key_exists('mobile_number', $validateData) && null !== $validateData['mobile_number']) {
                $memberQueries->updateOtpBasedOnMobileNumber($validateData['mobile_number'], $otp);
                $smsHistoryId = $smsHistoryQueries->addNew($validateData['mobile_number'], $message);
            }

            DB::commit();

            if (array_key_exists('email', $validateData) && null !== $validateData['email']) {
                SendConfirmationEmailJob::dispatch($validateData['email'], $message)->onQueue(
                    config('horizon.default_queue_name')
                );

                return [
                    'message' => 'We have sent a One-Time Password (OTP) to your registered email address. Please enter the OTP to proceed.',
                    'status' => true,
                ];
            }

            if (array_key_exists(
                'mobile_number',
                $validateData
            ) && null !== $validateData['mobile_number'] && null !== $smsHistoryId) {
                SendConfirmationSmsJob::dispatch($validateData['mobile_number'], $message, $smsHistoryId)->onQueue(
                    config('horizon.default_queue_name')
                );

                return [
                    'message' => 'We have sent a One-Time Password (OTP) to your registered phone number. Please enter the OTP to proceed.',
                    'status' => true,
                ];
            }

            return [
                'message' => 'We have sent a One-Time Password (OTP) to your registered phone number or email. Please enter the OTP to proceed.',
                'status' => true,
            ];
        } catch (Throwable $throwable) {
            Log::error('Member-App', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollback();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function validateOtp(Request $request): array
    {
        $validateData = $request->validate([
            'mobile_number' => ['sometimes', 'nullable', new MobileNumber()],
            'email' => ['sometimes', 'nullable', 'email'],
            'otp' => ['required', 'string', 'max:4'],
        ]);

        $validationResult = $this->validateContactInformation($validateData);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $member = null;
        $memberQueries = resolve(MemberQueries::class);

        if (array_key_exists('email', $validateData) && null !== $validateData['email']) {
            $member = $memberQueries->validateEmailOtp($validateData, $validateData['email']);
        }

        if (array_key_exists('mobile_number', $validateData) && null !== $validateData['mobile_number']) {
            $member = $memberQueries->validateMobileOtp($validateData, $validateData['mobile_number']);
        }

        if (null === $member) {
            return [
                'message' => 'Apologies, but the OTP you entered is incorrect. Please try again.',
                'status' => false,
            ];
        }

        $currentDateTime = Carbon::now();
        $memberExpiryDate = $member->otp_expire_date;

        if ($currentDateTime->diffInMinutes($memberExpiryDate) <= 10) {
            $memberQueries->updateLastLoginTime($member);
            $token = $memberQueries->generateToken($member);

            return [
                'message' => 'OTP verified. You can now enjoy the benefits of your ' . config(
                    'app.name'
                ) . ' Membership.',
                'token' => $token,
                'status' => true,
                'member' => new MemberAppListApiResource($member),
            ];
        }

        return [
            'message' => 'Apologies, but the OTP you entered is incorrect. Please try again..',
            'status' => false,
        ];
    }

    public function generateStaticOTPForTesting(string $mobileNumber): array
    {
        $memberQueries = resolve(MemberQueries::class);

        $otp = '9999';

        $memberQueries->updateOtpBasedOnMobileNumber($mobileNumber, $otp);

        return [
            'message' => 'We have sent a One-Time Password (OTP) to your registered phone number. Please enter the OTP to proceed.',
            'status' => true,
        ];
    }

    public function generateStaticEmailOTPForTesting(string $email): array
    {
        $memberQueries = resolve(MemberQueries::class);

        $otp = '9999';

        $memberQueries->updateOtpBasedOnEmail($email, $otp);

        return [
            'message' => 'We have sent a One-Time Password (OTP) to your registered email address. Please enter the OTP to proceed.',
            'status' => true,
        ];
    }

    public function logout(Request $request): void
    {
        $validatedData = $request->validate([
            'logout_from_all' => ['nullable', 'in:' . LogoutEnums::getValues()],
        ], [
            'logout_from_all.in' => 'The logout from all field must be either 0 or 1.',
        ]);

        $logoutFromAll = $validatedData['logout_from_all'] ?? null;

        /** @var Member $member */
        $member = $request->user();

        if ((int) $logoutFromAll === LogoutEnums::LOGOUT_FROM_ALL->value) {
            $member->tokens()->delete();
        } else {
            /** @var PersonalAccessToken $currentAccessToken */
            $currentAccessToken = $member->currentAccessToken();
            $tokenId = $currentAccessToken->id;
            $member->revokeCurrentToken($tokenId);
        }
    }

    public function getEcommerceToken(Request $request): array|JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();
        abort_unless($member instanceof Member, 401, 'You are not authenticated.');

        $saleChannel = $this->getSaleChannel();
        if (! $saleChannel['status']) {
            return response()->json([
                'message' => $saleChannel['message'],
            ], 412);
        }

        $ecommerceTokenResponse = $this->fetchEcommerceToken($saleChannel['data'], $member);

        if (! $ecommerceTokenResponse['status']) {
            return response()->json([
                'message' => $ecommerceTokenResponse['message'],
            ], 412);
        }

        $message = $ecommerceTokenResponse['message'] ?? 'Login successful';

        return response()->json([
            'message' => $message,
            'token' => $ecommerceTokenResponse['access_token'] ?? null,
        ], 200);
    }

    private function getSaleChannel(): array
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getECommerceSaleChannel();

        if (null === $saleChannel) {
            return [
                'message' => 'Apologies, but the sale channel is not configured.',
                'status' => false,
            ];
        }

        return [
            'status' => true,
            'data' => $saleChannel,
        ];
    }

    private function fetchEcommerceToken(SaleChannel $saleChannel, Member $member): array
    {
        $url = rtrim($saleChannel->url, '/');
        if (! str_ends_with($url, '/api/m-com/login')) {
            $url .= '/api/m-com/login';
        }

        $memberData = [
            'title_id' => $member->title_id ?? null,
            'first_name' => $member->first_name ?? null,
            'last_name' => $member->last_name ?? null,
            'gender_id' => $member->gender_id ?? null,
            'date_of_birth' => $member->date_of_birth ?? null,
            'mobile_number' => $member->mobile_number ?? null,
            'email' => $member->email ?? null,
            'notes' => $member->notes ?? null,
            'card_number' => $member->card_number ?? null,
            'status' => $member->status ?? true,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $saleChannel->secret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($url, [
            'member' => $memberData,
        ]);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            Log::channel('e_commerce')->info('Response: Member Login in E-Commerce', [
                'response' => $responseData,
            ]);

            if (isset($responseData['access_token'])) {
                return [
                    'message' => $responseData['message'] ?? 'Login successful',
                    'status' => true,
                    'access_token' => $responseData['access_token'],
                ];
            }
        }

        Log::channel('e_commerce')->info('Response: Error on Member Login in E-Commerce', [
            'status_code' => $response->status(),
            'response_body' => $response->body() ?: 'No response body provided',
            'request_data' => [
                'saleChannel_id' => $saleChannel->getKey(),
            ],
        ]);

        return [
            'message' => $response->json(
                'message'
            ) ?? 'There was an error while trying to get the ecommerce token. Please try again later.',
            'status' => false,
        ];
    }

    private function validateContactInformation(array $validateData): array
    {
        if (! array_key_exists('mobile_number', $validateData) && ! array_key_exists('email', $validateData)) {
            return [
                'message' => 'Apologies, but we require either a mobile number or email address to proceed. Please provide one of these to continue.',
                'status' => false,
            ];
        }

        if (array_key_exists('mobile_number', $validateData) && array_key_exists('email', $validateData)) {
            return [
                'message' => 'Apologies, but in order to proceed, we require either a mobile number or an email address. Please provide one of these to continue.',
                'status' => false,
            ];
        }

        return [
            'status' => true,
        ];
    }
}
