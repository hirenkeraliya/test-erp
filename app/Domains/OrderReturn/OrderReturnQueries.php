<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderCreditNote\OrderCreditNoteQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\OrderReturn;
use App\Models\StoreManager;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OrderReturnQueries
{
    public function doesOrderIdExist(string $orderId): bool
    {
        return OrderReturn::query()
            ->where('original_order_id', $orderId)
            ->exists();
    }

    public function addNew(
        StoreManager $storeManager,
        int $originalOrderId,
        int $locationId,
        string $digitalInvoiceNumber,
        string $receiptNumber,
        ?int $memberId,
    ): OrderReturn {
        return OrderReturn::create([
            'original_order_id' => $originalOrderId,
            'location_id' => $locationId,
            'store_manager_id' => $storeManager->id,
            'member_id' => $memberId,
            'receipt_number' => $receiptNumber,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function updateTotals(OrderReturn $orderReturn, float $roundOffAmount = 0.00): void
    {
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);

        $orderReturn->load('orderReturnItems:' . $orderReturnItemQueries->getColumnNamesForOrderUpdate());
        $orderReturn->update([
            'total_tax_amount' => $orderReturn->getOrderReturnItems()->sum('total_tax_amount'),
            'cart_discount_amount' => $orderReturn->getOrderReturnItems()->sum('cart_discount_amount'),
            'item_discount_amount' => $orderReturn->getOrderReturnItems()->sum('item_discount_amount'),
            'total_discount_amount' => $orderReturn->getOrderReturnItems()->sum('total_discount_amount'),
            'total_price_paid' => $orderReturn->getOrderReturnItems()->sum('total_price_paid') + $roundOffAmount,
            'round_off_amount' => $roundOffAmount,
            'total_amount_before_round_off' => $orderReturn->getOrderReturnItems()->sum('total_price_paid'),
        ]);
    }

    public function loadRelations(OrderReturn $orderReturn): OrderReturn
    {
        $productQueries = resolve(ProductQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $orderCreditNoteQueries = resolve(OrderCreditNoteQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cityQueries = resolve(CityQueries::class);

        $orderReturn->refresh();

        if (config('app.product_variant')) {
            return $orderReturn->load(
                'member:' . $memberQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
                'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNamesForOrderReturn(),
                'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'orderReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'orderReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'orderCreditNote:' . $orderCreditNoteQueries->getBasicColumnNames(),
                'orderCreditNote.member:' . $memberQueries->getBasicColumnNames(),
                'orderCreditNote.orderReturn:' . $this->getOfflineIdAndOrderReturnIdColumnNames(),
                'orderCreditNote.orderReturn.originalOrder:' . $orderQueries->getOfflineOrderId(),
            );
        }

        return $orderReturn->load(
            'member:' . $memberQueries->getBasicColumnNames(),
            'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            'location.city:' . $cityQueries->getBasicColumnNames(),
            'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
            'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNamesForOrderReturn(),
            'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
            'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
            'orderReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'orderReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
            'orderCreditNote:' . $orderCreditNoteQueries->getBasicColumnNames(),
            'orderCreditNote.member:' . $memberQueries->getBasicColumnNames(),
            'orderCreditNote.orderReturn:' . $this->getOfflineIdAndOrderReturnIdColumnNames(),
            'orderCreditNote.orderReturn.originalOrder:' . $orderQueries->getOfflineOrderId(),
        );
    }

    public function getOfflineIdAndOrderReturnIdColumnNames(): string
    {
        return 'id,receipt_number,original_order_id';
    }

    public function getPaginatedCompleteOrderWithRelations(
        array $filterData,
        int $storeManagerId,
        int $locationId,
    ): LengthAwarePaginator {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);

        return OrderReturn::query()
            ->select(...$this->getBasicColumns())
            ->when(0 !== $storeManagerId, function ($query) use ($storeManagerQueries, $storeManagerId): void {
                $query->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId));
            })
            ->when(0 !== $locationId, function ($query) use ($locationQueries, $locationId): void {
                $query->whereHas('location', $locationQueries->filterById($locationId, LocationTypes::STORE->value));
            })
            ->withSum('orderReturnItems', 'quantity')
            ->with([
                'member',
                'originalOrder:' . $orderQueries->getOfflineOrderId(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'location:' . $locationQueries->getNameColumnName(),
                'location.company:' . $companyQueries->getBasicColumnNames(),
                'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('receipt_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', $filterData['date_range'][0])
                    ->where('created_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getBasicColumns(): array
    {
        return [
            'id',
            'store_manager_id',
            'location_id',
            'member_id',
            'receipt_number',
            'original_order_id',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off_amount',
            'total_price_paid',
            'created_at',
            'digital_invoice_submitted',
            'digital_invoice_number',
        ];
    }

    public function getFilteredTotalsForReport(array $filterData, int $storeManagerId, int $locationId): Collection
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);

        return OrderReturn::query()
            ->select('id')
            ->with([
                'orderReturnItems' => $orderReturnItemQueries->getSumOfPriceAndQuantity(),
            ])
            ->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId))
            ->when(0 !== $locationId, function ($query) use ($locationQueries, $locationId): void {
                $query->whereHas('location', $locationQueries->filterById($locationId, LocationTypes::STORE->value));
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('receipt_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', $filterData['date_range'][0])
                    ->where('created_at', '<=', $filterData['date_range'][1]);
            })
            ->get();
    }

    public function getOrderReturnItemsForStoreManager(int $orderReturnId, int $locationId): OrderReturn
    {
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $orderQueries = resolve(OrderQueries::class);

        if (config('app.product_variant')) {
            return OrderReturn::query()
                ->select(
                    'id',
                    'receipt_number',
                    'store_manager_id',
                    'location_id',
                    'member_id',
                    'original_order_id',
                    'total_tax_amount',
                    'cart_discount_amount',
                    'item_discount_amount',
                    'total_discount_amount',
                    'total_amount_before_round_off',
                    'round_off_amount',
                    'total_price_paid',
                )
                ->with(
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                    'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                    'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                    'originalOrder:' . $orderQueries->getColumnNames(),
                )
                ->where('location_id', $locationId)
                ->findOrFail($orderReturnId);
        }

        return OrderReturn::query()
            ->select(
                'id',
                'receipt_number',
                'store_manager_id',
                'location_id',
                'member_id',
                'original_order_id',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_before_round_off',
                'round_off_amount',
                'total_price_paid',
            )
            ->with(
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'orderReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'originalOrder:' . $orderQueries->getColumnNames(),
            )
            ->where('location_id', $locationId)
            ->findOrFail($orderReturnId);
    }

    public function getOrderReturnItems(int $orderReturnId, int $companyId): OrderReturn
    {
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return OrderReturn::query()
                ->select(
                    'id',
                    'receipt_number',
                    'store_manager_id',
                    'location_id',
                    'member_id',
                    'original_order_id',
                    'total_tax_amount',
                    'cart_discount_amount',
                    'item_discount_amount',
                    'total_discount_amount',
                    'total_amount_before_round_off',
                    'round_off_amount',
                    'total_price_paid',
                )
                ->with([
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                    'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                    'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                    'originalOrder:' . $orderQueries->getColumnNames(),
                ])
                ->whereHas(
                    'location',
                    $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)
                )
                ->findOrFail($orderReturnId);
        }

        return OrderReturn::query()
            ->select(
                'id',
                'receipt_number',
                'store_manager_id',
                'location_id',
                'member_id',
                'original_order_id',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_before_round_off',
                'round_off_amount',
                'total_price_paid',
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'orderReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'originalOrder:' . $orderQueries->getColumnNames(),
            ])
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->findOrFail($orderReturnId);
    }

    public function getOrderReturnReceiptForStoreManager(int $orderReturnId, int $locationId): OrderReturn
    {
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $orderCreditNoteQueries = resolve(OrderCreditNoteQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return OrderReturn::query()
                ->select(
                    'id',
                    'store_manager_id',
                    'location_id',
                    'member_id',
                    'receipt_number',
                    'original_order_id',
                    'total_tax_amount',
                    'cart_discount_amount',
                    'item_discount_amount',
                    'total_discount_amount',
                    'total_amount_before_round_off',
                    'round_off_amount',
                    'total_price_paid',
                    'created_at'
                )
                ->with(
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                    'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                    'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                    'originalOrder:' . $orderQueries->getColumnNames(),
                    'orderCreditNote:' . $orderCreditNoteQueries->getBasicColumnNames(),
                    'orderCreditNote.member:' . $memberQueries->getBasicColumnNames(),
                    'orderCreditNote.orderReturn:' . $this->getOfflineIdAndOrderReturnIdColumnNames(),
                    'orderCreditNote.orderReturn.originalOrder:' . $orderQueries->getOfflineOrderId(),
                )
                ->where('location_id', $locationId)
                ->findOrFail($orderReturnId);
        }

        return OrderReturn::query()
            ->select(
                'id',
                'store_manager_id',
                'location_id',
                'member_id',
                'receipt_number',
                'original_order_id',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_before_round_off',
                'round_off_amount',
                'total_price_paid',
                'created_at'
            )
            ->with(
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'orderReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'originalOrder:' . $orderQueries->getColumnNames(),
                'orderCreditNote:' . $orderCreditNoteQueries->getBasicColumnNames(),
                'orderCreditNote.member:' . $memberQueries->getBasicColumnNames(),
                'orderCreditNote.orderReturn:' . $this->getOfflineIdAndOrderReturnIdColumnNames(),
                'orderCreditNote.orderReturn.originalOrder:' . $orderQueries->getOfflineOrderId(),
            )
            ->where('location_id', $locationId)
            ->findOrFail($orderReturnId);
    }

    public function getOrderReturnReceipt(int $orderReturnId, int $companyId): OrderReturn
    {
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $orderCreditNoteQueries = resolve(OrderCreditNoteQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return OrderReturn::query()
                ->select(
                    'id',
                    'store_manager_id',
                    'location_id',
                    'member_id',
                    'receipt_number',
                    'original_order_id',
                    'total_tax_amount',
                    'cart_discount_amount',
                    'item_discount_amount',
                    'total_discount_amount',
                    'total_amount_before_round_off',
                    'round_off_amount',
                    'total_price_paid',
                    'created_at'
                )
                ->with([
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                    'location.city:' . $cityQueries->getBasicColumnNames(),
                    'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                    'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                    'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderReturnItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                    'originalOrder:' . $orderQueries->getColumnNames(),
                    'orderCreditNote:' . $orderCreditNoteQueries->getBasicColumnNames(),
                    'orderCreditNote.member:' . $memberQueries->getBasicColumnNames(),
                    'orderCreditNote.orderReturn:' . $this->getOfflineIdAndOrderReturnIdColumnNames(),
                    'orderCreditNote.orderReturn.originalOrder:' . $orderQueries->getOfflineOrderId(),
                ])
                ->whereHas(
                    'location',
                    $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)
                )
                ->findOrFail($orderReturnId);
        }

        return OrderReturn::query()
            ->select(
                'id',
                'store_manager_id',
                'location_id',
                'member_id',
                'receipt_number',
                'original_order_id',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_before_round_off',
                'round_off_amount',
                'total_price_paid',
                'created_at'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
                'orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderReturnItems.orderItem:' . $orderItemQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderReturnItems.orderItem.promoters.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'orderReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'orderReturnItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderReturnItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderReturnItems.orderReturnReason:' . $saleReturnReasonQueries->getBasicColumnNames(),
                'originalOrder:' . $orderQueries->getColumnNames(),
                'orderCreditNote:' . $orderCreditNoteQueries->getBasicColumnNames(),
                'orderCreditNote.member:' . $memberQueries->getBasicColumnNames(),
                'orderCreditNote.orderReturn:' . $this->getOfflineIdAndOrderReturnIdColumnNames(),
                'orderCreditNote.orderReturn.originalOrder:' . $orderQueries->getOfflineOrderId(),
            ])
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->findOrFail($orderReturnId);
    }

    public function getIdAndOriginalOrderId(): string
    {
        return 'id,original_order_id';
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->whereHas(
            'location',
            $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)
        );
    }

    public function digitalInvoiceUpdate(int $orderReturnId): void
    {
        $orderReturn = OrderReturn::select('id', 'digital_invoice_submitted')
            ->where('digital_invoice_submitted', false)
            ->findOrFail($orderReturnId);

        $orderReturn->update([
            'digital_invoice_submitted' => true,
        ]);
    }

    public function getBasicColumnForDigitalInvoice(): Closure
    {
        return fn ($query) => $query->select('id', 'receipt_number', 'digital_invoice_number');
    }

    public function getOfflineOrderReturnId(): string
    {
        return 'id,receipt_number';
    }
}
