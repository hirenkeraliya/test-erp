<?php

declare(strict_types=1);

use App\Domains\EmailTemplate\DataObjects\EmailTemplateData;
use App\Domains\EmailTemplate\EmailTemplateQueries;
use App\Models\EmailTemplate;

beforeEach(function (): void {
    $this->emailTemplateA = EmailTemplate::factory()->create([
        'name' => 'ABCD',
    ]);
    $this->emailTemplateB = EmailTemplate::factory()->create([
        'name' => 'XYZW',
    ]);

    $this->emailTemplateQueries = new EmailTemplateQueries();
});

test('email template can be searched', function (): void {
    $response = $this->emailTemplateQueries->listQuery([
        'search_text' => $this->emailTemplateA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->emailTemplateA->name);
});

test('New email template can be added', function (): void {
    $this->emailTemplateQueries->addNew(new EmailTemplateData('email', [], 'test'));

    $this->assertDatabaseHas('email_templates', [
        'name' => 'email',
        'html' => 'test',
    ]);
});

test('A email template can be fetched', function (): void {
    $response = $this->emailTemplateQueries->getById($this->emailTemplateA->id);
    expect($response->toArray())
        ->toHaveKeys(['name', 'template_json', 'html']);
});

test('A email template can be updated', function (): void {
    $this->emailTemplateQueries->update(
        new EmailTemplateData('New email template', [], 'abcd'),
        $this->emailTemplateA->id,
    );

    $this->assertDatabaseHas('email_templates', [
        'name' => 'New email template',
        'html' => 'abcd',
    ]);
});

test('It call getAll method to fetch all records.', function (): void {
    $response = $this->emailTemplateQueries->getAll();
    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'name']);
});
