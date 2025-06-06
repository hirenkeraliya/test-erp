<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\Jobs\PrepareExternalProductsJob;
use App\Models\ExternalProduct;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExternalProductQueries
{
    public function addNew(array $product, int $receiverCompanyId, int $senderCompanyId): bool
    {
        $externalProduct = ExternalProduct::query()
            ->select('id', 'status')
            ->where('company_id', $receiverCompanyId)
            ->where('upc', $product['upc'])
            ->first();

        if ($externalProduct) {
            $externalProduct->status = ExternalProductStatuses::DUPLICATE->value;
            $externalProduct->save();

            return false;
        }

        ExternalProduct::create([
            'company_id' => $receiverCompanyId,
            'external_company_id' => $senderCompanyId,
            'upc' => $product['upc'],
            'product_name' => $product['name'],
            'product_details' => $product,
            'status' => ExternalProductStatuses::PENDING->value,
        ]);

        return true;
    }

    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return ExternalProduct::query()
            ->select(
                'id',
                'external_company_id',
                'product_name',
                'upc',
                'product_details',
                'status',
                'approved_by_id',
                'approved_by_type',
                'rejected_by_id',
                'rejected_by_type',
                'approved_at',
                'rejected_at',
                'created_at',
                'updated_at'
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['product_name', 'upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when((int) $filterData['status'] === ExternalProductStatuses::PENDING->value, function ($query): void {
                $query->where('status', ExternalProductStatuses::PENDING->value);
            })
            ->when((int) $filterData['status'] === ExternalProductStatuses::APPROVED->value, function ($query): void {
                $query->where('status', ExternalProductStatuses::APPROVED->value);
            })
            ->when((int) $filterData['status'] === ExternalProductStatuses::REJECTED->value, function ($query): void {
                $query->where('status', ExternalProductStatuses::REJECTED->value);
            })
            ->when((int) $filterData['status'] === ExternalProductStatuses::CREATED->value, function ($query): void {
                $query->where('status', ExternalProductStatuses::CREATED->value);
            })
            ->when(
                (int) $filterData['status'] === ExternalProductStatuses::IN_PROGRESS->value,
                function ($query): void {
                    $query->where('status', ExternalProductStatuses::IN_PROGRESS->value);
                }
            )
            ->when((int) $filterData['status'] === ExternalProductStatuses::DUPLICATE->value, function ($query): void {
                $query->where('status', ExternalProductStatuses::DUPLICATE->value);
            })
            ->paginate($filterData['per_page']);
    }

    public function markAsApproved(array $externalProductIds, User $user, int $companyId): void
    {
        $externalProducts = $this->getExternalProductByIdsAndCompanyId(
            $externalProductIds,
            $companyId,
            ExternalProductStatuses::PENDING->value
        );

        foreach ($externalProducts as $externalProduct) {
            $this->approvedExternalProduct($externalProduct, $user);
            PrepareExternalProductsJob::dispatch($externalProduct->id, $externalProduct->company_id, $user)->onQueue(
                'medium'
            );
        }
    }

    public function markAsRejected(array $externalProductIds, User $user, int $companyId): void
    {
        $externalProducts = $this->getExternalProductByIdsAndCompanyId(
            $externalProductIds,
            $companyId,
            ExternalProductStatuses::PENDING->value
        );

        foreach ($externalProducts as $externalProduct) {
            $this->rejectExternalProduct($externalProduct, $user);
        }
    }

    public function getExternalProductByIdsAndCompanyId(
        array $externalProductIds,
        int $companyId,
        int $status
    ): Collection {
        return ExternalProduct::query()
            ->select('id', 'company_id', 'external_company_id', 'status', 'upc', 'product_details')
            ->where('company_id', $companyId)
            ->where('status', $status)
            ->whereIntegerInRaw('id', $externalProductIds)
            ->get();
    }

    public function getExternalProductByIdAndCompanyId(int $externalProductId, int $companyId): ExternalProduct
    {
        return ExternalProduct::query()
            ->select('id', 'company_id', 'external_company_id', 'status', 'upc', 'product_details')
            ->where('company_id', $companyId)
            ->findOrFail($externalProductId);
    }

    public function changeStatus(ExternalProduct $externalProduct, int $status): void
    {
        $externalProduct->status = $status;
        $externalProduct->save();
    }

    private function approvedExternalProduct(ExternalProduct $externalProduct, User $user): void
    {
        $externalProduct->status = ExternalProductStatuses::APPROVED->value;
        $externalProduct->approved_by_id = $user->id;
        $externalProduct->approved_by_type = ModelMapping::getCaseName($user::class);
        $externalProduct->approved_at = now()->format('Y-m-d H:i:s');
        $externalProduct->save();
    }

    private function rejectExternalProduct(ExternalProduct $externalProduct, User $user): void
    {
        $externalProduct->status = ExternalProductStatuses::REJECTED->value;
        $externalProduct->rejected_by_id = $user->id;
        $externalProduct->rejected_by_type = ModelMapping::getCaseName($user::class);
        $externalProduct->rejected_at = now()->format('Y-m-d H:i:s');
        $externalProduct->save();
    }
}
