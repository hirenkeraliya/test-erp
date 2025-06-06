<?php

declare(strict_types=1);

use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Api\Integration\TemplateController;
use App\Models\Integration;
use Illuminate\Http\Request;

test('It calls the getAllTemplates method of the templateQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $templateData = [
        [
            'id' => 100,
            'company_id' => 1,
            'name' => 'TEST',
            'description' => '123',
            'is_variant' => 1,
        ],
    ];

    $this->mock(TemplateQueries::class, function ($mock) use ($templateData): void {
        $mock->shouldReceive('getAllTemplatesByCompanyId')
            ->once()
            ->andReturn(collect($templateData));
    });

    $templateController = new TemplateController();
    $response = $templateController->getAllTemplates($request);

    expect($response['templates']->first())->toHaveKeys([
        'id',
        'company_id',
        'name',
        'description',
        'is_variant',
    ]);
});
