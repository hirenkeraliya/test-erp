<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\Admin;
use App\Models\AggregateProcessTracker;
use App\Models\AssemblyChildMasterProduct;
use App\Models\AssemblyChildProduct;
use App\Models\AttachedTemplate;
use App\Models\Attribute;
use App\Models\AttributeChannelReference;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationMonthDate;
use App\Models\AutomatedNotificationProduct;
use App\Models\AutomatedNotificationStore;
use App\Models\AutomatedNotificationWeekDay;
use App\Models\Banner;
use App\Models\BannerChannelReference;
use App\Models\Batch;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\BookingPaymentProduct;
use App\Models\BookingPaymentRefund;
use App\Models\BookingPaymentUse;
use App\Models\BookingPaymentVoidUse;
use App\Models\BoxProduct;
use App\Models\BoxProductLoyaltyPoint;
use App\Models\Brand;
use App\Models\BrandChannelReference;
use App\Models\CancelCreditSale;
use App\Models\CancelLayawaySale;
use App\Models\Cashback;
use App\Models\CashbackPrice;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\CashierGroupPermission;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Category;
use App\Models\CategoryChannelReference;
use App\Models\CategoryWiseDailyTotal;
use App\Models\City;
use App\Models\CloseCounterDenomination;
use App\Models\CloseCounterPayment;
use App\Models\Color;
use App\Models\ColorChannelReference;
use App\Models\ColorGroup;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\ComplimentaryItemReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\CounterUpdateDeclarationAttemptPayment;
use App\Models\CounterUpdateEvent;
use App\Models\Country;
use App\Models\CountryChannelReference;
use App\Models\Courier;
use App\Models\CourierAccessToken;
use App\Models\CourierWebhookUrl;
use App\Models\CreditNote;
use App\Models\CreditNoteExpiration;
use App\Models\CreditNoteRefund;
use App\Models\CreditNoteUse;
use App\Models\CreditNoteVoidUse;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\CustomFieldValue;
use App\Models\Denomination;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DigitalInvoice;
use App\Models\Director;
use App\Models\DraftProductTransaction;
use App\Models\DreamPrice;
use App\Models\DreamPriceChannelReference;
use App\Models\DreamPriceProduct;
use App\Models\Driver;
use App\Models\DynamicMenu;
use App\Models\DynamicMenuChannelReference;
use App\Models\EcommerceLocation;
use App\Models\EmailRecipient;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\EmployeeTransaction;
use App\Models\ExportRecord;
use App\Models\ExportRecordTransaction;
use App\Models\ExternalCategory;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\ExternalLocation;
use App\Models\ExternalProduct;
use App\Models\ExternalPurchaseOrder;
use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderItemSerialNumber;
use App\Models\ExternalPurchaseOrderPartialReceive;
use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use App\Models\ExternalPurchaseOrderPartialReceiveItemBatch;
use App\Models\ExternalPurchaseOrderPartialReceiveItemSerialNumber;
use App\Models\ExternalPurchaseOrderTransaction;
use App\Models\GenuineProductVerification;
use App\Models\GenuineReceiptVerification;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\HoldBookingPaymentItem;
use App\Models\HoldSale;
use App\Models\HoldSaleDetail;
use App\Models\HoldSaleItem;
use App\Models\HoldSaleReturnItem;
use App\Models\ImportRecord;
use App\Models\ImportRecordFailedRow;
use App\Models\Integration;
use App\Models\IntegrationSyncUpdate;
use App\Models\IntegrationWebhookUrl;
use App\Models\Inventory;
use App\Models\InventoryRollbackOrderStatus;
use App\Models\InventoryUnit;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyCampaignChannelReference;
use App\Models\LoyaltyCampaignConfiguration;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\ManualNotification;
use App\Models\ManualNotificationMemberTypes;
use App\Models\MasterProduct;
use App\Models\MasterProductChannelReference;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\MemberAddressChannelReference;
use App\Models\MemberChannelReference;
use App\Models\MemberGroup;
use App\Models\MemberGroupChannelReference;
use App\Models\MemberGroupMember;
use App\Models\MemberProductReview;
use App\Models\Membership;
use App\Models\MembershipAssignment;
use App\Models\MergeMemberTransaction;
use App\Models\MergeProductTransaction;
use App\Models\Model;
use App\Models\ModelHasRole;
use App\Models\MysteryGift;
use App\Models\MysteryGiftProduct;
use App\Models\MysteryGiftUsage;
use App\Models\Notification;
use App\Models\OnlineOrderTransaction;
use App\Models\OnlineSalesChargeChannelReference;
use App\Models\OnlineSalesCharges;
use App\Models\OnlineSalesChargeTier;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderChannelReference;
use App\Models\OrderCreditNote;
use App\Models\OrderDiscount;
use App\Models\OrderIntegration;
use App\Models\OrderItem;
use App\Models\OrderItemAssemblyChildProduct;
use App\Models\OrderItemDiscount;
use App\Models\OrderItemExchange;
use App\Models\OrderItemUnit;
use App\Models\OrderLoyaltyPoint;
use App\Models\OrderPayment;
use App\Models\OrderPickingList;
use App\Models\OrderPickingListItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\PackageType;
use App\Models\PartiallyReceiveFulfillment;
use App\Models\PartiallyReceiveFulfillmentItem;
use App\Models\PastYearData;
use App\Models\PaymentType;
use App\Models\Permission;
use App\Models\PosAdvertisement;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\ProductAgeing;
use App\Models\ProductChannelReference;
use App\Models\ProductChannelReferenceCategory;
use App\Models\ProductCollection;
use App\Models\ProductCollectionChannelReference;
use App\Models\ProductCollectionFilter;
use App\Models\ProductCollectionFilterAttributeValue;
use App\Models\ProductCollectionFilterType;
use App\Models\ProductCollectionProduct;
use App\Models\ProductLoyaltyPoint;
use App\Models\ProductVariantValue;
use App\Models\Promoter;
use App\Models\PromoterCommission;
use App\Models\PromoterCommissionRegeneration;
use App\Models\PromoterCommissionUpdate;
use App\Models\PromoterGroup;
use App\Models\Promotion;
use App\Models\PromotionChannelReference;
use App\Models\PromotionMonthDate;
use App\Models\PromotionPromoCode;
use App\Models\PromotionTier;
use App\Models\PromotionWeekDay;
use App\Models\PurchaseAmount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentItemBatch;
use App\Models\PurchaseOrderFulfillmentItemTransaction;
use App\Models\PurchaseOrderFulfillmentItemUnit;
use App\Models\PurchaseOrderFulfillmentTransaction;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceTransaction;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderTransaction;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\PurchasePlanTransaction;
use App\Models\Region;
use App\Models\ReservedStock;
use App\Models\RetailPlanningHierarchy;
use App\Models\Reward;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleAchievedTarget;
use App\Models\SaleCashback;
use App\Models\SaleChannel;
use App\Models\SaleChannelInventoryRollbackOrderStatus;
use App\Models\SaleChannelWebhookUrl;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemAssemblyChildProduct;
use App\Models\SaleItemComplimentary;
use App\Models\SaleItemDiscount;
use App\Models\SaleItemExchange;
use App\Models\SaleItemPriceOverride;
use App\Models\SaleItemUnit;
use App\Models\SaleLoyaltyPoint;
use App\Models\SalePayment;
use App\Models\SalePriceOverride;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use App\Models\SaleReturnReasonType;
use App\Models\SaleSeason;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use App\Models\SaleThroughRatio;
use App\Models\SaleVoidCashback;
use App\Models\Season;
use App\Models\SellThroughAggregate;
use App\Models\Sequence;
use App\Models\SerialNumber;
use App\Models\ShippingZone;
use App\Models\ShippingZoneChannelReference;
use App\Models\SiteConfiguration;
use App\Models\Size;
use App\Models\SizeChannelReference;
use App\Models\SizeGroup;
use App\Models\SmsHistory;
use App\Models\State;
use App\Models\StateChannelReference;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTake;
use App\Models\StockTakeProduct;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemBatch;
use App\Models\StockTransferItemTransaction;
use App\Models\StockTransferItemUnit;
use App\Models\StockTransferReason;
use App\Models\StockTransferTransaction;
use App\Models\StoreDayClose;
use App\Models\StoreDayClosePayment;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use App\Models\StoreManagerAuthorizationCodeUsage;
use App\Models\StoreWiseDailyTotal;
use App\Models\Style;
use App\Models\SuperAdmin;
use App\Models\SyncTransaction;
use App\Models\Tag;
use App\Models\Template;
use App\Models\TopTwentyAggregateData;
use App\Models\TransitStock;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use App\Models\VoidSaleReasonType;
use App\Models\Voucher;
use App\Models\VoucherChannelReference;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationChannelReference;
use App\Models\VoucherConfigurationTier;
use App\Models\VoucherTransaction;
use App\Models\WarehouseManager;

