<?php

declare(strict_types=1);

namespace App\Domains\ExternalConnection;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\DataObjects\ExternalConnectionData;
use App\Domains\ExternalConnection\Enums\Statuses;
use App\Models\ExternalConnection;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExternalConnectionQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return $this->externalConnectionQuery($filterData)->paginate($filterData['per_page']);
    }

    public function addNew(ExternalConnectionData $externalConnectionData): ExternalConnection
    {
        return ExternalConnection::create($externalConnectionData->all());
    }

    public function addNewWithApprove(ExternalConnectionData $externalConnectionData): ExternalConnection
    {
        $externalConnectionRecord = $externalConnectionData->all();
        $externalConnectionRecord['status'] = Statuses::APPROVED->value;

        return ExternalConnection::create($externalConnectionRecord);
    }

    public function getById(int $externalConnectionId): ExternalConnection
    {
        return ExternalConnection::select(
            'id',
            'name',
            'url',
            'token',
            'approved_at',
            'rejected_at',
            'create_by_super_admin_id',
            'approve_by_super_admin_id',
            'status'
        )
            ->findOrFail($externalConnectionId);
    }

    public function getByExternalCompanyId(int $externalCompanyId): ExternalConnection
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);

        return ExternalConnection::select(
            'id',
            'name',
            'url',
            'token',
            'approved_at',
            'rejected_at',
            'create_by_super_admin_id',
            'approve_by_super_admin_id',
            'status'
        )
            ->whereHas('externalCompanies', $externalCompanyQueries->filterById($externalCompanyId))
            ->firstOrFail();
    }

    public function update(ExternalConnectionData $externalConnectionData, int $externalConnectionId): void
    {
        $externalConnection = $this->getById($externalConnectionId);
        $externalConnection->update($externalConnectionData->all());
    }

    public function reject(int $externalConnectionId): void
    {
        $externalConnection = $this->getById($externalConnectionId);
        $externalConnection->rejected_at = now()->format('Y-m-d H:i:s');
        $externalConnection->status = Statuses::REJECTED->value;
        $externalConnection->save();
    }

    public function approve(int $externalConnectionId): ExternalConnection
    {
        $externalConnection = $this->getById($externalConnectionId);
        $externalConnection->approved_at = now()->format('Y-m-d H:i:s');
        $externalConnection->status = Statuses::APPROVED->value;
        $externalConnection->token = Str::uuid()->toString();
        $externalConnection->save();

        return $externalConnection;
    }

    public function getAll(?int $externalConnectionId = null): Collection
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);

        return ExternalConnection::select(
            'id',
            'name',
            'url',
            'token',
            'approved_at',
            'rejected_at',
            'create_by_super_admin_id',
            'approve_by_super_admin_id',
            'status'
        )
            ->with(['externalCompanies:' . $externalCompanyQueries->getBasicColumn()])
            ->when($externalConnectionId, function ($query) use ($externalConnectionId): void {
                $query->where('id', $externalConnectionId);
            })
            ->get();
    }

    public function getByToken(string $token): ExternalConnection
    {
        return ExternalConnection::select('id')
            ->where('token', $token)
            ->firstOrFail();
    }

    public function existsByToken(string $token): bool
    {
        return ExternalConnection::select('id')
            ->where('token', $token)
            ->where('status', Statuses::APPROVED->value)
            ->exists();
    }

    public function existsByNameOrUrl(string $name, string $url): bool
    {
        return ExternalConnection::select('id')
            ->where('name', $name)
            ->orWhere('url', $url)
            ->exists();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,url,token';
    }

    public function filterByStatus(): Closure
    {
        return fn ($query) => $query->where('status', Statuses::APPROVED->value);
    }

    private function externalConnectionQuery(array $filterData): Builder
    {
        return ExternalConnection::query()
            ->select(
                'id',
                'name',
                'url',
                'token',
                'approved_at',
                'rejected_at',
                'create_by_super_admin_id',
                'approve_by_super_admin_id',
                'status'
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'url'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
