<?php

declare(strict_types=1);

use App\Domains\Template\DataObjects\TemplateData;
use App\Models\Company;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    setCompanyIdInSession($this->companyId);
});

test('template name is required.', function (): void {
    $template = [
        'name' => '',
    ];
    $request = new Request($template);
    $request->validate(TemplateData::rules($request));
})->throws(ValidationException::class);

test('template name cannot be more than 255.', function (): void {
    $template = [
        'name' => fake()->words(300, true),
    ];
    $request = new Request($template);
    $request->validate(TemplateData::rules($request));
})->throws(ValidationException::class);

test('template description cannot be more than 255.', function (): void {
    $template = [
        'name' => fake()->word(),
        'description' => fake()->words(300, true),
    ];
    $request = new Request($template);
    $request->validate(TemplateData::rules($request));
})->throws(ValidationException::class);

test('template description can be nullable.', function (): void {
    $template = [
        'name' => fake()->word(),
        'description' => null,
        'is_variant' => false,
    ];
    $request = new Request($template);
    $request->validate(TemplateData::rules($request));
})->throwsNoExceptions();

test('unique template name allowed', function (): void {
    $this->template = Template::factory()->create([
        'company_id' => $this->companyId,
        'name' => $name = 'ABC',
    ]);
    $template = [
        'name' => $name,
        'description' => null,
    ];
    $request = new Request($template);
    $request->validate(TemplateData::rules($request));
})->throws(ValidationException::class);
