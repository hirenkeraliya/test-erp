<?php

declare(strict_types=1);

use App\Domains\Member\Jobs\SendConfirmationEmailJob;
use App\Domains\Member\MemberQueries;
use App\Http\Controllers\Api\SaleChannel\EmailController;
use App\Models\SaleChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('Email OTP send successfully.', function (): void {
    Queue::fake();

    $saleChannel = SaleChannel::factory()->make([
        'company_id' => 1,
        'default_location_id' => 1,
    ]);

    $requestData = [
        'email' => 'abcd@example.com',
        'message' => 'Your OTP is 1234',
    ];

    $request = new Request($requestData);
    $request->setUserResolver(fn () => $saleChannel);

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('checkEmailExists')
            ->once()
            ->andReturn(true);
    });

    $emailController = new EmailController();
    $emailController->sendEmailOtp($request);

    Queue::assertPushed(SendConfirmationEmailJob::class);
});

it('Email wrong specify gets error message.', function (): void {
    Queue::fake();

    $saleChannel = SaleChannel::factory()->make([
        'company_id' => 1,
        'default_location_id' => 1,
    ]);

    $requestData = [
        'email' => 'abcd@example.com',
        'message' => 'Your OTP is 1234',
    ];

    $request = new Request($requestData);
    $request->setUserResolver(fn () => $saleChannel);

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('checkEmailExists')
            ->once()
            ->andReturn(false);
    });

    $emailController = new EmailController();
    $emailController->sendEmailOtp($request);

    Queue::assertNotPushed(SendConfirmationEmailJob::class);
})->throws(HttpException::class);
