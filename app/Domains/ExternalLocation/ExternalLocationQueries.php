<?php

declare(strict_types=1);

namespace App\Domains\ExternalLocation;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Models\ExternalLocation;
use Closure;
use Illuminate\Support\Collection;

class ExternalLocationQueries
{
    public function addNew(array $externalLocationRecord): void
    {
        ExternalLocation::create($externalLocationRecord);
    }

    public function update(ExternalLocation $externalLocation, array $externalLocationRecord): void
    {
        $externalLocation->update($externalLocationRecord);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,external_location_id,name,code,phone,address_line_1,address_line_2,city,fax,type_id';
    }

    public function getBasicColumn(): string
    {
        return 'id,external_location_id,name,code,external_location_id,external_company_id,fax';
    }

    public function filterById(int $externalLocationId): Closure
    {
        return fn ($query) => $query->where('id', $externalLocationId);
    }

    public function getBasicColumnForPrint(): string
    {
        return 'id,name,code,phone,address_line_1,address_line_2,city,fax,phone,external_location_id,type_id,external_company_id';
    }

    public function getAll(int $externalCompanyId): Collection
    {
        return ExternalLocation::select('id', 'name', 'code', 'type_id')
            ->where('external_company_id', $externalCompanyId)
            ->get();
    }

    public function getByExternalLocationId(int $externalLocationId, int $externalCompanyId): ExternalLocation
    {
        return ExternalLocation::select('id', 'name', 'code')
            ->where('external_location_id', $externalLocationId)
            ->where('external_company_id', $externalCompanyId)
            ->firstOrFail();
    }

    public function getByIdWithExternalCompanyAndExternalConnection(int $id): ?ExternalLocation
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);

        return ExternalLocation::select('id', 'external_company_id', 'external_location_id')
            ->with([
                'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
            ])
            ->where('id', $id)
            ->first();
    }
}
