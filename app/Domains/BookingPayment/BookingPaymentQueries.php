<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment;

use App\CommonFunctions;
use App\Domains\AssemblyMasterProduct\AssemblyChildMasterProductQueries;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentProduct\BookingPaymentProductQueries;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\BookingPaymentVoidUse\BookingPaymentVoidUseQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Models\BookingPayment;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingPaymentQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,available_amount,offline_id,counter_update_id,member_id';
    }

    public function getPaginatedBookingPaymentsWithProducts(
        array $paginatedBookingPaymentsDataForPos,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        $promoterQueries = resolve(PromoterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        return BookingPayment::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'member_id',
                'total_amount',
                'available_amount',
                'status',
                'remarks',
                'bill_reference_number',
                'created_at'
            )
            ->with(
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
                'bookingPaymentPayments.currency:' . $currencyQueries->getBasicColumnNames(),
                'bookingPaymentPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'products:' . $bookingPaymentProductQueries->getBasicColumnNames(),
                'products.product:' . $productQueries->getBasicColumnNames(),
                'products.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'products.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'products.product.assemblyChildProducts.product:' . $productQueries->getBasicColumnNames(),
                'products.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'products.product.masterProduct.assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames(),
                'products.product.masterProduct.assemblyChildMasterProducts.item:' . $masterProductQueries->getBasicColumnNames(),
                'products.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'products.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'products.product.color:' . $colorQueries->getBasicColumnNames(),
                'products.product.size:' . $sizeQueries->getBasicColumnNames(),
                'products.promoters:' . $promoterQueries->getBasicColumnNames(),
                'products.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'refund:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
                'refund.currency:' . $currencyQueries->getBasicColumnNames(),
                'refund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'refund.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'refund.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'refund.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'refund.counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'refunds:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
                'refunds.currency:' . $currencyQueries->getBasicColumnNames(),
                'refunds.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'refunds.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'refunds.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'refunds.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'refunds.counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'bookingPaymentUses:' . $bookingPaymentUseQueries->getColumnNamesForPaginatedBookingPayments(),
                'bookingPaymentUses.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'bookingPaymentUses.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'bookingPaymentUses.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'bookingPaymentUses.counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($paginatedBookingPaymentsDataForPos['member_id'], function ($query) use (
                $paginatedBookingPaymentsDataForPos
            ): void {
                $query->where('member_id', $paginatedBookingPaymentsDataForPos['member_id']);
            })->when(null !== $paginatedBookingPaymentsDataForPos['status'], function ($query) use (
                $paginatedBookingPaymentsDataForPos
            ): void {
                $query->where(
                    'status',
                    BookingPaymentStatuses::getValueByCaseName($paginatedBookingPaymentsDataForPos['status'])
                );
            })
            ->when($paginatedBookingPaymentsDataForPos['search_text'], function ($query) use (
                $paginatedBookingPaymentsDataForPos,
                $counterUpdateQueries,
                $memberQueries
            ): void {
                $query->where(function ($query) use (
                    $paginatedBookingPaymentsDataForPos,
                    $counterUpdateQueries,
                    $memberQueries
                ): void {
                    $query->whereHas(
                        'counterUpdate',
                        $counterUpdateQueries->searchByCashierName($paginatedBookingPaymentsDataForPos['search_text'])
                    )->orWhereHas(
                        'counterUpdate',
                        $counterUpdateQueries->searchByCounterAndStoreName(
                            $paginatedBookingPaymentsDataForPos['search_text']
                        )
                    )->orWhereHas(
                        'member',
                        $memberQueries->searchByBasicColumns($paginatedBookingPaymentsDataForPos['search_text'])
                    );
                });
            })
            ->when($paginatedBookingPaymentsDataForPos['promoter_id'], function ($query) use (
                $paginatedBookingPaymentsDataForPos,
                $promoterQueries
            ): void {
                $query->where(function ($query) use ($paginatedBookingPaymentsDataForPos, $promoterQueries): void {
                    $query
                        ->whereHas(
                            'promoters',
                            $promoterQueries->filterByPromoterId(
                                (int) $paginatedBookingPaymentsDataForPos['promoter_id']
                            )
                        )
                        ->orWhereHas('products', function ($query) use (
                            $promoterQueries,
                            $paginatedBookingPaymentsDataForPos
                        ): void {
                            $query->select('id')
                                ->whereHas(
                                    'promoters',
                                    $promoterQueries->filterByPromoterId(
                                        (int) $paginatedBookingPaymentsDataForPos['promoter_id']
                                    )
                                );
                        });
                });
            })
            ->when($paginatedBookingPaymentsDataForPos['after_updated_at'], function ($query) use (
                $paginatedBookingPaymentsDataForPos
            ): void {
                $query->where('updated_at', '>=', $paginatedBookingPaymentsDataForPos['after_updated_at']);
            }, function ($query) use ($paginatedBookingPaymentsDataForPos): void {
                $query->when($paginatedBookingPaymentsDataForPos['from_date'], function ($query) use (
                    $paginatedBookingPaymentsDataForPos
                ): void {
                    $query->where(
                        'created_at',
                        '>=',
                        CommonFunctions::addStartTime($paginatedBookingPaymentsDataForPos['from_date'])
                    );
                })->when($paginatedBookingPaymentsDataForPos['to_date'], function ($query) use (
                    $paginatedBookingPaymentsDataForPos
                ): void {
                    $query->where(
                        'created_at',
                        '<=',
                        CommonFunctions::addEndTime($paginatedBookingPaymentsDataForPos['to_date'])
                    );
                });
            })
            ->when($paginatedBookingPaymentsDataForPos['sort_by'], function ($query) use (
                $paginatedBookingPaymentsDataForPos
            ): void {
                $query->orderBy(
                    $paginatedBookingPaymentsDataForPos['sort_by'],
                    $paginatedBookingPaymentsDataForPos['sort_direction']
                );
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate();
    }

    public function getById(int $bookingPaymentId, int $companyId, int $locationId): BookingPayment
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);

        return BookingPayment::select(
            'id',
            'counter_update_id',
            'member_id',
            'total_amount',
            'available_amount',
            'status',
            'remarks',
            'bill_reference_number',
            'created_at',
            'offline_id'
        )
            ->with([
                'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
                'bookingPaymentPayments.currency:' . $currencyQueries->getBasicColumnNames(),
                'refund:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
                'refund.currency:' . $currencyQueries->getBasicColumnNames(),
                'refunds:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
                'refunds.currency:' . $currencyQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->findOrFail($bookingPaymentId);
    }

    public function markAsRefunded(BookingPayment $bookingPayment, float $refundAmount): void
    {
        $currentAvailableAmount = (float) $bookingPayment->available_amount;
        $newAvailableAmount = $currentAvailableAmount - $refundAmount;
        
        // Determine if status should be updated to REFUNDED
        $shouldMarkAsRefunded = CommonFunctions::compareFloatNumbers($currentAvailableAmount, $refundAmount);
        $newStatus = $shouldMarkAsRefunded ? BookingPaymentStatuses::REFUNDED->value : $bookingPayment->status;
        
        // Perform atomic update with WHERE clause to prevent race conditions
        $affectedRows = DB::table('booking_payments')
            ->where('id', $bookingPayment->id)
            ->where('available_amount', $currentAvailableAmount)
            ->update([
                'available_amount' => $newAvailableAmount,
                'status' => $newStatus,
                'updated_at' => now(),
            ]);
            
        // If no rows were affected, it means another process modified the record concurrently
        if ($affectedRows === 0) {
            throw new \RuntimeException('Booking payment was modified by another process. Please retry the operation.');
        }
        
        // Update the model instance to reflect the changes
        $bookingPayment->available_amount = $newAvailableAmount;
        $bookingPayment->status = $newStatus;
    }

    public function updateAmountColumnsForTopUp(BookingPayment $bookingPayment, float $amount): void
    {
        $currentTotalAmount = (float) $bookingPayment->total_amount;
        $currentAvailableAmount = (float) $bookingPayment->available_amount;
        $newTotalAmount = $currentTotalAmount + $amount;
        $newAvailableAmount = $currentAvailableAmount + $amount;
        
        // Perform atomic update with WHERE clause to prevent race conditions
        $affectedRows = DB::table('booking_payments')
            ->where('id', $bookingPayment->id)
            ->where('total_amount', $currentTotalAmount)
            ->where('available_amount', $currentAvailableAmount)
            ->update([
                'total_amount' => $newTotalAmount,
                'available_amount' => $newAvailableAmount,
                'updated_at' => now(),
            ]);
            
        // If no rows were affected, it means another process modified the record concurrently
        if ($affectedRows === 0) {
            throw new \RuntimeException('Booking payment was modified by another process. Please retry the operation.');
        }
        
        // Update the model instance to reflect the changes
        $bookingPayment->total_amount = $newTotalAmount;
        $bookingPayment->available_amount = $newAvailableAmount;
    }

    public function getBookingPaymentCountByCounterUpdateId(int $counterUpdateId): int
    {
        return BookingPayment::where('counter_update_id', $counterUpdateId)->count();
    }

    public function addNew(
        BookingPaymentData $bookingPaymentData,
        int $counterUpdateId,
        string $digitalInvoiceNumber
    ): BookingPayment {
        $bookingPayment = BookingPayment::create([
            'offline_id' => $bookingPaymentData->offline_id,
            'counter_update_id' => $counterUpdateId,
            'member_id' => $bookingPaymentData->member_id,
            'status' => BookingPaymentStatuses::ACTIVE->value,
            'total_amount' => $bookingPaymentData->amount,
            'available_amount' => $bookingPaymentData->amount,
            'happened_at' => $bookingPaymentData->happened_at,
            'remarks' => $bookingPaymentData->remarks,
            'bill_reference_number' => $bookingPaymentData->bill_reference_number,
            'authorizer_id' => $bookingPaymentData->store_manager_id ?? null,
            'authorizer_type' => $bookingPaymentData->store_manager_id ? ModelMapping::STORE_MANAGER->name : null,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);

        $this->updatePromoters($bookingPayment, (array) $bookingPaymentData->promoter_ids);

        return $bookingPayment;
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getByIds(array $bookingPaymentIds, int $locationId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return BookingPayment::query()
            ->select(
                'id',
                'counter_update_id',
                'member_id',
                'available_amount',
                'remarks',
                'bill_reference_number',
                'status'
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->whereIntegerInRaw('id', $bookingPaymentIds)
            ->get();
    }

    public function markAsUsed(BookingPayment $bookingPayment, float $amount): void
    {
        $currentAvailableAmount = (float) $bookingPayment->available_amount;
        $newAvailableAmount = $currentAvailableAmount - $amount;
        
        // Determine if status should be updated to USED
        $newStatus = $newAvailableAmount <= 0 ? BookingPaymentStatuses::USED->value : $bookingPayment->status;
        
        // Perform atomic update with WHERE clause to prevent race conditions
        $affectedRows = DB::table('booking_payments')
            ->where('id', $bookingPayment->id)
            ->where('available_amount', $currentAvailableAmount)
            ->update([
                'available_amount' => $newAvailableAmount,
                'status' => $newStatus,
                'updated_at' => now(),
            ]);
            
        // If no rows were affected, it means another process modified the record concurrently
        if ($affectedRows === 0) {
            throw new \RuntimeException('Booking payment was modified by another process. Please retry the operation.');
        }
        
        // Update the model instance to reflect the changes
        $bookingPayment->available_amount = $newAvailableAmount;
        $bookingPayment->status = $newStatus;
    }

    public function loadProductsMemberAndMismatchesRelations(BookingPayment $bookingPayment): BookingPayment
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        return $bookingPayment->load([
            'promoters:' . $promoterQueries->getBasicColumnNames(),
            'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'member:' . $memberQueries->getBasicColumnNames(),
            'products:' . $bookingPaymentProductQueries->getBasicColumnNames(),
            'products.promoters:' . $promoterQueries->getBasicColumnNames(),
            'products.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            'products.product:' . $productQueries->getBasicColumnNames(),
            'products.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'products.boxProduct' => function ($query) use ($boxProductQueries): void {
                $query->select(explode(',', $boxProductQueries->getBasicColumnNames()))
                    ->withoutGlobalScope(SoftDeletingScope::class);
            },
            'products.product.assemblyChildProducts.product:' . $productQueries->getBasicColumnNames(),
            'products.product.masterProduct.assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames(),
            'products.product.masterProduct.assemblyChildMasterProducts.item:' . $masterProductQueries->getBasicColumnNames(),
            'products.product.color:' . $colorQueries->getBasicColumnNames(),
            'products.product.size:' . $sizeQueries->getBasicColumnNames(),
            'products.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'products.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
            'bookingPaymentPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'bookingPaymentPayments.creditNoteUse:' . $creditNoteUseQueries->getBasicColumnNames(),
            'bookingPaymentPayments.creditNoteUse.creditNote:' . $creditNoteQueries->getBasicColumnNames(),
            'refund:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
            'refund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'refunds:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
            'refunds.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'products.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'bookingPaymentPayments.currency:' . $currencyQueries->getBasicColumnNames(),
            'refund.currency:' . $currencyQueries->getBasicColumnNames(),
            'refunds.currency:' . $currencyQueries->getBasicColumnNames(),
        ]);
    }

    public function loadPaymentsMemberAndMismatchesRelations(BookingPayment $bookingPayment): BookingPayment
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return $bookingPayment->load(
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'member:' . $memberQueries->getBasicColumnNames(),
            'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
            'bookingPaymentPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
        );
    }

    public function loadMemberRefundAndMismatchesRelations(BookingPayment $bookingPayment): BookingPayment
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return $bookingPayment->load(
            'member:' . $memberQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'refund:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
            'refund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'refunds:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
            'refunds.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
        );
    }

    public function doesOfflineIdExist(string $offlineId, int $companyId): bool
    {
        $counterUpdateQueries = new CounterUpdateQueries();

        return BookingPayment::query()
            ->where('offline_id', $offlineId)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->exists();
    }

    public function getBookingPaymentWithRelation(
        int $locationId,
        int $companyId,
        int|string $bookingPaymentId
    ): BookingPayment {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        return BookingPayment::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'member_id',
                'total_amount',
                'available_amount',
                'status',
                'remarks',
                'bill_reference_number',
                'created_at'
            )
            ->with(
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
                'bookingPaymentPayments.currency:' . $currencyQueries->getBasicColumnNames(),
                'bookingPaymentPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'products:' . $bookingPaymentProductQueries->getBasicColumnNames(),
                'products.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'products.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'products.product:' . $productQueries->getBasicColumnNames(),
                'products.product.assemblyChildProducts.product:' . $productQueries->getBasicColumnNames(),
                'products.product.color:' . $colorQueries->getBasicColumnNames(),
                'products.product.size:' . $sizeQueries->getBasicColumnNames(),
                'products.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'products.product.masterProduct.assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames(),
                'products.product.masterProduct.assemblyChildMasterProducts.item:' . $masterProductQueries->getBasicColumnNames(),
                'products.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'products.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'products.promoters:' . $promoterQueries->getBasicColumnNames(),
                'products.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'refund:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
                'refund.currency:' . $currencyQueries->getBasicColumnNames(),
                'refund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'refund.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'refund.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'refund.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'refund.counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'refunds:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
                'refunds.currency:' . $currencyQueries->getBasicColumnNames(),
                'refunds.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'refunds.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'refunds.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'refunds.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'refunds.counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'bookingPaymentUses:' . $bookingPaymentUseQueries->getColumnNamesForPaginatedBookingPayments(),
                'bookingPaymentUses.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'bookingPaymentUses.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'bookingPaymentUses.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'bookingPaymentUses.counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->where(function ($query) use ($bookingPaymentId): void {
                $query->where('offline_id', $bookingPaymentId)
                    ->orWhere('id', $bookingPaymentId);
            })
            ->firstOrFail();
    }

    public function updatePromoters(BookingPayment $bookingPayment, ?array $promoterIds): void
    {
        if ([] !== $promoterIds) {
            $bookingPayment->promoters()->sync((array) $promoterIds);
        }
    }

    public function incrementAvailableAmountAndActivate(int $bookingPaymentId, float $amount): void
    {
        $bookingPayment = BookingPayment::findOrFail($bookingPaymentId);
        
        $currentAvailableAmount = (float) $bookingPayment->available_amount;
        $newAvailableAmount = $currentAvailableAmount + $amount;
        $newStatus = BookingPaymentStatuses::ACTIVE->value;
        
        // Perform atomic update with WHERE clause to prevent race conditions
        $affectedRows = DB::table('booking_payments')
            ->where('id', $bookingPaymentId)
            ->where('available_amount', $currentAvailableAmount)
            ->update([
                'available_amount' => $newAvailableAmount,
                'status' => $newStatus,
                'updated_at' => now(),
            ]);
            
        // If no rows were affected, it means another process modified the record concurrently
        if ($affectedRows === 0) {
            throw new \RuntimeException('Booking payment was modified by another process. Please retry the operation.');
        }
        
        // Update the model instance to reflect the changes
        $bookingPayment->available_amount = $newAvailableAmount;
        $bookingPayment->status = $newStatus;
    }

    public function getPaginatedBookingPaymentList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getBookingPaymentList($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getBookingPaymentForExport(array $filterData, int $companyId): Collection
    {
        return $this->getBookingPaymentList($filterData, $companyId)->get();
    }

    public function getSumOfAvailableAmountByCompany(array $filterData, int $companyId): float
    {
        return (float) $this->getBookingPaymentList($filterData, $companyId)->sum('available_amount');
    }

    public function getDetailsById(int $bookingPaymentId, int $companyId): BookingPayment
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
        $bookingPaymentVoidUseQueries = resolve(BookingPaymentVoidUseQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
            'promoters:' . $promoterQueries->getBasicColumnNames(),
            'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            'products:' . $bookingPaymentProductQueries->getBasicColumnNames(),
            'products.product:' . $productQueries->getBasicColumnNames(),
            'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
            'bookingPaymentPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'products.promoters:' . $promoterQueries->getBasicColumnNames(),
            'products.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            'refund:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
            'refund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'refunds:' . $bookingPaymentRefundQueries->getBasicColumnNames(),
            'refunds.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'bookingPaymentUses:' . $bookingPaymentUseQueries->getBasicColumnNames(),
            'bookingPaymentUses.salePayment:' . $salePaymentQueries->getSaleIdColumn(),
            'bookingPaymentUses.salePayment.sale:' . $saleQueries->getOfflineSaleId(),
            'bookingPaymentVoidUses:' . $bookingPaymentVoidUseQueries->getColumnNames(),
            'bookingPaymentVoidUses.voidSale:' . $voidSaleQueries->getColumnsForListPage(),
            'bookingPaymentVoidUses.voidSale.sale:' . $saleQueries->getOfflineSaleId(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'products.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'products.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'products.product.color:' . $colorQueries->getBasicColumnNames(),
                'products.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return BookingPayment::query()
            ->select('id', 'offline_id', 'counter_update_id', 'remarks', 'bill_reference_number', 'total_amount')
            ->with($relations)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->findOrFail($bookingPaymentId);
    }

    public function getPaginatedBookingPaymentListForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->getBookingPaymentListForStoreManager($filterData, $companyId, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function getBookingPaymentForExportForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->getBookingPaymentListForStoreManager($filterData, $companyId, $locationId)->get();
    }

    public function getDetailsForPrint(int $bookingPaymentId, int $companyId, ?int $locationId): BookingPayment
    {
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'products:' . $bookingPaymentProductQueries->getBasicColumnNames(),
            'products.product:' . $productQueries->getBasicColumnNames(),
            'bookingPaymentPayments:' . $bookingPaymentPaymentQueries->getBasicColumnNames(),
            'bookingPaymentPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'member:' . $memberQueries->getBasicColumnNamesForPrintReport(),
            'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'products.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'products.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'products.product.color:' . $colorQueries->getBasicColumnNames(),
                'products.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return BookingPayment::query()
            ->select(
                'id',
                'offline_id',
                'member_id',
                'counter_update_id',
                'remarks',
                'bill_reference_number',
                'total_amount'
            )
            ->with($relations)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when(null !== $locationId, function ($query) use ($locationId, $counterUpdateQueries): void {
                $query->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreId((int) $locationId));
            })
            ->findOrFail($bookingPaymentId);
    }

    public function digitalInvoiceUpdate(int $bookingPaymentId): void
    {
        $bookingPayment = BookingPayment::select('id', 'digital_invoice_submitted')
            ->where('digital_invoice_submitted', false)
            ->findOrFail($bookingPaymentId);

        $bookingPayment->update([
            'digital_invoice_submitted' => true,
        ]);
    }

    public function getBookingPaymentByStoreIdCounterId(
        string $receiptNumber,
        int $locationId,
        int $counterId
    ): ?BookingPayment {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return BookingPayment::select('id', 'digital_invoice_submitted')
            ->where('offline_id', $receiptNumber)
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounterIdAndLocationId($locationId, $counterId))
            ->first();
    }

    private function getBookingPaymentList(array $filterData, int $companyId): Builder
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        return BookingPayment::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'member_id',
                'total_amount',
                'available_amount',
                'status',
                'remarks',
                'bill_reference_number',
                'authorizer_id',
                'authorizer_type',
                'happened_at',
                'created_at',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->with(
                'authorizer:' . $this->getMorphAuthorizerBasicColumns(),
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData, $memberQueries): void {
                $query->where(function ($query) use ($filterData, $memberQueries): void {
                    $query->whereAny(
                        ['offline_id', 'bill_reference_number', 'total_amount', 'available_amount'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                        ->orWhereHas('member', $memberQueries->searchByBasicColumns($filterData['search_text']));
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData, $memberQueries): void {
                $query->whereHas('member', $memberQueries->filterById((int) $filterData['member_id']));
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when(array_key_exists('receipt_id', $filterData) && null !== $filterData['receipt_id'], function ($query) use (
                $filterData
            ): void {
                $query->where('offline_id', $filterData['receipt_id']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                    });
                });
            })
            ->when($filterData['status_id'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getBookingPaymentListForStoreManager(array $filterData, int $companyId, int $locationId): Builder
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        return BookingPayment::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'member_id',
                'total_amount',
                'available_amount',
                'status',
                'remarks',
                'bill_reference_number',
                'authorizer_id',
                'authorizer_type',
                'happened_at',
                'created_at',
                'digital_invoice_submitted',
                'digital_invoice_number',
            )
            ->with(
                'authorizer:' . $this->getMorphAuthorizerBasicColumns(),
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->whereHas('counterUpdate', function ($query) use ($counterQueries, $locationId): void {
                $query->select('id', 'counter_id')
                    ->whereHas('counter', $counterQueries->filterByLocationId($locationId));
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $memberQueries): void {
                $query->where(function ($query) use ($filterData, $memberQueries): void {
                    $query->whereAny(
                        ['offline_id', 'bill_reference_number', 'total_amount', 'available_amount'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                        ->orWhereHas('member', $memberQueries->searchByBasicColumns($filterData['search_text']));
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData, $memberQueries): void {
                $query->whereHas('member', $memberQueries->filterById((int) $filterData['member_id']));
            })
            ->when(array_key_exists('receipt_id', $filterData) && null !== $filterData['receipt_id'], function ($query) use (
                $filterData
            ): void {
                $query->where('offline_id', $filterData['receipt_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where(function ($query) use ($filterData): void {
                        $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                    })->orWhere(function ($query) use ($filterData): void {
                        $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                    });
                });
            })
            ->when($filterData['status_id'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getBasicColumnForDigitalInvoice(): Closure
    {
        return fn ($query) => $query->select('id', 'offline_id', 'digital_invoice_number');
    }

    private function getMorphAuthorizerBasicColumns(): string
    {
        return 'id,employee_id';
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $bookingPayments = BookingPayment::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($bookingPayments as $bookingPayment) {
            $bookingPayment->member_id = $newMemberId;
            $bookingPayment->save();
        }
    }
}
