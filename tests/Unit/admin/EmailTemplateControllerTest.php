<?php

declare(strict_types=1);

use App\Domains\EmailTemplate\DataObjects\EmailTemplateData;
use App\Domains\EmailTemplate\EmailTemplateQueries;
use App\Domains\EmailTemplate\Resources\EmailTemplateListResource;
use App\Http\Controllers\Admin\EmailTemplateController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the List query method of the email template queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => 15,
        ];

        $emailTemplateQueries = $this->mock(EmailTemplateQueries::class, function ($mock) use (
            $requestParameter,
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $emailTemplateController = new EmailTemplateController($emailTemplateQueries);

        $response = $emailTemplateController->index();

        $response = $emailTemplateController->fetch(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(EmailTemplateListResource::collection(collect([])), $response['data']);
    }
);

test('It calls the addNew method of email template queries class', function (): void {
    $emailTemplateData = new EmailTemplateData('email template', [], 'abc');
    $emailTemplateQueries = $this->mock(EmailTemplateQueries::class, function ($mock) use (
        $emailTemplateData,
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($emailTemplateData);
    });

    $emailTemplateController = new EmailTemplateController($emailTemplateQueries);
    $redirectResponse = $emailTemplateController->store($emailTemplateData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Email Template added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('email-templates', $redirectResponse->getTargetUrl());
});

test('It calls the update method of email template queries class', function (): void {
    $emailTemplateData = new EmailTemplateData('email template', [], 'abc');

    $emailTemplateQueries = $this->mock(EmailTemplateQueries::class, function ($mock) use (
        $emailTemplateData,
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($emailTemplateData, 1);
    });

    $emailTemplateController = new EmailTemplateController($emailTemplateQueries);
    $redirectResponse = $emailTemplateController->update($emailTemplateData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Email Template updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('email-templates', $redirectResponse->getTargetUrl());
});

test(
    'It calls the getAll query method of the email template queries class and returns proper response',
    function (): void {
        $emailTemplateQueries = $this->mock(EmailTemplateQueries::class, function ($mock): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([]));
        });

        $emailTemplateController = new EmailTemplateController($emailTemplateQueries);
        $response = $emailTemplateController->getAll();

        $this->assertEquals(collect([]), $response['email_templates']);
    }
);
