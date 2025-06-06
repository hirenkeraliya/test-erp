<?php

declare(strict_types=1);

namespace App\Domains\EmailRecipient;

use App\Domains\EmailRecipient\DataObjects\EmailRecipientData;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Models\EmailRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class EmailRecipientQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->emailRecipientQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(EmailRecipientData $emailRecipientData, int $companyId): void
    {
        $data = $emailRecipientData->all();
        $data['company_id'] = $companyId;

        EmailRecipient::create($data);
    }

    public function getById(int $emailRecipientId, int $companyId): EmailRecipient
    {
        return EmailRecipient::select('id', 'email_type_id', 'receiver_name', 'receiver_email', 'is_email_verified')
            ->where('company_id', $companyId)
            ->findOrFail($emailRecipientId);
    }

    public function update(EmailRecipientData $emailRecipientData, int $emailRecipientId, int $companyId): void
    {
        $emailRecipient = $this->getById($emailRecipientId, $companyId);
        $emailRecipient->update($emailRecipientData->all());
    }

    public function getByEmailType(int $companyId, int $emailTypeId): Collection
    {
        return EmailRecipient::where('company_id', $companyId)->where('email_type_id', $emailTypeId)->get();
    }

    public function getAutomatedEmailReceivers(int $companyId): Collection
    {
        return EmailRecipient::select('id', 'receiver_name')
            ->where('company_id', $companyId)
            ->where('email_type_id', EmailTypes::AUTOMATED_NOTIFICATION->value)
            ->get();
    }

    public function getEmailRecipientExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->emailRecipientQuery($filterData, $companyId)->get();
    }

    public function getReceiverNameColumn(): string
    {
        return 'id,receiver_name';
    }

    public function getReceiverEmailColumn(): string
    {
        return 'id,receiver_email';
    }

    private function emailRecipientQuery(array $filterData, int $companyId): Builder
    {
        return EmailRecipient::query()
            ->select('id', 'email_type_id', 'receiver_name', 'receiver_email', 'is_email_verified')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['receiver_name', 'receiver_email'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'email_type_id',
                            EmailTypes::getMatchingCases($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