enum ModelMapping: string
{
    use PrepareEnumDataMethods;

    case EMPLOYEE = Employee::class;
    case PRODUCT_COLLECTION_FILTER_ATTRIBUTE_VALUE = ProductCollectionFilterAttributeValue::class;
    case MEMBER = Member::class;
    case ADMIN = Admin::class;
    case GOODS_RECEIVED_NOTE = GoodsReceivedNote::class;
    case INVENTORY = Inventory::class;
    case GOODS_RECEIVED_NOTE_PRODUCT = GoodsReceivedNoteProduct::class;
    case INVENTORY_UPDATE = InventoryUpdate::class;
    case INVENTORY_UNIT = InventoryUnit::class;
    case PAYMENT_TYPE = PaymentType::class;
    case SUPER_ADMIN = SuperAdmin::class;
    case COMPANY = Company::class;
    case COMPANY_SETTINGS = CompanySetting::class;
    case BRAND = Brand::class;
    case SEASON = Season::class;
    case CATEGORY = Category::class;
    case UNIT_OF_MEASURE = UnitOfMeasure::class;
    case PACKAGE_TYPE = PackageType::class;
    case UNIT_OF_MEASURE_DERIVATIVE = UnitOfMeasureDerivative::class;
    case STYLE = Style::class;
    case COLOR = Color::class;
    case SIZE = Size::class;
    case TAG = Tag::class;
    case STOCK_ADJUSTMENT = StockAdjustment::class;
    case STOCK_ADJUSTMENT_ITEM = StockAdjustmentItem::class;
    case STOCK_TRANSFER = StockTransfer::class;
    case STOCK_TRANSFER_ITEM = StockTransferItem::class;
    case STOCK_TRANSFER_ITEM_UNIT = StockTransferItemUnit::class;
    case DEPARTMENT = Department::class;
    case CASHIER_GROUP = CashierGroup::class;
    case CASHIER = Cashier::class;
    case PRODUCT = Product::class;
    case BOOKING_PAYMENT = BookingPayment::class;
    case BOOKING_PAYMENT_PAYMENT = BookingPaymentPayment::class;
    case BOOKING_PAYMENT_PRODUCT = BookingPaymentProduct::class;
    case BOOKING_PAYMENT_REFUND = BookingPaymentRefund::class;
    case BOOKING_PAYMENT_USE = BookingPaymentUse::class;
    case CASHBACK = Cashback::class;
    case CASHIER_GROUP_PERMISSION = CashierGroupPermission::class;
    case CASH_MOVEMENT = CashMovement::class;
    case CASH_MOVEMENT_REASON = CashMovementReason::class;
    case CREDIT_NOTE = CreditNote::class;
    case CREDIT_NOTE_EXPIRATION = CreditNoteExpiration::class;
    case CREDIT_NOTE_REFUND = CreditNoteRefund::class;
    case CREDIT_NOTE_USE = CreditNoteUse::class;
    case DESIGNATION = Designation::class;
    case DENOMINATION = Denomination::class;
    case DIRECTOR = Director::class;
    case DREAM_PRICE = DreamPrice::class;
    case DREAM_PRICE_PRODUCT = DreamPriceProduct::class;
    case EMAIL_RECIPIENT = EmailRecipient::class;
    case COUNTER_UPDATE = CounterUpdate::class;
    case COUNTER = Counter::class;
    case PROMOTER = Promoter::class;
    case PROMOTION = Promotion::class;
    case PROMOTION_MONTH_DATE = PromotionMonthDate::class;
    case PROMOTION_TIER = PromotionTier::class;
    case PROMOTION_WEEK_DAY = PromotionWeekDay::class;
    case PURCHASE_AMOUNT = PurchaseAmount::class;
    case EXTERNAL_PURCHASE_ORDER = ExternalPurchaseOrder::class;
    case SALE = Sale::class;
    case SALE_CASHBACK = SaleCashback::class;
    case SALE_DISCOUNT = SaleDiscount::class;
    case SALE_ITEM = SaleItem::class;
    case SALE_ITEM_DISCOUNT = SaleItemDiscount::class;
    case SALE_ITEM_PRICE_OVERRIDE = SaleItemPriceOverride::class;
    case SALE_PRICE_OVERRIDE = SalePriceOverride::class;
    case SALE_ITEM_UNIT = SaleItemUnit::class;
    case SALE_PAYMENT = SalePayment::class;
    case SALE_RETURN = SaleReturn::class;
    case SALE_RETURN_ITEM = SaleReturnItem::class;
    case SALE_RETURN_REASON = SaleReturnReason::class;
    case STORE_MANAGER = StoreManager::class;
    case BATCH = Batch::class;
    case VOID_SALE = VoidSale::class;
    case VOID_SALE_REASON = VoidSaleReason::class;
    case VOUCHER = Voucher::class;
    case VOUCHER_CONFIGURATION = VoucherConfiguration::class;
    case VOUCHER_CONFIGURATION_TIER = VoucherConfigurationTier::class;
    case IMPORT_RECORD = ImportRecord::class;
    case IMPORT_RECORD_FAILED_ROW = ImportRecordFailedRow::class;
    case COMPLIMENTARY_ITEM_REASON = ComplimentaryItemReason::class;
    case LOYALTY_CAMPAIGN = LoyaltyCampaign::class;
    case LOYALTY_POINT = LoyaltyPoint::class;
    case LOYALTY_POINT_UPDATE = LoyaltyPointUpdate::class;
    case MEMBERSHIP = Membership::class;
    case MEMBERSHIP_ASSIGNMENT = MembershipAssignment::class;
    case CLOSE_COUNTER_DENOMINATION = CloseCounterDenomination::class;
    case CLOSE_COUNTER_PAYMENT = CloseCounterPayment::class;
    case STORE_DAY_CLOSE = StoreDayClose::class;
    case STORE_DAY_CLOSE_PAYMENT = StoreDayClosePayment::class;
    case MODEL = Model::class;
    case STOCK_TRANSFER_ITEM_BATCH = StockTransferItemBatch::class;
    case SALE_ITEM_COMPLIMENTARY = SaleItemComplimentary::class;
    case POS_MISMATCH = PosMismatch::class;
    case NOTIFICATION = Notification::class;
    case MANUAL_NOTIFICATION = ManualNotification::class;
    case COUNTER_UPDATE_EVENT = CounterUpdateEvent::class;
    case SALE_ITEM_EXCHANGE = SaleItemExchange::class;
    case WAREHOUSE_MANAGER = WarehouseManager::class;
    case SYNC_TRANSACTION = SyncTransaction::class;
    case POS_ADVERTISEMENT = PosAdvertisement::class;
    case STORE_WISE_DAILY_TOTAL = StoreWiseDailyTotal::class;
    case CATEGORY_WISE_DAILY_TOTAL = CategoryWiseDailyTotal::class;
    case PROMOTER_COMMISSION_REGENERATION = PromoterCommissionRegeneration::class;
    case STOCK_TAKES = StockTake::class;
    case HOLD_SALE = HoldSale::class;
    case HOLD_SALE_ITEM = HoldSaleItem::class;
    case HOLD_SALE_DETAIL = HoldSaleDetail::class;
    case HOLD_SALE_RETURN_ITEM = HoldSaleReturnItem::class;
    case HOLD_BOOKING_PAYMENT_ITEM = HoldBookingPaymentItem::class;
    case MEMBER_GROUP = MemberGroup::class;
    case EMPLOYEE_GROUP = EmployeeGroup::class;
    case EXPORT_RECORD = ExportRecord::class;
    case EXPORT_RECORD_TRANSACTION = ExportRecordTransaction::class;
    case REWORD = Reward::class;
    case REGION = Region::class;
    case COLOR_GROUP = ColorGroup::class;
    case VENDOR = Vendor::class;
    case STOCK_TRANSFER_TRANSACTION = StockTransferTransaction::class;
    case SIZE_GROUP = SizeGroup::class;
    case SMS_HISTORY = SmsHistory::class;
    case PROMOTER_GROUP = PromoterGroup::class;
    case SITE_CONFIGURATION = SiteConfiguration::class;
    case CANCEL_LAYAWAY_SALE = CancelLayawaySale::class;
    case CANCEL_CREDIT_SALE = CancelCreditSale::class;
    case RESERVED_STOCK = ReservedStock::class;
    case ROLE = Role::class;
    case PERMISSION = Permission::class;
    case MODEL_HAS_ROLE = ModelHasRole::class;
    case PRODUCT_LOYALTY_POINT = ProductLoyaltyPoint::class;
    case ORDER = Order::class;
    case ORDER_ITEM = OrderItem::class;
    case ORDER_ITEM_EXCHANGE = OrderItemExchange::class;
    case ORDER_ITEM_UNIT = OrderItemUnit::class;
    case ORDER_PAYMENT = OrderPayment::class;
    case ORDER_RETURN = OrderReturn::class;
    case ORDER_RETURN_ITEM = OrderReturnItem::class;
    case ORDER_CREDIT_NOTE = OrderCreditNote::class;
    case STOCK_TRANSFER_ITEM_TRANSACTION = StockTransferItemTransaction::class;
    case EXTERNAL_CONNECTION = ExternalConnection::class;
    case EXTERNAL_COMPANY = ExternalCompany::class;
    case EXTERNAL_LOCATION = ExternalLocation::class;
    case PURCHASE_ORDER = PurchaseOrder::class;
    case PURCHASE_ORDER_ITEM = PurchaseOrderItem::class;
    case PURCHASE_ORDER_FULFILLMENT = PurchaseOrderFulfillment::class;
    case PURCHASE_ORDER_FULFILLMENT_ITEM = PurchaseOrderFulfillmentItem::class;
    case PURCHASE_ORDER_FULFILLMENT_ITEM_UNIT = PurchaseOrderFulfillmentItemUnit::class;
    case MERGE_PRODUCT_TRANSACTION = MergeProductTransaction::class;
    case PROMOTION_PROMO_CODE = PromotionPromoCode::class;
    case GIFT_CARD = GiftCard::class;
    case BOOKING_PAYMENT_VOID_USE = BookingPaymentVoidUse::class;
    case COUNTER_UPDATE_DECLARATION_ATTEMPT_PAYMENT = CounterUpdateDeclarationAttemptPayment::class;
    case PROMOTER_COMMISSION_UPDATE = PromoterCommissionUpdate::class;
    case CREDIT_NOTE_VOID_USE = CreditNoteVoidUse::class;
    case GIFT_CARD_TRANSACTION = GiftCardTransaction::class;
    case STOCK_TAKE_PRODUCT = StockTakeProduct::class;
    case VOUCHER_TRANSACTION = VoucherTransaction::class;
    case SEQUENCE = Sequence::class;
    case STOCK_TRANSFER_REASON = StockTransferReason::class;
    case PROMOTER_COMMISSION = PromoterCommission::class;
    case SALE_VOID_CASHBACK = SaleVoidCashback::class;
    case COUNTER_UPDATE_DECLARATION_ATTEMPT = CounterUpdateDeclarationAttempt::class;
    case PURCHASE_ORDER_TRANSACTION = PurchaseOrderTransaction::class;
    case PURCHASE_ORDER_FULFILLMENT_ITEM_BATCH = PurchaseOrderFulfillmentItemBatch::class;
    case PURCHASE_ORDER_FULFILLMENT_ITEM_TRANSACTION = PurchaseOrderFulfillmentItemTransaction::class;
    case PURCHASE_ORDER_INVOICE = PurchaseOrderInvoice::class;
    case PURCHASE_ORDER_FULFILLMENT_TRANSACTION = PurchaseOrderFulfillmentTransaction::class;
    case PURCHASE_ORDER_INVOICE_TRANSACTION = PurchaseOrderInvoiceTransaction::class;
    case SALE_THROUGH_RATIO = SaleThroughRatio::class;
    case AUTOMATED_NOTIFICATION = AutomatedNotification::class;
    case AUTOMATED_NOTIFICATION_WEEK_DAY = AutomatedNotificationWeekDay::class;
    case AUTOMATED_NOTIFICATION_MONTH_DATE = AutomatedNotificationMonthDate::class;
    case SALE_LOYALTY_POINT = SaleLoyaltyPoint::class;
    case HAPPY_HOUR_DISCOUNT = HappyHourDiscount::class;
    case SALE_TARGET = SaleTarget::class;
    case SALE_TARGET_TIME_FRAME = SaleTargetTimeframe::class;
    case SALE_ACHIEVED_TARGET = SaleAchievedTarget::class;
    case EMPLOYEE_TRANSACTION = EmployeeTransaction::class;
    case ASSEMBLY_CHILD_PRODUCT = AssemblyChildProduct::class;
    case BOX_PRODUCT = BoxProduct::class;
    case STORE_MANAGER_AUTHORIZATION_CODE = StoreManagerAuthorizationCode::class;
    case SALE_ITEM_ASSEMBLY_CHILD_PRODUCT = SaleItemAssemblyChildProduct::class;
    case ORDER_ITEM_ASSEMBLY_CHILD_PRODUCT = OrderItemAssemblyChildProduct::class;
    case STORE_MANAGER_AUTHORIZATION_CODE_USAGE = StoreManagerAuthorizationCodeUsage::class;
    case STOCK_TRANSFER_AVERAGE_LEAD_DAYS = StockTransferAverageLeadDays::class;
    case BOX_PRODUCT_LOYALTY_POINT = BoxProductLoyaltyPoint::class;
    case MANUAL_NOTIFICATION_MEMBER_TYPES = ManualNotificationMemberTypes::class;
    case SALE_SEASON = SaleSeason::class;
    case EXTERNAL_PRODUCT = ExternalProduct::class;
    case PARTIALLY_RECEIVE_FULFILLMENT = PartiallyReceiveFulfillment::class;
    case PARTIALLY_RECEIVE_FULFILLMENT_ITEM = PartiallyReceiveFulfillmentItem::class;
    case EXTERNAL_PURCHASE_ORDER_PARTIAL_RECEIVE_ITEM_BATCH = ExternalPurchaseOrderPartialReceiveItemBatch::class;
    case DRAFT_PRODUCT_TRANSACTION = DraftProductTransaction::class;
    case HAPPY_HOUR_DISCOUNT_TRANSACTION = HappyHourDiscountTransaction::class;
    case INVENTORY_ROLLBACK_ORDER_STATUS = InventoryRollbackOrderStatus::class;
    case MEMBER_ADDRESS = MemberAddress::class;
    case PRODUCT_AGEING = ProductAgeing::class;
    case TOP_TWENTY_AGGREGATE_DATA = TopTwentyAggregateData::class;
    case BANNER = Banner::class;
    case PRODUCT_COLLECTION = ProductCollection::class;
    case PRODUCT_COLLECTION_FILTER = ProductCollectionFilter::class;
    case PRODUCT_COLLECTION_FILTER_TYPE = ProductCollectionFilterType::class;
    case PRODUCT_COLLECTION_PRODUCT = ProductCollectionProduct::class;
    case SALE_RETURN_REASON_TYPE = SaleReturnReasonType::class;
    case TEMPLATE = Template::class;
    case ATTRIBUTE = Attribute::class;
    case LOYALTY_CAMPAIGN_CONFIGURATION = LoyaltyCampaignConfiguration::class;
    case VOID_SALE_REASON_TYPE = VoidSaleReasonType::class;
    case CUSTOM_FIELD_VALUE = CustomFieldValue::class;
    case ATTACHED_TEMPLATE = AttachedTemplate::class;
    case LOCATION = Location::class;
    case ECOMMERCE_LOCATION = EcommerceLocation::class;
    case PAST_YEAR_DATA = PastYearData::class;
    case AUTOMATED_NOTIFICATION_STORE = AutomatedNotificationStore::class;
    case AUTOMATED_NOTIFICATION_PRODUCT = AutomatedNotificationProduct::class;
    case PRODUCT_CHANNEL_REFERENCE = ProductChannelReference::class;
    case SALE_CHANNEL = SaleChannel::class;
    case SALE_CHANNEL_INVENTORY_ROLLBACK_ORDER_STATUS = SaleChannelInventoryRollbackOrderStatus::class;
    case SALE_CHANNEL_WEBHOOK_URL = SaleChannelWebhookUrl::class;
    case INTEGRATION_WEBHOOK_URL = IntegrationWebhookUrl::class;
    case ONLINE_SALES_CHARGES = OnlineSalesCharges::class;
    case COUNTRY = Country::class;
    case CURRENCY = Currency::class;
    case CURRENCY_RATE = CurrencyRate::class;
    case CASHBACK_PRICE = CashbackPrice::class;
    case ORDER_PICKING_LIST = OrderPickingList::class;
    case ORDER_PICKING_LIST_ITEM = OrderPickingListItem::class;
    case ORDER_ADDRESS = OrderAddress::class;
    case STATE = State::class;
    case CITY = City::class;
    case DIGITAL_INVOICE = DigitalInvoice::class;
    case ORDER_CHANNEL_REFERENCE = OrderChannelReference::class;
    case MERGE_MEMBER_TRANSACTION = MergeMemberTransaction::class;
    case RETAIL_PLANNING_HIERARCHY = RetailPlanningHierarchy::class;
    case TRANSIT_STOCK = TransitStock::class;
    case CATEGORY_CHANNEL_REFERENCE = CategoryChannelReference::class;
    case USER = User::class;
    case SELL_THROUGH_AGGREGATE = SellThroughAggregate::class;
    case AGGREGATE_PROCESS_TRACKER = AggregateProcessTracker::class;
    case MASTER_PRODUCT = MasterProduct::class;
    case ASSEMBLY_CHILD_MASTER_PRODUCT = AssemblyChildMasterProduct::class;
    case PRODUCT_VARIANT_VALUE = ProductVariantValue::class;
    case EMAIL_TEMPLATE = EmailTemplate::class;
    case SERIAL_NUMBER = SerialNumber::class;
    case MEMBER_GROUP_MEMBER = MemberGroupMember::class;
    case ORDER_INTEGRATION = OrderIntegration::class;
    case COURIER_ACCESS_TOKEN = CourierAccessToken::class;
    case COURIER = Courier::class;
    case COURIER_WEBHOOK_URL = CourierWebhookUrl::class;
    case ONLINE_ORDER_TRANSACTION = OnlineOrderTransaction::class;
    case MEMBER_CHANNEL_REFERENCE = MemberChannelReference::class;
    case SIZE_CHANNEL_REFERENCE = SizeChannelReference::class;
    case COLOR_CHANNEL_REFERENCE = ColorChannelReference::class;
    case BRAND_CHANNEL_REFERENCE = BrandChannelReference::class;
    case BANNER_CHANNEL_REFERENCE = BannerChannelReference::class;
    case MEMBER_ADDRESS_CHANNEL_REFERENCE = MemberAddressChannelReference::class;
    case MEMBER_GROUP_CHANNEL_REFERENCE = MemberGroupChannelReference::class;
    case VOUCHER_CONFIGURATION_CHANNEL_REFERENCE = VoucherConfigurationChannelReference::class;
    case LOYALTY_CAMPAIGN_CHANNEL_REFERENCE = LoyaltyCampaignChannelReference::class;
    case ORDER_DISCOUNT = OrderDiscount::class;
    case ORDER_LOYALTY_POINT = OrderLoyaltyPoint::class;
    case VOUCHER_CHANNEL_REFERENCE = VoucherChannelReference::class;
    case ATTRIBUTE_CHANNEL_REFERENCE = AttributeChannelReference::class;
    case PURCHASE_PLAN = PurchasePlan::class;
    case PURCHASE_PLAN_ITEM = PurchasePlanItem::class;
    case EXTERNAL_PURCHASE_ORDER_ITEM = ExternalPurchaseOrderItem::class;
    case PURCHASE_PLAN_TRANSACTION = PurchasePlanTransaction::class;
    case EXTERNAL_PURCHASE_ORDER_TRANSACTION = ExternalPurchaseOrderTransaction::class;
    case PRODUCT_COLLECTION_CHANNEL_REFERENCE = ProductCollectionChannelReference::class;
    case EXTERNAL_PURCHASE_ORDER_ITEM_SERIAL_NUMBER = ExternalPurchaseOrderItemSerialNumber::class;
    case EXTERNAL_PURCHASE_ORDER_PARTIAL_RECEIVE = ExternalPurchaseOrderPartialReceive::class;
    case EXTERNAL_PURCHASE_ORDER_PARTIAL_RECEIVE_ITEM = ExternalPurchaseOrderPartialReceiveItem::class;
    case EXTERNAL_PURCHASE_ORDER_PARTIAL_RECEIVE_ITEM_SERIAL_NUMBER = ExternalPurchaseOrderPartialReceiveItemSerialNumber::class;
    case MASTER_PRODUCT_CHANNEL_REFERENCE = MasterProductChannelReference::class;
    case ONLINE_SALES_CHARGE_CHANNEL_REFERENCE = OnlineSalesChargeChannelReference::class;
    case PROMOTION_CHANNEL_REFERENCE = PromotionChannelReference::class;
    case GENUINE_PRODUCT_VERIFICATION = GenuineProductVerification::class;
    case DREAM_PRICE_CHANNEL_REFERENCE = DreamPriceChannelReference::class;
    case MYSTERY_GIFT = MysteryGift::class;
    case MYSTERY_GIFT_USAGES = MysteryGiftUsage::class;
    case MYSTERY_GIFT_PRODUCT = MysteryGiftProduct::class;
    case EXTERNAL_CATEGORY = ExternalCategory::class;
    case INTEGRATION = Integration::class;
    case PRODUCT_CHANNEL_REFERENCE_CATEGORY = ProductChannelReferenceCategory::class;
    case ORDER_ITEM_DISCOUNT = OrderItemDiscount::class;
    case SHIPPING_ZONE = ShippingZone::class;
    case ONLINE_SALES_CHARGE_TIER = OnlineSalesChargeTier::class;
    case DYNAMIC_MENU = DynamicMenu::class;
    case DYNAMIC_MENU_CHANNEL_REFERENCE = DynamicMenuChannelReference::class;
    case GENUINE_RECEIPT_VERIFICATION = GenuineReceiptVerification::class;
    case SHIPPING_ZONE_CHANNEL_REFERENCE = ShippingZoneChannelReference::class;
    case COUNTRY_CHANNEL_REFERENCE = CountryChannelReference::class;
    case STATE_CHANNEL_REFERENCE = StateChannelReference::class;
    case INTEGRATION_SYNC_UPDATE = IntegrationSyncUpdate::class;
    case DRIVER = Driver::class;
    case MEMBER_PRODUCT_REVIEW = MemberProductReview::class;
    case VEHICLE = Vehicle::class;

