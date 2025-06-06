<?php

use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Admin\CustomFieldValueController;
use App\Models\Template;
use Illuminate\Http\Request;

it('fetches attributes of a given template', function (): void {
    $customFieldValueData = new Request([
        'templateId' => 1,
    ]);

    setCompanyIdInSession(1);

    $template = [
        'company_id' => 1,
        'name' => fake()->word(),
        'description' => fake()->sentence(),
    ];

    $templateData = new Template($template);

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use (
        $customFieldValueData,
        $templateData,
    ): void {
        $mock->shouldReceive('fetchAttributesByTemplate')
            ->once()
            ->with($customFieldValueData->templateId, 1)
            ->andReturn($templateData);
    });

    $customFieldViewController = new CustomFieldValueController();
    $response = $customFieldViewController->fetch($customFieldValueData, $templateQueries);

    expect($response)->toHaveKey('template');
});
