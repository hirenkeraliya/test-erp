<?php

declare(strict_types=1);

use App\Domains\Member\Jobs\SendConfirmationEmailJob;
use App\Domains\Member\Jobs\SendConfirmationSmsJob;
use App\Domains\Member\MemberQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SmsHistory\SmsHistoryQueries;
use App\Http\Controllers\Api\Member\Auth\LoginController;
use App\Models\Member;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('member user can receive the OTP if member exists using mobile', function (): void {
    Queue::fake();

    $request = new Request([
        'mobile_number' => '601112145678',
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'mobile_number' => '601112145678',
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('checkMobileNumberExists')
            ->once()
            ->with($member->mobile_number)
            ->andReturn(true);
        $mock->shouldReceive('updateOtpBasedOnMobileNumber')
            ->once();

        $mock->shouldReceive('checkCompanyDelete')
            ->once()
            ->andReturn(true);
    });

    $this->mock(SmsHistoryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(1);
    });

    $memberLoginController = new LoginController();
    $response = $memberLoginController->sendOtp($request);

    Queue::assertPushed(SendConfirmationSmsJob::class);

    expect($response)
        ->toHaveKey('message');
});

test('member user can receive the Email OTP if member exists', function (): void {
    Queue::fake();

    $request = new Request([
        'email' => 'test@test.com',
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'mobile_number' => '601112145678',
        'email' => 'test@test.com',
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('checkEmailExists')
            ->once()
            ->with($member->email)
            ->andReturn(true);

        $mock->shouldReceive('updateOtpBasedOnEmail')
            ->once();

        $mock->shouldReceive('checkCompanyDelete')
            ->once()
            ->andReturn(true);
    });

    $this->mock(SmsHistoryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(1);
    });

    $memberLoginController = new LoginController();
    $response = $memberLoginController->sendOtp($request);

    Queue::assertPushed(SendConfirmationEmailJob::class);

    expect($response)
        ->toHaveKey('message');
});

