<?php

declare(strict_types=1);

namespace App\Domains\Vendor;

use App\Domains\Vendor\DataObjects\VendorData;
use App\Models\Vendor;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VendorQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->vendorQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getByIds(array $vendorIds): Collection
    {
        return Vendor::select('name')
            ->whereIntegerInRaw('id', $vendorIds)
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function filterByIsConsignmentTrue(): Closure
    {
        return fn ($query) => $query->select('id', 'is_consignment', 'commission_percentage')
            ->where('is_consignment', true);
    }

    public function addNew(VendorData $vendorData, int $companyId): void
    {
        $vendorDetails = $vendorData->all();
        $vendorDetails['company_id'] = $companyId;
        Vendor::create($vendorDetails);
    }

    public function updateByPhone(array $vendorData, int $companyId): void
    {
        $vendorData['company_id'] = $companyId;
        $vendor = Vendor::select('id')
            ->where('company_id', $companyId)
            ->where('phone', $vendorData['phone'])
            ->first();
        if ($vendor instanceof Vendor) {
            $vendor->update($vendorData);
        }
    }

    public function getById(int $vendorId, int $companyId): Vendor
    {
        return Vendor::select(
            'id',
            'company_id',
            'name',
            'registration_number',
            'sst_number',
            'code',
            'email',
            'phone',
            'mobile',
            'fax',
            'website',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'is_consignment',
            'commission_percentage',
            'is_email_verified'
        )
            ->where('company_id', $companyId)
            ->findOrFail($vendorId);
    }

    public function update(VendorData $vendorData, int $vendorId, int $companyId): void
    {
        $vendor = $this->getById($vendorId, $companyId);
        $vendor->update($vendorData->all());
    }

    public function getBasicColumnNames(): string
    {
        return 'id,company_id,name';
    }

    public function getBasicColumnNamesForConsignmentReport(): string
    {
        return 'id,name,commission_percentage';
    }

    public function getBasicColumnNamesForPurchasePlan(): string
    {
        return 'id,name,code,email,phone,mobile,fax,address_line_1,address_line_2,city,area_code';
    }

    public function getVendorsExport(array $filterData, int $companyId): Collection
    {
        return $this->vendorQuery($filterData, $companyId)->get();
    }

    public function getVendorByCompanyId(int $companyId, ?string $searchText = null): Collection
    {
        return Vendor::select('id', 'name')
            ->where('company_id', $companyId)
            ->when(null !== $searchText, function ($query) use ($searchText): void {
                $query->where('name', 'like', '%' . $searchText . '%');
            })
            ->get();
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Vendor::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Vendor::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): ?Vendor
    {
        return Vendor::select('id')->whereCaseSensitive('name', $name)->where('company_id', $companyId)->first();
    }

    public function existsByNameExpectCurrentRecord(string $name, string $phone, int $companyId): bool
    {
        return Vendor::whereCaseSensitive('name', $name)
            ->whereNotCaseSensitive('phone', $phone)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function existsByPhone(string $phone, int $companyId): bool
    {
        return Vendor::whereCaseSensitive('phone', $phone)->where('company_id', $companyId)->exists();
    }

    public function getByIdAndCompanyId(int $vendorId, int $companyId): ?Vendor
    {
        return Vendor::select('id')
            ->where('company_id', $companyId)
            ->find($vendorId);
    }

    public function getByIdForEmailVerification(int $vendorId, int $companyId): Vendor
    {
        return Vendor::select('id', 'email')
            ->where('company_id', $companyId)
            ->findOrFail($vendorId);
    }

    public function getAllVendorByCompanyId(int $companyId): Collection
    {
        return Vendor::select(
            'id',
            'company_id',
            'name',
            'email',
            'phone',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code'
        )
            ->where('company_id', $companyId)
            ->get();
    }

    private function vendorQuery(array $filterData, int $companyId): Builder
    {
        return Vendor::query()
            ->select(
                'id',
                'name',
                'email',
                'code',
                'sst_number',
                'registration_number',
                'address_line_1',
                'address_line_2',
                'phone',
                'area_code',
                'fax',
                'city',
                'website',
                'mobile',
                'is_consignment',
                'commission_percentage',
                'is_email_verified'
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['name', 'email', 'code', 'phone', 'city'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
