<?php

declare(strict_types=1);

namespace App\Domains\SubPaymentType;

use App\Domains\SubPaymentType\DataObjects\SubPaymentTypeData;
use App\Models\PaymentType;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SubPaymentTypeQueries
{
    public function listQuery(array $filterData, int $paymentTypeId, int $companyId): LengthAwarePaginator
    {
        return $this->subPaymentTypeQuery($filterData, $paymentTypeId, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(SubPaymentTypeData $subPaymentTypeData, int $paymentTypeId, int $companyId): void
    {
        $data = $subPaymentTypeData->all();
        $data['parent_payment_type_id'] = $paymentTypeId;
        $data['company_id'] = $companyId;

        PaymentType::create($data);
    }

    public function getById(int $paymentTypeId, int $subPaymentTypeId, int $companyId): PaymentType
    {
        return PaymentType::select(
            'id',
            'name',
            'is_member_required',
            'is_available_for_refund',
            'trigger_card_payment_machine',
            'trigger_qr_code_payment_machine',
            'trigger_card_affin_payment_machine',
            'is_card_payment',
            'status',
            'image_name',
            'payment_terminal_key',
            'trigger_card_bank_rakyat_terminal',
            'is_available_in_pos',
        )
            ->where('parent_payment_type_id', $paymentTypeId)
            ->where('company_id', $companyId)
            ->findOrFail($subPaymentTypeId);
    }

    public function update(
        SubPaymentTypeData $subPaymentTypeData,
        int $paymentTypeId,
        int $subPaymentTypeId,
        int $companyId
    ): void {
        $subPaymentType = $this->getById($paymentTypeId, $subPaymentTypeId, $companyId);
        $subPaymentType->update($subPaymentTypeData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function setStatus(int $subPaymentTypeId, int $companyId, bool $status): void
    {
        $paymentType = PaymentType::query()
            ->where('company_id', $companyId)
            ->findOrFail($subPaymentTypeId);
        $paymentType->status = $status;
        $paymentType->save();
    }

    public function getSubPaymentTypesExport(array $filterData, int $paymentTypeId, int $companyId): Collection
    {
        return $this->subPaymentTypeQuery($filterData, $paymentTypeId, $companyId)->get();
    }

    private function subPaymentTypeQuery(array $filterData, int $paymentTypeId, int $companyId): Builder
    {
        return PaymentType::query()
            ->select('id', 'name', 'image_name', 'status', 'is_card_payment')
            ->where('parent_payment_type_id', $paymentTypeId)
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
