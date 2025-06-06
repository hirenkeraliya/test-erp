<?php

declare(strict_types=1);

namespace App\Domains\EmailTemplate;

use App\Domains\EmailTemplate\DataObjects\EmailTemplateData;
use App\Models\EmailTemplate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmailTemplateQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return EmailTemplate::query()
            ->select('id', 'name', 'usage', 'clicks', 'revenue', 'conversion', 'created_at')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filterData['per_page']);
    }

    public function addNew(EmailTemplateData $emailTemplateData): void
    {
        $data = $emailTemplateData->all();
        $data['usage'] = random_int(0, 10);
        $data['clicks'] = random_int(100, 10000);
        $data['revenue'] = number_format(random_int(1000, 10000) / 100, 2);
        $data['conversion'] = random_int(0, 100);
        $data['template_json'] = json_encode($data['template_json'], JSON_THROW_ON_ERROR);
        EmailTemplate::create($data);
    }

    public function getById(int $emailTemplateId): EmailTemplate
    {
        return EmailTemplate::select('id', 'name', 'template_json', 'html')
            ->findOrFail($emailTemplateId);
    }

    public function update(EmailTemplateData $emailTemplateData, int $emailTemplateId): void
    {
        $emailRecipient = $this->getById($emailTemplateId);
        $emailRecipient->update($emailTemplateData->all());
    }

    public function getAll(): Collection
    {
        return EmailTemplate::query()
            ->select('id', 'name')
            ->get();
    }
}
