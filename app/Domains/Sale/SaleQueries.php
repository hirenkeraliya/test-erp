<?php

declare(strict_types=1);

namespace App\Domains\Sale;

use App\CommonFunctions;
use App\Domains\AssemblyProduct\AssemblyChildProductQueries;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\CancelCreditSale\CancelCreditSaleQueries;
use App\Domains\CancelLayawaySale\CancelLayawaySaleQueries;
use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\CompanySettingQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Enums\SaleDiscountTypeReports;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemAssemblyChildProduct\SaleItemAssemblyChildProductQueries;
use App\Domains\SaleItemComplimentary\SaleItemComplimentaryQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemPriceOverride\SaleItemPriceOverrideQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaleQueries
{
    public function getPaginatedRegularAndCompleteSalesWithRelations(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->withSum('saleItems', 'quantity')
            ->withSum('saleItems', 'returned_quantity')
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
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
            ->when($filterData['offline_sale_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_id', $filterData['offline_sale_id']);
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

    public function getFilteredTotalsForReport(array $filterData, int $companyId): ?Sale
    {
        return Sale::join('counter_updates as cu', 'sales.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->select(
                DB::raw('count(DISTINCT sales.id) as total_sales'),
                DB::raw('SUM(si.quantity) as total_units_sold'),
                DB::raw('SUM(total_amount_paid) as total_sales_amount')
            )
            ->join(
                DB::raw('(SELECT sale_id, SUM(quantity) as quantity FROM sale_items GROUP BY sale_id) as si'),
                function ($join): void {
                    $join->on('sales.id', '=', 'si.sale_id');
                }
            )
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')
                    ->isNotExchange()
                    ->whereNull('sale_return_item_id');
            })
            ->whereIntegerInRaw('sales.status', SaleStatus::getOnlyLayawayAndCreditCompleteSaleStatusValues())
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('s.id', $filterData['location_ids']);
            }, function ($query) use ($companyId): void {
                $query->where('s.company_id', $companyId);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sales.offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('sales.digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('cu.counter_id', $filterData['counter_ids']);
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData): void {
                $query->where('cu.cashier_id', $filterData['cashier_id']);
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('sales.member_id', $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['offline_sale_id'], function ($query) use ($filterData): void {
                $query->where('sales.offline_sale_id', $filterData['offline_sale_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('sales.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sales.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->first();
    }

    public function getPaginatedSaleExchangesWithRelations(array $filterData, int $companyId): LengthAwarePaginator
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'sale_return_id',
                    'offline_sale_id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'total_amount_before_round_off',
                    'happened_at',
                    'bill_reference_number',
                    'round_off',
                    'member_id',
                    'digital_invoice_submitted',
                    'digital_invoice_number',
                )
                ->whereNotNull('sale_return_id')
                ->whereHas('saleItems', function ($query): void {
                    $query->select('id')->isExchange();
                })
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->with([
                    'member:' . $memberQueries->getBasicColumnNamesForSale(),
                    'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                    'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                    'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                    'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                    'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'cashback:' . $saleCashbackQueries->getColumnNamesForAdminReports(),
                    'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                    'mismatches',
                    'saleReturn:' . $saleReturnQueries->getBasicColumnNames(),
                    'saleReturn.mismatches',
                    'saleReturn.saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                    'saleReturn.saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleReturn.saleReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleReturn.saleReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleReturn.saleReturnItems.saleItem:' . $saleItemQueries->getBasicColumnNamesForSaleExchanges(),
                    'saleReturn.saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                ])
                ->when($filterData['search_text'], function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
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
                ->when($filterData['date_range'], function ($query) use ($filterData): void {
                    $query->where('happened_at', '>=', $filterData['date_range'][0])
                        ->where('happened_at', '<=', $filterData['date_range'][1]);
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }, function ($query): void {
                    $query->orderBy('happened_at', 'desc');
                })
                ->paginate($filterData['per_page']);
        }

        return Sale::query()
            ->select(
                'id',
                'sale_return_id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'total_amount_before_round_off',
                'happened_at',
                'bill_reference_number',
                'round_off',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->whereNotNull('sale_return_id')
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isExchange();
            })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForAdminReports(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'mismatches',
                'saleReturn:' . $saleReturnQueries->getBasicColumnNames(),
                'saleReturn.mismatches',
                'saleReturn.saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'saleReturn.saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'saleReturn.saleReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleReturn.saleReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleReturn.saleReturnItems.saleItem:' . $saleItemQueries->getBasicColumnNamesForSaleExchanges(),
                'saleReturn.saleReturnItems.saleReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
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
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('happened_at', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getSaleExchangesWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'sale_return_id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'total_amount_before_round_off',
                'happened_at',
                'bill_reference_number',
                'member_id'
            )
            ->whereNotNull('sale_return_id')
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getBasicColumnNamesForSaleExchanges(),
                'saleReturn:' . $saleReturnQueries->getBasicColumnNames(),
                'saleReturn.saleReturnItems:' . $saleReturnItemQueries->getQuantityColumnForSaleExchanges(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
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
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('happened_at', 'desc');
            })
            ->get();
    }

    public function getPaginatedRegularSalesAndCompleteWithRelationsForStoreManager(
        array $filterData,
        array $locationIds,
        int $companyId,
    ): LengthAwarePaginator {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdsAndCompanyId($locationIds, $companyId))
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->withSum('saleItems', 'quantity')
            ->withSum('saleItems', 'returned_quantity')
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
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
            ->when($filterData['offline_sale_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_id', $filterData['offline_sale_id']);
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

    public function getPaginatedVoidSalesWithRelations(array $filterData, int $companyId): LengthAwarePaginator
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'notes',
                'has_mismatch',
                'status',
                'bill_reference_number',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->onlyVoidedSales()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getVoidSaleNumberPrefixColumn(),
                'voidSale:' . $voidSaleQueries->getColumnsForListPage(),
                'voidSale.voidedByStoreManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'voidSale.voidedByStoreManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when($filterData['void_sale_number'], function ($query) use ($filterData): void {
                $query->whereHas('voidSale', function ($query) use ($filterData): void {
                    $query->select('id', 'void_sale_number')
                        ->where('void_sale_number', $filterData['void_sale_number']);
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
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
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
            ->orderBy('id', 'desc')
            ->paginate($filterData['per_page']);
    }

    public function getPaginatedVoidSalesWithRelationsForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): LengthAwarePaginator {
        return $this->getVoidSalesWithRelationsForStoreManager($filterData, $locationId, $companyId)->paginate();
    }

    public function loadVoidSaleRelations(Sale $sale): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $saleItemAssemblyChildProductQueries = resolve(SaleItemAssemblyChildProductQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);

        $sale->refresh();

        return $sale->load([
            'member:' . $memberQueries->getBasicColumnNamesForSale(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company:' . $companyQueries->getVoidSaleNumberPrefixColumn(),
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts.discountable',
            'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator:' . $saleItemPriceOverrideQueries->getNegotiatorBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
            'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
            'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemUnits:' . $saleItemUnitQueries->getBasicColumnNames(),
            'saleItems.saleItemAssemblyChildProducts:' . $saleItemAssemblyChildProductQueries->getBasicColumnNames(),
            'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
            'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'payments:' . $salePaymentQueries->getBasicColumnNames(),
            'payments.currency:' . $currencyQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'generatedVouchers:' . $voucherQueries->getColumnNames(),
            'generatedVouchers.voucherConfiguration:' . $voucherConfigurationQueries->getFooterColumns(),
            'generatedVouchers.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
            'generatedVouchers.voucherTransactions.sale:' . $this->getBasicColumns(),
            'generatedVouchers.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
            'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
            'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
            'usedVoucher.discountable.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
            'usedVoucher.discountable.voucherTransactions.sale:' . $this->getBasicColumns(),
            'usedVoucher.discountable.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            'voidSale:' . $voidSaleQueries->getColumnsForListPage(),
            'voidSale.voidedByStoreManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'voidSale.voidedByStoreManager.employee:' . $employeeQueries->getBasicColumnNames(),
            'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
            'voidSale.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'mismatches',
        ]);
    }

    public function loadCancelLayawaySaleRelations(Sale $sale): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $cancelLayawaySaleQueries = resolve(CancelLayawaySaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $sale->refresh();

        return $sale->load([
            'member:' . $memberQueries->getBasicColumnNamesForSale(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company:' . $companyQueries->getVoidSaleNumberPrefixColumn(),
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts.discountable',
            'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator:' . $saleItemPriceOverrideQueries->getNegotiatorBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
            'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
            'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'payments:' . $salePaymentQueries->getBasicColumnNames(),
            'payments.currency:' . $currencyQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'cancelLayawaySale:' . $cancelLayawaySaleQueries->getBasicColumnNames(),
            'cancelLayawaySale.creditNote:' . $creditNoteQueries->getBasicColumnNames(),
            'cancelLayawaySale.storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'cancelLayawaySale.storeManager.employee:' . $employeeQueries->getBasicColumnNames(),
            'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
            'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
            'mismatches',
        ]);
    }

    public function loadCancelCreditSaleRelations(Sale $sale): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $cancelCreditSaleQueries = resolve(CancelCreditSaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $sale->refresh();

        return $sale->load([
            'member:' . $memberQueries->getBasicColumnNamesForSale(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company:' . $companyQueries->getVoidSaleNumberPrefixColumn(),
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts.discountable',
            'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator:' . $saleItemPriceOverrideQueries->getNegotiatorBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
            'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
            'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'payments:' . $salePaymentQueries->getBasicColumnNames(),
            'payments.currency:' . $currencyQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'cancelCreditSale:' . $cancelCreditSaleQueries->getBasicColumnNames(),
            'cancelCreditSale.creditNote:' . $creditNoteQueries->getBasicColumnNames(),
            'cancelCreditSale.storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'cancelCreditSale.storeManager.employee:' . $employeeQueries->getBasicColumnNames(),
            'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
            'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
            'mismatches',
        ]);
    }

    public function getPaginatedPendingLayawaySalesWithRelations(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'total_amount_before_round_off',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->when(
                $filterData['status_id'] === CreditAndLayawaySaleStatuses::PENDING->value,
                function ($query): void {
                    $query->onlyPendingLayawaySale();
                },
                function ($query): void {
                    $query->onlyCompleteLayawaySale();
                }
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'layawayAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'layawayAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
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

    public function getPaginatedPendingCreditSalesWithRelations(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'credit_authorizer_id',
                'credit_authorizer_type',
                'happened_at',
                'status',
                'bill_reference_number',
                'total_amount_before_round_off',
                'notes',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->when(
                $filterData['status_id'] === CreditAndLayawaySaleStatuses::PENDING->value,
                function ($query): void {
                    $query->onlyPendingCreditSale();
                },
                function ($query): void {
                    $query->onlyCompleteCreditSale();
                }
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'creditAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'creditAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->whereAny(
                        ['offline_sale_id', 'bill_reference_number'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
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

    public function getPaginatedPendingLayawaySalesWithRelationsForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): LengthAwarePaginator {
        return $this->getPendingLayawaySalesWithRelationsForStoreManager(
            $filterData,
            $locationId,
            $companyId
        )->paginate($filterData['per_page']);
    }

    public function getPaginatedPendingCreditSalesWithRelationsForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): LengthAwarePaginator {
        return $this->getPendingCreditSalesWithRelationsForStoreManager($filterData, $locationId, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function addNew(
        ?int $memberId,
        int $counterUpdateId,
        SaleData $saleData,
        string $digitalInvoiceNumber,
        bool $hasSaleMismatches,
        ?int $saleReturnId = null,
    ): Sale {
        return Sale::create([
            'sale_return_id' => $saleReturnId,
            'offline_sale_id' => $saleData->offline_sale_id,
            'member_id' => $memberId,
            'counter_update_id' => $counterUpdateId,
            'notes' => $saleData->sale_notes,
            'bill_reference_number' => $saleData->bill_reference_number,
            'happened_at' => $saleData->happened_at,
            'has_mismatch' => $hasSaleMismatches,
            'change_due' => $saleData->change_due,
            'extra_details' => $saleData->extra_details ?? null,
            'status' => SaleStatus::getValueByCaseName('REGULAR_SALE'),
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function updateTotals(Sale $sale, ?float $roundOffAmount): void
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $sale->load('saleItems:' . $saleItemQueries->getColumnNamesForSaleUpdate());
        $saleItemTotalPricePaid = $sale->saleItems->sum('total_price_paid');

        $sale->update([
            'total_tax_amount' => $sale->saleItems->sum('total_tax_amount'),
            'items_discount_amount' => $sale->saleItems->sum('item_discount_amount'),
            'cart_discount_amount' => $sale->saleItems->sum('cart_discount_amount'),
            'total_discount_amount' => $sale->saleItems->sum('total_discount_amount'),
            'total_amount_paid' => ($sale->status === SaleStatus::PENDING_LAYAWAY_SALE->value || $sale->status === SaleStatus::PENDING_CREDIT_SALE->value) ?
                $saleItemTotalPricePaid :
                $saleItemTotalPricePaid + $roundOffAmount,
            'total_amount_before_round_off' => $saleItemTotalPricePaid,
            'round_off' => $roundOffAmount,
        ]);
    }

    public function updateLayawayPendingAmountAndStatus(
        Sale $sale,
        float $layawayPendingAmount,
        ?int $layawayStoreManagerId,
    ): void {
        $sale->update([
            'layaway_pending_amount' => $layawayPendingAmount,
            'status' => $layawayPendingAmount > 0 ?
                SaleStatus::PENDING_LAYAWAY_SALE->value :
                SaleStatus::COMPLETE_LAYAWAY_SALE->value,
            'layaway_authorizer_id' => $layawayStoreManagerId ?? null,
            'layaway_authorizer_type' => $layawayStoreManagerId ? ModelMapping::STORE_MANAGER->name : null,
        ]);
    }

    public function updateCreditPendingAmountAndStatus(
        Sale $sale,
        float $creditPendingAmount,
        ?int $creditStoreManagerId,
    ): void {
        $sale->update([
            'credit_pending_amount' => $creditPendingAmount,
            'status' => $creditPendingAmount > 0 ?
                SaleStatus::PENDING_CREDIT_SALE->value :
                SaleStatus::COMPLETE_CREDIT_SALE->value,
            'credit_authorizer_id' => $creditStoreManagerId ?? null,
            'credit_authorizer_type' => $creditStoreManagerId ? ModelMapping::STORE_MANAGER->name : null,
        ]);
    }

    public function loadRelations(Sale $sale): Sale
    {
        $productQueries = resolve(ProductQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemDiscountsQueries = resolve(SaleItemDiscountQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $sale->refresh();

        return $sale->load([
            'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
            'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
            'member.company:' . $companyQueries->getBasicColumnNames(),
            'member.company.media:' . $mediaQueries->getBasicColumnNames(),
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
            'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
            'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator:' . $saleItemPriceOverrideQueries->getNegotiatorBasicColumnNames(),
            'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountsQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts.discountable',
            'saleItems.saleItemUnits:' . $saleItemUnitQueries->getColumnNamesForPos(),
            'saleItems.saleItemUnits.batch:' . $batchQueries->getBasicColumnNames(),
            'saleItems.saleItemUnits.serialNumber:' . $serialNumberQueries->getBasicColumnNames(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.product.categories:' . $categoryQueries->getBasicColumnNames(),
            'saleItems.product.assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
            'saleItems.product.assemblyChildProducts.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.product.brand:' . $brandQueries->getBasicColumnNames(),
            'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'saleItems.product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
            'saleItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
            'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
            'payments.currency:' . $currencyQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'payments.bookingPaymentUse:' . $bookingPaymentUseQueries->getBasicColumnNames(),
            'payments.bookingPaymentUse.bookingPayment:' . $bookingPaymentQueries->getBasicColumnNames(),
            'generatedVouchers:' . $voucherQueries->getColumnNames(),
            'generatedVouchers.voucherConfiguration:' . $voucherConfigurationQueries->getFooterColumns(),
            'generatedVouchers.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
            'generatedVouchers.voucherTransactions.sale:' . $this->getBasicColumns(),
            'generatedVouchers.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
            'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
            'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
            'usedPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
            'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
            'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
            'usedVoucher.discountable.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
            'usedVoucher.discountable.voucherTransactions.sale:' . $this->getBasicColumns(),
            'usedVoucher.discountable.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            'mismatches',
        ]);
    }

    public function loadSaleItemsProductAndBrand(Sale $sale): Sale
    {
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $sale->refresh();

        return $sale->load(
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.product.brand:' . $brandQueries->getIdAndNameColumnNames(),
        );
    }

    public function getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits(int $saleId): Sale
    {
        $saleItemQueries = new SaleItemQueries();
        $saleItemUnitQueries = new SaleItemUnitQueries();
        $memberQueries = resolve(MemberQueries::class);
        $saleItemAssemblyChildProductQueries = new SaleItemAssemblyChildProductQueries();
        $productQueries = new ProductQueries();

        return Sale::select(
            'id',
            'sale_return_id',
            'offline_sale_id',
            'counter_update_id',
            'total_tax_amount',
            'cart_discount_amount',
            'items_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off',
            'total_amount_paid',
            'change_due',
            'layaway_pending_amount',
            'layaway_completed_at',
            'layaway_authorizer_id',
            'layaway_authorizer_type',
            'status',
            'notes',
            'bill_reference_number',
            'happened_at',
            'has_mismatch',
            'extra_details',
            'credit_pending_amount',
            'credit_completed_at',
            'credit_authorizer_id',
            'credit_authorizer_type',
            'member_id'
        )
            ->whereIntegerInRaw('status', SaleStatus::getCommonActiveSaleStatusValues())
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getBasicColumnNames(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.saleItemAssemblyChildProducts:' . $saleItemAssemblyChildProductQueries->getBasicColumnNames(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleId);
    }

    public function getPendingLayawaySaleByIdWithRelations(int $saleId): Sale
    {
        $saleItemQueries = new SaleItemQueries();
        $saleItemUnitQueries = new SaleItemUnitQueries();
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return Sale::select(
            'id',
            'sale_return_id',
            'offline_sale_id',
            'counter_update_id',
            'total_tax_amount',
            'cart_discount_amount',
            'items_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off',
            'total_amount_paid',
            'change_due',
            'layaway_pending_amount',
            'layaway_completed_at',
            'layaway_authorizer_id',
            'layaway_authorizer_type',
            'status',
            'notes',
            'bill_reference_number',
            'happened_at',
            'has_mismatch',
            'extra_details',
            'credit_pending_amount',
            'credit_completed_at',
            'credit_authorizer_id',
            'credit_authorizer_type',
            'member_id'
        )
            ->where('status', SaleStatus::PENDING_LAYAWAY_SALE->value)
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getBasicColumnNames(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleId);
    }

    public function markAsVoid(Sale $sale): void
    {
        $sale->status = SaleStatus::VOID_SALE->value;
        $sale->save();
    }

    public function markAsCancelLayaway(Sale $sale): void
    {
        $sale->status = SaleStatus::CANCEL_LAYAWAY_SALE->value;
        $sale->save();
    }

    public function markAsCancelCredit(Sale $sale): void
    {
        $sale->status = SaleStatus::CANCEL_CREDIT_SALE->value;
        $sale->save();
    }

    public function getPaginatedVoidedSales(array $filterData, int $locationId): LengthAwarePaginator
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'status',
                'member_id'
            )
            ->onlyVoidedSales()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'voidSale:' . $voidSaleQueries->getColumnsForListPage(),
                'voidSale.voidedByStoreManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'voidSale.voidedByStoreManager.employee:' . $employeeQueries->getBasicColumnNames(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'saleItems:' . $saleItemQueries->getColumnsForPaginatedVoidSales(),
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getBasicColumnNames(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'generatedVouchers.voucherConfiguration:' . $voucherConfigurationQueries->getFooterColumns(),
                'generatedVouchers.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'generatedVouchers.voucherTransactions.sale:' . $this->getBasicColumns(),
                'generatedVouchers.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'usedPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'usedVoucher.discountable.voucherTransactions:' . $voucherTransactionQueries->getBasicColumnNames(),
                'usedVoucher.discountable.voucherTransactions.sale:' . $this->getBasicColumns(),
                'usedVoucher.discountable.voucherTransactions.location:' . $locationQueries->getNameColumnName(),
                'voidSale.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'mismatches',
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('member', $this->searchByName($filterData['search_text']));
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', $filterData['member_id']);
            })->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })->when($filterData['is_user'], function ($query) use ($filterData): void {
                $query->when($filterData['is_user'], function ($query): void {
                    $query->whereNotNull('member_id');
                }, function ($query): void {
                    $query->whereNull('member_id');
                });
            })->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query) use ($filterData): void {
                $query->when($filterData['from_date'], function ($query) use ($filterData): void {
                    $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['from_date']));
                });
                $query->when($filterData['to_date'], function ($query) use ($filterData): void {
                    $query->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['to_date']));
                });
            })
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function getPaginatedRegularAndCompletedLayawaySalesWithItemsPaymentsAndMismatches(
        array $filterData,
        int $locationId,
    ): LengthAwarePaginator {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $saleItemDiscountsQueries = resolve(SaleItemDiscountQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'extra_details',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'round_off',
                'status',
                'credit_pending_amount',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountsQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getColumnNamesForPos(),
                'saleItems.saleItemUnits.serialNumber:' . $serialNumberQueries->getBasicColumnNames(),
                'saleItems.saleItemUnits.batch:' . $batchQueries->getBasicColumnNames(),
                'saleItems.product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'generatedVouchers.voucherConfiguration:' . $voucherConfigurationQueries->getFooterColumns(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->when($filterData['status_id'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_id']);
            }, function ($query): void {
                $query->onlyRegularCompleteCreditAndCompleteLayawaySale();
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })->when($filterData['counter_id'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas(
                    'counterUpdate.counter',
                    $counterQueries->filterById((int) $filterData['counter_id'])
                );
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->where(function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas(
                            'counterUpdate',
                            $counterUpdateQueries->searchByCashierName($filterData['search_text'])
                        )->orWhereHas(
                            'counterUpdate',
                            $counterUpdateQueries->searchByCounterAndStoreName($filterData['search_text'])
                        )
                        ->orWhereHas('member', $this->searchByName($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('user_id' === $filterData['sort_by']) {
                    $query->orderBy('member_id', $filterData['sort_direction']);
                }

                if ('user_id' !== $filterData['sort_by']) {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
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
            ->paginate($filterData['per_page']);
    }

    public function getSaleWithRelations(int $companyId, int|string $saleId): ?Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'round_off',
                'status',
                'credit_pending_amount',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'saleItems.product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getColumnNamesForPos(),
                'saleItems.saleItemUnits.batch:' . $batchQueries->getBasicColumnNames(),
                'saleItems.saleItemUnits.serialNumber:' . $serialNumberQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'voidSale:' . $voidSaleQueries->getBasicColumnNames(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'mismatches',
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->where(function ($query) use ($saleId): void {
                $query->where('offline_sale_id', $saleId)
                    ->orWhere('id', $saleId);
            })
            ->firstOrFail();
    }

    public function getPendingLayawaySalesWithItemsPaymentsAndMismatches(
        array $filterData,
        int $locationId,
    ): Collection {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'status',
                'round_off',
                'member_id'
            )
            ->onlyPendingLayawaySale()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.categories:' . $categoryQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'usedPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', $filterData['member_id']);
            })->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('member', $this->searchByName($filterData['search_text']));
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
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getPendingCreditSalesWithRelations(array $filterData, int $locationId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashbackQueries = resolve(CashbackQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'status',
                'round_off',
                'credit_pending_amount',
                'member_id'
            )
            ->onlyPendingCreditSale()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'cashback.cashbackConfiguration:' . $cashbackQueries->getBasicColumnNamesForPos(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', $filterData['member_id']);
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('member', $this->searchByName($filterData['search_text']));
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
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getPendingLayawaySaleByIdWithItemsPaymentsAndMismatches(int $saleId, int $locationId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'status',
                'round_off',
                'member_id',
            )
            ->onlyPendingLayawaySale()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->findOrFail($saleId);
    }

    public function getPendingCreditSaleByIdWithItemsPaymentsAndMismatches(int $saleId, int $locationId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'member_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'credit_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'status',
                'round_off',
            )
            ->onlyPendingCreditSale()
            ->with(
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->findOrFail($saleId);
    }

    public function getPendingCreditSaleByIdWithRelations(int|string $saleId, int $locationId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'status',
                'round_off',
                'credit_pending_amount',
                'member_id'
            )
            ->onlyPendingCreditSale()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.categories:' . $categoryQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->where(function ($query) use ($saleId): void {
                $query->where('offline_sale_id', $saleId)
                    ->orWhere('id', $saleId);
            })
            ->firstOrFail();
    }

    public function getSaleByIdWithSaleItems(int $saleId): Sale
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $companySettingQueries = resolve(CompanySettingQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'credit_pending_amount',
                'status',
                'notes',
                'bill_reference_number',
                'happened_at',
                'has_mismatch',
                'round_off',
                'member_id',
                'total_amount_before_round_off',
                'credit_completed_at',
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'member.membership:' . $membershipQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdColumnName(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company.companySetting:' . $companySettingQueries->getNameColumnName(),
                'saleItems:' . $saleItemQueries->getColumnsForCompleteLayawaySale(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.categories:' . $categoryQueries->getBasicColumnNames(),
                'saleItems.product.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'saleItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'saleItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleId);
    }

    public function updateLayawayAmountOf(Sale $sale, Collection $payments, string $happenedAt): Sale
    {
        $sale = $this->loadSaleItems($sale);
        $sale->layaway_pending_amount -= $payments->sum('amount');
        $sale->total_amount_paid += $payments->sum('amount');
        $sale->total_amount_before_round_off += $payments->sum('amount');

        if ($sale->layaway_pending_amount <= 0) {
            $sale->total_amount_before_round_off = $sale->saleItems->sum('total_price_paid');
            $sale->layaway_pending_amount = null;
            $sale->status = SaleStatus::COMPLETE_LAYAWAY_SALE->value;
            $sale->layaway_completed_at = $happenedAt;
        }

        $sale->save();

        return $sale;
    }

    public function updateCreditAmountOf(Sale $sale, Collection $payments, string $happenedAt): Sale
    {
        $sale = $this->loadSaleItems($sale);
        $sale->credit_pending_amount -= $payments->sum('amount');
        $sale->total_amount_paid += $payments->sum('amount');
        $sale->total_amount_before_round_off += $payments->sum('amount');

        if ($sale->credit_pending_amount <= 0) {
            $sale->total_amount_before_round_off = $sale->saleItems->sum('total_price_paid');
            $sale->credit_pending_amount = null;
            $sale->status = SaleStatus::COMPLETE_CREDIT_SALE->value;
            $sale->credit_completed_at = $happenedAt;
        }

        $sale->save();

        return $sale;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,offline_sale_id,counter_update_id,bill_reference_number,layaway_pending_amount,status,happened_at,member_id,cart_discount_amount,total_amount_paid';
    }

    public function getBasicColumnNamesForUsedVoucher(): string
    {
        return 'id,offline_sale_id,counter_update_id,total_tax_amount,cart_discount_amount,items_discount_amount,total_discount_amount,total_amount_paid,change_due,layaway_pending_amount,credit_pending_amount,total_amount_before_round_off,layaway_completed_at,happened_at,extra_details,notes,bill_reference_number,has_mismatch,round_off,status,credit_completed_at,member_id';
    }

    public function getTotalDiscountAmountColumn(): string
    {
        return 'id,member_id,total_discount_amount';
    }

    public function getOfflineSaleId(): string
    {
        return 'id,offline_sale_id';
    }

    public function getOfflineSaleIdWithStatus(): string
    {
        return 'id,offline_sale_id,status,happened_at';
    }

    public function getBasicColumnsForReport(): string
    {
        return 'id,offline_sale_id,counter_update_id,happened_at';
    }

    public function getBasicColumnsForSaleReturnReport(): string
    {
        return 'id,offline_sale_id,counter_update_id,sale_return_id,happened_at';
    }

    public function getBasicColumnNamesForSerialNumberDetails(): string
    {
        return 'id,offline_sale_id,counter_update_id,sale_return_id,happened_at,member_id';
    }

    public function getBasicColumns(): string
    {
        return 'id,offline_sale_id,counter_update_id,total_tax_amount,cart_discount_amount,items_discount_amount,total_discount_amount,total_amount_paid,layaway_pending_amount,status,member_id';
    }

    public function getBasicColumnsForSaleDetails(): string
    {
        return 'id,offline_sale_id,member_id,counter_update_id,total_tax_amount,total_discount_amount,total_amount_paid,happened_at,round_off,bill_reference_number,notes,total_amount_before_round_off,status';
    }

    public function loadSaleItems(Sale $sale): Sale
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return $sale->load([
            'saleItems:' . $saleItemQueries->getBasicColumnNames(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
        ]);
    }

    public function getRegularSalesByCounterUpdateId(int $counterUpdateId): Collection
    {
        return Sale::query()
            ->select(
                'id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_amount_paid',
                'round_off'
            )
            ->where('counter_update_id', $counterUpdateId)
            ->onlyRegular()
            ->get();
    }

    public function getSalesWithoutVoidSaleByCounterUpdateId(int $counterUpdateId): Collection
    {
        $saleItemQueries = resolve(SaleItemQueries::class);

        return Sale::query()
            ->select('id', 'total_amount_paid')
            ->with('saleItems:' . $saleItemQueries->getColumnNamesForPos())
            ->withoutVoidSale()
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }

    public function getLayawaySalesByCounterUpdateId(int $counterUpdateId): Collection
    {
        return Sale::query()
            ->select(
                'id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'round_off'
            )
            ->where('counter_update_id', $counterUpdateId)
            ->onlyLayawaySale()
            ->get();
    }

    public function getCancelLayawaySalesByCounterUpdateId(int $counterUpdateId): Collection
    {
        return Sale::query()
            ->select(
                'id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'round_off'
            )
            ->where('counter_update_id', $counterUpdateId)
            ->where('status', SaleStatus::CANCEL_LAYAWAY_SALE->value)
            ->get();
    }

    public function getCreditSalesByCounterUpdateId(int $counterUpdateId): Collection
    {
        return Sale::query()
            ->select(
                'id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'round_off'
            )
            ->where('counter_update_id', $counterUpdateId)
            ->onlyCreditSale()
            ->get();
    }

    public function getVoidedSalesByCounterUpdateId(int $counterUpdateId): Collection
    {
        return Sale::query()
            ->select('id', 'total_amount_paid')
            ->where('counter_update_id', $counterUpdateId)
            ->onlyVoidedSales()
            ->get();
    }

    public function filterByRegularLayawaySaleByCounterUpdateId(int $counterUpdateId): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw(
            'status',
            SaleStatus::getOnlyLayawayPendingAndCompleteSaleStatusValues()
        )
            ->where('counter_update_id', $counterUpdateId);
    }

    public function filterByRegularCreditAndLayawaySaleByCounterUpdateId(int $counterUpdateId): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw(
            'status',
            SaleStatus::getRegularPendingCancelAndCompleteActiveSaleStatusValues()
        )
            ->where('counter_update_id', $counterUpdateId);
    }

    public function getMorphAuthorizerColumns(): string
    {
        return 'id,employee_id';
    }

    public function doesOfflineSaleIdExist(string $offlineSaleId, int $companyId): bool
    {
        $counterUpdateQueries = new CounterUpdateQueries();

        return Sale::query()
            ->select('id', 'counter_update_id')
            ->where('offline_sale_id', $offlineSaleId)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->exists();
    }

    public function getSalesByPromoter(int $locationId, int $promoterId, ?string $afterUpdatedAt = null): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'happened_at',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'round_off',
                'status',
                'credit_pending_amount',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches',
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->whereHas('saleItems', $saleItemQueries->filterByPromoterId($promoterId))
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getSalesByCounterUpdateId(int $counterUpdateId, ?string $afterUpdatedAt = null): Collection
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'status',
                'notes',
                'bill_reference_number',
                'happened_at',
                'total_discount_amount',
                'credit_pending_amount',
                'member_id',
                'has_mismatch'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'mismatches',
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'voidSale:' . $voidSaleQueries->getBasicColumnNames(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
            ])
            ->where('counter_update_id', $counterUpdateId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function filterByCompanyIdForMemberSalesReport(int $companyId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas('member', function ($query): void {
            $query->whereNull('employee_id');
        })
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId));
    }

    public function filterByCompanyIdForEmployeeSalesReport(int $companyId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas('member', function ($query): void {
            $query->whereNotNull('employee_id');
        })
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId));
    }

    public function filterByCompanyIdForSalesCollectionExport(int $companyId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId));
    }

    public function filterByStoreIds(array $locationIds): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate.counter',
            $counterQueries->filterByLocations($locationIds)
        );
    }

    public function filterByStoreIdForMemberSalesReport(int $locationId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas('member', function ($query): void {
            $query->whereNull('employee_id');
        })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId));
    }

    public function filterByStoreIdForEmployeeSalesReport(int $locationId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas('member', function ($query): void {
            $query->whereNotNull('employee_id');
        })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId));
    }

    public function searchByMemberNameAndMobileNumber(string $search): Closure
    {
        return fn ($query) => $query->whereHas('member', function ($query) use ($search): void {
            $query->select('id')
                ->whereAny(['first_name', 'last_name', 'mobile_number'], 'LIKE', '%' . $search . '%');
        });
    }

    public function filterByFirstAndLastName(string $name): Closure
    {
        return fn ($query) => $query
            ->select('id', 'first_name', 'last_name')
            ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $name . '%');
    }

    public function filterByCounterUpdateId(int $counterUpdateId): Closure
    {
        return fn ($query) => $query->select('id')->where('counter_update_id', $counterUpdateId);
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate',
            $counterUpdateQueries->filterByCompanyId($companyId)
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

    public function filterByLocationId(int $locationId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate',
            $counterUpdateQueries->filterByCounter($locationId)
        );
    }

    public function getSalesByEmployeeWithDateRange(
        string $previousDate,
        string $currentDate,
        int $employeeId,
    ): Collection {
        return Sale::select('id', 'total_amount_paid', 'member_id')
            ->whereHas('member', function ($query) use ($employeeId): void {
                $query->where('employee_id', $employeeId);
            })
            ->where('happened_at', '>=', CommonFunctions::addStartTime($previousDate))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($currentDate))
            ->withoutVoidSale()
            ->get();
    }

    public function filterByCounterId(int $counterId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate',
            $counterUpdateQueries->filterByCounterId($counterId)
        );
    }

    public function filterByCounterIds(array $counterIds): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas(
            'counterUpdate',
            $counterUpdateQueries->filterByCounterIds($counterIds)
        );
    }

    public function filterByHappenedAtWithinDateRange(array $date): Closure
    {
        return fn ($query) => $query->where('happened_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date[1]));
    }

    public function filterForSalesByPromotersReport(array $filterData): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->select('id', 'offline_sale_id', 'counter_update_id')
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where($this->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterStores($filterData['location_ids'])
                );
            });
    }

    public function getRegularAndLayawaySalesWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'notes',
                'bill_reference_number',
                'total_amount_before_round_off',
                'member_id',
                'digital_invoice_number'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('saleItems', function ($query): void {
                $query->isNotExchange();
            })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
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
            ->when($filterData['offline_sale_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_id', $filterData['offline_sale_id']);
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
            ->get();
    }

    public function getVoidSalesWithRelationForExport(array $filterData, int $companyId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'notes',
                'has_mismatch',
                'status',
                'bill_reference_number',
                'member_id',
                'digital_invoice_number'
            )
            ->onlyVoidedSales()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getVoidSaleNumberPrefixColumn(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'voidSale:' . $voidSaleQueries->getColumnsForListPage(),
                'voidSale.voidedByStoreManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'voidSale.voidedByStoreManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
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
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getPendingLayawaySalesWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'happened_at',
                'status',
                'total_amount_before_round_off',
                'member_id',
                'notes',
                'bill_reference_number',
                'digital_invoice_number'
            )
            ->when(
                $filterData['status_id'] === CreditAndLayawaySaleStatuses::PENDING->value,
                function ($query): void {
                    $query->onlyPendingLayawaySale();
                },
                function ($query): void {
                    $query->onlyCompleteLayawaySale();
                }
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'layawayAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'layawayAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($counterQueries, $filterData): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
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
            ->get();
    }

    public function getPendingCreditSalesWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'credit_authorizer_id',
                'credit_authorizer_type',
                'happened_at',
                'total_amount_before_round_off',
                'status',
                'member_id',
                'digital_invoice_number',
                'bill_reference_number',
                'notes',
            )
            ->when(
                $filterData['status_id'] === CreditAndLayawaySaleStatuses::PENDING->value,
                function ($query): void {
                    $query->onlyPendingCreditSale();
                },
                function ($query): void {
                    $query->onlyCompleteCreditSale();
                }
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'creditAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'creditAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($counterQueries, $filterData): void {
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
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
            ->get();
    }

    public function getRegularAndLayawaySalesWithRelationsForExportInStoreManagerPanel(
        array $filterData,
        array $locationIds,
    ): Collection {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'notes',
                'bill_reference_number',
                'total_amount_before_round_off',
                'member_id',
                'digital_invoice_number'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIds($locationIds))
            ->whereHas('saleItems', function ($query): void {
                $query->isNotExchange();
            })
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('member', $this->filterByFirstAndLastName($filterData['search_text']));
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
            ->when($filterData['offline_sale_id'], function ($query) use ($filterData): void {
                $query->where('offline_sale_id', $filterData['offline_sale_id']);
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

    public function getVoidSalesWithRelationsForExportInStoreManagerPanel(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Collection {
        return $this->getVoidSalesWithRelationsForStoreManager($filterData, $locationId, $companyId)->get();
    }

    public function getPendingLayawaySalesWithRelationsForExportInStoreManagerPanel(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Collection {
        return $this->getPendingLayawaySalesWithRelationsForStoreManager($filterData, $locationId, $companyId)->get();
    }

    public function getPendingCreditSalesWithRelationsForExportInStoreManagerPanel(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Collection {
        return $this->getPendingCreditSalesWithRelationsForStoreManager($filterData, $locationId, $companyId)->get();
    }

    public function getByStoreIdForSalesCollectionExport(array $filterData): Collection
    {
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'offline_sale_id',
                'notes',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'total_tax_amount',
                'sale_return_id'
            )
            ->with([
                'payments' => function ($query): void {
                    $query->select('id', 'sale_id', 'payment_type_id', 'amount')
                        ->whereNot('payment_type_id', StaticPaymentTypes::CREDIT_NOTE->value);
                },
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdColumnName(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            ])
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
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

    public function getColumnNamesForVoidSaleCashBack(): string
    {
        return 'id,offline_sale_id,counter_update_id,happened_at';
    }

    public function getByStoreIdForSalesVoidReportExport(array $filterData): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);

        return Sale::query()
            ->select('id', 'offline_sale_id', 'happened_at')
            ->with([
                'saleItems:id,sale_id,price_paid_per_unit,quantity,product_id',
                'saleItems.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns);
                },
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'voidSale:' . $voidSaleQueries->getColumnsForListPage(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
                'voidSale.voidedByStoreManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'voidSale.voidedByStoreManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
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
            })
            ->onlyVoidedSales()
            ->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->whereHas('counterUpdate', function ($query) use (
                $filterData,
                $counterUpdateQueries,
                $counterQueries
            ): void {
                $query->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']))
                    ->when(null !== $filterData['counter_ids'], function ($query) use (
                        $counterUpdateQueries,
                        $filterData
                    ): void {
                        $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getSalesDataForChart(int $companyId, ?int $locationId): QueryBuilder
    {
        return DB::table('sales')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('locations.company_id', $companyId)
            ->when($locationId, function ($query) use ($locationId): void {
                $query->where('counters.location_id', $locationId);
            })
            ->where('sales.happened_at', '>=', Carbon::now()->startOfDay()->format('Y-m-d H:i:s'))
            ->where('sales.happened_at', '<=', Carbon::now()->endOfDay()->format('Y-m-d H:i:s'))
            ->whereIntegerInRaw('sales.status', SaleStatus::getOnlyLayawayPendingAndCompleteSaleStatusValues());
    }

    public function getHourlyBasedData(
        int $companyId,
        int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh,
    ): Collection {
        $cacheKey = 'cache-hourly-sales-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $date . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => DB::table('sales')
                ->selectRaw("DATE_FORMAT(happened_at, '%H') AS hour_of_day")
                ->selectRaw("DATE_FORMAT(happened_at, '%h %p') AS hour_of_day_string")
                ->selectRaw('SUM(sale_items.total_price_paid) AS today_sales')
                ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('locations.company_id', $companyId)
                ->when($locationId > 0, function ($query) use ($locationId): void {
                    $query->where('counters.location_id', $locationId);
                })
                ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                    $query->where('products.brand_id', $brandId);
                })
                ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date))
                ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date))
                ->groupBy('hour_of_day')
                ->orderBy('hour_of_day', 'ASC')
                ->get()
        );
    }

    public function getDailyStoreWiseData(string $startDate, string $endDate): Collection
    {
        return DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->where('sales.happened_at', '>=', $startDate)
            ->where('sales.happened_at', '<=', $endDate)
            ->selectRaw('
                SUM(sale_items.total_price_paid) as total_sales_amount,
                SUM(sale_items.quantity) as total_units_sold,
                sales.counter_update_id,
                counters.location_id as location_id,
                products.brand_id,
                counter_updates.opened_by_pos_at,
                counter_updates.created_at,
                locations.company_id,
                COUNT(DISTINCT(sale_items.sale_id)) as total_sales_count
            ')
            ->groupBY('counters.location_id')
            ->groupBY('products.brand_id')
            ->groupBY('sales.counter_update_id')
            ->get();
    }

    public function getDailyStoreWiseDataForCounterUpdate(int $counterUpdateId): Collection
    {
        return DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->where('sales.counter_update_id', $counterUpdateId)
            ->selectRaw('
                SUM(sale_items.total_price_paid) as total_sales_amount,
                SUM(sale_items.quantity) as total_units_sold,
                sales.counter_update_id,
                counters.location_id as location_id,
                products.brand_id,
                counter_updates.opened_by_pos_at,
                counter_updates.created_at,
                locations.company_id,
                COUNT(DISTINCT(sale_items.sale_id)) as total_sales_count
            ')
            ->groupBY('location_id')
            ->groupBY('products.brand_id')
            ->groupBY('sales.counter_update_id')
            ->get();
    }

    public function getTotalSalesAmountAndTotalSale(
        string $cacheKey,
        string $fromDate,
        string $toDate,
        int $companyId,
        ?int $locationId,
    ): ?Sale {
        $cacheFileName = 'cache-hourly-sales-' . $cacheKey . '-' . $locationId;

        return Cache::remember(
            $cacheFileName,
            Cache::has($cacheFileName) && ! Cache::get($cacheFileName) ? 600 : 150,
            fn (): ?Sale => Sale::join('sale_items as si', 'sales.id', '=', 'si.sale_id')
                ->join('counter_updates as cu', 'sales.counter_update_id', '=', 'cu.id')
                ->join('counters as c', 'cu.counter_id', '=', 'c.id')
                ->join('locations as s', 'c.location_id', '=', 's.id')
                ->select(
                    DB::raw('SUM(si.total_price_paid) as total_amount'),
                    DB::raw('SUM(si.quantity) as total_units_sold'),
                    DB::raw('count(DISTINCT sales.id) as total_sales_count')
                )
                ->whereIntegerInRaw('sales.status', SaleStatus::getOnlyLayawayPendingAndCompleteSaleStatusValues())
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('s.id', $locationId);
                }, function ($query) use ($companyId): void {
                    $query->where('s.company_id', $companyId);
                })
                ->where('sales.happened_at', '>=', $fromDate)
                ->where('sales.happened_at', '<=', $toDate)
                ->first()
        );
    }

    public function getByOfflineId(string $offlineSaleId, int $companyId): ?Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return Sale::query()
            ->select('id')
            ->where('offline_sale_id', $offlineSaleId)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->first();
    }

    public function getSaleItemsBy(int $saleId, int $companyId): Sale
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'offline_sale_id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_amount_before_round_off',
                    'total_discount_amount',
                    'total_amount_paid',
                    'happened_at',
                    'round_off',
                    'status',
                    'member_id',
                    'digital_invoice_number'
                )
                ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->whereHas('saleItems', function ($query): void {
                    $query->select('id')->isNotExchange();
                })
                ->with([
                    'member:' . $memberQueries->getBasicColumnNamesForSale(),
                    'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                    'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                    'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                    'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                    'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'cashback:' . $saleCashbackQueries->getColumnNamesForAdminReports(),
                    'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                    'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                ])
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_amount_before_round_off',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'member_id',
                'digital_invoice_number'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForAdminReports(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleId);
    }

    public function getLayawaySaleItemsBy(int $saleId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'layaway_pending_amount',
                    'total_amount_before_round_off',
                    'digital_invoice_number',
                    'offline_sale_id'
                )
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->with(
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts.discountable',
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                )
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'total_amount_before_round_off',
                'digital_invoice_number',
                'offline_sale_id'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with(
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            )
            ->findOrFail($saleId);
    }

    public function getLayawaySaleItemsByForPrint(int $saleId, int $companyId, ?int $locationId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'offline_sale_id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'layaway_pending_amount',
                    'member_id',
                    'total_amount_before_round_off'
                )
                ->onlyPendingLayawaySale()
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                    $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
                })
                ->with(
                    'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
                    'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                    'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                    'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesWithCode(),
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                )
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'member_id',
                'total_amount_before_round_off'
            )
            ->onlyPendingLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
            })
            ->with(
                'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
                'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            )
            ->findOrFail($saleId);
    }

    public function getCreditSaleItemsBy(int $saleId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'credit_pending_amount',
                    'total_amount_before_round_off',
                )
                ->onlyPendingCreditSale()
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->with(
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts.discountable',
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                )
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'total_amount_before_round_off',
            )
            ->onlyPendingCreditSale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with(
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            )
            ->findOrFail($saleId);
    }

    public function getCreditSaleItemsByForPrint(int $saleId, int $companyId, ?int $locationId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'offline_sale_id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'credit_pending_amount',
                    'total_amount_before_round_off',
                    'member_id'
                )
                ->onlyPendingCreditSale()
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                    $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
                })
                ->with(
                    'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
                    'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts.discountable',
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                )
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'total_amount_before_round_off',
                'member_id'
            )
            ->onlyPendingCreditSale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
            })
            ->with(
                'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
                'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            )
            ->findOrFail($saleId);
    }

    public function getSaleItemsForStoreManager(int $saleId, int $locationId, int $companyId): Sale
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [];
        if (config('app.product_variant')) {
            $relations = [
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ];
        } else {
            $relations = [
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            ];
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'total_amount_before_round_off',
                'member_id'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForAdminReports(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                ...$relations,
            ])
            ->findOrFail($saleId);
    }

    public function getVoidSaleItemsBy(int $saleId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'total_tax_amount',
                    'cart_discount_amount',
                    'items_discount_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                )
                ->onlyVoidedSales()
                ->with(
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                )
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
            )
            ->onlyVoidedSales()
            ->with(
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->findOrFail($saleId);
    }

    public function getLayawaySaleItemsForStoreManager(int $saleId, int $locationId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations[] = 'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames();
        } else {
            $relations[] = 'saleItems.product.color:' . $colorQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.size:' . $sizeQueries->getBasicColumnNames();
        }

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'happened_at',
                'status',
                'total_amount_before_round_off'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with($relations)
            ->findOrFail($saleId);
    }

    public function getCreditSaleItemsForStoreManager(int $saleId, int $locationId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts.discountable',
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations[] = 'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames();
        } else {
            $relations[] = 'saleItems.product.color:' . $colorQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.size:' . $sizeQueries->getBasicColumnNames();
        }

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'happened_at',
                'status',
                'total_amount_before_round_off',
            )
            ->onlyPendingCreditSale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with($relations)
            ->findOrFail($saleId);
    }

    public function getVoidSaleItemsForStoreManager(int $saleId, int $locationId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'mismatches',
        ];

        if (config('app.product_variant')) {
            $relations[] = 'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames();
        } else {
            $relations[] = 'saleItems.product.color:' . $colorQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.size:' . $sizeQueries->getBasicColumnNames();
        }

        return Sale::query()
            ->select(
                'id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'has_mismatch',
            )
            ->onlyVoidedSales()
            ->with($relations)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->findOrFail($saleId);
    }

    public function getOpenCounterSalesDetailsForReportsList(
        array $filterData,
        int $counterUpdateId,
        int $companyId,
    ): LengthAwarePaginator {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'total_amount_before_round_off',
                'member_id'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->where('counter_update_id', $counterUpdateId)
            ->whereHas('counterUpdate.counter.location.company', $companyQueries->filterById($companyId))
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->withSum('saleItems', 'quantity')
            ->withSum('saleItems', 'returned_quantity')
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getFilteredTotalsForOpenCountersReport(
        array $filterData,
        int $companyId,
        int $counterUpdateId,
    ): ?Sale {
        return Sale::join('sale_items as si', 'sales.id', '=', 'si.sale_id')
            ->join('counter_updates as cu', 'sales.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->select(
                DB::raw('count(DISTINCT sales.id) as total_sales'),
                DB::raw('SUM(si.quantity) as total_units_sold'),
                DB::raw('SUM(si.total_price_paid) as total_sales_amount')
            )
            ->where('si.is_exchange', false)
            ->where('sales.counter_update_id', $counterUpdateId)
            ->where('s.company_id', $companyId)
            ->whereNull('si.sale_return_item_id')
            ->whereNull('sales.layaway_pending_amount')
            ->whereIntegerInRaw('sales.status', SaleStatus::getOnlyLayawayPendingAndCompleteSaleStatusValues())
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sales.offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->first();
    }

    public function getSalesForTheStoreManagerApplication(array $filterData, int $companyId): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Sale::query()
            ->select('id', 'offline_sale_id', 'total_amount_paid', 'member_id', 'counter_update_id')
            ->withoutVoidSale()
            ->with([
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
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
                $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getSaleItemsForStoreManagerApi(int $saleId, int $locationId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'total_amount_before_round_off',
                'member_id'
            )
            ->withoutVoidSale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleId);
    }

    public function getPaginatedSaleListForMemberApi(array $filterData, int $memberId): LengthAwarePaginator
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $saleItemDiscountsQueries = resolve(SaleItemDiscountQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'total_amount_before_round_off',
                'layaway_completed_at',
                'credit_pending_amount',
                'happened_at',
                'extra_details',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'round_off',
                'status',
                'credit_completed_at',
                'member_id'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdNumberColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountsQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getColumnNamesForPos(),
                'saleItems.saleItemUnits.batch:' . $batchQueries->getBasicColumnNames(),
                'saleItems.product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->where('member_id', $memberId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getSaleDetailsById(int $saleId, int $memberId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashBackQueries = resolve(CashbackQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $saleItemDiscountsQueries = resolve(SaleItemDiscountQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'credit_pending_amount',
                'total_amount_before_round_off',
                'layaway_completed_at',
                'happened_at',
                'extra_details',
                'notes',
                'bill_reference_number',
                'has_mismatch',
                'round_off',
                'status',
                'credit_completed_at',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'generatedVouchers:' . $voucherQueries->getColumnNames(),
                'loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'cashback:' . $saleCashbackQueries->getColumnNamesForPos(),
                'usedVoucher:' . $saleDiscountQueries->getBasicColumnNames(),
                'usedVoucher.discountable:' . $voucherQueries->getVoucherConfigurationIdColumn(),
                'usedVoucher.discountable.voucherConfiguration:' . $voucherConfigurationQueries->getBasicColumnNamesForSalesApi(),
                'cashback.cashbackConfiguration:' . $cashBackQueries->getBasicColumnNamesForPos(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountsQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts.discountable',
                'saleItems.saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItems.saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItems.saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItems.saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getColumnNamesForPos(),
                'saleItems.saleItemUnits.batch:' . $batchQueries->getBasicColumnNames(),
                'saleItems.product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleItems.loyaltyPointUpdates:' . $loyaltyPointUpdateQueries->getBasicColumns(),
                'saleItems.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->where('member_id', $memberId)
            ->where('id', $saleId)
            ->get();
    }

    public function getSalesReceiptCount(int $locationId, array $date): Sale
    {
        $counterQueries = resolve(CounterQueries::class);

        return Sale::query()
            ->selectRaw('COUNT(DISTINCT sales.id) as total_sales')
            ->selectRaw('SUM(sale_items.total_price_paid) as total_sales_amount')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->whereHas('counterUpdate', function ($query) use ($locationId, $counterQueries): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', $counterQueries->filterByLocation($locationId));
            })
            ->where('sale_items.is_exchange', false)
            ->whereNull('sale_items.sale_return_item_id')
            ->whereNull('sales.layaway_pending_amount')
            ->whereNot('status', SaleStatus::VOID_SALE->value)
            ->where($this->filterByHappenedAtWithinDateRange($date))
            ->firstOrFail();
    }

    public function getCancelLayawaySaleItemsByForPrint(int $saleId, int $companyId, ?int $locationId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cancelLayawaySaleQueries = resolve(CancelLayawaySaleQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'offline_sale_id',
                    'bill_reference_number',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'layaway_pending_amount',
                    'total_amount_before_round_off',
                    'member_id'
                )
                ->cancelLayawaySale()
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                    $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
                })
                ->with(
                    'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
                    'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                    'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                    'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                    'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesWithCode(),
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'cancelLayawaySale:' . $cancelLayawaySaleQueries->getSaleIdColumn(),
                    'cancelLayawaySale.creditNote:' . $creditNoteQueries->getCancelLayawaySaleColumn(),
                )
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'bill_reference_number',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'total_amount_before_round_off',
                'member_id'
            )
            ->cancelLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
            })
            ->with(
                'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
                'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'cancelLayawaySale:' . $cancelLayawaySaleQueries->getSaleIdColumn(),
                'cancelLayawaySale.creditNote:' . $creditNoteQueries->getCancelLayawaySaleColumn(),
            )
            ->findOrFail($saleId);
    }

    public function getCancelLayawaySaleItemsBy(int $saleId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Sale::query()
                ->select(
                    'id',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'layaway_pending_amount',
                    'total_amount_before_round_off',
                )
                ->cancelLayawaySale()
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->with(
                    'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                    'saleItems.product:' . $productQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                    'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                    'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                )
                ->findOrFail($saleId);
        }

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'total_amount_before_round_off',
            )
            ->cancelLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with(
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            )
            ->findOrFail($saleId);
    }

    public function getCancelLayawaySaleItemsByForStoreManager(int $saleId, int $locationId, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
            'saleItems.saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'saleItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations[] = 'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames();
        } else {
            $relations[] = 'saleItems.product.color:' . $colorQueries->getBasicColumnNames();
            $relations[] = 'saleItems.product.size:' . $sizeQueries->getBasicColumnNames();
        }

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'happened_at',
                'status',
                'total_amount_before_round_off',
            )
            ->cancelLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with($relations)
            ->findOrFail($saleId);
    }

    public function getPaginatedCancelLayawaySalesWithRelations(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        return $this->getCancelLayawaySalesQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getCancelLayawaySalesWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        return $this->getCancelLayawaySalesQuery($filterData, $companyId)->get();
    }

    public function getPaginatedCancelLayawaySalesForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): LengthAwarePaginator {
        return $this->getCancelLayawaySalesQueryForStoreManager($filterData, $locationId, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function getCancelLayawaySalesExportForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Collection {
        return $this->getCancelLayawaySalesQueryForStoreManager($filterData, $locationId, $companyId)->get();
    }

    public function getFirstSaleHappenedAt(): string
    {
        $sale = Sale::query()
            ->select('id', 'happened_at')
            ->whereNotNull('happened_at')
            ->orderBy('happened_at')
            ->first();

        return $sale instanceof Sale ? $sale->happened_at : now()->format('Y-m-d H:i:s');
    }

    public function getSalesDataCollectionForTheIOICityMall(int $locationId, string $date): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
            )
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->onlyRegular()
            ->with([
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->where('happened_at', '>=', CommonFunctions::addStartTime($date))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date))
            ->whereHas('counterUpdate', function ($query) use ($counterQueries, $locationId): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', $counterQueries->filterByLocationId($locationId));
            })
            ->get();
    }

    public function getSalesDataCollectionForTheTRXMall(int $locationId, string $date): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return Sale::query()
            ->select(
                'id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
            )
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->onlyRegular()
            ->with([
                'payments:' . $salePaymentQueries->getBasicColumnNamesForSale(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->where('happened_at', '>=', CommonFunctions::addStartTime($date))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date))
            ->whereHas('counterUpdate', function ($query) use ($counterQueries, $locationId): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', $counterQueries->filterByLocationId($locationId));
            })
            ->get();
    }

    public function getSaleTotalByMemberId(int $memberId): int
    {
        return Sale::query()
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->where('member_id', $memberId)
            ->count();
    }

    public function getTotalAmountForSaleCompanyTarget(string $startDate, string $endDate, int $companyId): Sale
    {
        return Sale::join('counter_updates as cu', 'sales.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->join('locations as s', 'c.location_id', '=', 's.id')
            ->select(DB::raw('SUM(total_amount_paid) as total_sales_amount'))
            ->where('happened_at', '>=', CommonFunctions::addStartTime($startDate))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($endDate))
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->where('s.company_id', $companyId)
            ->firstOrFail();
    }

    public function getTotalAmountForSaleStoreTarget(string $startDate, string $endDate, array $locationIds): Collection
    {
        return Sale::join('counter_updates as cu', 'sales.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->select('c.location_id', DB::raw('SUM(total_amount_paid) as total_sales_amount'))
            ->where('happened_at', '>=', CommonFunctions::addStartTime($startDate))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($endDate))
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->whereIntegerInRaw('c.location_id', $locationIds)
            ->groupBy('location_id')
            ->get();
    }

    public function getSalesForTheStoreManagerApplicationDashboard(int $locationId, array $date, int $companyId): Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return Sale::query()
            ->selectRaw('COUNT(DISTINCT sales.id) as total_sales')
            ->selectRaw('SUM(total_amount_paid) as total_sales_amount')
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->whereIntegerInRaw('status', SaleStatus::getOnlyLayawayPendingAndCompleteSaleStatusValues())
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByOpenedByPosAtWithinDateRange($date))
            ->firstOrFail();
    }

    public function getSumOfQuantity(): Closure
    {
        return fn ($query) => $query->select('id', 'counter_update_id')
            ->withSum('saleItems as quantity', 'quantity');
    }

    public function getIdAndSaleIdColumn(): string
    {
        return 'id,sale_id';
    }

    public function getSelectIdANdOfflineIdColumn(): Closure
    {
        return fn ($query) => $query->select('id', 'offline_sale_id');
    }

    public function getSelectUsedLoyaltyPointColumn(): Closure
    {
        return fn ($query) => $query->select(
            'id',
            'offline_sale_id',
            'counter_update_id',
            'total_tax_amount',
            'cart_discount_amount',
            'items_discount_amount',
            'total_discount_amount',
            'total_amount_paid',
            'change_due',
            'layaway_pending_amount',
            'credit_pending_amount',
            'total_amount_before_round_off',
            'layaway_completed_at',
            'happened_at',
            'extra_details',
            'notes',
            'bill_reference_number',
            'has_mismatch',
            'round_off',
            'status',
            'credit_completed_at',
            'member_id'
        );
    }

    public function getBasicColumnForDigitalInvoice(): Closure
    {
        return fn ($query) => $query->select('id', 'offline_sale_id', 'digital_invoice_number');
    }

    public function totalCreditSalePendingAmount(int $companyId, ?int $locationId): float
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $totalCreditSalePendingAmount = Sale::query()
            ->selectRaw('SUM(credit_pending_amount) as credit_pending_amount')
            ->onlyPendingCreditSale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when((int) $locationId > 0, function ($query) use ($locationId, $counterUpdateQueries): void {
                $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
            })
            ->firstOrFail()->credit_pending_amount;

        return (float) $totalCreditSalePendingAmount;
    }

    public function getPaginatedMemberSaleDetails(array $filterData, int $memberId): LengthAwarePaginator
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'member_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'round_off',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off'
            )
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
            ->where('member_id', $memberId)
            ->whereHas('saleItems', function ($query): void {
                $query->select('id')->isNotExchange();
            })
            ->withSum('saleItems', 'quantity')
            ->withSum('saleItems', 'returned_quantity')
            ->with(
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when(null !== $filterData['location_id'], function ($query) use (
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByStoreId((int) $filterData['location_id'])
                );
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function loadSaleItemAndOtherRelation(Sale $sale): Sale
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);

        return $sale->load(
            'saleItems:' . $saleItemQueries->getBasicColumnNamesForSaleSaveEvent(),
            'saleItems.product:' . $productQueries->getColumnNameAndId(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getLocationIdColumn(),
            'counterUpdate.counter.location:' . $locationQueries->getColumnsForPriceFallDownCalculation(),
            'counterUpdate.counter.location.storeManagers:' . $storeManagerQueries->getIdColumnName(),
        );
    }

    public function getSaleHourForPrint(array $filterData, int $companyId): Collection
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $startDate = Carbon::parse($filterData['date_range'][0])->format('Y-m-d H:00:00');
        $endDate = Carbon::parse($filterData['date_range'][1])->format('Y-m-d H:59:59');

        return Sale::query()
            ->select('id', 'offline_sale_id', 'counter_update_id', 'happened_at', 'total_amount_paid')
            ->with([
                'saleItems:' . $saleItemQueries->getBasicColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            ])
            ->onlyRegularCompleteCreditAndCompleteLayawaySale()
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

    public function getSalesByRegionId(int $regionId, string $fromDate, string $toDate): Collection
    {
        return DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('regions', 'locations.region_id', '=', 'regions.id')
            ->where('counter_updates.closed_at', '>=', $fromDate)
            ->where('counter_updates.closed_at', '<=', $toDate)
            ->where('regions.id', $regionId)
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->selectRaw('
                SUM(sale_items.total_price_paid) as total_sales_amount,
                SUM(sale_items.quantity) as total_units_sold,
                sales.counter_update_id,
                counters.location_id as location_id,
                products.brand_id,
                counter_updates.opened_by_pos_at,
                counter_updates.created_at,
                locations.company_id,
                locations.name as location_name,
                brands.name as brand_name,
                COUNT(DISTINCT(sale_items.sale_id)) as total_sales_count
            ')
            ->groupBY('counters.location_id')
            ->groupBY('products.brand_id')
            ->groupBY('sales.counter_update_id')
            ->get();
    }

    public function filterByStoreIdAndForEmployeeSalesReport(int $locationId, int $companyId): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return fn ($query) => $query->whereHas('member', function ($query): void {
            $query->whereNotNull('employee_id');
        })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId));
    }

    private function getCancelLayawaySalesQuery(array $filterData, int $companyId): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'total_amount_before_round_off',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->cancelLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'layawayAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'layawayAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getVoidSalesWithRelationsForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Builder {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'happened_at',
                'notes',
                'has_mismatch',
                'status',
                'bill_reference_number',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->onlyVoidedSales()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getVoidSaleNumberPrefixColumn(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'voidSale:' . $voidSaleQueries->getColumnsForListPage(),
                'voidSale.voidedByStoreManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'voidSale.voidedByStoreManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'voidSale.voidSaleReason:' . $voidSaleReasonQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->where('offline_sale_id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['void_sale_number'], function ($query) use ($filterData): void {
                $query->whereHas('voidSale', function ($query) use ($filterData): void {
                    $query->select('id', 'void_sale_number')
                        ->where('void_sale_number', $filterData['void_sale_number']);
                });
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
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->orderBy('id', 'desc');
    }

    private function getPendingLayawaySalesWithRelationsForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Builder {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->when(
                $filterData['status_id'] === CreditAndLayawaySaleStatuses::PENDING->value,
                function ($query): void {
                    $query->onlyPendingLayawaySale();
                },
                function ($query): void {
                    $query->onlyCompleteLayawaySale();
                }
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'layawayAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'layawayAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
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
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getPendingCreditSalesWithRelationsForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Builder {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'credit_authorizer_id',
                'credit_authorizer_type',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->when(
                $filterData['status_id'] === CreditAndLayawaySaleStatuses::PENDING->value,
                function ($query): void {
                    $query->onlyPendingCreditSale();
                },
                function ($query): void {
                    $query->onlyCompleteCreditSale();
                }
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'creditAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'creditAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
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
            });
    }

    private function getCancelLayawaySalesQueryForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId,
    ): Builder {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'total_amount_before_round_off',
                'credit_pending_amount',
                'credit_authorizer_id',
                'credit_authorizer_type',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'member_id',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->cancelLayawaySale()
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'layawayAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'layawayAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'creditAuthorizer:' . $this->getMorphAuthorizerColumns(),
                'creditAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $salePaymentQueries->getNecessaryColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            ])

            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query
                        ->whereAny(
                            ['offline_sale_id', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhere(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                            $query->whereHas('counterUpdate', function ($query) use (
                                $filterData,
                                $cashierQueries,
                                $counterQueries
                            ): void {
                                $query->select('id', 'cashier_id', 'counter_id')
                                    ->whereHas('cashier', $cashierQueries->searchByName($filterData['search_text']))
                                    ->orWhereHas(
                                        'counter',
                                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                                    );
                            });
                        });
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
            ->when(
                array_key_exists('offline_sale_id', $filterData) && $filterData['offline_sale_id'],
                function ($query) use ($filterData): void {
                    $query->where('offline_sale_id', $filterData['offline_sale_id']);
                }
            )
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
            });
    }

    private function getMorphLocationBasicColumns(): string
    {
        return 'id,employee_id';
    }

    private function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id', 'first_name', 'last_name')
            ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $searchText . '%');
    }

    public function getSeasonalSalesData(array $filterData, array $dateRange, int $companyId, string $name): Collection
    {
        return DB::table('sales')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->when(config('app.master_product'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
                    ->join('brands', 'master_products.brand_id', '=', 'brands.id');
            }, function ($query): void {
                $query->join('brands', 'products.brand_id', '=', 'brands.id');
            })
            ->where('locations.company_id', $companyId)
            ->when(null !== $filterData['location_ids'] && $filterData['location_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
            })
            ->when(null !== $filterData['brand_ids'] && $filterData['brand_ids'], function ($query) use (
                $filterData
            ): void {
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
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->select(
                'sales.id',
                'brands.id as brand_id',
                'brands.name as brand_name',
                'locations.id as location_id',
                'locations.name as location_name',
                DB::raw('DATE_FORMAT(happened_at,"%Y-%m-%d") as happened_at'),
                DB::raw('SUM(sale_items.total_price_paid) as ' . $name),
            )
            ->groupBy('brand_id', 'location_id', 'happened_at')
            ->get();
    }

    public function getLayawaySalesWithItemsData(array $filterData, int $companyId): Collection
    {
        $locationIds = $filterData['location_ids'];

        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'member:' . $memberQueries->getBasicColumnNamesForSale(),
            'layawayAuthorizer:' . $this->getMorphAuthorizerColumns(),
            'layawayAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'member_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off'
            )
            ->onlyLayawaySale()
            ->with($relations)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdsAndCompanyId($locationIds, $companyId))
            ->when(null !== $filterData['counter_ids'], function ($query) use (
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when(null !== $filterData['cashier_ids'], function ($query) use (
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierIds((array) $filterData['cashier_ids'])
                );
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->get();
    }

    public function getCreditSalesWithItemsData(array $filterData, int $companyId): Collection
    {
        $locationIds = $filterData['location_ids'];

        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'member:' . $memberQueries->getBasicColumnNamesForSale(),
            'creditAuthorizer:' . $this->getMorphAuthorizerColumns(),
            'creditAuthorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
            'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
            'saleItems.product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'saleItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'saleItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'saleItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'member_id',
                'counter_update_id',
                'total_tax_amount',
                'total_discount_amount',
                'total_amount_paid',
                'credit_pending_amount',
                'credit_authorizer_id',
                'credit_authorizer_type',
                'happened_at',
                'status',
                'bill_reference_number',
                'notes',
                'total_amount_before_round_off'
            )
            ->onlyCreditSale()
            ->with($relations)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdsAndCompanyId($locationIds, $companyId))
            ->when(null !== $filterData['counter_ids'], function ($query) use (
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when(null !== $filterData['cashier_ids'], function ($query) use (
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierIds((array) $filterData['cashier_ids'])
                );
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->get();
    }

    public function getPendingCreditSaleByIdAndRelations(int $saleId): Sale
    {
        $saleItemQueries = new SaleItemQueries();
        $saleItemUnitQueries = new SaleItemUnitQueries();
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return Sale::select(
            'id',
            'sale_return_id',
            'offline_sale_id',
            'counter_update_id',
            'total_tax_amount',
            'cart_discount_amount',
            'items_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off',
            'total_amount_paid',
            'change_due',
            'status',
            'notes',
            'bill_reference_number',
            'happened_at',
            'has_mismatch',
            'extra_details',
            'credit_pending_amount',
            'credit_completed_at',
            'credit_authorizer_id',
            'credit_authorizer_type',
            'member_id'
        )
            ->onlyPendingCreditSale()
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItems:' . $saleItemQueries->getBasicColumnNames(),
                'saleItems.saleItemUnits:' . $saleItemUnitQueries->getBasicColumnNames(),
                'payments:' . $salePaymentQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleId);
    }

    public function digitalInvoiceUpdate(int $saleId): void
    {
        $sale = Sale::select('id', 'digital_invoice_submitted', 'offline_sale_id')
            ->where('digital_invoice_submitted', false)
            ->findOrFail($saleId);

        $sale->update([
            'digital_invoice_submitted' => true,
        ]);
    }

    public function getSaleByStoreIdCounterId(string $receiptNumber, int $locationId, int $counterId): ?Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return Sale::select('id', 'digital_invoice_submitted')
            ->where('offline_sale_id', $receiptNumber)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounterIdAndLocationId($locationId, $counterId))
            ->first();
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $sales = Sale::query()
            ->select('id', 'member_id', 'offline_sale_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($sales as $sale) {
            $sale->member_id = $newMemberId;
            $sale->save();
        }
    }

    public function getAchievedSaleTargetSales(
        array $dateRange,
        int $companyId,
        ?array $locationIds,
        ?array $promoterIds,
    ): Collection {
        return Sale::query()
            ->select(
                'offline_sale_id',
                'locations.name as location_name',
                'counters.name as counter_name',
                'sales.total_amount_paid as amount'
            )
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                $query->whereIn('locations.id', $locationIds);
            })
            ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                    ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
            })
            ->where('happened_at', '>=', CommonFunctions::addStartTime($dateRange[0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($dateRange[1]))
            ->get();
    }

    public function getYearlySalesAndSaleReturnsGroupByMonth(
        array $dateRange,
        int $companyId,
        ?array $locationIds = null,
        ?array $promoterIds = null,
        ?int $targetType = null,
        int $filterId = 0,
    ): Collection {
        $cacheKey = sprintf('yearly_sales_%d_', $filterId) . md5(json_encode(func_get_args(), JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use (
            $dateRange,
            $companyId,
            $locationIds,
            $promoterIds,
            $targetType,
        ) {
            $sales = Sale::query()
                ->select(
                    DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
                    DB::raw('0 as total_sale_returns'),
                    DB::raw('MONTHNAME(sales.happened_at) as month')
                )
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->whereBetween('sales.happened_at', [$dateRange[0], $dateRange[1]])
                ->groupBy(DB::raw('MONTH(sales.happened_at)'));

            $saleReturns = SaleReturn::query()
                ->select(
                    DB::raw('0 as total_sales'),
                    DB::raw('SUM(sale_return_items.total_price_paid) as total_sale_returns'),
                    DB::raw('MONTHNAME(sale_returns.happened_at) as month')
                )
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_items', 'sale_return_items.original_sale_item_id', '=', 'sale_items.id')
                        ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->whereBetween('sale_returns.happened_at', [$dateRange[0], $dateRange[1]])
                ->groupBy(DB::raw('MONTH(sale_returns.happened_at)'));

            $saleTargets = [];
            $targetIndex = 0;

            if ($targetType) {
                $saleTargetQueries = resolve(SaleTargetQueries::class);
                $saleTargets = $saleTargetQueries->getSaleTargetForChart(
                    $dateRange,
                    $companyId,
                    $targetType,
                    TimeIntervalType::YEARLY->value
                );
            }

            return DB::query()->fromSub($sales->getQuery()->unionAll($saleReturns->getQuery()), 'combined_sales')
                ->get()
                ->groupBy('month')
                ->map(function ($group) use ($saleTargets, $targetIndex): array {
                    $total_sales = $group->sum('total_sales');
                    $total_sale_returns = $group->sum('total_sale_returns');

                    $targetAmount = $saleTargets[$targetIndex]['amount'] ?? 0;
                    $targetIndex++;

                    return [
                        'month' => $group->first()->month ?? '',
                        'net_sales' => $total_sales - $total_sale_returns,
                        'target' => $targetAmount,
                    ];
                });
        });
    }

    public function getMonthlySalesAndSaleReturnsGroupByMonth(
        array $dateRanges,
        int $companyId,
        ?array $locationIds = null,
        ?array $promoterIds = null,
        ?int $targetType = null,
        int $filterId = 0,
    ): Collection {
        $cacheKey = 'monthly_sales_and_returns_' . md5(json_encode([$dateRanges, $filterId], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use (
            $dateRanges,
            $companyId,
            $locationIds,
            $promoterIds,
            $targetType
        ) {
            $sales = Sale::query()
                ->select(
                    DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
                    DB::raw('0 as total_sale_returns'),
                    DB::raw('MONTHNAME(sales.happened_at) as month')
                )
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->where(function ($query) use ($dateRanges): void {
                    foreach ($dateRanges as $dateRange) {
                        $query->orWhereBetween('sales.happened_at', [$dateRange[0], $dateRange[1]]);
                    }
                })
                ->groupBy(DB::raw('MONTH(sales.happened_at)'));

            $saleReturns = SaleReturn::query()
                ->select(
                    DB::raw('0 as total_sales'),
                    DB::raw('SUM(sale_return_items.total_price_paid) as total_sale_returns'),
                    DB::raw('MONTHNAME(sale_returns.happened_at) as month')
                )
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_items', 'sale_return_items.original_sale_item_id', '=', 'sale_items.id')
                        ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->where(function ($query) use ($dateRanges): void {
                    foreach ($dateRanges as $dateRange) {
                        $query->orWhereBetween('sale_returns.happened_at', [$dateRange[0], $dateRange[1]]);
                    }
                })
                ->groupBy(DB::raw('MONTH(sale_returns.happened_at)'));

            $saleTargets = [];
            $targetIndex = 0;

            if ($targetType) {
                $saleTargetQueries = resolve(SaleTargetQueries::class);
                foreach ($dateRanges as $dateRange) {
                    $saleTargets[] = $saleTargetQueries->getSaleTargetForChart(
                        $dateRange,
                        $companyId,
                        $targetType,
                        TimeIntervalType::MONTHLY->value
                    );
                }
            }

            return DB::query()->fromSub($sales->getQuery()->unionAll($saleReturns->getQuery()), 'combined_sales')
                ->get()
                ->groupBy('month')
                ->map(function ($group, $index) use ($saleTargets, $targetIndex): array {
                    $total_sales = $group->sum('total_sales');
                    $total_sale_returns = $group->sum('total_sale_returns');
                    $targetAmount = $saleTargets[$targetIndex]['amount'] ?? 0;
                    $targetIndex++;

                    return [
                        'month' => $group->first()->month ?? '',
                        'net_sales' => $total_sales - $total_sale_returns,
                        'target' => $targetAmount,
                    ];
                });
        });
    }

    public function getWeeklySalesAndSaleReturnsGroupByWeek(
        array $dateRanges,
        int $companyId,
        ?array $locationIds = null,
        ?array $promoterIds = null,
        ?int $targetType = null,
        int $filterId = 0,
    ): Collection {
        $cacheKey = 'weekly_sales_and_returns_' . md5(json_encode([$dateRanges, $filterId], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use (
            $dateRanges,
            $companyId,
            $locationIds,
            $promoterIds,
            $targetType,
        ) {
            $sales = Sale::query()
                ->select(
                    DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
                    DB::raw('0 as total_sale_returns'),
                    DB::raw('WEEK(sales.happened_at, 3) as week'),
                    DB::raw('CONCAT("Week ", WEEK(sales.happened_at, 3)) as week_name')
                )
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->where(function ($query) use ($dateRanges): void {
                    foreach ($dateRanges as $dateRange) {
                        $query->orWhereBetween('sales.happened_at', [$dateRange[0], $dateRange[1]]);
                    }
                })
                ->groupBy('week_name');

            $saleReturns = SaleReturn::query()
                ->select(
                    DB::raw('0 as total_sales'),
                    DB::raw('SUM(sale_return_items.total_price_paid) as total_sale_returns'),
                    DB::raw('WEEK(sale_returns.happened_at, 3) as week'),
                    DB::raw('CONCAT("Week ", WEEK(sale_returns.happened_at, 3)) as week_name')
                )
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_items', 'sale_return_items.original_sale_item_id', '=', 'sale_items.id')
                        ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->where(function ($query) use ($dateRanges): void {
                    foreach ($dateRanges as $dateRange) {
                        $query->orWhereBetween('sale_returns.happened_at', [$dateRange[0], $dateRange[1]]);
                    }
                })
                ->groupBy('week_name');

            $saleTargets = [];
            $targetIndex = 0;

            if ($targetType) {
                $saleTargetQueries = resolve(SaleTargetQueries::class);
                foreach ($dateRanges as $dateRange) {
                    $saleTargets[] = $saleTargetQueries->getSaleTargetForChart(
                        $dateRange,
                        $companyId,
                        $targetType,
                        TimeIntervalType::WEEKLY->value
                    );
                }
            }

            return DB::query()->fromSub($sales->getQuery()->unionAll($saleReturns->getQuery()), 'combined_sales')
                ->get()
                ->groupBy('week_name')
                ->map(function ($group, $index) use ($saleTargets, $targetIndex): array {
                    $total_sales = $group->sum('total_sales');
                    $total_sale_returns = $group->sum('total_sale_returns');
                    $targetAmount = $saleTargets[$targetIndex]['amount'] ?? 0;
                    $targetIndex++;

                    return [
                        'week_name' => $group->first()->week_name ?? '',
                        'net_sales' => $total_sales - $total_sale_returns,
                        'target' => $targetAmount,
                    ];
                });
        });
    }

    public function getDailySalesAndSaleReturnsGroupByWeek(
        array $dateRanges,
        int $companyId,
        ?array $locationIds = null,
        ?array $promoterIds = null,
        ?int $targetType = null,
        int $filterId = 0,
    ): Collection {
        $cacheKey = 'daily_sales_and_returns_' . md5(json_encode([$dateRanges, $filterId], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use (
            $dateRanges,
            $companyId,
            $locationIds,
            $promoterIds,
            $targetType
        ) {
            $sales = Sale::query()
                ->select(
                    DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
                    DB::raw('0 as total_sale_returns'),
                    DB::raw('DATE(sales.happened_at) as date')
                )
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->where('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->where(function ($query) use ($dateRanges): void {
                    foreach ($dateRanges as $dateRange) {
                        $query->orWhereBetween(
                            'sales.happened_at',
                            [CommonFunctions::addStartTime($dateRange[0]), CommonFunctions::addEndTime($dateRange[1])]
                        );
                    }
                })
                ->groupBy(DB::raw('DATE(sales.happened_at)'));

            $saleReturns = SaleReturn::query()
                ->select(
                    DB::raw('0 as total_sales'),
                    DB::raw('SUM(sale_return_items.total_price_paid) as total_sale_returns'),
                    DB::raw('DATE(sale_returns.happened_at) as date')
                )
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                    $query->whereIn('locations.id', $locationIds);
                })
                ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                    $query->join('sale_items', 'sale_return_items.original_sale_item_id', '=', 'sale_items.id')
                        ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                        ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
                })
                ->where(function ($query) use ($dateRanges): void {
                    foreach ($dateRanges as $dateRange) {
                        $query->orWhereBetween(
                            'sale_returns.happened_at',
                            [CommonFunctions::addStartTime($dateRange[0]), CommonFunctions::addEndTime($dateRange[1])]
                        );
                    }
                })
                ->groupBy(DB::raw('DATE(sale_returns.happened_at)'));

            $saleTargets = [];
            $targetIndex = 0;

            if ($targetType) {
                $saleTargetQueries = resolve(SaleTargetQueries::class);
                foreach ($dateRanges as $dateRange) {
                    $saleTargets[] = $saleTargetQueries->getSaleTargetForChart(
                        $dateRange,
                        $companyId,
                        $targetType,
                        TimeIntervalType::DAILY->value
                    );
                }
            }

            return DB::query()->fromSub($sales->getQuery()->unionAll($saleReturns->getQuery()), 'combined_sales')
                ->get()
                ->groupBy('date')
                ->map(function ($group, $index) use ($saleTargets, $targetIndex): array {
                    $total_sales = $group->sum('total_sales');
                    $total_sale_returns = $group->sum('total_sale_returns');
                    $targetAmount = $saleTargets[$targetIndex]['amount'] ?? 0;
                    $targetIndex++;

                    return [
                        'date' => $group->first()->date ?? '',
                        'net_sales' => $total_sales - $total_sale_returns,
                        'target' => $targetAmount,
                    ];
                });
        });
    }

    public function getWeeklySalesAndSaleReturns(
        int $companyId,
        int $selectedMonth,
        int $selectedYear,
        ?array $locationIds = [],
        ?array $promoterIds = [],
        ?int $targetType = null,
        ?array $dateRange = null,
    ): Collection {
        $sales = Sale::query()
            ->select(
                DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
                DB::raw('0 as total_sale_returns'),
                DB::raw('WEEK(sales.happened_at, 3) as week')
            )
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                $query->whereIn('locations.id', $locationIds);
            })
            ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                    ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
            })
            ->where('locations.company_id', $companyId)
            ->whereMonth('happened_at', $selectedMonth)
            ->whereYear('happened_at', $selectedYear)
            ->groupBy(DB::raw('WEEK(sales.happened_at, 3)'));

        $saleReturns = SaleReturn::query()
            ->select(
                DB::raw('0 as total_sales'),
                DB::raw('SUM(sale_return_items.total_price_paid) as total_sale_returns'),
                DB::raw('WEEK(sale_returns.happened_at, 3) as week')
            )
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                $query->whereIn('locations.id', $locationIds);
            })
            ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->join('sale_items', 'sale_return_items.original_sale_item_id', '=', 'sale_items.id')
                    ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                    ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
            })
            ->where('locations.company_id', $companyId)
            ->whereMonth('happened_at', $selectedMonth)
            ->whereYear('happened_at', $selectedYear)
            ->groupBy(DB::raw('WEEK(sale_returns.happened_at, 3)'));

        $saleTargets = [];
        $targetIndex = 0;

        if ($targetType && [] !== $dateRange) {
            $saleTargetQueries = resolve(SaleTargetQueries::class);
            /** @var array $dateRange */
            $saleTargets[] = $saleTargetQueries->getSaleTargetForChart(
                $dateRange,
                $companyId,
                $targetType,
                TimeIntervalType::DAILY->value
            );
        }

        return DB::query()->fromSub($sales->getQuery()->unionAll($saleReturns->getQuery()), 'combined_sales')
            ->get()
            ->groupBy('week')
            ->map(function ($group) use ($saleTargets, $targetIndex): array {
                $total_sales = $group->sum('total_sales');
                $total_sale_returns = $group->sum('total_sale_returns');

                $targetAmount = $saleTargets[$targetIndex]['amount'] ?? 0;
                $targetIndex++;

                return [
                    'week' => $group->first()->week ?? '',
                    'net_sales' => $total_sales - $total_sale_returns,
                    'target' => $targetAmount,
                ];
            });
    }

    public function getDailySalesAndSaleReturns(
        int $companyId,
        int $selectedWeek,
        int $selectedYear,
        ?array $locationIds = [],
        ?array $promoterIds = [],
        ?int $targetType = null,
        ?array $dateRange = [],
    ): Collection {
        $sales = Sale::query()
            ->select(
                DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
                DB::raw('0 as total_sale_returns'),
                DB::raw('DATE(sales.happened_at) as date'),
                DB::raw('DAYNAME(sales.happened_at) as day'),
                DB::raw('MONTHNAME(sales.happened_at) as month_name')
            )
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                $query->whereIn('locations.id', $locationIds);
            })
            ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                    ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
            })
            ->where(DB::raw('WEEK(sales.happened_at, 3)'), $selectedWeek)
            ->whereYear('sales.happened_at', $selectedYear)
            ->groupBy(DB::raw('DATE(sales.happened_at)'));

        $saleReturns = SaleReturn::query()
            ->select(
                DB::raw('0 as total_sales'),
                DB::raw('SUM(sale_return_items.total_price_paid) as total_sale_returns'),
                DB::raw('DATE(sale_returns.happened_at) as date'),
                DB::raw('DAYNAME(sale_returns.happened_at) as day'),
                DB::raw('MONTHNAME(sale_returns.happened_at) as month_name')
            )
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->when(null !== $locationIds && [] !== $locationIds, function ($query) use ($locationIds): void {
                $query->whereIn('locations.id', $locationIds);
            })
            ->when(null !== $promoterIds && [] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->join('sale_items', 'sale_return_items.original_sale_item_id', '=', 'sale_items.id')
                    ->join('sale_item_promoter', 'sale_item_promoter.sale_item_id', '=', 'sale_items.id')
                    ->whereIn('sale_item_promoter.promoter_id', $promoterIds);
            })
            ->where(DB::raw('WEEK(sale_returns.happened_at, 3)'), $selectedWeek)
            ->whereYear('sale_returns.happened_at', $selectedYear)
            ->groupBy(DB::raw('DATE(sale_returns.happened_at)'));

        $saleTargets = [];
        $targetIndex = 0;

        if ($targetType && [] !== $dateRange) {
            $saleTargetQueries = resolve(SaleTargetQueries::class);
            /** @var array $dateRange */
            $saleTargets[] = $saleTargetQueries->getSaleTargetForChart(
                $dateRange,
                $companyId,
                $targetType,
                TimeIntervalType::WEEKLY->value
            );
        }

        return DB::query()->fromSub($sales->getQuery()->unionAll($saleReturns->getQuery()), 'combined_sales')
            ->get()
            ->groupBy('date')
            ->map(function ($group) use ($saleTargets, $targetIndex): array {
                $total_sales = $group->sum('total_sales');
                $total_sale_returns = $group->sum('total_sale_returns');

                $targetAmount = $saleTargets[$targetIndex]['amount'] ?? 0;
                $targetIndex++;

                return [
                    'date' => $group->first()->day ?? '',
                    'net_sales' => $total_sales - $total_sale_returns,
                    'target' => $targetAmount,
                ];
            });
    }

    public function getSalesUnitsSoldAndFocUnitsSold(int $productId, int $locationId, string $date): Collection
    {
        return Sale::select(
            'sales.id',
            DB::raw('SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN quantity ELSE 0 END) as foc_units_sold'),
            DB::raw('SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN quantity ELSE 0 END) as units_sold'),
            DB::raw('SUM(sale_items.total_price_paid) as total_price_paid')
        )
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->whereDate('sales.happened_at', '=', $date)
            ->where('locations.id', $locationId)
            ->where('products.id', $productId)
            ->get();
    }

    public function getNewAndExistingMembers(int $currentYear, int $companyId, int $locationId): Collection
    {
        return Sale::query()
            ->join('members', 'sales.member_id', '=', 'members.id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'locations.id', '=', 'counters.location_id')
            ->select(
                DB::raw("DATE_FORMAT(sales.happened_at, '%M') as sales_month"),
                DB::raw("DATE_FORMAT(members.created_at, '%M') as member_months"),
                DB::raw(
                    "CASE WHEN sales.happened_at = members.created_at THEN 'New Member' ELSE 'Existing Member' END as member_status"
                )
            )
            ->where('members.company_id', $companyId)
            ->where('locations.company_id', $companyId)
            ->whereYear('sales.happened_at', $currentYear)
            ->whereYear('members.created_at', $currentYear)
            ->when(0 !== $locationId, function ($query) use ($locationId): void {
                $query->where(function ($q) use ($locationId): void {
                    $q->where('counters.location_id', $locationId)
                        ->orWhere('members.created_location_id', $locationId);
                });
            })
            ->get();
    }

    public function getInactiveMembers(int $companyId, int $locationId, int $days): int
    {
        return DB::table('sales')
            ->select('member_id')
            ->addSelect(DB::raw('DATEDIFF(CURDATE(), MAX(happened_at)) as days_since_last_purchase'))
            ->having('days_since_last_purchase', '>', $days)
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->when($locationId > 0, function ($query) use ($locationId): void {
                $query->where('counters.location_id', $locationId);
            })
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->groupBy('member_id')
            ->count();
    }

    public function getMemberAgeGroupCounts(int $currentYear, int $companyId, int $locationId): Collection
    {
        $counterUpdateQueries = new CounterUpdateQueries();

        return Cache::remember(
            'get-this-year-member-age-group-wise-with-revenue' . $companyId . $locationId . $currentYear,
            900,
            fn (): Collection => Sale::select(
                DB::raw("
                    CASE
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= 18 THEN 'Below 18'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 55 THEN '55+'
                        ELSE 'Unknown'
                    END AS age_group
                "),
                DB::raw('COUNT(DISTINCT members.id) AS count'),
                DB::raw('SUM(sales.total_amount_paid) as total_revenue')
            )
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->whereYear('happened_at', $currentYear)
                ->when(0 !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                    $query->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId));
                })
                ->join('members', 'members.id', '=', 'sales.member_id')
                ->whereNotNull('members.date_of_birth')
                ->whereNotNull('member_id')
                ->groupBy('age_group')
                ->having('age_group', '!=', 'Unknown')
                ->orderByRaw("
                CASE
                    WHEN age_group = 'Below 18' THEN 1
                    WHEN age_group = '18-24' THEN 2
                    WHEN age_group = '25-34' THEN 3
                    WHEN age_group = '35-44' THEN 4
                    WHEN age_group = '45-54' THEN 5
                    WHEN age_group = '55+' THEN 6
                END
            ")
                ->get()
        );
    }

    public function getMemberGender(int $currentYear, int $companyId, int $locationId): Collection
    {
        $counterUpdateQueries = new CounterUpdateQueries();

        return Cache::remember(
            'get-this-year-member-gender' . $companyId . $locationId . $currentYear,
            900,
            fn (): Collection => Sale::select(
                DB::raw("
                    CASE
                        WHEN gender_id = 1 THEN 'Male'
                        WHEN gender_id = 2 THEN 'Female'
                        ELSE 'N/A'
                    END AS gender
                "),
                DB::raw('COUNT(DISTINCT members.id) AS count'),
                DB::raw('SUM(sales.total_amount_paid) as total_revenue')
            )
                ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
                ->whereYear('happened_at', $currentYear)
                ->when(0 !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                    $query->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId));
                })
                ->join('members', 'members.id', '=', 'sales.member_id')
                ->groupBy('gender')
                ->orderBy('gender')
                ->get()
        );
    }

    public function getSaleDiscounts(array $filterData): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'counter_update_id',
                'total_amount_paid',
                'cart_discount_amount',
                'total_tax_amount',
                'status',
                'happened_at'
            )
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'saleDiscounts' => function ($query) use ($saleDiscountQueries, $filterData): void {
                    $columns = explode(',', $saleDiscountQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->when(null !== $filterData['report_type'], function ($query) use ($filterData): void {
                            $query->when(
                                (int) $filterData['report_type'] === SaleDiscountTypeReports::VOUCHER->value,
                                function ($query): void {
                                    $query->where('discountable_type', SaleDiscountTypeReports::VOUCHER->name);
                                }
                            )
                                ->when(
                                    (int) $filterData['report_type'] === SaleDiscountTypeReports::CASHBACK->value,
                                    function ($query): void {
                                        $query->where('discountable_type', SaleDiscountTypeReports::CASHBACK->name);
                                    }
                                )
                                ->when(
                                    (int) $filterData['report_type'] === SaleDiscountTypeReports::PROMOTION->value,
                                    function ($query): void {
                                        $query->where('discountable_type', SaleDiscountTypeReports::PROMOTION->name);
                                    }
                                )
                                ->when(
                                    (int) $filterData['report_type'] === SaleDiscountTypeReports::SALE_PRICE_OVERRIDE->value,
                                    function ($query): void {
                                        $query->where(
                                            'discountable_type',
                                            SaleDiscountTypeReports::SALE_PRICE_OVERRIDE->name
                                        );
                                    }
                                )
                                ->when(
                                    (int) $filterData['report_type'] === SaleDiscountTypeReports::SALE_LOYALTY_POINT->value,
                                    function ($query): void {
                                        $query->where(
                                            'discountable_type',
                                            SaleDiscountTypeReports::SALE_LOYALTY_POINT->name
                                        );
                                    }
                                );
                        });
                },
            ])
            ->where($this->filterByStoreIds($filterData['location_ids']))
            ->where($this->filterByHappenedAtWithinDateRange($filterData['date_range']))
            ->whereIntegerInRaw('status', SaleStatus::getCommonActiveSaleStatusValues())
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::VOUCHER->value,
                function ($query): void {
                    $query->whereHas('saleDiscounts', function ($query): void {
                        $query->where('discountable_type', SaleDiscountTypeReports::VOUCHER->name);
                    });
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::CASHBACK->value,
                function ($query): void {
                    $query->whereHas('saleDiscounts', function ($query): void {
                        $query->where('discountable_type', SaleDiscountTypeReports::CASHBACK->name);
                    });
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::PROMOTION->value,
                function ($query): void {
                    $query->whereHas('saleDiscounts', function ($query): void {
                        $query->where('discountable_type', SaleDiscountTypeReports::PROMOTION->name);
                    });
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::SALE_PRICE_OVERRIDE->value,
                function ($query): void {
                    $query->whereHas('saleDiscounts', function ($query): void {
                        $query->where('discountable_type', SaleDiscountTypeReports::SALE_PRICE_OVERRIDE->name);
                    });
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::SALE_LOYALTY_POINT->value,
                function ($query): void {
                    $query->whereHas('saleDiscounts', function ($query): void {
                        $query->where('discountable_type', SaleDiscountTypeReports::SALE_LOYALTY_POINT->name);
                    });
                }
            )
            ->when(
                (int) $filterData['report_type'] === 0,
                function ($query): void {
                    $query->whereHas('saleDiscounts');
                }
            )
            ->get();
    }

    public function getIdByOfflineSaleId(string $offlineSaleId): ?Sale
    {
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Sale::query()
            ->select('id', 'offline_sale_id', 'counter_update_id', 'member_id')
            ->with([
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'saleItems.product.media:' . $mediaQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            ])
            ->where('offline_sale_id', $offlineSaleId)
            ->first();
    }

    public function getProductIdsBySaleId(string $saleId): array
    {
        return SaleItem::query()
            ->select('product_id')
            ->where('sale_id', $saleId)
            ->pluck('product_id')
            ->toArray();
    }

    public function getSaleByOfflineId(string $offlineSaleId): ?Sale
    {
        return Sale::query()
            ->select('id', 'member_id', 'status', 'happened_at', 'total_amount_paid')
            ->where('offline_sale_id', $offlineSaleId)
            ->withSum('saleItems', 'quantity')
            ->withCount('mysteryGiftUsages')
            ->first();
    }

    public function getByOfflineIdWithLocation(string $offlineSaleId): ?Sale
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'member_id',
                'counter_update_id',
                'status',
                'happened_at',
                'total_amount_paid'
            )
            ->with([
                'counterUpdate:'.$counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.counter:'.$counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:'.$locationQueries->getNameColumnName(),
            ])
            ->where('offline_sale_id', $offlineSaleId)
            ->withSum('saleItems', 'quantity')
            ->withCount('mysteryGiftUsages')
            ->first();
    }

    public function addMemberToSale(Sale $sale, int $memberId): void
    {
        $sale->member_id = $memberId;
        $sale->save();
    }

    public function getDayCloseSalesForExport(array $counterUpdateIds): Collection
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);

        return Sale::query()
            ->select(
                'id',
                'offline_sale_id',
                'digital_invoice_number',
                'counter_update_id',
                'total_tax_amount',
                'cart_discount_amount',
                'items_discount_amount',
                'total_amount_paid',
                'change_due',
                'layaway_pending_amount',
                'status',
                'total_discount_amount',
                'credit_pending_amount',
                'member_id',
                'happened_at',
            )
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'saleItems:' . $saleItemQueries->getColumnNamesForPos(),
                'saleItems.product:' . $productQueries->getBasicColumnNames(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.company.defaultCountry.currency:'. $currencyQueries->getBasicColumnNames(),
            ])
            ->whereIn('counter_update_id', $counterUpdateIds)
            ->get();
    }
}
