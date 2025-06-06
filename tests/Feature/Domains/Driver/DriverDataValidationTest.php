<?php

declare(strict_types=1);

use App\Domains\Driver\DataObjects\DriverData;
use App\Models\Company;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    setCompanyIdInSession($this->companyId);
});

test('driver validation passes when all required fields are provided', function (): void {
    $request = new Request([
        'name' => 'John Doe',
        'id_number' => 'ID123456',
        'email' => 'john.doe@example.com',
        'mobile_number' => '1234567890',
        'country_code' => '91',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
    $this->assertTrue(true);
});

test('driver validation passes without optional email', function (): void {
    $request = new Request([
        'name' => 'John Doe',
        'id_number' => 'ID123456',
        'email' => null,
        'mobile_number' => '1234567890',
        'country_code' => '91',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
    $this->assertTrue(true);
});

test('driver validation fails when required fields are missing', function (): void {
    $request = new Request([
        'name' => '',
        'id_number' => '',
        'mobile_number' => '',
        'country_code' => '',
    ]);

    $request->validate(DriverData::rules($request));
})->throws(ValidationException::class);

test('driver validation fails when name exceeds maximum length', function (): void {
    $request = new Request([
        'name' => str_repeat('A', 256), // 256 characters
        'id_number' => 'ID123456',
        'mobile_number' => '1234567890',
        'country_code' => '+1',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
})->throws(ValidationException::class);

test('driver validation fails when id_number exceeds maximum length', function (): void {
    $request = new Request([
        'name' => 'John Doe',
        'id_number' => str_repeat('1', 256), // 256 characters
        'mobile_number' => '1234567890',
        'country_code' => '+1',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
})->throws(ValidationException::class);

test('driver validation fails with invalid email format', function (): void {
    $request = new Request([
        'name' => 'John Doe',
        'id_number' => 'ID123456',
        'email' => 'invalid-email',
        'mobile_number' => '1234567890',
        'country_code' => '+1',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
})->throws(ValidationException::class);

test('unique id_number validation works for same company while adding', function (): void {
    Driver::factory()->create([
        'company_id' => $this->companyId,
        'id_number' => 'ID123456',
    ]);

    $request = new Request([
        'name' => 'Jane Doe',
        'id_number' => 'ID123456',
        'mobile_number' => '9876543210',
        'country_code' => '+1',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
})->throws(ValidationException::class);

test('same id_number can be used for different companies', function (): void {
    $otherCompanyId = Company::factory()->create()->id;

    Driver::factory()->create([
        'company_id' => $otherCompanyId,
        'id_number' => 'ID123456',
    ]);

    $request = new Request([
        'name' => 'Jane Doe',
        'id_number' => 'ID123456',
        'mobile_number' => '9876543210',
        'country_code' => '91',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
    $this->assertTrue(true);
});

test('mobile_number validation accepts valid phone numbers', function (): void {
    $request = new Request([
        'name' => 'John Doe',
        'id_number' => 'ID' . random_int(100000, 999999),
        'mobile_number' => '1234567890',
        'country_code' => '91',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));

    $this->assertTrue(true);
});

test('mobile_number validation fails for numbers exceeding max length', function (): void {
    config([
        'app.validate_mobile_number' => true,
    ]);

    $request = new Request([
        'name' => 'John Doe',
        'id_number' => 'ID123456',
        'mobile_number' => str_repeat('1', 21), // 21 characters
        'country_code' => '91',
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
})->throws(NumberParseException::class);

test('country_code validation accepts valid country codes', function (): void {
    $validCountryCodes = ['93', '91'];
    $validMobileNumbers = ['0701234567', '1234567890'];

    foreach ($validCountryCodes as $key => $countryCode) {
        $request = new Request([
            'name' => 'John Doe',
            'id_number' => 'ID' . random_int(100000, 999999),
            'mobile_number' => $validMobileNumbers[$key],
            'country_code' => $countryCode,
            'status' => true,
        ]);

        $request->validate(DriverData::rules($request));
    }

    $this->assertTrue(true);
});

test('country_code validation fails for codes exceeding max length', function (): void {
    config([
        'app.validate_mobile_number' => true,
    ]);

    $request = new Request([
        'name' => 'John Doe',
        'id_number' => 'ID123456',
        'mobile_number' => '1234567890',
        'country_code' => str_repeat('+1', 6), // 12 characters
        'status' => true,
    ]);

    $request->validate(DriverData::rules($request));
})->throws(NumberParseException::class);

test('status validation accepts boolean values', function (): void {
    foreach ([true, false, 1, 0, '1', '0'] as $status) {
        $request = new Request([
            'name' => 'John Doe',
            'id_number' => 'ID' . random_int(100000, 999999),
            'mobile_number' => '1234567890',
            'country_code' => '91',
            'status' => $status,
        ]);

        $request->validate(DriverData::rules($request));
    }

    $this->assertTrue(true);
});
