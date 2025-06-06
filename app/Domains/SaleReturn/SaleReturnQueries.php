<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemComplimentary\SaleItemComplimentaryQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Domains\Size\SizeQueries;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaleReturnQueries
{
    public function getPaginatedSaleReturnsWithRelations(array $filterData, int $companyId): LengthAwarePaginator
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'member_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'originalSale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['offline_sale_return_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_return_id', $filterData['offline_sale_return_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getFilteredTotalsForReport(array $filterData, int $companyId): ?SaleReturn
    {
        return SaleReturn::join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->select(
                DB::raw('count(DISTINCT sale_returns.id) as total_return_sales'),
                DB::raw('SUM(sri.quantity) as total_units_returned'),
                DB::raw('SUM(total_price_paid) as total_return_amount')
            )
            ->join(
                DB::raw(
                    '(SELECT sale_return_id, SUM(quantity) as quantity FROM sale_return_items GROUP BY sale_return_id) as sri'
                ),
                function ($join): void {
                    $join->on('sale_returns.id', '=', 'sri.sale_return_id');
                }
            )
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('s.id', $filterData['location_ids']);
            }, function ($query) use ($companyId): void {
                $query->where('s.company_id', $companyId);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sale_returns.offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('sale_returns.digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('cu.counter_id', $filterData['counter_ids']);
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData): void {
                $query->where('cu.cashier_id', $filterData['cashier_id']);
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('sale_returns.member_id', $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['offline_sale_return_id'], function ($query) use ($filterData): void {
                $query->where('sale_returns.offline_sale_return_id', $filterData['offline_sale_return_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->first();
    }

    public function getFilteredTotalsDifferentStoreForReport(array $filterData, int $companyId): ?SaleReturn
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return SaleReturn::join('sale_return_items as si', 'sale_returns.id', '=', 'si.sale_return_id')
            ->join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->join('sales as os', 'sale_returns.original_sale_id', '=', 'os.id')
            ->join('counter_updates as ocu', 'os.counter_update_id', '=', 'ocu.id')
            ->join('counters as oc', 'ocu.counter_id', '=', 'oc.id')
            ->select(
                DB::raw('count(DISTINCT sale_returns.id) as total_return_sales'),
                DB::raw('SUM(si.quantity) as total_units_returned'),
                DB::raw('SUM(si.total_price_paid) as total_return_amount')
            )
            ->whereRaw('c.location_id != oc.location_id')
            ->when($filterData['location_ids'], function ($query) use ($filterData, $companyId): void {
                $query->whereIntegerInRaw('s.id', $filterData['location_ids'])
                    ->where('s.company_id', $companyId);
            }, function ($query) use ($companyId): void {
                $query->where('s.company_id', $companyId);
            })
            ->when(
                array_key_exists(
                    'original_sale_location_ids',
                    $filterData
                ) && [] !== $filterData['original_sale_location_ids'],
                function ($query) use ($filterData, $counterQueries): void {
                    $query->whereHas('originalSale', function ($query) use ($counterQueries, $filterData): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                                $query->select('id', 'counter_id')
                                    ->whereHas(
                                        'counter',
                                        $counterQueries->filterByLocations(
                                            (array) $filterData['original_sale_location_ids']
                                        )
                                    );
                            });
                    });
                }
            )
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('sale_returns.digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sale_returns.offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('cu.counter_id', $filterData['counter_ids']);
            })
            ->when(
                array_key_exists(
                    'original_sale_counter_ids',
                    $filterData
                ) && [] !== $filterData['original_sale_counter_ids'],
                function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas('originalSale', function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas(
                                'counterUpdate',
                                $counterUpdateQueries->filterByCounterIds(
                                    (array) $filterData['original_sale_counter_ids']
                                )
                            );
                    });
                }
            )
            ->when($filterData['cashier_id'], function ($query) use ($filterData): void {
                $query->where('cu.cashier_id', $filterData['cashier_id']);
            })
            ->when(
                array_key_exists(
                    'original_sale_cashier_id',
                    $filterData
                ) && null !== $filterData['original_sale_cashier_id'],
                function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas('originalSale', function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas(
                                'counterUpdate',
                                $counterUpdateQueries->filterByCashierId((int) $filterData['original_sale_cashier_id'])
                            );
                    });
                }
            )
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('sale_returns.member_id', $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->first();
    }

    public function getPaginatedSaleReturnsWithRelationsForStoreManager(
        array $filterData,
        array $locationIds,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getSaleReturnsWithRelationsForStoreManager($filterData, $locationIds, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function getPaginatedDifferentStoresReturnsForStoreManager(
        array $filterData,
        array $locationIds,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getDifferentStoreReturnsForStoreManager($filterData, $locationIds, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function addNew(
        ?int $memberId,
        int $counterUpdateId,
        int $originalSaleId,
        SaleData $saleData,
        bool $hasSaleReturnMismatches,
        string $digitalInvoiceNumber
    ): SaleReturn {
        return SaleReturn::create([
            'offline_sale_return_id' => $saleData->offline_sale_id,
            'original_sale_id' => $originalSaleId,
            'counter_update_id' => $counterUpdateId,
            'member_id' => $memberId,
            'happened_at' => $saleData->happened_at,
            'notes' => $saleData->sale_notes,
            'has_mismatch' => $hasSaleReturnMismatches,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function updateTotals(SaleReturn $saleReturn, float $roundOffAmount = 0.00): void
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        $saleReturn->load('saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForSaleUpdate());
        $saleReturn->update([
            'total_tax_amount' => $saleReturn->saleReturnItems->sum('total_tax_amount'),
            'cart_discount_amount' => $saleReturn->saleReturnItems->sum('cart_discount_amount'),
            'items_discount_amount' => $saleReturn->saleReturnItems->sum('item_discount_amount'),
            'total_discount_amount' => $saleReturn->saleReturnItems->sum('total_discount_amount'),
            'total_price_paid' => $saleReturn->saleReturnItems->sum('total_price_paid') + $roundOffAmount,
            'round_off_amount' => $roundOffAmount,
            'total_amount_before_round_off' => $saleReturn->saleReturnItems->sum('total_price_paid'),
        ]);
    }

    public function getByCounterUpdateId(int $counterUpdateId): Collection
    {
        return SaleReturn::query()
            ->select('id', 'total_price_paid', 'round_off_amount')
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }

    public function doesOfflineSaleReturnIdExist(string $offlineSaleId, int $companyId): bool
    {
        $counterUpdateQueries = new CounterUpdateQueries();

        return SaleReturn::query()
            ->select('id', 'counter_update_id')
            ->where('offline_sale_return_id', $offlineSaleId)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->exists();
    }

    public function loadRelations(SaleReturn $saleReturn): SaleReturn
    {
        $productQueries = resolve(ProductQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemDiscountsQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $saleReturn->refresh();

        return $saleReturn->load([
            'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
            'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
            'saleReturnItems.saleItem:' . $saleItemQueries->getBasicColumnNamesForSaleReturn(),
            'saleReturnItems.saleItem.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
            'saleReturnItems.saleItem.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'saleReturnItems.saleItem.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
            'saleReturnItems.saleItem.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleReturnItems.saleItem.saleItemDiscounts:' . $saleItemDiscountsQueries->getBasicColumnNames(),
            'saleReturnItems.saleItem.saleItemDiscounts.discountable',
            'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
            'saleReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'saleReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'saleReturnItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'saleReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'saleReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'creditNote:' . $creditNoteQueries->getBasicColumnNames(),
            'creditNote.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'creditNote.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'creditNote.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'creditNote.counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'creditNote.member',
            'creditNote.mismatches',
            'creditNote.saleReturn:' . $this->getOfflineIdAndSaleReturnIdColumnNames(),
            'creditNote.saleReturn.originalSale:' . $saleQueries->getOfflineSaleId(),
            'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'mismatches',
        ]);
    }

    public function filterByHappenedAtWithinDateRange(array $date): Closure
    {
        return fn ($query) => $query->where('happened_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date[1]));
    }

    public function getPaginatedSaleReturnsWithAllRelations(
        array $filterData,
        int $locationId,
        int $companyId,
    ): LengthAwarePaginator {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItemDiscountsQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'total_amount_before_round_off',
                'happened_at',
                'notes',
                'has_mismatch',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturnItems.saleItem:' . $saleItemQueries->getColumnNamesForPos(),
                'saleReturnItems.saleItem.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.saleItemDiscounts:' . $saleItemDiscountsQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.saleItemDiscounts.discountable',
                'saleReturnItems.saleItem.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'saleReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleReturnItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'creditNote:' . $creditNoteQueries->getBasicColumnNames(),
                'creditNote.member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'creditNote.mismatches',
                'creditNote.saleReturn:' . $this->getOfflineIdAndSaleReturnIdColumnNames(),
                'creditNote.saleReturn.originalSale:' . $saleQueries->getOfflineSaleId(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'mismatches',
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($filterData['member_id'], function ($query) use ($filterData, $memberQueries): void {
                $member = $memberQueries->memberById((int) $filterData['member_id']);
                if ($member->status === Status::ACTIVE->value) {
                    $query->where('member_id', $filterData['member_id']);
                }
            })->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->where(function ($query) use ($filterData, $productQueries): void {
                    $query->where('offline_sale_return_id', 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('member', $this->filterByFirstAndLastName($filterData['search_text']))
                        ->orWhereHas('saleReturnItems', function ($query) use ($productQueries, $filterData): void {
                            $query->select('id', 'product_id')
                                ->whereHas('product', $productQueries->filterByUpc($filterData['search_text']));
                        });
                });
            })
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query) use ($filterData): void {
                $query->when($filterData['from_date'], function ($query) use ($filterData): void {
                    $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['from_date']));
                });
                $query->when($filterData['to_date'], function ($query) use ($filterData): void {
                    $query->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['to_date']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function filterByFirstAndLastName(string $name): Closure
    {
        return fn ($query) => $query
            ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $name . '%');
    }

    public function getSaleReturnsWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
                'member_id',
                'digital_invoice_number'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'originalSale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['offline_sale_return_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_return_id', $filterData['offline_sale_return_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getByStoreIdForSalesCollectionExport(array $filterData): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'happened_at',
                'notes',
                'round_off_amount as round_off',
            )
            ->with(
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            )
            ->whereHas('counterUpdate', function ($query) use (
                $filterData,
                $counterUpdateQueries,
                $counterQueries
            ): void {
                $query->select('id', 'counter_id', 'cashier_id')
                    ->where('opened_by_pos_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
                    ->whereHas('counter', function ($query) use ($counterQueries, $filterData): void {
                        $query->select('id')->where($counterQueries->filterByLocations($filterData['location_ids']));
                    })
                    ->when(null !== $filterData['counter_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->select('id')->where(
                            $counterUpdateQueries->filterByCounterIds($filterData['counter_ids'])
                        );
                    })
                    ->when(null !== $filterData['cashier_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->select('id')->where(
                            $counterUpdateQueries->filterByCashierIds($filterData['cashier_ids'])
                        );
                    });
            })
            ->orderBy('happened_at', 'asc')
            ->get();
    }

    public function getSaleReturnsWithRelationsForStoreManagerExport(
        array $filterData,
        array $locationIds,
        int $companyId
    ): Collection {
        return $this->getSaleReturnsWithRelationsForStoreManager($filterData, $locationIds, $companyId)->get();
    }

    public function getDifferentStoresReturnsForStoreManagerExport(
        array $filterData,
        array $locationIds,
        int $companyId
    ): Collection {
        return $this->getDifferentStoreReturnsForStoreManager($filterData, $locationIds, $companyId)->get();
    }

    public function getDailyStoreWiseData(string $startDate, string $endDate): Collection
    {
        return SaleReturn::join('sale_return_items as si', 'sale_returns.id', '=', 'si.sale_return_id')
            ->join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->join('products', 'si.product_id', '=', 'products.id')
            ->where('sale_returns.happened_at', '>=', $startDate)
            ->where('sale_returns.happened_at', '<=', $endDate)
            ->selectRaw('
                SUM(si.total_price_paid) as total_sale_return_amount,
                SUM(si.quantity) as total_units_return,
                c.location_id,
                products.brand_id,
                cu.opened_by_pos_at,
                cu.created_at,
                s.company_id,
                sale_returns.counter_update_id
            ')
            ->groupBY('c.location_id')
            ->groupBY('products.brand_id')
            ->groupBY('sale_returns.counter_update_id')
            ->get();
    }

    public function getDailyStoreWiseDataForCounterUpdate(int $counterUpdateId): Collection
    {
        return SaleReturn::join('sale_return_items as si', 'sale_returns.id', '=', 'si.sale_return_id')
            ->join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->join('products', 'si.product_id', '=', 'products.id')
            ->where('sale_returns.counter_update_id', $counterUpdateId)
            ->selectRaw('
                SUM(si.total_price_paid) as total_sale_return_amount,
                SUM(si.quantity) as total_units_return,
                c.location_id,
                products.brand_id,
                cu.opened_by_pos_at,
                cu.created_at,
                s.company_id,
                sale_returns.counter_update_id
            ')
            ->groupBY('c.location_id')
            ->groupBY('products.brand_id')
            ->groupBY('sale_returns.counter_update_id')
            ->get();
    }

    public function getTotalSaleReturnsAmountAndCount(
        string $cacheKey,
        string $fromDate,
        string $toDate,
        int $companyId,
        ?int $locationId
    ): ?SaleReturn {
        $cacheFileName = 'cache-hourly-sales-return-' . $cacheKey . '-' . $locationId;

        return Cache::remember(
            $cacheFileName,
            Cache::has($cacheFileName) && ! Cache::get($cacheFileName) ? 600 : 150,
            fn (): ?SaleReturn => SaleReturn::join(
                'sale_return_items as si',
                'sale_returns.id',
                '=',
                'si.sale_return_id'
            )
                ->join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
                ->join('counters as c', 'cu.counter_id', '=', 'c.id')
                ->join('locations as s', 'c.location_id', '=', 's.id')
                ->selectRaw('
                SUM(si.total_price_paid) as total_sale_return_amount,
                SUM(si.quantity) as total_units_return
            ')
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('s.id', $locationId);
                }, function ($query) use ($companyId): void {
                    $query->where('s.company_id', $companyId);
                })
                ->where('sale_returns.happened_at', '>=', $fromDate)
                ->where('sale_returns.happened_at', '<=', $toDate)
                ->first()
        );
    }

    public function getForSaleReturnReport(array $filterData): Collection
    {
        return $this->getForSaleReturnAndSaleExchangeReportQuery($filterData)
            ->get();
    }

    public function getForSaleReturnAndSaleExchangeReport(array $filterData): Collection
    {
        return $this->getForSaleReturnAndSaleExchangeReportQuery($filterData)
            ->get();
    }

    public function getForExchangeReport(array $filterData): Collection
    {
        return $this->getForSaleReturnAndSaleExchangeReportQuery($filterData)
            ->whereHas('exchangeSale', function ($query): void {
                $query->select('id')
                    ->whereHas('saleItems', function ($query): void {
                        $query->select('id')
                            ->where('is_exchange', true);
                    });
            })
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_update_id,member_id,happened_at,total_tax_amount,total_discount_amount,total_price_paid,total_amount_before_round_off,notes,round_off_amount';
    }

    public function getOfflineIdAndSaleReturnIdColumnNames(): string
    {
        return 'id,offline_sale_return_id,original_sale_id';
    }

    public function getBasicColumnNamesForSerialNumberDetails(): string
    {
        return 'id,offline_sale_return_id,original_sale_id,counter_update_id,happened_at,member_id';
    }

    public function getBasicColumnsForReport(): string
    {
        return 'id,offline_sale_return_id,happened_at,notes';
    }

    public function getBasicColumnNamesForReturnAndExchangeReport(): string
    {
        return 'id,offline_sale_return_id,happened_at,counter_update_id';
    }

    public function getOfflineSaleReturnId(): string
    {
        return 'id,offline_sale_return_id,notes';
    }

    public function getOfflineAndCounterUpdateId(): string
    {
        return 'id,offline_sale_return_id,counter_update_id';
    }

    public function getOfflineColumn(): string
    {
        return 'id,offline_sale_return_id';
    }

    public function getIdColumn(): string
    {
        return 'id,total_tax_amount,total_discount_amount,total_price_paid,round_off_amount';
    }

    public function getByOfflineId(string $offlineSaleReturnId, int $companyId): ?SaleReturn
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SaleReturn::query()
            ->select('id')
            ->where('offline_sale_return_id', $offlineSaleReturnId)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->first();
    }

    public function getSaleReturnItemsBy(int $saleReturnId, int $companyId): SaleReturn
    {
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return SaleReturn::query()
                ->select(
                    'id',
                    'offline_sale_return_id',
                    'original_sale_id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_price_paid',
                    'round_off_amount',
                    'happened_at',
                    'member_id',
                    'digital_invoice_number'
                )
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->with([
                    'member:' . $memberQueries->getBasicColumnNamesForSale(),
                    'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                    'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                    'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                    'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                    'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                    'saleReturnItems.saleItem:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                    'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                ])
                ->findOrFail($saleReturnId);
        }

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
                'member_id',
                'digital_invoice_number'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturnItems.saleItem:' . $saleItemQueries->getColumnNamesForPos(),
                'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'saleReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleReturnId);
    }

    public function getSaleReturnItemsForStoreManager(int $saleReturnId, int $locationId, int $companyId): SaleReturn
    {
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'member:' . $memberQueries->getBasicColumnNamesForSale(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
            'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
            'saleReturnItems.saleItem:' . $saleItemQueries->getColumnNamesForPos(),
            'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
            'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
            'saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'originalSale:' . $saleQueries->getOfflineSaleId(),
        ];

        if (config('app.product_variant')) {
            $relations[] = 'saleReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames();
            $relations[] = 'saleReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames();
        } else {
            $relations[] = 'saleReturnItems.product.color:' . $colorQueries->getBasicColumnNames();
            $relations[] = 'saleReturnItems.product.size:' . $sizeQueries->getBasicColumnNames();
        }

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
                'member_id'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with($relations)
            ->findOrFail($saleReturnId);
    }

    public function getHourlyBasedData(
        int $companyId,
        int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh
    ): Collection {
        $cacheKey = 'cache-hourly-sale-returns-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $date . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => DB::table('sale_returns')
                ->selectRaw("DATE_FORMAT(happened_at, '%H') AS hour_of_day")
                ->selectRaw('SUM(sale_return_items.total_price_paid) AS today_sales')
                ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->join('sale_return_items', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
                ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                ->where('locations.company_id', $companyId)
                ->when($locationId > 0, function ($query) use ($locationId): void {
                    $query->where('counters.location_id', $locationId);
                })
                ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                    $query->where('products.brand_id', $brandId);
                })
                ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date))
                ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date))
                ->groupBy('hour_of_day')
                ->orderBy('hour_of_day', 'ASC')
                ->get()
        );
    }

    public function filterByStoreId(int $locationId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate',
            $counterUpdateQueries->filterByCounter($locationId)
        );
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate',
            $counterUpdateQueries->filterByCompanyId($companyId)
        );
    }

    public function getSaleReturnsByEmployeeWithDateRange(
        string $previousDate,
        string $currentDate,
        int $employeeId
    ): Collection {
        return SaleReturn::select('id', 'total_price_paid', 'member_id')
            ->whereHas('member', function ($query) use ($employeeId): void {
                $query->where('employee_id', $employeeId);
            })
            ->where('happened_at', '>=', CommonFunctions::addStartTime($previousDate))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($currentDate))
            ->get();
    }

    public function getSaleReturnsReceiptCount(int $locationId, int $companyId, array $date): SaleReturn
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SaleReturn::query()
            ->selectRaw('COUNT(DISTINCT sale_returns.id) as total_sales')
            ->selectRaw('SUM(sale_return_items.total_price_paid) as total_sales_amount')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->where($this->filterByHappenedAtWithinDateRange($date))
            ->firstOrFail();
    }

    public function getSaleReturnsForTheStoreManagerApplication(array $filterData, int $companyId): Collection
    {
        resolve(CounterUpdateQueries::class);
        resolve(CounterQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'total_price_paid',
                'member_id',
                'counter_update_id'
            )
            ->with([
                'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->whereHas('counterUpdate', function ($query) use ($locationQueries, $filterData, $companyId): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', function ($query) use ($filterData, $companyId, $locationQueries): void {
                        $query->select('id', 'location_id')
                            ->where('location_id', (int) $filterData['location_id'])
                            ->whereHas(
                                'location',
                                $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)
                            );
                    })
                    ->where('opened_by_pos_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
                    ->where('opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
                    ->when($filterData['counter_id'], function ($query) use ($filterData): void {
                        $query->where('counter_id', (int) $filterData['counter_id']);
                    })
                    ->when($filterData['cashier_id'], function ($query) use ($filterData): void {
                        $query->where('cashier_id', (int) $filterData['cashier_id']);
                    });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', $filterData['member_id']);
            })->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when(null !== $filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getSaleReturnItemsForStoreManagerApi(int $saleReturnId, int $locationId, int $companyId): SaleReturn
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
                'member_id'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturnItems.saleItem:' . $saleItemQueries->getColumnNamesForPos(),
                'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'originalSale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->findOrFail($saleReturnId);
    }

    public function getPaginatedDifferentStoreReturnsWithRelation(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->commonQueryForDifferentStoreReturnsWithRelation($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getDifferentStoreReturnWithRelationForExport(array $filterData, int $companyId): Collection
    {
        return $this->commonQueryForDifferentStoreReturnsWithRelation($filterData, $companyId)
            ->get();
    }

    public function getSaleReturnsDataCollectionForTheIOICityMall(int $locationId, string $date): Collection
    {
        $counterQueries = resolve(CounterQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'happened_at',
            )
            ->whereHas('exchangeSale', function ($query): void {
                $query->select('id', 'sale_return_id')
                    ->whereHas('saleItems', function ($query): void {
                        $query->select('id', 'sale_id')
                            ->where('is_exchange', false);
                    });
            })
            ->where('happened_at', '>=', CommonFunctions::addStartTime($date))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date))
            ->whereHas('counterUpdate', function ($query) use ($counterQueries, $locationId): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', $counterQueries->filterByLocationId($locationId));
            })
            ->get();
    }

    public function getSaleReturnsDataCollectionForTheTRXMall(int $locationId, string $date): Collection
    {
        $counterQueries = resolve(CounterQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'happened_at',
            )
            ->whereHas('exchangeSale', function ($query): void {
                $query->select('id', 'sale_return_id')
                    ->whereHas('saleItems', function ($query): void {
                        $query->select('id', 'sale_id')
                            ->where('is_exchange', false);
                    });
            })
            ->where('happened_at', '>=', CommonFunctions::addStartTime($date))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date))
            ->whereHas('counterUpdate', function ($query) use ($counterQueries, $locationId): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', $counterQueries->filterByLocationId($locationId));
            })
            ->get();
    }

    public function getTotalAmountForSaleCompanyTarget(string $startDate, string $endDate, int $companyId): SaleReturn
    {
        return SaleReturn::join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->select(DB::raw('SUM(total_price_paid) as total_return_amount'))
            ->where('happened_at', '>=', CommonFunctions::addStartTime($startDate))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($endDate))
            ->where('s.company_id', $companyId)
            ->firstOrFail();
    }

    public function getTotalAmountForSaleStoreTarget(string $startDate, string $endDate, array $locationIds): Collection
    {
        return SaleReturn::join('counter_updates as cu', 'sale_returns.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->select('c.location_id', DB::raw('SUM(total_price_paid) as total_return_amount'))
            ->where('happened_at', '>=', CommonFunctions::addStartTime($startDate))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($endDate))
            ->whereIntegerInRaw('c.location_id', $locationIds)
            ->groupBy('location_id')
            ->get();
    }

    public function getSumOfQuantity(): Closure
    {
        return fn ($query) => $query->select('id', 'counter_update_id')
            ->withSum('saleReturnItems as quantity', 'quantity');
    }

    public function getSelectIdANdOfflineIdColumn(): Closure
    {
        return fn ($query) => $query->select('id', 'offline_sale_return_id');
    }

    public function getBasicColumnForDigitalInvoice(): Closure
    {
        return fn ($query) => $query->select('id', 'offline_sale_return_id', 'digital_invoice_number');
    }

    public function getIdAndSaleReturnIdColumns(): string
    {
        return 'id,sale_return_id';
    }

    private function commonQueryForDifferentStoreReturnsWithRelation(array $filterData, int $companyId): Builder
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleReturn::query()
            ->select(
                'sale_returns.id',
                'sale_returns.offline_sale_return_id',
                'sale_returns.original_sale_id',
                'sale_returns.member_id',
                'sale_returns.counter_update_id',
                'sale_returns.total_tax_amount',
                'sale_returns.total_discount_amount',
                'sale_returns.total_price_paid',
                'sale_returns.round_off_amount',
                'sale_returns.happened_at',
                'sale_returns.digital_invoice_submitted',
                'sale_returns.digital_invoice_number',
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'originalSale:' . $saleQueries->getBasicColumnNames(),
                'originalSale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'originalSale.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'originalSale.counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'originalSale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'originalSale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            ])
            ->join(
                'counter_updates as counter_updates_returns',
                'sale_returns.counter_update_id',
                '=',
                'counter_updates_returns.id'
            )
            ->join('counters as counter_returns', 'counter_updates_returns.counter_id', '=', 'counter_returns.id')
            ->join('sales as original_sales', 'original_sales.id', '=', 'sale_returns.original_sale_id')
            ->join(
                'counter_updates as counter_updates_sales',
                'original_sales.counter_update_id',
                '=',
                'counter_updates_sales.id'
            )
            ->join('counters as counter_sales', 'counter_updates_sales.counter_id', '=', 'counter_sales.id')
            ->whereRaw('counter_returns.location_id != counter_sales.location_id')
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('sale_returns.digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when(
                array_key_exists(
                    'original_sale_location_ids',
                    $filterData
                ) && [] !== $filterData['original_sale_location_ids'],
                function ($query) use ($filterData, $counterQueries): void {
                    $query->whereHas('originalSale', function ($query) use ($counterQueries, $filterData): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                                $query->select('id', 'counter_id')
                                    ->whereHas(
                                        'counter',
                                        $counterQueries->filterByLocations(
                                            (array) $filterData['original_sale_location_ids']
                                        )
                                    );
                            });
                    });
                }
            )
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when(
                array_key_exists(
                    'original_sale_counter_ids',
                    $filterData
                ) && [] !== $filterData['original_sale_counter_ids'],
                function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas('originalSale', function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas(
                                'counterUpdate',
                                $counterUpdateQueries->filterByCounterIds(
                                    (array) $filterData['original_sale_counter_ids']
                                )
                            );
                    });
                }
            )
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when(
                array_key_exists(
                    'original_sale_cashier_id',
                    $filterData
                ) && null !== $filterData['original_sale_cashier_id'],
                function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas('originalSale', function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas(
                                'counterUpdate',
                                $counterUpdateQueries->filterByCashierId((int) $filterData['original_sale_cashier_id'])
                            );
                    });
                }
            )
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getForSaleReturnAndSaleExchangeReportQuery(array $filterData): Builder
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
            )
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'saleReturnItems.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns);
                },
                'saleReturnItems.saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleReturnItems.saleItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'originalSale:' . $saleQueries->getBasicColumnsForReport(),
                'originalSale.saleItems:' . $saleItemQueries->getBasicColumnNames(),
                'originalSale.saleItems.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns);
                },
                'exchangeSale:' . $saleQueries->getBasicColumnsForSaleReturnReport(),
                'exchangeSale.saleItems:' . $saleItemQueries->getBasicColumnNamesForSaleReturnReport(),
                'exchangeSale.saleItems.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns);
                },
            ])
            ->whereHas('saleReturnItems', function ($query): void {
                $query->select('id', 'product_id')
                    ->whereHas('product', function ($query): void {
                        if (config('app.product_variant')) {
                            $query->select('id', 'master_product_id')
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_selling_item', false);
                                });
                        } else {
                            $query->select('id')
                                ->where('is_non_selling_item', false);
                        }
                    });
            })
            ->whereHas('originalSale', function ($query): void {
                $query->select('id')
                    ->whereHas('saleItems', function ($query): void {
                        $query->select('id', 'product_id')
                            ->whereHas('product', function ($query): void {
                                if (config('app.product_variant')) {
                                    $query->select('id', 'master_product_id')
                                        ->whereHas('masterProduct', function ($query): void {
                                            $query->where('is_non_selling_item', false);
                                        });
                                } else {
                                    $query->select('id')
                                        ->where('is_non_selling_item', false);
                                }
                            });
                    });
            })
            ->whereHas('counterUpdate', function ($query) use (
                $filterData,
                $counterUpdateQueries,
                $counterQueries
            ): void {
                $query->whereHas('counter', function ($query) use ($counterQueries, $filterData): void {
                    $query->where($counterQueries->filterByLocations($filterData['location_ids']));
                })
                ->when(null !== $filterData['counter_ids'], function ($query) use (
                    $filterData,
                    $counterUpdateQueries
                ): void {
                    $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                })
                ->when(null !== $filterData['cashier_ids'], function ($query) use (
                    $filterData,
                    $counterUpdateQueries
                ): void {
                    $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            });
    }

    private function getSaleReturnsWithRelationsForStoreManager(
        array $filterData,
        array $locationIds,
        int $companyId
    ): Builder {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdsAndCompanyId($locationIds, $companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'originalSale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['offline_sale_return_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_return_id', $filterData['offline_sale_return_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getDifferentStoreReturnsForStoreManager(
        array $filterData,
        array $locationIds,
        int $companyId
    ): Builder {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleReturn::query()
            ->select(
                'sale_returns.id',
                'sale_returns.offline_sale_return_id',
                'sale_returns.original_sale_id',
                'sale_returns.member_id',
                'sale_returns.counter_update_id',
                'sale_returns.total_tax_amount',
                'sale_returns.total_discount_amount',
                'sale_returns.total_price_paid',
                'sale_returns.round_off_amount',
                'sale_returns.happened_at',
                'sale_returns.digital_invoice_submitted',
                'sale_returns.digital_invoice_number',
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdsAndCompanyId($locationIds, $companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'originalSale:' . $saleQueries->getBasicColumnNames(),
                'originalSale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'originalSale.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'originalSale.counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'originalSale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'originalSale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            ])
            ->join(
                'counter_updates as counter_updates_returns',
                'sale_returns.counter_update_id',
                '=',
                'counter_updates_returns.id'
            )
            ->join('counters as counter_returns', 'counter_updates_returns.counter_id', '=', 'counter_returns.id')
            ->join('sales as original_sales', 'original_sales.id', '=', 'sale_returns.original_sale_id')
            ->join(
                'counter_updates as counter_updates_sales',
                'original_sales.counter_update_id',
                '=',
                'counter_updates_sales.id'
            )
            ->join('counters as counter_sales', 'counter_updates_sales.counter_id', '=', 'counter_sales.id')
            ->whereRaw('counter_returns.location_id != counter_sales.location_id')
            ->when(
                array_key_exists(
                    'original_sale_location_ids',
                    $filterData
                ) && [] !== $filterData['original_sale_location_ids'],
                function ($query) use ($filterData, $counterQueries): void {
                    $query->whereHas('originalSale', function ($query) use ($counterQueries, $filterData): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                                $query->select('id', 'counter_id')
                                    ->whereHas(
                                        'counter',
                                        $counterQueries->filterByLocations(
                                            (array) $filterData['original_sale_location_ids']
                                        )
                                    );
                            });
                    });
                }
            )
            ->when(
                array_key_exists(
                    'original_sale_counter_ids',
                    $filterData
                ) && [] !== $filterData['original_sale_counter_ids'],
                function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas('originalSale', function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas(
                                'counterUpdate',
                                $counterUpdateQueries->filterByCounterIds(
                                    (array) $filterData['original_sale_counter_ids']
                                )
                            );
                    });
                }
            )
            ->when(
                array_key_exists(
                    'original_sale_cashier_id',
                    $filterData
                ) && null !== $filterData['original_sale_cashier_id'],
                function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas('originalSale', function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->select('id', 'counter_update_id')
                            ->whereHas(
                                'counterUpdate',
                                $counterUpdateQueries->filterByCashierId((int) $filterData['original_sale_cashier_id'])
                            );
                    });
                }
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getSaleReturnHourForPrint(array $filterData, int $companyId): Collection
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $startDate = Carbon::parse($filterData['date_range'][0])->format('Y-m-d H:00:00');
        $endDate = Carbon::parse($filterData['date_range'][1])->format('Y-m-d H:59:59');

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'counter_update_id',
                'original_sale_id',
                'happened_at',
                'total_price_paid'
            )
            ->with([
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when(null !== $filterData['location_id'], function ($query) use (
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByStoreId((int) $filterData['location_id'])
                );
            })
            ->whereBetween('happened_at', [$startDate, $endDate])
            ->orderBy('happened_at', 'asc')
            ->get();
    }

    public function getSeasonalSaleReturnsData(
        array $filterData,
        array $dateRange,
        int $companyId,
        string $name
    ): Collection {
        return DB::table('sale_returns')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'sale_return_items.product_id', '=', 'products.id')
            ->when(config('app.master_product'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
                    ->join('brands', 'master_products.brand_id', '=', 'brands.id');
            }, function ($query): void {
                $query->join('brands', 'products.brand_id', '=', 'brands.id');
            })
            ->where('locations.company_id', $companyId)
            ->when(null !== $filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                if (config('app.master_product')) {
                    $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                } else {
                    $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                }
            })
            ->where(function ($query) use ($dateRange): void {
                $query->where(function ($query) use ($dateRange): void {
                    $query->where('happened_at', '>=', CommonFunctions::addStartTime($dateRange[0]))
                        ->where('happened_at', '<=', CommonFunctions::addEndTime($dateRange[1]));
                })
                    ->when(count($dateRange) === 4, function ($query) use ($dateRange): void {
                        $query->orWhere(function ($query) use ($dateRange): void {
                            $query->where('happened_at', '>=', CommonFunctions::addStartTime($dateRange[2]))
                                ->where('happened_at', '<=', CommonFunctions::addEndTime($dateRange[3]));
                        });
                    });
            })
            ->select(
                'sale_returns.id',
                'brands.id as brand_id',
                'brands.name as brand_name',
                'locations.id as location_id',
                'locations.name as location_name',
                DB::raw('DATE_FORMAT(happened_at,"%Y-%m-%d") as happened_at'),
                DB::raw('SUM(sale_return_items.total_price_paid) as ' . $name),
            )
            ->groupBy('brand_id', 'location_id', 'happened_at')
            ->get();
    }

    public function digitalInvoiceUpdate(int $saleReturnId): void
    {
        $saleReturn = SaleReturn::select('id', 'digital_invoice_submitted', 'offline_sale_return_id')
            ->where('digital_invoice_submitted', false)
            ->findOrFail($saleReturnId);
        $saleReturn->update([
            'digital_invoice_submitted' => true,
        ]);
    }

    public function getSaleReturnByStoreIdCounterId(string $receiptNumber, int $locationId, int $counterId): ?SaleReturn
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SaleReturn::select('id', 'digital_invoice_submitted')
            ->where('offline_sale_return_id', $receiptNumber)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounterIdAndLocationId($locationId, $counterId))
            ->first();
    }

    public function getPaginatedMemberSaleReturnDetails(array $filterData, int $companyId): LengthAwarePaginator
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'offline_sale_return_id',
                'original_sale_id',
                'member_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_price_paid',
                'round_off_amount',
                'happened_at',
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'originalSale:' . $saleQueries->getBasicColumnNames(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNames(),
            ])
            ->whereHas(
                'counterUpdate',
                $counterUpdateQueries->filterByCompanyIdAndStoreId($companyId, $filterData['location_id'])
            )
            ->withSum('saleReturnItems', 'quantity')
            ->where('member_id', (int) $filterData['member_id'])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_return_id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $saleReturns = SaleReturn::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($saleReturns as $saleReturn) {
            $saleReturn->member_id = $newMemberId;
            $saleReturn->save();
        }
    }

    public function getAchievedSaleTargetSaleReturn(
        array $dateRange,
        int $companyId,
        ?array $locationIds,
        ?array $promoterIds
    ): Collection {
        return SaleReturn::query()
            ->select(
                'offline_sale_return_id',
                'locations.name as location_name',
                'counters.name as counter_name',
                'sale_returns.total_price_paid as amount'
            )
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                $query->whereIn('locations.id', $locationIds);
            })
            ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.original_sale_item_id')
                    ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                    ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
            })
            ->where('happened_at', '>=', CommonFunctions::addStartTime($dateRange[0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($dateRange[1]))
            ->get();
    }

    public function getSaleReturnQuantity(int $productId, int $locationId, string $date): Collection
    {
        return SaleReturn::select(
            'sale_returns.id',
            DB::raw('SUM(sale_return_items.quantity) as return_units'),
            DB::raw('SUM(sale_return_items.total_price_paid) as total_return_amount')
        )
        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
        ->join('locations', 'counters.location_id', '=', 'locations.id')
        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
        ->whereDate('sale_returns.happened_at', '=', $date)
        ->where('locations.id', $locationId)
        ->where('products.id', $productId)
        ->get();
    }

    public function getDayCloseSaleReturnsForExport(array $counterUpdateIds): Collection
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        return SaleReturn::query()
            ->select(
                'id',
                'original_sale_id',
                'offline_sale_return_id',
                'digital_invoice_number',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_price_paid',
                'happened_at',
            )
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'originalSale:' . $saleQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.company.defaultCountry.currency:'. $currencyQueries->getBasicColumnNames(),
            ])
            ->whereIn('counter_update_id', $counterUpdateIds)
            ->get();
    }
}
