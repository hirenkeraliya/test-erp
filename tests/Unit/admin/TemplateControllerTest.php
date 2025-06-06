<?php

declare(strict_types=1);

use App\Domains\Template\DataObjects\TemplateData;
use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Admin\TemplateController;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

it('lists templates successfully', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'Wallets',
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ];

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $templateController = new TemplateController($templateQueries);

    $response = $templateController->fetchTemplates(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

it('calls the addNew method of the template queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession();

    $template = [
        'name' => fake()->word(),
        'description' => fake()->sentence(),
        'is_variant' => false,
    ];

    $templateData = new TemplateData(...$template);

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use ($templateData, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($templateData, $companyId);
    });

    $templateController = new TemplateController($templateQueries);
    $redirectResponse = $templateController->store($templateData);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Template added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/templates', $redirectResponse->getTargetUrl());
});

it('calls update method of the template queries class', function (): void {
    $companyId = 1;
    $templateId = fake()->numberBetween();

    setCompanyIdInSession($companyId);

    $templateData = Template::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    unset($templateData['company_id']);

    $templateRecords = new TemplateData(...$templateData);

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use (
        $templateRecords,
        $templateId,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($templateRecords, $templateId, $companyId);
    });

    $templateController = new TemplateController($templateQueries);
    $redirectResponse = $templateController->update($templateRecords, $templateId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Template updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/templates', $redirectResponse->getTargetUrl());
});

it('calls the delete method of the template queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);
    $templateId = fake()->numberBetween();

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use ($companyId, $templateId): void {
        $mock->shouldReceive('delete')
            ->once()
            ->with($templateId, $companyId);
    });

    $templateController = new TemplateController($templateQueries);
    $redirectResponse = $templateController->delete($templateId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Template deleted successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/templates', $redirectResponse->getTargetUrl());
});

test('Edit Template Functionality', function (): void {
    $companyId = 1;

    $templateId = 2;

    setCompanyIdInSession($companyId);

    $requestParameter = Template::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use (
        $requestParameter,
        $companyId,
        $templateId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with($templateId, $companyId)
            ->andReturn(new Template($requestParameter));
    });

    $templateController = new TemplateController($templateQueries);
    $response = $templateController->edit($templateId);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has('template', fn (Assert $season): Assert => $season->where('name', $requestParameter['name'])->etc())
    );
});
