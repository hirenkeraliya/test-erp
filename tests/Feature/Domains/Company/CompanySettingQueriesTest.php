<?php

declare(strict_types=1);

use App\Domains\Company\CompanySettingQueries;
use App\Models\Company;
use App\Models\CompanySetting;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);
    $this->companySettings = CompanySetting::factory()->make()->toArray();

    $this->companySettingQueries = new CompanySettingQueries();
});

test('Companies setting can be added', function (): void {
    $response = $this->companySettingQueries->addNew($this->companySettings, $this->companyA->id);

    $this->assertEmpty($response);
    expect(null);
});

test('Companies setting can be updated', function (): void {
    $response = $this->companySettingQueries->update($this->companySettings, $this->companyA->id);

    $this->assertEmpty($response);
    expect(null);
});
