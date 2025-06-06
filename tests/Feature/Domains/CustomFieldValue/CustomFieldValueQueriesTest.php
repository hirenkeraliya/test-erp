<?php

declare(strict_types=1);

use App\Domains\CustomFieldValue\CustomFieldValueQueries;
use App\Models\Attribute;
use App\Models\Company;
use App\Models\CustomFieldValue;
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

    $this->attribute = Attribute::factory()->create();

    $this->template->attributes($this->attribute->id);

    $this->customFieldValueQueries = new CustomFieldValueQueries();
});

test('custom field value can be created', function (): void {
    $customFieldValue = CustomFieldValue::factory()->make([
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
        'attribute_id' => $this->attribute->id,
        'value' => true,
    ]);

    $this->customFieldValueQueries->addNew($customFieldValue->toArray());

    $this->assertDatabaseHas('custom_field_values', [
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
        'attribute_id' => $this->attribute->id,
        'value' => true,
    ]);
});

test('custom field value can be deleted', function (): void {
    $customFieldValue = CustomFieldValue::factory()->create([
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
        'attribute_id' => $this->attribute->id,
        'value' => true,
    ]);

    $this->customFieldValueQueries->delete($this->product);

    $this->assertDatabaseMissing('custom_field_values', [
        'id' => $customFieldValue->id,
        'model_id' => $this->product->id,
        'template_id' => $this->template->id,
        'attribute_id' => $this->attribute->id,
        'value' => true,
    ]);
});
