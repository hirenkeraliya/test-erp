<?php

declare(strict_types=1);

namespace App\Domains\ExternalCompany;

use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Models\ExternalCompany;
use Closure;
use Illuminate\Support\Collection;

class ExternalCompanyQueries
{
    public function addNew(array $externalCompanyRecord): ExternalCompany
    {
        return ExternalCompany::create($externalCompanyRecord);
    }

    public function update(ExternalCompany $externalCompany, array $externalCompanyRecord): ExternalCompany
    {
        $externalCompany->update($externalCompanyRecord);

        return $externalCompany;
    }

    public function getById(int $externalCompanyId): ExternalCompany
    {
        return ExternalCompany::select('id', 'external_connection_id', 'external_company_id')
            ->findOrFail($externalCompanyId);
    }

    public function getByIdWithExternalConnection(int $externalCompanyId): ?ExternalCompany
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);

        return ExternalCompany::select(
            'id',
            'external_connection_id',
            'external_company_id',
            'name',
            'code',
            'email',
            'fax',
            'address'
        )
            ->with([
                'externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'externalLocations:' . $externalLocationQueries->getBasicColumn(),
            ])
            ->where('id', $externalCompanyId)
            ->whereHas('externalConnection', $externalConnectionQueries->filterByStatus())
            ->first();
    }

    public function getByExternalCompanyId(int $externalCompanyId, int $externalConnectionId): ExternalCompany
    {
        return ExternalCompany::select(
            'id',
            'external_connection_id',
            'external_company_id',
            'name',
            'code',
            'email',
            'fax',
            'address'
        )
            ->where('external_company_id', $externalCompanyId)
            ->where('external_connection_id', $externalConnectionId)
            ->firstOrFail();
    }

    public function getAll(): Collection
    {
        return ExternalCompany::select('id', 'name')->get();
    }

    public function getAllCompanies(): Collection
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);

        return ExternalCompany::select('id', 'name')
            ->whereHas('externalConnection', $externalConnectionQueries->filterByStatus())
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getBasicColumn(): string
    {
        return 'id,name,code,external_company_id,external_connection_id,fax,address,social_security_number';
    }

    public function filterById(int $externalCompanyId): Closure
    {
        return fn ($query) => $query->where('id', $externalCompanyId);
    }

    public function uploadLogos(ExternalCompany $externalCompany, array $logos): void
    {
        if ($logos['light_logo']) {
            $headers = @get_headers($logos['light_logo']);
            if ($headers && strpos($headers[0], '200')) {
                $externalCompany->addMediaFromUrl($logos['light_logo'])->toMediaCollection('light_logo');
            }
        }

        if ($logos['dark_logo']) {
            $headers = @get_headers($logos['dark_logo']);
            if ($headers && strpos($headers[0], '200')) {
                $externalCompany->addMediaFromUrl($logos['dark_logo'])->toMediaCollection('dark_logo');
            }
        }

        if ($logos['email_footer_logo']) {
            $headers = @get_headers($logos['email_footer_logo']);
            if ($headers && strpos($headers[0], '200')) {
                $externalCompany->addMediaFromUrl($logos['email_footer_logo'])->toMediaCollection('email_footer_logo');
            }
        }
    }

    public function getExternalCompanyWithRelationById(int $id): ExternalCompany
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);

        return ExternalCompany::select('id', 'external_connection_id', 'external_company_id', 'name')
            ->with(['externalConnection:' . $externalConnectionQueries->getBasicColumnNames()])
            ->findOrFail($id);
    }

    public function getApprovedExternalCompaniesWithBasicColumns(): Collection
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);

        return ExternalCompany::select('id', 'external_company_id')
            ->whereHas('externalConnection', $externalConnectionQueries->filterByStatus())
            ->get();
    }

    public function getByIdWithExternalCompanyId(int $id): ExternalCompany
    {
        return ExternalCompany::select('id', 'external_company_id')
            ->where('id', $id)
            ->firstOrFail();
    }

    public function delete(int $externalConnectionId, int $externalCompanyId): void
    {
        $externalCompany = ExternalCompany::query()
            ->where('external_connection_id', $externalConnectionId)
            ->where('external_company_id', $externalCompanyId)
            ->firstOrFail();

        $externalCompany->delete();
    }

    public function restore(int $externalConnectionId, int $externalCompanyId): void
    {
        $externalCompany = ExternalCompany::query()
            ->where('external_connection_id', $externalConnectionId)
            ->where('external_company_id', $externalCompanyId)
            ->withTrashed()
            ->firstOrFail();

        $externalCompany->restore();
    }
}
