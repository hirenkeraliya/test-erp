<?php

declare(strict_types=1);

use App\Domains\AttachedTemplate\AttachedTemplateQueries;
use App\Models\AttachedTemplate;
use App\Models\Company;
use App\Models\Product;
use App\Models\Template;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->template = Template::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->attachedTemplateQueries = new AttachedTemplateQueries();
});

test('attached template can be created', function (): void {
    $attachedTemplateRecord = AttachedTemplate::factory()->make([
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
    ]);

    $this->attachedTemplateQueries->addNew($attachedTemplateRecord->toArray());

    $this->assertDatabaseHas('attached_templates', [
        'model_id' => $attachedTemplateRecord->model_id,
        'template_id' => $attachedTemplateRecord->template_id,
    ]);
});

test('attached template can be deleted', function (): void {
    $attachedTemplateRecord = AttachedTemplate::factory()->create([
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
    ]);

    $this->attachedTemplateQueries->delete($this->product);

    $this->assertDatabaseMissing('attached_templates', [
        'id' => $attachedTemplateRecord->id,
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
    ]);
});