    public static function getFormattedArray(): array
    {
        $modelMapping = [];

        foreach (self::cases() as $modelMap) {
            $modelMapping[$modelMap->name] = $modelMap->value;
        }

        return $modelMapping;
    }

    public static function getCaseName(string $className): string
    {
        foreach (self::cases() as $modelMap) {
            if ($modelMap->value === $className) {
                return $modelMap->name;
            }

            if ('App\\Models\\' . str_replace(' ', '', $className) === $modelMap->value) {
                return $modelMap->name;
            }
        }

        return $className;
    }

    public static function getParentChildModules(): array
    {
        return [
            self::STOCK_TRANSFER->name => [
                self::STOCK_TRANSFER_TRANSACTION->name,
                self::STOCK_TRANSFER_ITEM->name,
                self::STOCK_TRANSFER_ITEM_UNIT->name,
                self::STOCK_TRANSFER_ITEM_BATCH->name,
                self::STOCK_TRANSFER_ITEM_TRANSACTION->name,
                self::TRANSIT_STOCK->name,
            ],
            self::GOODS_RECEIVED_NOTE->name => [self::GOODS_RECEIVED_NOTE_PRODUCT->name],
            self::INVENTORY->name => [self::INVENTORY_UNIT->name, self::INVENTORY_UPDATE->name],
            self::SALE->name => [
                self::SALE_CASHBACK->name,
                self::SALE_DISCOUNT->name,
                self::SALE_LOYALTY_POINT->name,
                self::SALE_PAYMENT->name,
                self::SALE_PRICE_OVERRIDE->name,
                self::CANCEL_LAYAWAY_SALE->name,
                self::CANCEL_CREDIT_SALE->name,
                self::SALE_ITEM->name,
                self::SALE_ITEM_ASSEMBLY_CHILD_PRODUCT->name,
                self::SALE_ITEM_COMPLIMENTARY->name,
                self::SALE_ITEM_DISCOUNT->name,
                self::SALE_ITEM_EXCHANGE->name,
                self::SALE_ITEM_PRICE_OVERRIDE->name,
                self::SALE_ITEM_UNIT->name,
            ],
            self::VOID_SALE->name => [self::SALE_VOID_CASHBACK->name],
            self::MANUAL_NOTIFICATION->name => [self::MANUAL_NOTIFICATION_MEMBER_TYPES->name],
            self::SALE_SEASON->name => [],
            self::EXTERNAL_PRODUCT->name => [],
            self::NOTIFICATION->name => [],
            self::RESERVED_STOCK->name => [],
            self::POS_MISMATCH->name => [],
            self::ROLE->name => [],
            self::PERMISSION->name => [],
            self::WAREHOUSE_MANAGER->name => [],
            self::POS_ADVERTISEMENT->name => [],
            self::STORE_WISE_DAILY_TOTAL->name => [],
            self::CATEGORY_WISE_DAILY_TOTAL->name => [],
            self::STORE_MANAGER->name => [
                self::STORE_MANAGER_AUTHORIZATION_CODE->name,
                self::STORE_MANAGER_AUTHORIZATION_CODE_USAGE->name,
            ],
            self::EMPLOYEE->name => [self::EMPLOYEE_GROUP->name, self::EMPLOYEE_TRANSACTION->name],
            self::MEMBER->name => [self::MEMBER_GROUP->name, self::MERGE_MEMBER_TRANSACTION->name],
            self::EXPORT_RECORD->name => [self::EXPORT_RECORD_TRANSACTION->name],
            self::REGION->name => [],
            self::ADMIN->name => [],
            self::PAYMENT_TYPE->name => [],
            self::SUPER_ADMIN->name => [],
            self::COMPANY->name => [],
            self::BRAND->name => [],
            self::SEASON->name => [],
            self::CATEGORY->name => [],
            self::PACKAGE_TYPE->name => [],
            self::STYLE->name => [],
            self::COLOR->name => [self::COLOR_GROUP->name],
            self::VENDOR->name => [],
            self::SIZE->name => [self::SIZE_GROUP->name],
            self::SMS_HISTORY->name => [],
            self::TAG->name => [],
            self::DEPARTMENT->name => [],
            self::CASHIER->name => [self::CASHIER_GROUP->name, self::CASHIER_GROUP_PERMISSION->name],
            self::UNIT_OF_MEASURE->name => [self::UNIT_OF_MEASURE_DERIVATIVE->name],
            self::STOCK_ADJUSTMENT->name => [self::STOCK_ADJUSTMENT_ITEM->name],
            self::PRODUCT->name => [
                self::PRODUCT_LOYALTY_POINT->name,
                self::BOX_PRODUCT->name,
                self::ASSEMBLY_CHILD_PRODUCT->name,
                self::BOX_PRODUCT_LOYALTY_POINT->name,
            ],
            self::BOOKING_PAYMENT->name => [
                self::BOOKING_PAYMENT_PAYMENT->name,
                self::BOOKING_PAYMENT_PRODUCT->name, self::BOOKING_PAYMENT_VOID_USE->name,
                self::BOOKING_PAYMENT_REFUND->name,
                self::BOOKING_PAYMENT_USE->name,
                self::HOLD_BOOKING_PAYMENT_ITEM->name,
            ],
            self::CASHBACK->name => [self::CASHBACK_PRICE->name],
            self::CASH_MOVEMENT_REASON->name => [],
            self::CASH_MOVEMENT->name => [],
            self::CREDIT_NOTE->name => [
                self::CREDIT_NOTE_EXPIRATION->name,
                self::CREDIT_NOTE_REFUND->name,
                self::CREDIT_NOTE_USE->name,
                self::CREDIT_NOTE_VOID_USE->name,
            ],
            self::DESIGNATION->name => [],
            self::DENOMINATION->name => [],
            self::DIRECTOR->name => [],
            self::DREAM_PRICE->name => [self::DREAM_PRICE_PRODUCT->name],
            self::EMAIL_RECIPIENT->name => [],
            self::COUNTER_UPDATE->name => [
                self::CLOSE_COUNTER_DENOMINATION->name,
                self::CLOSE_COUNTER_PAYMENT->name,
                self::COUNTER_UPDATE_EVENT->name,
                self::COUNTER_UPDATE_DECLARATION_ATTEMPT_PAYMENT->name,
                self::COUNTER_UPDATE_DECLARATION_ATTEMPT->name,
            ],
            self::STORE_DAY_CLOSE->name => [self::STORE_DAY_CLOSE_PAYMENT->name],
            self::COUNTER->name => [],
            self::PROMOTER->name => [self::PROMOTER_GROUP->name],
            self::PROMOTION->name => [
                self::PROMOTION_MONTH_DATE->name,
                self::PROMOTION_PROMO_CODE->name,
                self::PROMOTION_TIER->name,
                self::PROMOTION_WEEK_DAY->name,
            ],
            self::PURCHASE_AMOUNT->name => [],
            self::SALE_RETURN->name => [self::SALE_RETURN_ITEM->name],
            self::SALE_RETURN_REASON->name => [],
            self::BATCH->name => [],
            self::VOID_SALE_REASON->name => [],
            self::SITE_CONFIGURATION->name => [],
            self::VOUCHER->name => [self::VOUCHER_TRANSACTION->name],
            self::VOUCHER_CONFIGURATION->name => [self::VOUCHER_CONFIGURATION_TIER->name],
            self::IMPORT_RECORD->name => [self::IMPORT_RECORD_FAILED_ROW->name],
            self::COMPLIMENTARY_ITEM_REASON->name => [],
            self::LOYALTY_CAMPAIGN->name => [self::LOYALTY_CAMPAIGN_CONFIGURATION->name],
            self::LOYALTY_POINT->name => [self::LOYALTY_POINT_UPDATE->name],
            self::MEMBERSHIP->name => [self::MEMBERSHIP_ASSIGNMENT->name],
            self::PROMOTER_COMMISSION->name => [
                self::PROMOTER_COMMISSION_REGENERATION->name,
                self::PROMOTER_COMMISSION_UPDATE->name,
            ],
            self::STOCK_TAKES->name => [self::STOCK_TAKE_PRODUCT->name],
            self::HOLD_SALE->name => [
                self::HOLD_SALE_DETAIL->name,
                self::HOLD_SALE_ITEM->name,
                self::HOLD_SALE_RETURN_ITEM->name,
            ],
            self::ORDER->name => [
                self::ORDER_ITEM->name,
                self::ORDER_CREDIT_NOTE->name,
                self::ORDER_ITEM_ASSEMBLY_CHILD_PRODUCT->name,
                self::ORDER_ITEM_EXCHANGE->name,
                self::ORDER_ITEM_UNIT->name,
                self::ORDER_PAYMENT->name,
                self::ORDER_ADDRESS->name,
            ],
            self::ORDER_RETURN->name => [self::ORDER_RETURN_ITEM->name],
            self::EXTERNAL_COMPANY->name => [self::EXTERNAL_LOCATION->name],
            self::PURCHASE_ORDER->name => [
                self::PURCHASE_ORDER_ITEM->name,
                self::PURCHASE_ORDER_FULFILLMENT->name,
                self::PURCHASE_ORDER_FULFILLMENT_ITEM->name,
                self::PURCHASE_ORDER_FULFILLMENT_ITEM_UNIT->name,
                self::PURCHASE_ORDER_TRANSACTION->name,
                self::PURCHASE_ORDER_FULFILLMENT_ITEM_BATCH->name,
                self::PURCHASE_ORDER_FULFILLMENT_ITEM_TRANSACTION->name,
                self::PURCHASE_ORDER_INVOICE->name,
                self::PURCHASE_ORDER_FULFILLMENT_TRANSACTION->name,
                self::PURCHASE_ORDER_INVOICE_TRANSACTION->name,
                self::PARTIALLY_RECEIVE_FULFILLMENT->name,
                self::PARTIALLY_RECEIVE_FULFILLMENT_ITEM->name,
            ],
            self::MERGE_PRODUCT_TRANSACTION->name => [],
            self::GIFT_CARD->name => [self::GIFT_CARD_TRANSACTION->name],
            self::SEQUENCE->name => [],
            self::STOCK_TRANSFER_REASON->name => [],
            self::SALE_THROUGH_RATIO->name => [],
            self::AUTOMATED_NOTIFICATION->name => [
                self::AUTOMATED_NOTIFICATION_WEEK_DAY->name,
                self::AUTOMATED_NOTIFICATION_MONTH_DATE->name,
                self::AUTOMATED_NOTIFICATION_PRODUCT->name,
                self::AUTOMATED_NOTIFICATION_STORE->name,
                self::AUTOMATED_NOTIFICATION_PRODUCT->name,
            ],
            self::HAPPY_HOUR_DISCOUNT->name => [self::HAPPY_HOUR_DISCOUNT_TRANSACTION->name],
            self::SALE_TARGET->name => [self::SALE_TARGET_TIME_FRAME->name, self::SALE_ACHIEVED_TARGET->name],
            self::STOCK_TRANSFER_AVERAGE_LEAD_DAYS->name => [],
            self::DRAFT_PRODUCT_TRANSACTION->name => [],
            self::INVENTORY_ROLLBACK_ORDER_STATUS->name => [],
            self::PRODUCT_AGEING->name => [],
            self::TOP_TWENTY_AGGREGATE_DATA->name => [],
            self::BANNER->name => [],
            self::PRODUCT_COLLECTION->name => [
                self::PRODUCT_COLLECTION_FILTER->name,
                self::PRODUCT_COLLECTION_FILTER_TYPE->name,
                self::PRODUCT_COLLECTION_PRODUCT->name,
            ],
            self::SALE_RETURN_REASON_TYPE->name => [],
            self::TEMPLATE->name => [
                self::ATTRIBUTE->name,
                self::ATTACHED_TEMPLATE->name,
                self::ATTRIBUTE_CHANNEL_REFERENCE->name,
            ],
            self::VOID_SALE_REASON_TYPE->name => [],
            self::PRODUCT_CHANNEL_REFERENCE->name => [],
            self::MASTER_PRODUCT_CHANNEL_REFERENCE->name => [],
            self::CATEGORY_CHANNEL_REFERENCE->name => [],
            self::MEMBER_CHANNEL_REFERENCE->name => [],
            self::SELL_THROUGH_AGGREGATE->name => [],
            self::AGGREGATE_PROCESS_TRACKER->name => [],
            self::SIZE_CHANNEL_REFERENCE->name => [],
            self::COLOR_CHANNEL_REFERENCE->name => [],
            self::BRAND_CHANNEL_REFERENCE->name => [],
            self::MEMBER_ADDRESS_CHANNEL_REFERENCE->name => [],
            self::MEMBER_GROUP_CHANNEL_REFERENCE->name => [],
            self::BANNER_CHANNEL_REFERENCE->name => [],
            self::VOUCHER_CONFIGURATION_CHANNEL_REFERENCE->name => [],
            self::LOYALTY_CAMPAIGN_CHANNEL_REFERENCE->name => [],
            self::VOUCHER_CHANNEL_REFERENCE->name => [],
            self::ONLINE_SALES_CHARGE_CHANNEL_REFERENCE->name => [],
            self::PROMOTION_CHANNEL_REFERENCE->name => [],
            self::DREAM_PRICE_CHANNEL_REFERENCE->name => [],
            self::SHIPPING_ZONE_CHANNEL_REFERENCE->name => [],
            self::COUNTRY_CHANNEL_REFERENCE->name => [],
            self::STATE_CHANNEL_REFERENCE->name => [],
        ];
    }
}