test('Member can successfully validate the OTP using the correct OTP', function (): void {
    $request = new Request([
        'mobile_number' => '601112145678',
        'otp' => '1111',
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'mobile_number' => '601112145678',
        'otp' => '1111',
        'otp_expire_date' => Carbon::now()->addMinutes(10),
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($request, $member): void {
        $mock->shouldReceive('validateMobileOtp')
            ->once()
            ->with($request->toArray(), $member->mobile_number)
            ->andReturn($member);
        $mock->shouldReceive('updateLastLoginTime')
            ->once();
        $mock->shouldReceive('generateToken')
            ->once();
    });

    $memberLoginController = new LoginController();
    $response = $memberLoginController->validateOtp($request);

    expect($response)
        ->toHaveKey(
            'message',
            'OTP verified. You can now enjoy the benefits of your ' . config('app.name') . ' Membership.'
        )
        ->toHaveKey('token');
});

test(
    'Member cannot successfully validate the OTP using the correct OTP if time is more than 10 minutes',
    function (): void {
        $request = new Request([
            'mobile_number' => '601112145678',
            'otp' => '1111',
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'mobile_number' => '601112145678',
            'otp' => '1111',
            'otp_expire_date' => Carbon::now()->addMinutes(20),
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($request, $member): void {
            $mock->shouldReceive('validateMobileOtp')
                ->once()
                ->with($request->toArray(), $member->mobile_number)
                ->andReturn($member);
        });

        $memberLoginController = new LoginController();
        $response = $memberLoginController->validateOtp($request);

        expect($response)
            ->toHaveKey('message', 'Apologies, but the OTP you entered is incorrect. Please try again..');
    }
);

test('Member can successfully validate the OTP using the correct OTP from email', function (): void {
    $request = new Request([
        'email' => 'test@gmail.com',
        'otp' => '1111',
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'email' => 'test@gmail.com',
        'otp' => '1111',
        'otp_expire_date' => Carbon::now()->addMinutes(10),
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($request, $member): void {
        $mock->shouldReceive('validateEmailOtp')
            ->once()
            ->with($request->toArray(), $member->email)
            ->andReturn($member);
        $mock->shouldReceive('updateLastLoginTime')
            ->once();
        $mock->shouldReceive('generateToken')
            ->once();
    });

    $memberLoginController = new LoginController();
    $response = $memberLoginController->validateOtp($request);

    expect($response)
        ->toHaveKey(
            'message',
            'OTP verified. You can now enjoy the benefits of your ' . config('app.name') . ' Membership.'
        )
        ->toHaveKey('token');
});

test(
    'Member cannot successfully1kf validate the OTP using the correct OTP if time is more than 10 minutes from email',
    function (): void {
        $request = new Request([
            'email' => 'test@gmail.com',
            'otp' => '1111',
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'email' => 'test@gmail.com',
            'otp' => '1111',
            'otp_expire_date' => Carbon::now()->addMinutes(20),
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($request, $member): void {
            $mock->shouldReceive('validateEmailOtp')
                ->once()
                ->with($request->toArray(), $member->email)
                ->andReturn($member);
        });

        $memberLoginController = new LoginController();
        $response = $memberLoginController->validateOtp($request);

        expect($response)
            ->toHaveKey('message', 'Apologies, but the OTP you entered is incorrect. Please try again..');
    }
);

test('Member cannot successfully validate the OTP using the incorrect OTP from email', function (): void {
    $request = new Request([
        'email' => 'test@gmail.com',
        'otp' => '1111',
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($request): void {
        $mock->shouldReceive('validateEmailOtp')
            ->once()
            ->with($request->toArray(), 'test@gmail.com')
            ->andReturn(null);
    });

    $memberLoginController = new LoginController();
    $response = $memberLoginController->validateOtp($request);

    expect($response)
        ->toHaveKey('message', 'Apologies, but the OTP you entered is incorrect. Please try again.');
});

test('test member user can receive the static OTP', function (): void {
    Queue::fake();

    $request = new Request([
        'mobile_number' => '601999999999',
    ]);

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('updateOtpBasedOnMobileNumber')
            ->once();
    });

    $memberLoginController = new LoginController();
    $response = $memberLoginController->sendOtp($request);

    Queue::assertNotPushed(SendConfirmationSmsJob::class);

    expect($response)
        ->toHaveKey('message');
});

test('getEcommerceToken inserts new user and returns token if user does not exist', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Member => $member);

    $saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'secret' => 'test_secret',
        'url' => 'http://localhost/',
        'company_id' => 1,
        'default_location_id' => 1,
        'type_id' => 1,
    ]);

    $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
        $mock->shouldReceive('getECommerceSaleChannel')
            ->once()
            ->andReturn($saleChannel);
    });

    Http::fake([
        'http://localhost/api/m-com/login' => Http::response([
            'access_token' => 'ecommerce-access-token',
            'message' => 'Login successful',
        ], 200),
    ]);

    $memberLoginController = new LoginController();
    $response = $memberLoginController->getEcommerceToken($request);

    expect($response->getData(true))
        ->toHaveKey('message', 'Login successful')
        ->toHaveKey('token', 'ecommerce-access-token');
});

test('getEcommerceToken fails with invalid email', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request([
        'email' => 'invalid-email',
    ]);
    $request->setUserResolver(fn (): Member => $member);

    $saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'secret' => 'test_secret',
        'url' => 'http://localhost/',
        'company_id' => 1,
        'default_location_id' => 1,
        'type_id' => 1,
    ]);

    $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
        $mock->shouldReceive('getECommerceSaleChannel')
            ->once()
            ->andReturn($saleChannel);
    });

    Http::fake([
        'http://localhost/api/m-com/login' => Http::response([
            'message' => 'Invalid email address',
        ], 422),
    ]);

    $memberLoginController = new LoginController();
    $response = $memberLoginController->getEcommerceToken($request);

    expect($response->getData(true))
        ->toHaveKey('message', 'Invalid email address');
});

test('getEcommerceToken fails with invalid mobile number', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request([
        'mobile_number' => 'invalid-mobile',
    ]);
    $request->setUserResolver(fn (): Member => $member);

    $saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'secret' => 'test_secret',
        'url' => 'http://localhost/',
        'company_id' => 1,
        'default_location_id' => 1,
        'type_id' => 1,
    ]);

    $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
        $mock->shouldReceive('getECommerceSaleChannel')
            ->once()
            ->andReturn($saleChannel);
    });

    Http::fake([
        'http://localhost/api/m-com/login' => Http::response([
            'message' => 'Invalid mobile number',
        ], 422),
    ]);

    $memberLoginController = new LoginController();
    $response = $memberLoginController->getEcommerceToken($request);

    expect($response->getData(true))
        ->toHaveKey('message', 'Invalid mobile number');
});
