<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Attribute\Events\AttributeCreateEvent;
use App\Domains\Attribute\Events\AttributeDeleteEvent;
use App\Domains\Attribute\Events\AttributeUpdateEvent;
use App\Domains\Attribute\Listeners\AttributeCreateListener;
use App\Domains\Attribute\Listeners\AttributeDeleteListener;
use App\Domains\Attribute\Listeners\AttributeUpdateListener;
use App\Domains\Banner\Events\BannerCreateEvent;
use App\Domains\Banner\Events\BannerUpdateEvent;
use App\Domains\Banner\Listeners\BannerCreateListener;
use App\Domains\Banner\Listeners\BannerUpdateListener;
use App\Domains\Brand\Events\BrandCreateEvent;
use App\Domains\Brand\Events\BrandUpdateEvent;
use App\Domains\Brand\Listeners\BrandCreateListener;
use App\Domains\Brand\Listeners\BrandUpdateListener;
use App\Domains\Category\Events\CategoryCreateEvent;
use App\Domains\Category\Events\CategoryUpdateEvent;
use App\Domains\Category\Listeners\CategoryCreateListener;
use App\Domains\Category\Listeners\CategoryUpdateListener;
use App\Domains\City\Events\CityCreateEvent;
use App\Domains\City\Events\CityUpdateEvent;
use App\Domains\City\Listeners\CityCreateListener;
use App\Domains\City\Listeners\CityUpdateListener;
use App\Domains\Color\Events\ColorCreateEvent;
use App\Domains\Color\Events\ColorUpdateEvent;
use App\Domains\Color\Listeners\ColorCreateListener;
use App\Domains\Color\Listeners\ColorUpdateListener;
use App\Domains\Company\Events\CompanyCreateEvent;
use App\Domains\Company\Events\CompanyUpdateEvent;
use App\Domains\Company\Listeners\CompanyCreateListener;
use App\Domains\Company\Listeners\CompanyUpdateListener;
use App\Domains\Country\Events\CountryCreateEvent;
use App\Domains\Country\Events\CountryUpdateEvent;
use App\Domains\Country\Listeners\CountryCreateListener;
use App\Domains\Country\Listeners\CountryUpdateListener;
use App\Domains\DreamPrice\Events\DreamPriceCreateEvent;
use App\Domains\DreamPrice\Events\DreamPriceUpdateEvent;
use App\Domains\DreamPrice\Listeners\DreamPriceCreateListener;
use App\Domains\DreamPrice\Listeners\DreamPriceUpdateListener;
use App\Domains\DynamicMenus\Events\DynamicMenuCreateOrUpdateEvent;
use App\Domains\DynamicMenus\Listeners\DynamicMenuCreateOrUpdateListener;
use App\Domains\Inventory\Events\InventoryCreateEvent;
use App\Domains\Inventory\Events\InventoryUpdateEvent;
use App\Domains\Inventory\Listeners\InventoryCreateListener;
use App\Domains\Inventory\Listeners\InventoryUpdateListener;
use App\Domains\Location\Events\LocationCreateEvent;
use App\Domains\Location\Events\LocationUpdateEvent;
use App\Domains\Location\Listeners\LocationCreateListener;
use App\Domains\Location\Listeners\LocationUpdateListener;
use App\Domains\LoyaltyCampaign\Events\LoyaltyCampaignCreateEvent;
use App\Domains\LoyaltyCampaign\Events\LoyaltyCampaignUpdateEvent;
use App\Domains\LoyaltyCampaign\Listeners\LoyaltyCampaignCreateListener;
use App\Domains\LoyaltyCampaign\Listeners\LoyaltyCampaignUpdateListener;
use App\Domains\LoyaltyPoint\Events\LoyaltyPointCreateEvent;
use App\Domains\LoyaltyPoint\Events\LoyaltyPointUpdateEvent;
use App\Domains\LoyaltyPoint\Listeners\LoyaltyPointCreateListener;
use App\Domains\LoyaltyPoint\Listeners\LoyaltyPointUpdateListener;
use App\Domains\LoyaltyPointUpdate\Events\LoyaltyPointUpdatesCreateEvent;
use App\Domains\LoyaltyPointUpdate\Events\LoyaltyPointUpdatesUpdateEvent;
use App\Domains\LoyaltyPointUpdate\Listeners\LoyaltyPointUpdatesCreateListener;
use App\Domains\LoyaltyPointUpdate\Listeners\LoyaltyPointUpdatesUpdateListener;
use App\Domains\MasterProduct\Events\MasterProductCreateEvent;
use App\Domains\MasterProduct\Events\MasterProductUpdateEvent;
use App\Domains\MasterProduct\Listeners\MasterProductCreateListener;
use App\Domains\MasterProduct\Listeners\MasterProductRetailPlanningUpdateListener;
use App\Domains\MasterProduct\Listeners\MasterProductUpdateListener;
use App\Domains\Member\Events\MemberCreateEvent;
use App\Domains\Member\Events\MemberRegisteredEvent;
use App\Domains\Member\Events\MemberUpdateEvent;
use App\Domains\Member\Listeners\MemberCreateListener;
use App\Domains\Member\Listeners\MemberUpdateListener;
use App\Domains\Member\Listeners\WelcomeMemberVoucherListener;
use App\Domains\MemberAddress\Events\MemberAddressCreateEvent;
use App\Domains\MemberAddress\Events\MemberAddressDeletedEvent;
use App\Domains\MemberAddress\Events\MemberAddressUpdateEvent;
use App\Domains\MemberAddress\Listeners\MemberAddressCreateListener;
use App\Domains\MemberAddress\Listeners\MemberAddressDeleteListener;
use App\Domains\MemberAddress\Listeners\MemberAddressUpdateListener;
use App\Domains\MemberGroup\Events\MemberGroupUpdateEvent;
use App\Domains\MemberGroup\Listeners\MemberGroupUpdateListener;
use App\Domains\MemberGroupMember\Events\MemberGroupMemberCreateEvent;
use App\Domains\MemberGroupMember\Events\MemberGroupMemberDeleteEvent;
use App\Domains\MemberGroupMember\Events\MemberGroupMemberUpdateEvent;
use App\Domains\MemberGroupMember\Listeners\MemberGroupMemberCreateListener;
use App\Domains\MemberGroupMember\Listeners\MemberGroupMemberDeleteListener;
use App\Domains\MemberGroupMember\Listeners\MemberGroupMemberUpdateListener;
use App\Domains\Membership\Events\MembershipCreateEvent;
use App\Domains\Membership\Events\MembershipUpdateEvent;
use App\Domains\Membership\Listeners\MembershipCreateListener;
use App\Domains\Membership\Listeners\MembershipUpdateListener;
use App\Domains\Notification\Events\NotificationFirebaseEvent;
use App\Domains\Notification\Listeners\NotificationFirebaseListener;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeCreateEvent;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeDeleteEvent;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeUpdateEvent;
use App\Domains\OnlineSalesCharges\Listeners\OnlineSaleChargeCreateListener;
use App\Domains\OnlineSalesCharges\Listeners\OnlineSaleChargeDeleteListener;
use App\Domains\OnlineSalesCharges\Listeners\OnlineSaleChargeUpdateListener;
use App\Domains\PaymentType\Events\PaymentTypeCreateEvent;
use App\Domains\PaymentType\Events\PaymentTypeUpdateEvent;
use App\Domains\PaymentType\Listeners\PaymentTypeCreateListener;
use App\Domains\PaymentType\Listeners\PaymentTypeUpdateListener;
use App\Domains\Product\Events\EcommerceProductDeleteEvent;
use App\Domains\Product\Events\EcommerceProductUpdateEvent;
use App\Domains\Product\Events\ProductCreateEvent;
use App\Domains\Product\Events\ProductUpdateEvent;
use App\Domains\Product\Listeners\EcommerceProductDeleteListener;
use App\Domains\Product\Listeners\EcommerceProductUpdateListener;
use App\Domains\Product\Listeners\ProductCreateListener;
use App\Domains\Product\Listeners\ProductRetailPlanningUpdateListener;
use App\Domains\Product\Listeners\ProductUpdateListener;
use App\Domains\ProductCollection\Events\ProductCollectionDeleteEvent;
use App\Domains\ProductCollection\Events\ProductCollectionUpdateEvent;
use App\Domains\ProductCollection\Listeners\ProductCollectionDeleteListener;
use App\Domains\ProductCollection\Listeners\ProductCollectionUpdateListener;
use App\Domains\Promotion\Events\PromotionCreateEvent;
use App\Domains\Promotion\Events\PromotionUpdateEvent;
use App\Domains\Promotion\Listeners\PromotionCreateListener;
use App\Domains\Promotion\Listeners\PromotionUpdateListener;
use App\Domains\Region\Events\RegionCreateEvent;
use App\Domains\Region\Events\RegionUpdateEvent;
use App\Domains\Region\Listeners\RegionCreateListener;
use App\Domains\Region\Listeners\RegionUpdateListener;
use App\Domains\Sale\Events\SaleCreatedEvent;
use App\Domains\Sale\Listeners\PriceFallDownNotificationListener;
use App\Domains\Season\Events\SeasonCreateEvent;
use App\Domains\Season\Events\SeasonUpdateEvent;
use App\Domains\Season\Listeners\SeasonCreateListener;
use App\Domains\Season\Listeners\SeasonUpdateListener;
use App\Domains\ShippingZone\Events\ShippingZoneCreateEvent;
use App\Domains\ShippingZone\Events\ShippingZoneUpdateEvent;
use App\Domains\ShippingZone\Listeners\ShippingZoneCreateListener;
use App\Domains\ShippingZone\Listeners\ShippingZoneUpdateListener;
use App\Domains\Size\Events\SizeCreateEvent;
use App\Domains\Size\Events\SizeUpdateEvent;
use App\Domains\Size\Listeners\SizeCreateListener;
use App\Domains\Size\Listeners\SizeUpdateListener;
use App\Domains\State\Events\StateCreateEvent;
use App\Domains\State\Events\StateUpdateEvent;
use App\Domains\State\Listeners\StateCreateListener;
use App\Domains\State\Listeners\StateUpdateListener;
use App\Domains\StoreDayClose\Events\StoreDayCloseEvent;
use App\Domains\StoreDayClose\Listeners\StoreDayCloseCreateListener;
use App\Domains\Style\Events\StyleCreateEvent;
use App\Domains\Style\Events\StyleUpdateEvent;
use App\Domains\Style\Listeners\StyleCreateListener;
use App\Domains\Style\Listeners\StyleUpdateListener;
use App\Domains\Template\Events\TemplateCreateEvent;
use App\Domains\Template\Events\TemplateUpdateEvent;
use App\Domains\Template\Listeners\TemplateCreateListener;
use App\Domains\Template\Listeners\TemplateUpdateListener;
use App\Domains\Vendor\Events\VendorCreateEvent;
use App\Domains\Vendor\Events\VendorUpdateEvent;
use App\Domains\Vendor\Listeners\VendorCreateListener;
use App\Domains\Vendor\Listeners\VendorUpdateListener;
use App\Domains\Voucher\Events\VoucherCreateEvent;
use App\Domains\Voucher\Events\VoucherUpdateEvent;
use App\Domains\Voucher\Listeners\VoucherCreateListener;
use App\Domains\Voucher\Listeners\VoucherUpdateListener;
use App\Domains\VoucherConfiguration\Events\VoucherConfigurationCreateEvent;
use App\Domains\VoucherConfiguration\Events\VoucherConfigurationUpdateEvent;
use App\Domains\VoucherConfiguration\Listeners\VoucherConfigurationCreateListener;
use App\Domains\VoucherConfiguration\Listeners\VoucherConfigurationUpdateListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [SendEmailVerificationNotification::class],
        MemberRegisteredEvent::class => [WelcomeMemberVoucherListener::class],
        NotificationFirebaseEvent::class => [NotificationFirebaseListener::class],
        SaleCreatedEvent::class => [PriceFallDownNotificationListener::class],
        ProductCreateEvent::class => [ProductCreateListener::class],
        ProductUpdateEvent::class => [ProductUpdateListener::class, ProductRetailPlanningUpdateListener::class],
        CategoryCreateEvent::class => [CategoryCreateListener::class],
        CategoryUpdateEvent::class => [CategoryUpdateListener::class],
        InventoryCreateEvent::class => [InventoryCreateListener::class],
        InventoryUpdateEvent::class => [InventoryUpdateListener::class],
        BannerCreateEvent::class => [BannerCreateListener::class],
        BannerUpdateEvent::class => [BannerUpdateListener::class],
        OnlineSaleChargeCreateEvent::class => [OnlineSaleChargeCreateListener::class],
        OnlineSaleChargeUpdateEvent::class => [OnlineSaleChargeUpdateListener::class],
        OnlineSaleChargeDeleteEvent::class => [OnlineSaleChargeDeleteListener::class],
        DreamPriceCreateEvent::class => [DreamPriceCreateListener::class],
        DreamPriceUpdateEvent::class => [DreamPriceUpdateListener::class],
        ProductCollectionDeleteEvent::class => [ProductCollectionDeleteListener::class],
        BrandCreateEvent::class => [BrandCreateListener::class],
        BrandUpdateEvent::class => [BrandUpdateListener::class],
        ColorCreateEvent::class => [ColorCreateListener::class],
        ColorUpdateEvent::class => [ColorUpdateListener::class],
        SizeCreateEvent::class => [SizeCreateListener::class],
        SizeUpdateEvent::class => [SizeUpdateListener::class],
        MemberCreateEvent::class => [MemberCreateListener::class],
        MemberUpdateEvent::class => [MemberUpdateListener::class],
        MemberAddressCreateEvent::class => [MemberAddressCreateListener::class],
        MemberAddressUpdateEvent::class => [MemberAddressUpdateListener::class],
        MemberAddressDeletedEvent::class => [MemberAddressDeleteListener::class],
        MemberGroupUpdateEvent::class => [MemberGroupUpdateListener::class],
        MembershipCreateEvent::class => [MembershipCreateListener::class],
        MembershipUpdateEvent::class => [MembershipUpdateListener::class],
        MemberGroupMemberCreateEvent::class => [MemberGroupMemberCreateListener::class],
        MemberGroupMemberUpdateEvent::class => [MemberGroupMemberUpdateListener::class],
        MemberGroupMemberDeleteEvent::class => [MemberGroupMemberDeleteListener::class],
        VoucherConfigurationCreateEvent::class => [VoucherConfigurationCreateListener::class],
        VoucherConfigurationUpdateEvent::class => [VoucherConfigurationUpdateListener::class],
        LoyaltyCampaignCreateEvent::class => [LoyaltyCampaignCreateListener::class],
        LoyaltyCampaignUpdateEvent::class => [LoyaltyCampaignUpdateListener::class],
        VoucherCreateEvent::class => [VoucherCreateListener::class],
        VoucherUpdateEvent::class => [VoucherUpdateListener::class],
        PromotionCreateEvent::class => [PromotionCreateListener::class],
        PromotionUpdateEvent::class => [PromotionUpdateListener::class],
        AttributeCreateEvent::class => [AttributeCreateListener::class],
        AttributeUpdateEvent::class => [AttributeUpdateListener::class],
        AttributeDeleteEvent::class => [AttributeDeleteListener::class],
        PaymentTypeCreateEvent::class => [PaymentTypeCreateListener::class],
        PaymentTypeUpdateEvent::class => [PaymentTypeUpdateListener::class],
        ProductCollectionUpdateEvent::class => [ProductCollectionUpdateListener::class],
        MasterProductCreateEvent::class => [MasterProductCreateListener::class],
        MasterProductUpdateEvent::class => [
            MasterProductUpdateListener::class,
            MasterProductRetailPlanningUpdateListener::class,
        ],
        EcommerceProductUpdateEvent::class => [EcommerceProductUpdateListener::class],
        EcommerceProductDeleteEvent::class => [EcommerceProductDeleteListener::class],
        CompanyCreateEvent::class => [CompanyCreateListener::class],
        CompanyUpdateEvent::class => [CompanyUpdateListener::class],
        TemplateCreateEvent::class => [TemplateCreateListener::class],
        TemplateUpdateEvent::class => [TemplateUpdateListener::class],
        CountryCreateEvent::class => [CountryCreateListener::class],
        CountryUpdateEvent::class => [CountryUpdateListener::class],
        StateCreateEvent::class => [StateCreateListener::class],
        StateUpdateEvent::class => [StateUpdateListener::class],
        CityCreateEvent::class => [CityCreateListener::class],
        CityUpdateEvent::class => [CityUpdateListener::class],
        RegionCreateEvent::class => [RegionCreateListener::class],
        RegionUpdateEvent::class => [RegionUpdateListener::class],
        StyleCreateEvent::class => [StyleCreateListener::class],
        StyleUpdateEvent::class => [StyleUpdateListener::class],
        VendorCreateEvent::class => [VendorCreateListener::class],
        VendorUpdateEvent::class => [VendorUpdateListener::class],
        SeasonCreateEvent::class => [SeasonCreateListener::class],
        SeasonUpdateEvent::class => [SeasonUpdateListener::class],
        LocationCreateEvent::class => [LocationCreateListener::class],
        LocationUpdateEvent::class => [LocationUpdateListener::class],
        LoyaltyPointUpdatesCreateEvent::class => [LoyaltyPointUpdatesCreateListener::class],
        LoyaltyPointUpdatesUpdateEvent::class => [LoyaltyPointUpdatesUpdateListener::class],
        LoyaltyPointCreateEvent::class => [LoyaltyPointCreateListener::class],
        LoyaltyPointUpdateEvent::class => [LoyaltyPointUpdateListener::class],
        ShippingZoneCreateEvent::class => [ShippingZoneCreateListener::class],
        ShippingZoneUpdateEvent::class => [ShippingZoneUpdateListener::class],
        DynamicMenuCreateOrUpdateEvent::class => [DynamicMenuCreateOrUpdateListener::class],
        StoreDayCloseEvent::class => [StoreDayCloseCreateListener::class],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
