<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountData;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscountTransaction\HappyHourDiscountTransactionQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\Style\StyleQueries;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\HappyHourDiscount;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HappyHourDiscountQueries
{
    public function getPaginatedHappyHourDiscounts(array $filterData): LengthAwarePaginator
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);

        return $this->happyHourDiscountQuery($filterData, (int) $filterData['company_id'])
            ->with([
                'happyHourDiscountTransaction:' . $happyHourDiscountTransactionQueries->getBasicColumnNames(),
                'happyHourDiscountTransaction.authorizer:' . $this->getMorphAuthorizerBasicColumns(),
                'happyHourDiscountTransaction.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->when($filterData['product_type_id'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('product_type_id', $filterData['product_type_id']);
                });
            })
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->where('location_id', (int) $filterData['location_id'])
            ->paginate($filterData['per_page']);
    }

    public function addNew(
        HappyHourDiscountDataForPos $happyHourDiscountDataForPos,
        int $companyId,
        Cashier $cashier,
        int $locationId,
        ?int $counterUpdateId = null
    ): HappyHourDiscount {
        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);
        $happyHourDiscountValidatedData = $happyHourDiscountDataForPos->all();
        $authorizerData = $this->getAuthorizeIdAndType($happyHourDiscountDataForPos);

        unset(
            $happyHourDiscountValidatedData['brand_ids'],
            $happyHourDiscountValidatedData['category_ids'],
            $happyHourDiscountValidatedData['style_ids'],
            $happyHourDiscountValidatedData['department_ids'],
            $happyHourDiscountValidatedData['store_manager_id'],
            $happyHourDiscountValidatedData['store_manager_passcode'],
            $happyHourDiscountValidatedData['store_manager_authorization_code'],
            $happyHourDiscountValidatedData['director_id'],
            $happyHourDiscountValidatedData['director_passcode']
        );

        $happyHourDiscountValidatedData['counter_update_id'] = $counterUpdateId;
        $happyHourDiscountValidatedData['company_id'] = $companyId;
        $happyHourDiscountValidatedData['location_id'] = $locationId;
        $happyHourDiscountValidatedData['authorizer_id'] = $authorizerData['authorizer_id'];
        $happyHourDiscountValidatedData['authorizer_type'] = $authorizerData['authorizer_type'];

        $happyHourDiscount = $this->checkIfExists($happyHourDiscountValidatedData);
        if ($happyHourDiscount instanceof HappyHourDiscount) {
            $happyHourDiscountTransactionQueries->addNew($happyHourDiscount->id, $happyHourDiscountValidatedData);
            $this->sendNotificationToStoreManagers(
                $locationId,
                $companyId,
                $cashier,
                $happyHourDiscountDataForPos->name
            );

            return $happyHourDiscount;
        }

        $happyHourDiscount = $this->createHappyHourDiscount($happyHourDiscountValidatedData);
        $happyHourDiscountTransactionQueries->addNew($happyHourDiscount->id, $happyHourDiscountValidatedData);
        $this->productTypesSync($happyHourDiscount, $happyHourDiscountDataForPos);

        return $happyHourDiscount;
    }

    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->happyHourDiscountQuery($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function addNewForAdmin(
        HappyHourDiscountData $happyHourDiscountData,
        Admin $user,
        int $companyId
    ): void {
        $happyHourDiscountDetails = $happyHourDiscountData->all();

        $happyHourDiscountDetails = $this->prepareHappyHourDiscount($happyHourDiscountDetails, $companyId, $user);

        unset($happyHourDiscountDetails['brand_ids']);
        unset($happyHourDiscountDetails['category_ids']);
        unset($happyHourDiscountDetails['style_ids']);
        unset($happyHourDiscountDetails['department_ids']);

        $happyHourDiscount = $this->createHappyHourDiscount($happyHourDiscountDetails);
        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);
        $happyHourDiscountTransactionQueries->addNew($happyHourDiscount->id, $happyHourDiscountDetails);
        $this->productTypesSync($happyHourDiscount, $happyHourDiscountData);
    }

    public function prepareHappyHourDiscount(array $happyHourDiscountValidatedData, int $companyId, Admin $user): array
    {
        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);

        $happyHourDiscountValidatedData['happened_at'] = Carbon::now()->format('Y-m-d H:i:s');
        $happyHourDiscountValidatedData['offline_id'] = $happyHourDiscountTransactionQueries->generateUniqueOfflineId();
        $happyHourDiscountValidatedData['counter_update_id'] = null;
        $happyHourDiscountValidatedData['authorizer_id'] = $user->id;
        $happyHourDiscountValidatedData['company_id'] = $companyId;
        $happyHourDiscountValidatedData['authorizer_type'] = ModelMapping::getCaseName($user::class);

        return $happyHourDiscountValidatedData;
    }

    public function getById(int $happyHourDiscountId, int $companyId): HappyHourDiscount
    {
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);

        return HappyHourDiscount::select(
            'id',
            'location_id',
            'product_type_id',
            'name',
            'new_price',
            'start_date',
            'end_date',
        )
        ->with([
            'location:' . $locationQueries->getNameColumnName(),
            'styles:' . $styleQueries->getBasicColumnNames(),
            'brands:' . $brandQueries->getIdAndNameColumnNames(),
            'categories:' . $categoryQueries->getBasicColumnNames(),
            'departments:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
            'happyHourDiscountTransaction:' . $happyHourDiscountTransactionQueries->getBasicColumnNames(),
        ])
        ->where('company_id', $companyId)
        ->findOrFail($happyHourDiscountId);
    }

    public function update(HappyHourDiscountData $happyHourDiscountData, int $happyHourDiscountId, int $companyId): void
    {
        $happyHourDiscount = $this->getById($happyHourDiscountId, $companyId);
        $happyHourDiscountDetails = $happyHourDiscountData->all();
        $happyHourDiscountDetails['company_id'] = $companyId;
        unset($happyHourDiscountDetails['brand_ids']);
        unset($happyHourDiscountDetails['category_ids']);
        unset($happyHourDiscountDetails['style_ids']);
        unset($happyHourDiscountDetails['department_ids']);

        $happyHourDiscount->update($happyHourDiscountDetails);

        $this->productTypesSync($happyHourDiscount, $happyHourDiscountData);
    }

    public function checkIfExists(array $happyHourDiscountDataForPos): ?HappyHourDiscount
    {
        return HappyHourDiscount::query()
            ->select('id')
            ->where('company_id', $happyHourDiscountDataForPos['company_id'])
            ->where('product_type_id', $happyHourDiscountDataForPos['product_type_id'])
            ->where('location_id', $happyHourDiscountDataForPos['location_id'])
            ->where('new_price', $happyHourDiscountDataForPos['new_price'])
            ->where('start_date', $happyHourDiscountDataForPos['start_date'])
            ->where('end_date', $happyHourDiscountDataForPos['end_date'])
            ->first();
    }

    private function productTypesSync(
        HappyHourDiscount $happyHourDiscount,
        HappyHourDiscountData|HappyHourDiscountDataForPos $data,
    ): void {
        $happyHourDiscount->brands()->detach();
        $happyHourDiscount->styles()->detach();
        $happyHourDiscount->categories()->detach();
        $happyHourDiscount->departments()->detach();

        if ($data->brand_ids && $data->product_type_id === ProductTypes::BRAND->value) {
            $happyHourDiscount->brands()->attach($data->brand_ids);
        }

        if ($data->style_ids && $data->product_type_id === ProductTypes::STYLE->value) {
            $happyHourDiscount->styles()->attach($data->style_ids);
        }

        if ($data->category_ids && $data->product_type_id === ProductTypes::CATEGORY->value) {
            $happyHourDiscount->categories()->attach($data->category_ids);
        }

        if (! $data->department_ids) {
            return;
        }

        if ($data->product_type_id !== ProductTypes::DEPARTMENTS->value) {
            return;
        }

        $happyHourDiscount->departments()->attach($data->department_ids);
    }

    public function happyHourDiscountExport(array $filterData, int $companyId): Collection
    {
        return $this->happyHourDiscountQuery($filterData, $companyId)
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function getByOfflineIdsWithRelations(array $offlineIds, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);

        return HappyHourDiscount::select(
            'id',
            'location_id',
            'product_type_id',
            'name',
            'new_price',
            'start_date',
            'end_date',
        )
            ->with([
                'location:' . $locationQueries->getNameColumnName(),
                'styles:' . $styleQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getIdAndNameColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'departments:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
                'happyHourDiscountTransaction' => function ($query) use ($offlineIds): void {
                    $query->select(
                        'id',
                        'happy_hour_discount_id',
                        'counter_update_id',
                        'offline_id',
                        'authorizer_id',
                        'authorizer_type',
                        'happened_at'
                    )
                        ->whereInCaseSensitive('offline_id', $offlineIds);
                },
            ])
            ->where('company_id', $companyId)
            ->whereHas('happyHourDiscountTransaction', function ($query) use ($offlineIds): void {
                $query->select('id', 'happy_hour_discount_id')
                    ->whereInCaseSensitive('offline_id', $offlineIds);
            })
            ->get();
    }

    private function happyHourDiscountQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);

        return HappyHourDiscount::query()
            ->select('id', 'location_id', 'product_type_id', 'name', 'new_price', 'start_date', 'end_date')
            ->with(
                [
                    'location:' . $locationQueries->getNameColumnName(),
                    'styles:' . $styleQueries->getBasicColumnNames(),
                    'brands:' . $brandQueries->getIdAndNameColumnNames(),
                    'categories:' . $categoryQueries->getBasicColumnNames(),
                    'departments:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
                    'happyHourDiscountTransactions:' . $happyHourDiscountTransactionQueries->getBasicColumnNames(),
                    'happyHourDiscountTransactions.authorizer:' . $this->getMorphAuthorizerBasicColumns(),
                    'happyHourDiscountTransactions.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                ]
            )
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

    private function getMorphAuthorizerBasicColumns(): string
    {
        return 'id,employee_id';
    }

    private function getAuthorizeIdAndType(HappyHourDiscountDataForPos $happyHourDiscountDataForPos): array
    {
        if ($happyHourDiscountDataForPos->store_manager_id) {
            return [
                'authorizer_id' => $happyHourDiscountDataForPos->store_manager_id,
                'authorizer_type' => AuthorizerTypes::STORE_MANAGER->name,
            ];
        }

        return [
            'authorizer_id' => $happyHourDiscountDataForPos->director_id,
            'authorizer_type' => AuthorizerTypes::DIRECTOR->name,
        ];
    }

    private function sendNotificationToStoreManagers(
        int $locationId,
        int $companyId,
        Cashier $cashier,
        string $name
    ): void {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagers = $storeManagerQueries->getAllStoreManagerByStoreIdAndCompanyId($locationId, $companyId);
        $notificationQueries = resolve(NotificationQueries::class);

        foreach ($storeManagers as $storeManager) {
            $message = 'Happy Hour discount name :' . $name . ' is overlapping By ' . $cashier->username;
            $textMessage = 'Happy Hour discount name :' . $name . ' is overlapping By ' . $cashier->username;
            $notificationQueries->addNew(
                $companyId,
                null,
                null,
                ModelMapping::STORE_MANAGER->name,
                $storeManager->id,
                $message,
                null,
                $textMessage,
                null,
            );
        }
    }

    private function createHappyHourDiscount(array $happyHourDiscountValidatedData): HappyHourDiscount
    {
        return HappyHourDiscount::create([
            'company_id' => $happyHourDiscountValidatedData['company_id'],
            'product_type_id' => $happyHourDiscountValidatedData['product_type_id'],
            'name' => $happyHourDiscountValidatedData['name'],
            'location_id' => $happyHourDiscountValidatedData['location_id'],
            'new_price' => $happyHourDiscountValidatedData['new_price'],
            'start_date' => $happyHourDiscountValidatedData['start_date'],
            'end_date' => $happyHourDiscountValidatedData['end_date'],
        ]);
    }
}
