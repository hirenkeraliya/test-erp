<?php

declare(strict_types=1);

use App\Domains\Vendor\DataObjects\VendorData;
use App\Models\Company;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->vendorA = Vendor::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test Name',
        'code' => '123456',
        'phone' => '123456789011',
    ]);
});

test(
    'vendor with the same name or code cannot added for the same company',
    function (): void {
        setCompanyIdInSession($this->companyId);

        $vendorDetails = getVendorDetails('Test Name', '4555555', $this->companyId);
        $request = new Request($vendorDetails);

        $request->validate(VendorData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can add same vendor name and code with different company.',
    function (): void {
        $companyBId = Company::factory()->create()->id;
        setCompanyIdInSession($companyBId);
        $vendorDetails = getVendorDetails('Test Name', '1234567890', $companyBId, 'test@gmail.com');
        $request = new Request($vendorDetails);
        $request->validate(VendorData::rules($request));
        $this->assertTrue(true);
    }
);

function getVendorDetails(string $name, string $code, $companyId, ?string $email = null): array
{
    return Vendor::factory()->make([
        'name' => $name,
        'code' => $code,
        'company_id' => $companyId,
        'phone' => '123456789012',
        'email' => $email,
        'is_consignment' => false,
        'commission_percentage' => null,
    ])->toArray();
}
