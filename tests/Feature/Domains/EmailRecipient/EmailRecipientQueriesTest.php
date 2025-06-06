<?php

declare(strict_types=1);

use App\Domains\EmailRecipient\DataObjects\EmailRecipientData;
use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Models\Company;
use App\Models\EmailRecipient;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->emailRecipientA = EmailRecipient::factory()->create([
        'company_id' => $this->companyId,
        'receiver_name' => 'ABCD',
        'email_type_id' => EmailTypes::EXPORT_INVENTORY_REPORT->value,
    ]);

    $this->emailRecipientB = EmailRecipient::factory()->create([
        'company_id' => $this->companyId,
        'receiver_name' => 'XYZW',
        'email_type_id' => EmailTypes::IMPORT_RECORDS_STATUS_UPDATES->value,
    ]);

    $this->emailRecipientQueries = new EmailRecipientQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('Email recipients can be searched', function (): void {
    $response = $this->emailRecipientQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('receiver_name', $this->emailRecipientA->receiver_name)
        ->toHaveKey('receiver_email', $this->emailRecipientA->receiver_email);
});

test('New email recipient can be added', function (): void {
    $this->emailRecipientQueries->addNew(new EmailRecipientData(1, 'JKLM', 'test1@gmail.com'), $this->companyId);

    $this->assertDatabaseHas('email_recipients', [
        'company_id' => $this->companyId,
        'email_type_id' => 1,
        'receiver_name' => 'JKLM',
        'receiver_email' => 'test1@gmail.com',
    ]);
});

test('An email recipient can be fetched', function (): void {
    $response = $this->emailRecipientQueries->getById($this->emailRecipientA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('receiver_name', $this->emailRecipientA->receiver_name)
        ->toHaveKey('receiver_email', $this->emailRecipientA->receiver_email);
});

test('An email recipient can be updated', function (): void {
    $this->emailRecipientQueries->update(
        new EmailRecipientData(1, 'EFGH', 'test2@gmail.com'),
        $this->emailRecipientA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('email_recipients', [
        'company_id' => $this->companyId,
        'email_type_id' => 1,
        'receiver_name' => 'EFGH',
        'receiver_email' => 'test2@gmail.com',
    ]);
});

test('getByEmailType returns the email recipient for specified email type', function (): void {
    $response = $this->emailRecipientQueries->getByEmailType(
        $this->companyId,
        EmailTypes::IMPORT_RECORDS_STATUS_UPDATES->value
    );

    expect($response->toArray())
        ->toHaveKey('0.receiver_name', $this->emailRecipientB->receiver_name)
        ->toHaveKey('0.receiver_email', $this->emailRecipientB->receiver_email);
});

test('getEmailRecipientExport method returns email recipient as expected', function (): void {
    $response = $this->emailRecipientQueries->getEmailRecipientExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('receiver_name', $this->emailRecipientA->receiver_name)
        ->toHaveKey('receiver_email', $this->emailRecipientA->receiver_email);
});

test('getAutomatedEmailReceivers method returns only automated email type recipients.', function (): void {
    $emailRecipient = EmailRecipient::factory()->create([
        'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        'company_id' => $this->companyId,
    ]);

    $response = $this->emailRecipientQueries->getAutomatedEmailReceivers($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $emailRecipient->id)
        ->toHaveKey('receiver_name', $emailRecipient->receiver_name);
});
