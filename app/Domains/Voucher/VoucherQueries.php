<?php

declare(strict_types=1);

namespace App\Domains\Voucher;

use App\CommonFunctions;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VoucherQueries
{
    public function addNew(
        VoucherConfiguration $voucherConfiguration,
        float $getValue,
        int $discountType,
        ?Carbon $expiryDate,
        ?int $memberId = null,
        ?string $voucherNumber = null,
        ?int $saleId = null,
        ?int $locationId = null,
        ?int $orderId = null,
    ): Voucher {
        return Voucher::create([
            'voucher_configuration_id' => $voucherConfiguration->id,
            'member_id' => $memberId,
            'generated_by_sale_id' => $saleId,
            'generated_by_order_id' => $orderId,
            'created_by_location_id' => $locationId,
            'discount_type' => $voucherConfiguration->discount_type,
            'number' => $voucherNumber ?: $this->generateUniqueVoucherNumber(),
            'minimum_spend_amount' => $voucherConfiguration->use_minimum_spend_amount,
            'percentage' => $discountType === DiscountTypes::PERCENTAGE->value ? $getValue : null,
            'flat_amount' => $discountType === DiscountTypes::FLAT->value ? $getValue : null,
            'expiry_date' => $expiryDate instanceof Carbon ? $expiryDate->format('Y-m-d') : null,
            'dream_price_applicable' => $voucherConfiguration->dream_price_applicable ?? false,
            'item_wise_promotion_applicable' => $voucherConfiguration->item_wise_promotion_applicable ?? false,
            'cart_wide_promotion_applicable' => $voucherConfiguration->cart_wide_promotion_applicable ?? false,
        ]);
    }

    public function getPaginatedList(array $filterData, int $companyId): LengthAwarePaginator
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'expiry_date',
            'dream_price_applicable',
            'item_wise_promotion_applicable',
            'cart_wide_promotion_applicable',
            'created_by_location_id',
            'status',
        )
            ->with([
                'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'voucherTransactions.sale:' . $saleQueries->getOfflineSaleIdWithStatus(),
                'voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'member:' . $memberQueries->getBasicColumnNames(),
            ])
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->whereNull('cancelled_at')
            ->orderBy('id', 'desc')
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query): void {
                $query->where('status', VoucherStatusTypes::ACTIVE->value);
            })
            ->paginate($filterData['per_page']);
    }

    public function getByVoucherNumberAndCompanyIdWithProductsAndCategories(
        string $voucherNumber,
        int $companyId
    ): ?Voucher {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'expiry_date',
            'used_at',
            'dream_price_applicable',
            'item_wise_promotion_applicable',
            'cart_wide_promotion_applicable',
            'status',
        )
            ->with([
                'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNames(),
                'voucherConfiguration.products:' . $productQueries->getBasicColumnNames(),
                'voucherConfiguration.categories:' . $categoryQueries->getBasicColumnNames(),
            ])
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->where('number', $voucherNumber)
            ->first();
    }

    public function doVoucherNumbersExist(array $numbers, int $companyId): bool
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        return Voucher::query()
            ->select('id', 'number')
            ->whereIn('number', $numbers)
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->exists();
    }

    public function markAsUsed(Voucher $voucher, string $happenedAt): void
    {
        $voucher->used_at = $happenedAt;
        $voucher->status = VoucherStatusTypes::USED->value;
        $voucher->save();
    }

    public function getCountByCounterUpdateId(int $counterUpdateId): int
    {
        $saleQueries = resolve(SaleQueries::class);

        return Voucher::query()
            ->whereHas('sale', $saleQueries->filterByCounterUpdateId($counterUpdateId))
            ->count();
    }

    public function filterByActiveVouchers(): Closure
    {
        return fn ($query) => $query
            ->select(
                'id',
                'voucher_configuration_id',
                'member_id',
                'discount_type',
                'number',
                'minimum_spend_amount',
                'percentage',
                'flat_amount',
                'used_at',
                'expiry_date',
                'dream_price_applicable',
                'item_wise_promotion_applicable',
                'cart_wide_promotion_applicable'
            )
            ->onlyActive();
    }

    public function getColumnNames(): string
    {
        return 'id,voucher_configuration_id,member_id,generated_by_sale_id,created_by_location_id,discount_type,number,minimum_spend_amount,percentage,flat_amount,used_at,expiry_date,cancelled_at,dream_price_applicable,item_wise_promotion_applicable,cart_wide_promotion_applicable,created_at,status';
    }

    public function getBasicColumnForMemberPos(): string
    {
        return 'id,used_at,expiry_date,cancelled_at,created_at';
    }

    public function getVoucherConfigurationIdColumn(): string
    {
        return 'id,voucher_configuration_id,number';
    }

    public function getVoucherConfigurationIdNumberColumn(): string
    {
        return 'id,voucher_configuration_id,number';
    }

    public function getIdAndMemberIdColumn(): string
    {
        return 'id,member_id';
    }

    public function loadVoucherWithMismatchesRelations(Voucher $voucher): Voucher
    {
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        $voucher->refresh();

        return $voucher->load([
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
            'member:' . $memberQueries->getBasicColumnNames(),
            'voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
            'voucherTransactions.sale:' . $saleQueries->getBasicColumns(),
            'voucherTransactions.location:' . $locationQueries->getNameColumnName(),
        ]);
    }

    public function loadRelations(Voucher $voucher): Voucher
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        $voucher->refresh();

        return $voucher->load(
            'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
            'member:' . $memberQueries->getBasicColumnNames(),
        );
    }

    public function getVouchersBySaleId(int $saleId): Collection
    {
        return Voucher::select('id', 'cancelled_at', 'member_id')
            ->where('generated_by_sale_id', $saleId)
            ->get();
    }

    public function getVouchersByOrderId(int $orderId): Collection
    {
        return Voucher::select('id', 'cancelled_at', 'member_id')
            ->where('generated_by_order_id', $orderId)
            ->whereNull('used_at')
            ->get();
    }

    public function checkGeneratedVoucherIsUsed(int $saleId): bool
    {
        return Voucher::select('id', 'cancelled_at', 'used_at')
            ->where('generated_by_sale_id', $saleId)
            ->whereNotNull('used_at')
            ->exists();
    }

    public function updateCancelledAt(Voucher $voucher): void
    {
        $voucher->cancelled_at = Carbon::now()->toDateTimeString();
        $voucher->status = VoucherStatusTypes::CANCELLED->value;
        $voucher->save();
    }

    public function resetUsedAt(Voucher $voucher): void
    {
        $voucher->used_at = null;
        $voucher->status = VoucherStatusTypes::ACTIVE->value;
        $voucher->save();
    }

    public function getById(int $voucherId): Voucher
    {
        return Voucher::select('id', 'used_at', 'member_id')
            ->findOrFail($voucherId);
    }

    public function getPaginatedVoucherList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getVoucherList($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getCountOfActiveVouchers(array $filterData, int $companyId): int
    {
        return $this->getVoucherList($filterData, $companyId)->where(
            'status',
            VoucherStatusTypes::ACTIVE->value
        )->count('id');
    }

    public function getVouchersForExport(array $filterData, int $companyId): Collection
    {
        return $this->getVoucherList($filterData, $companyId)->get();
    }

    public function getPaginatedVoucherListForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->getVoucherListForStoreManager($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getVouchersForExportStoreManager(array $filterData, int $companyId, int $locationId): Collection
    {
        return $this->getVoucherListForStoreManager($filterData, $companyId)->get();
    }

    public function fetchVoucherTransactionDetails(int $voucherId, int $companyId): ?Voucher
    {
        $saleQueries = resolve(SaleQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        return Voucher::query()
            ->select('id', 'used_at', 'generated_by_sale_id', 'cancelled_at')
            ->where('id', $voucherId)
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->with([
                'voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'voucherTransactions.sale:' . $saleQueries->getBasicColumns(),
                'voucherTransactions.order:' . $orderQueries->getColumnNamesForMarketPlace(),
                'voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            ])
            ->first();
    }

    public function getPaginatedListForMemberApi(array $filteredData, int $memberId): LengthAwarePaginator
    {
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'used_at',
            'expiry_date',
            'created_by_location_id',
            'status'
        )
            ->with([
                'voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'voucherTransactions.sale:' . $saleQueries->getOfflineSaleIdWithStatus(),
                'voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'voucherConfiguration:' . $voucherConfigurationQueries->getSeasonalSalesBasicColumns(),
            ])
            ->where('member_id', $memberId)
            ->whereNull('cancelled_at')
            ->when($filteredData['status'], function ($query) use ($filteredData): void {
                $query->where('status', (int) $filteredData['status']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function getActiveVoucherCountFor(int $memberId): int
    {
        return (int) Voucher::where('member_id', $memberId)
            ->onlyActive()
            ->count();
    }

    public function getVoucherWithExpiryDue(): array
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $expiredVouchers = Voucher::query()
            ->select('id', 'cancelled_at', 'used_at', 'expiry_date', 'member_id')
            ->whereNotNull('expiry_date')
            ->whereNull('cancelled_at')
            ->where('status', '!=', VoucherStatusTypes::EXPIRED->value)
            ->where('expiry_date', '<=', $yesterday)
            ->get();

        foreach ($expiredVouchers->whereNull('used_at') as $collection) {
            $collection->status = VoucherStatusTypes::EXPIRED->value;
            $collection->save();
        }

        return $expiredVouchers->pluck('member_id')->toArray();
    }

    public function getActiveVoucher(): Closure
    {
        return fn ($query) => $query->select('id', 'member_id')
            ->onlyActive();
    }

    public function filterByOnlyActiveQuery(): Closure
    {
        return fn ($query) => $query->onlyActive();
    }

    public function getVoucherStoreWiseForApplication(int $companyId, int $locationId): Collection
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'expiry_date',
            'dream_price_applicable',
            'item_wise_promotion_applicable',
            'cart_wide_promotion_applicable',
            'created_by_location_id',
            'status',
            'created_at'
        )
            ->with([
                'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'voucherTransactions.sale:' . $saleQueries->getBasicColumnNames(),
            ])
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->whereNull('cancelled_at')
            ->where('created_by_location_id', $locationId)
            ->where('status', VoucherStatusTypes::ACTIVE->value)
            ->get();
    }

    public function getByOnlyId(int $voucherId): Voucher
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'expiry_date',
            'dream_price_applicable',
            'item_wise_promotion_applicable',
            'cart_wide_promotion_applicable',
            'created_by_location_id',
            'status',
            'created_at',
            'used_at',
            'generated_by_order_id',
            'cancelled_at',
        )
        ->with(['voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForEcommerce()])
        ->findOrFail($voucherId);
    }

    private function getVoucherList(array $filterData, int $companyId): Builder
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'generated_by_sale_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'created_at',
            'used_at',
            'cancelled_at',
            'expiry_date',
            'status',
            'created_by_location_id',
        )
            ->with([
                'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'createdByLocation:' . $locationQueries->getBasicColumnNames(),
            ])
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData, $memberQueries): void {
                $query->where(function ($query) use ($filterData, $memberQueries): void {
                    $query
                        ->whereAny(
                            ['number', 'minimum_spend_amount', 'percentage', 'flat_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('member', $memberQueries->searchByBasicColumns($filterData['search_text']));
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData, $memberQueries): void {
                $query->whereHas('member', $memberQueries->filterById((int) $filterData['member_id']));
            })
            ->when($filterData['status_type'], function ($query) use ($filterData): void {
                $date = Carbon::now()->format('Y-m-d');
                $query->when(
                    (int) $filterData['status_type'] === VoucherStatusTypes::ACTIVE->value,
                    function ($query) use ($date): void {
                        $query->whereNull('used_at')
                            ->whereDate('expiry_date', '>=', $date);
                    }
                )
                    ->when(
                        (int) $filterData['status_type'] === VoucherStatusTypes::EXPIRED->value,
                        function ($query) use ($date): void {
                            $query->whereNull('used_at')
                                ->whereDate('expiry_date', '<', $date);
                        }
                    )
                    ->when(
                        (int) $filterData['status_type'] === VoucherStatusTypes::USED->value,
                        function ($query): void {
                            $query->whereNotNull('used_at');
                        }
                    );
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $saleQueries): void {
                $query->whereHas('sale', function ($query) use ($filterData, $saleQueries): void {
                    $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                        ->where($saleQueries->filterByStoreIds($filterData['location_ids']));
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getVoucherListForStoreManager(array $filterData, int $companyId): Builder
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'generated_by_sale_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'created_at',
            'used_at',
            'cancelled_at',
            'expiry_date',
            'status',
            'created_by_location_id'
        )
            ->with([
                'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'createdByLocation:' . $locationQueries->getBasicColumnNames(),
            ])
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData, $memberQueries): void {
                $query->where(function ($query) use ($filterData, $memberQueries): void {
                    $query
                        ->whereAny(
                            ['number', 'minimum_spend_amount', 'percentage', 'flat_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('member', $memberQueries->searchByBasicColumns($filterData['search_text']));
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData, $memberQueries): void {
                $query->whereHas('member', $memberQueries->filterById((int) $filterData['member_id']));
            })
            ->when($filterData['status_type'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['status_type']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function generateUniqueVoucherNumber(): string
    {
        $randomString = Str::upper(Str::random(5));
        $timestamp = Carbon::now()->getTimestamp();
        $voucherNumber = $randomString . $timestamp;

        $existVoucherNumbers = Voucher::whereCaseSensitive('number', $voucherNumber)->exists();

        if ($existVoucherNumbers) {
            return $this->generateUniqueVoucherNumber();
        }

        return $voucherNumber;
    }

    public function getSeasonalSalesVoucherColumns(): Closure
    {
        $voucherConfigurationQueries = new VoucherConfigurationQueries();

        return fn ($query) => $query->select('id', 'voucher_configuration_id')
            ->with('voucherConfiguration:' . $voucherConfigurationQueries->getSeasonalSalesBasicColumns());
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $vouchers = Voucher::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($vouchers as $voucher) {
            $voucher->member_id = $newMemberId;
            $voucher->save();
        }
    }

    public function getListForEcommerceWithRelatedData(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Voucher::select(
            'id',
            'voucher_configuration_id',
            'member_id',
            'discount_type',
            'number',
            'minimum_spend_amount',
            'percentage',
            'flat_amount',
            'expiry_date',
            'dream_price_applicable',
            'item_wise_promotion_applicable',
            'cart_wide_promotion_applicable',
            'created_by_location_id',
            'status',
        )
            ->with([
                'voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForPosMemberApi(),
                'voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'member:' . $memberQueries->getBasicColumnNames(),
            ])
            ->whereHas('voucherConfiguration', $voucherConfigurationQueries->filterByCompany($companyId))
            ->whereNull('cancelled_at')
            ->orderBy('id', 'desc')
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('status', VoucherStatusTypes::ACTIVE->value);
            })
            ->get();
    }
}
