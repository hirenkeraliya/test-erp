<?php

declare(strict_types=1);

use App\Domains\EmailRecipient\DataObjects\EmailRecipientData;
use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Http\Controllers\Admin\EmailRecipientController;
use App\Models\EmailRecipient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the email recipient queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $emailRecipientQueries = $this->mock(EmailRecipientQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $emailRecipientController = new EmailRecipientController($emailRecipientQueries);

        $response = $emailRecipientController->fetchEmailRecipients(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the add Email recipient method of email recipient queries class', function (): void {
    $emailRecipientData = new EmailRecipientData(2, 'ABCD', 'test@gmail.com');
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $emailRecipientQueries = $this->mock(EmailRecipientQueries::class, function ($mock) use (
        $emailRecipientData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($emailRecipientData, $companyId);
    });

    $emailRecipientController = new EmailRecipientController($emailRecipientQueries);
    $redirectResponse = $emailRecipientController->store($emailRecipientData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Email recipient added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/email-recipients', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the email recipient queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'email_type_id' => 1,
            'receiver_name' => 'STUV',
            'receiver_email' => 'test@gmail.com',
        ];

        $emailRecipientQueries = $this->mock(EmailRecipientQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new EmailRecipient($requestParameter));
        });

        $emailRecipientController = new EmailRecipientController($emailRecipientQueries);
        $response = $emailRecipientController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'emailRecipient',
            fn (Assert $emailRecipient): Assert => $emailRecipient->where('receiver_name', 'STUV')->where(
                'receiver_email',
                'test@gmail.com'
            )->where('email_type_id', 1)
        )
        );
    }
);

test('It calls the update email recipient method of email recipient queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $emailRecipientData = new EmailRecipientData(2, 'STUV', 'test@gmail.com');

    $emailRecipientQueries = $this->mock(EmailRecipientQueries::class, function ($mock) use (
        $emailRecipientData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($emailRecipientData, 1, $companyId);
    });

    $emailRecipientController = new EmailRecipientController($emailRecipientQueries);
    $redirectResponse = $emailRecipientController->update($emailRecipientData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Email recipient updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/email-recipients', $redirectResponse->getTargetUrl());
});

test('It calls the exportEmailRecipients method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $emailRecipientQueries = $this->mock(EmailRecipientQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getEmailRecipientExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new EmailRecipient()));
    });

    $emailRecipientController = new EmailRecipientController($emailRecipientQueries);

    $response = $emailRecipientController->exportEmailRecipients('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
