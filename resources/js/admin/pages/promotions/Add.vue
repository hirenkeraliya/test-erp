<template>
    <PageTitle title="Add Promotion" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promotions
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-12 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <InfoAlert
                color="primary"
            >
                <span class="flex">
                    Promotions will be applied after the dream price and price override have been applied.
                </span>
            </InfoAlert>
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Add Promotion</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="addPromotion();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 intro-y">
                            <PromotionSteps
                                :steps="state.steps"
                                :current-step="state.currentStep"
                                @update:step-key="goToStep"
                            />

                            <div class="col-span-12 sm:col-span-12 md:col-span-8 lg:col-span-9 xl:col-span-9 2xl:col-span-10">
                                <div
                                    v-if="state.currentStep === state.stepPromotionType"
                                    class="intro-y bg-slate-50"
                                >
                                    <div class="font-medium text-lg py-2 px-5 sm:p-5 border-b">
                                        Promotion Type
                                    </div>

                                    <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                        <OutlinePrimaryButton
                                            v-for="(promotionApplicableType, index) in promotionApplicableTypes"
                                            :key="'promotion-applicable-type-'+index"
                                            :text="promotionApplicableType.name"
                                            class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                            :class="promotionForm.promotion_applicable_type_id === promotionApplicableType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                                            @click="selectPromotionApplicableType(promotionApplicableType)"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="state.currentStep === state.stepPromotionSelection"
                                    class="intro-y bg-slate-50"
                                >
                                    <div class="font-medium text-lg sm:text-xl py-2 px-5 sm:p-5 border-b">
                                        Promotion Selection
                                    </div>

                                    <div
                                        v-if="promotionForm.promotion_applicable_type_id === null"
                                        class="intro-y text-base sm:text-lg p-5"
                                    >
                                        Please provide details in the previous step(s) first.
                                    </div>

                                    <div
                                        v-if="promotionForm.promotion_applicable_type_id ===
                                            staticDetails.limited_cart_wide"
                                    >
                                        <div>
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="As per Amount"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.cart_type_as_per_amount ===
                                                        promotionForm.cart_wide_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="setCartWideAsPerAmountTypeId(staticDetails.cart_type_as_per_amount)"
                                                />
                                                <OutlinePrimaryButton
                                                    text="As per Payment Type"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.cart_type_as_per_payment_type ===
                                                        promotionForm.cart_wide_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="setCartWideAsPerAmountTypeId(staticDetails.cart_type_as_per_payment_type)"
                                                />
                                            </div>
                                            <div
                                                v-if="promotionForm.cart_wide_promotion_type_id"
                                                class="intro-y w-full block sm:w-auto lg:flex p-5"
                                            >
                                                <OutlinePrimaryButton
                                                    text="Percentage"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.type_percentage ===
                                                        promotionForm.discount_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showCartWideAsPerAmountPercentageDetails()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Flat"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.type_flat ===
                                                        promotionForm.discount_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showCartWideAsPerAmountFlatDetails()"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="promotionForm.promotion_applicable_type_id ===
                                            staticDetails.limited_item_wise"
                                    >
                                        <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                            <OutlinePrimaryButton
                                                text="As per SKU"
                                                class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                :class="state.displayAsPerSku ? 'btn btn-primary text-white hover:text-primary' : ''"
                                                @click="showAsPerSku()"
                                            />
                                            <OutlinePrimaryButton
                                                text="As per quantity"
                                                class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                :class="state.displayAsPerQuantity ? 'btn btn-primary text-white hover:text-primary' : ''"
                                                @click="showAsPerQuantity()"
                                            />

                                            <OutlinePrimaryButton
                                                text="As per Amount"
                                                class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                :class="state.displayAsPerAmount ? 'btn btn-primary text-white hover:text-primary' : ''"
                                                @click="showAsPerAmount()"
                                            />
                                        </div>

                                        <div v-if="state.displayAsPerSku">
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="Limited to Products"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.limited_to_products ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForLimitedByProduct()"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Limited to Categories"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.limited_to_categories ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForLimitedByCategory()"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Limited to Brands"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.limited_to_brands ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForLimitedByBrand()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Limited to Tags"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.limited_to_tags ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForLimitedByTag()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Limited to Product Collection"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.limited_to_product_collection ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForLimitedByProductCollection()"
                                                />
                                            </div>
                                        </div>

                                        <div v-if="state.displayAsPerAmount">
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="Limited to Brands"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.as_per_amount_limited_to_brands ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForAsPerAmountLimitedByBrand()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Get Off On Others"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.as_per_amount_get_off_on_others ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForAsPerAmountGetOffOnOthers()"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Limited to Price"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.as_per_amount_limited_to_price ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showDiscountTypeSelectionForAsPerAmountLimitedToPrice()"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            v-if="staticDetails.limited_to_products ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.limited_to_categories ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.limited_to_brands ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.as_per_amount_limited_to_brands ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.as_per_amount_get_off_on_others ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.as_per_amount_limited_to_price ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.limited_to_tags ===
                                                promotionForm.item_wise_promotion_type_id ||
                                                staticDetails.limited_to_product_collection ===
                                                promotionForm.item_wise_promotion_type_id
                                            "
                                        >
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="Percentage"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="promotionForm.discount_type_id ===
                                                        staticDetails.type_percentage ?
                                                            'btn btn-primary text-white hover:text-primary'
                                                        : ''"
                                                    @click="updateDiscountType(staticDetails.type_percentage)"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Flat"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="promotionForm.discount_type_id ===
                                                        staticDetails.type_flat ?
                                                            'btn btn-primary text-white hover:text-primary'
                                                        : ''"
                                                    @click="updateDiscountType(staticDetails.type_flat)"
                                                />
                                            </div>
                                        </div>

                                        <div v-if="state.displayAsPerQuantity">
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="Percentage Off"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="state.displayPercentageOff ? 'btn btn-primary text-white hover:text-primary' : ''"
                                                    @click="showPercentageOff()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Flat Off"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="state.displayFlatOff ? 'btn btn-primary text-white hover:text-primary' : ''"
                                                    @click="showFlatOff()"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Free Items"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="state.displayFreeItems ? 'btn btn-primary text-white hover:text-primary' : ''"
                                                    @click="showFreeItems()"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Bundle Buy"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="state.displayBundleBuy
                                                        ? 'btn btn-primary text-white hover:text-primary'
                                                        : ''"
                                                    @click="showItemWiseBundleBuyDetails()"
                                                />
                                                <OutlinePrimaryButton
                                                    :text="'Buy 2 and get 1 quantity at '+currencySymbol+'1'"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="state.displayBuyTwoAndGetTwoQuantityAtRMOne
                                                        ? 'btn btn-primary text-white hover:text-primary'
                                                        : ''"
                                                    @click="showItemWiseBuyTwoAndGetTwoQuantityAtRMOneDetails()"
                                                />
                                            </div>
                                        </div>

                                        <div v-if="state.displayPercentageOff">
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="Buy 2 get 50% off on others(or More)"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.quantity_buy_two_get_50_off ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showBuyTwoGetFiftyOffDetails()"
                                                />
                                                <OutlinePrimaryButton
                                                    text="Buy any 3 or more and get 30% off"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.quantity_buy_any_three_get_percentage_off ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showBuyAnyThreeOrMoreGetThirtyOffDetails()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Percentage discount for next item"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.percentage_discount_for_next_item ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showPercentageDiscountForNextItemDetails()"
                                                />
                                            </div>
                                        </div>

                                        <div v-if="state.displayFlatOff">
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    :text="'Buy 2 get '+currencySymbol+'50 off on others(or More)'"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.quantity_buy_two_get_RM_50_off ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showBuyTwoGetRMFiftyOffDetails()"
                                                />
                                                <OutlinePrimaryButton
                                                    :text="'Buy any 3 or more and get '+currencySymbol+'30 off'"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.quantity_buy_any_three_get_flat_off ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showBuyAnyThreeOrMoreGetRMThirtyOffDetails()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Flat discount for next item"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.flat_discount_for_next_item ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showFlatDiscountForNextItemDetails()"
                                                />
                                            </div>
                                        </div>

                                        <div v-if="state.displayFreeItems">
                                            <div class="intro-y w-full block sm:w-auto lg:flex p-5">
                                                <OutlinePrimaryButton
                                                    text="Buy 3 Get 1(or More)"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.quantity_buy_three_get_one ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showBuyTwoGetOneOrMoreDetails()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Gift with Purchase"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.gift_with_purchase ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showItemWiseGiftWithPurchaseDetails()"
                                                />

                                                <OutlinePrimaryButton
                                                    text="Cheapest Free"
                                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                                                    :class="staticDetails.quantity_cheapest_free ===
                                                        promotionForm.item_wise_promotion_type_id ?
                                                            'btn btn-primary text-white hover:text-primary' :
                                                            ''"
                                                    @click="showItemWiseCheapestFreeDetails()"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="state.currentStep === state.stepPromotionDetails"
                                    class="intro-y bg-slate-50"
                                >
                                    <PromotionDetails
                                        :promotion-form="promotionForm"
                                        :categories="categories"
                                        :brands="brands"
                                        :tags="tags"
                                        :product-collections="productCollections"
                                        :static-details="staticDetails"
                                        :static-product-upload-types="staticProductUploadTypes"
                                        :payment-types="paymentTypes"
                                        :selected-payment-types="promotionForm.payment_types"
                                        @update:column-details="updateColumnDetails"
                                        @update:tier-value-details="updateTierValueDetails"
                                        @add:new-tier-details="addNewTierDetails"
                                        @remove:tier-details-of="removeTierDetailsOf"
                                        @update:selected-payment-types="updateSelectedPaymentTypes"
                                    />
                                </div>

                                <div
                                    v-if="state.currentStep === state.stepBasicDetails"
                                    class="intro-y bg-slate-50"
                                >
                                    <div class="font-medium text-lg sm:text-xl py-2 px-5 sm:p-5 border-b">
                                        Basic Details
                                    </div>

                                    <BasicDetails
                                        :locations="locations"
                                        :member-groups="memberGroups"
                                        :employee-groups="employeeGroups"
                                        :promotion-form="promotionForm"
                                        :selected-locations="promotionForm.locations"
                                        :selected-member-groups="promotionForm.member_groups"
                                        :selected-employee-groups="promotionForm.employee_groups"
                                        :selected-sale-channels="promotionForm.sale_channels"
                                        :display-clear-button="state.displayClearButton"
                                        :promotion-usage-types="promotionUsageTypes"
                                        :promotion-single-usage="promotionSingleUsage"
                                        :static-details="staticDetails"
                                        :sale-channels="saleChannels"
                                        :memberships="memberships"
                                        :selected-memberships="promotionForm.memberships"
                                        @add-new-promo-code="addNewPromoCode"
                                        @clear-promo-codes="clearPromoCodes"
                                        @set-promo-codes="setPromoCodes"
                                        @update-promo-code-details="updatePromoCodeDetails"
                                        @update-upload-column-details="updateUploadColumnDetailsForPromoCode"
                                        @update:column-details="updateColumnDetails"
                                        @update:selected-locations="updateSelectedLocations"
                                        @update:selected-member-groups="updateSelectedMemberGroups"
                                        @update:selected-employee-groups="updateSelectedEmployeeGroups"
                                        @update:selected-sale-channels="updateSelectedSaleChannels"
                                        @update:selected-memberships="updateSelectedMemberships"
                                    />
                                </div>

                                <div
                                    v-if="state.currentStep === state.stepTimeframe"
                                    class="intro-y bg-slate-50"
                                >
                                    <div class="font-medium text-lg sm:text-xl py-2 px-5 sm:p-5 border-b">
                                        Timeframe
                                    </div>

                                    <TimeframeDetails
                                        :time-frames="timeFrames"
                                        :promotion-form="promotionForm"
                                        :static-details="staticDetails"
                                        @remove:week-day="removeWeekDay"
                                        @add:new-week-day="addNewWeekDay"
                                        @remove:month-date="removeMonthDate"
                                        @add:new-month-date="addNewMonthDate"
                                        @clear:columns="clearColumns"
                                        @update:column-details="updateColumnDetails"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.promotions.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mb-1"
                                />
                            </Link>

                            <SecondaryButton
                                v-if="state.currentStep !== state.stepPromotionType"
                                type="button"
                                text="Previous"
                                class="w-24 mb-1 ml-1"
                                @click="goToPrevious()"
                            />

                            <PrimaryButton
                                v-if="state.currentStep === state.stepTimeframe"
                                type="submit"
                                text="Save"
                                class="w-24 mb-1 ml-1"
                            />

                            <PrimaryButton
                                v-else
                                type="button"
                                text="Next"
                                class="w-24 mb-1 ml-1"
                                @click="goToNext()"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { route } from 'ziggy';
import { computed, onMounted, reactive } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import BasicDetails from '@adminPages/promotions/partials/BasicDetails.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import TimeframeDetails from '@adminPages/promotions/partials/TimeframeDetails.vue';
import PromotionDetails from '@adminPages/promotions/partials/PromotionDetails.vue';
import PromotionSteps from '@adminPages/promotions/partials/PromotionSteps.vue';
import { showErrorNotification } from '@commonServices/notifier';
import InfoAlert from '@commonComponents/InfoAlert.vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    locations: {
        type: Array,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    brands: {
        type: Array,
        required: true
    },
    memberGroups: {
        type: Array,
        required: true
    },
    employeeGroups: {
        type: Array,
        required: true
    },
    timeFrames: {
        type: Array,
        required: true
    },
    promotionApplicableTypes: {
        type: Array,
        required: true
    },
    staticDetails: {
        type: Object,
        required: true
    },
    clonePromotion: {
        type: Object,
        default: () => {},
    },
    staticProductUploadTypes: {
        type: Object,
        required: true
    },
    tags: {
        type: Array,
        required: true
    },
    promotionUsageTypes: {
        type: Array,
        required: true
    },
    promotionSingleUsage: {
        type: Number,
        required: true
    },
    productCollections: {
        type: Array,
        required: true
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    paymentTypes: {
        type: Array,
        required: true
    },
    memberships: {
        type: Array,
        required: true
    },
});

const state = reactive({
    steps: [
        {
            key: 'promotion-type',
            label: 'Promotion Type',
        }, {
            key: 'promotion-selection',
            label: 'Promotion Selection',
        }, {
            key: 'promotion-details',
            label: 'Promotion Details',
        }, {
            key: 'basic-details',
            label: 'Basic Details',
        }, {
            key: 'timeframe',
            label: 'Timeframe',
        },
    ],

    currentStep: 'promotion-type',
    stepPromotionType: 'promotion-type',
    stepPromotionSelection: 'promotion-selection',
    stepPromotionDetails: 'promotion-details',
    stepBasicDetails: 'basic-details',
    stepTimeframe: 'timeframe',

    displayAsPerSku: false,
    displayAsPerQuantity: false,
    displayAsPerAmount: false,
    displayFreeItems: false,
    displayPercentageOff: false,
    displayFlatOff: false,
    displayBundleBuy: false,
    displayBuyTwoAndGetTwoQuantityAtRMOne: false,
    displayClearButton: false,
});

const promotionForm = useForm({
    name: null,
    locations: [],
    location_ids: [],
    allow_registered_member: true,
    allow_employee: true,
    allow_walk_in_member: true,
    dream_price_applicable: true,
    regular_products: [],
    regular_product_ids: [],
    buy_products: [],
    buy_product_ids: [],
    get_products: [],
    get_product_ids: [],
    category_ids: [],
    categories: [],
    brand_ids: [],
    brands: [],
    tag_ids: [],
    productCollections: [],
    product_collection_ids: [],
    tags: [],
    member_groups: [],
    member_group_ids: [],
    employee_groups: [],
    employee_group_ids: [],
    start_date: null,
    end_date: null,
    week_days: [],
    month_dates: [],
    start_time: null,
    end_time: null,
    promotion_applicable_type_id: null,
    discount_type_id: null,
    cart_wide_promotion_type_id: null,
    item_wise_promotion_type_id: null,
    timeframe_type_id: props.staticDetails.timeframe_manually_anytime,
    percentage: null,
    flat_amount: null,
    is_automatic: true,
    usage_type: null,
    is_available_in_pos: true,
    is_available_in_ecommerce: false,
    promo_codes: [''],
    tiers: [
        {
            buy_value: null,
            get_value: null,
            max_value: null,
        }
    ],
    sale_channels: [],
    sale_channel_ids: [],
    payment_types: [],
    payment_type_ids: [],
    is_membership_required: false,
    memberships: [],
    membership_ids: []
});

const selectPromotionApplicableType = (promotionApplicableType) => {
    if (promotionApplicableType.id === props.staticDetails.limited_cart_wide) {
        promotionForm.item_wise_promotion_type_id = null;
        promotionForm.regular_product_ids = [];
        promotionForm.buy_product_ids = [];
        promotionForm.get_product_ids = [];
        promotionForm.flat_amount = null;
        promotionForm.percentage = null;
        promotionForm.categories = [];
    }

    if (promotionApplicableType.id === props.staticDetails.limited_item_wise) {
        promotionForm.cart_wide_promotion_type_id = null;
        promotionForm.regular_product_ids = [];
        promotionForm.buy_product_ids = [];
        promotionForm.get_product_ids = [];
        promotionForm.tiers = [{ buy_value: null, get_value: null }];
    }

    updateColumnDetails({
        column_name: 'promotion_applicable_type_id',
        value: promotionApplicableType.id
    });

    goToNext();
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    promotionForm[columnName] = details.value;
};

const addNewWeekDay = (weekDay) => {
    promotionForm.week_days.push(weekDay);
};

const addNewPromoCode = () => {
    promotionForm.promo_codes.push('');
};

const clearPromoCodes = () => {
    promotionForm.promo_codes = [''];
    promotionForm.usage_type = null;
};

const setPromoCodes = (promoCodes) => {
    promotionForm.promo_codes = promoCodes;
};

const updatePromoCodeDetails = (promoCodeDetails) => {
    promotionForm.promo_codes[promoCodeDetails.key] = promoCodeDetails.value;
};

const updateUploadColumnDetailsForPromoCode = (promoCodeDetails) => {
    for (const key in promoCodeDetails) {
        promotionForm.promo_codes[key] = promoCodeDetails[key];
    }
};

const removeWeekDay = (weekDayKey) => {
    promotionForm.week_days.splice(weekDayKey, 1);
};

const addNewMonthDate = (monthDate) => {
    promotionForm.month_dates.push(monthDate);
};

const removeMonthDate = (monthDateKey) => {
    promotionForm.month_dates.splice(monthDateKey, 1);
};

const clearColumns = (columnDetails) => {
    for (const key in columnDetails) {
        promotionForm[key] = columnDetails[key];
    }
};

const goToStep = (stepKey) => {
    state.currentStep = stepKey;
};

const goToNext = () => {
    for (const key in state.steps) {
        if (state.steps[key].key === state.currentStep) {
            state.currentStep = state.steps[parseInt(key) + 1].key;
            return;
        }
    }
};

const goToPrevious = () => {
    for (const key in state.steps) {
        if (state.steps[key].key === state.currentStep) {
            state.currentStep = state.steps[parseInt(key) - 1].key;
            return;
        }
    }
};

const updateSelectedLocations = (details) => {
    promotionForm.locations = details.locations;
    state.displayClearButton = true;
    if (promotionForm.locations.length === 0) {
        state.displayClearButton = false;
    }
};

const updateSelectedPaymentTypes = (details) => {
    promotionForm.payment_types = details;
};

const updateSelectedMemberGroups = (details) => {
    promotionForm.member_groups = details.memberGroups;
};

const updateSelectedEmployeeGroups = (details) => {
    promotionForm.employee_groups = details.employeeGroups;
};

const updateSelectedSaleChannels = (details) => {
    if (details === null) {
        promotionForm.sale_channels = details;
        promotionForm.sale_channel_ids = null;
        return;
    }

    promotionForm.sale_channels = details.saleChannels;
};

const preparePromotionFormDetails = () => {
    promotionForm.location_ids = promotionForm.locations.map((location) => {
        return location.id;
    });

    if (promotionForm.member_groups) {
        promotionForm.member_group_ids = promotionForm.member_groups.map((memberGroup) => {
            return memberGroup.id;
        });
    }

    if (promotionForm.employee_groups) {
        promotionForm.employee_group_ids = promotionForm.employee_groups.map((employeeGroup) => {
            return employeeGroup.id;
        });
    }

    promotionForm.category_ids = promotionForm.categories.map((category) => {
        return category.id;
    });

    promotionForm.brand_ids = promotionForm.brands.map((brand) => {
        return brand.id;
    });

    promotionForm.regular_product_ids = promotionForm.regular_products.map((product) => {
        return product.id;
    });

    promotionForm.buy_product_ids = promotionForm.buy_products.map((product) => {
        return product.id;
    });

    promotionForm.get_product_ids = promotionForm.get_products.map((product) => {
        return product.id;
    });

    promotionForm.tag_ids = promotionForm.tags.map((tag) => {
        return tag.id;
    });

    promotionForm.product_collection_ids = promotionForm.productCollections.map((productCollection) => {
        return productCollection.id;
    });

    if (promotionForm.sale_channels) {
        promotionForm.sale_channel_ids = promotionForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }

    if (promotionForm.payment_types) {
        promotionForm.payment_type_ids = promotionForm.payment_types.map((types) => {
            return types.id;
        });
    }

    if (promotionForm.memberships) {
        promotionForm.membership_ids = promotionForm.memberships.map((membership) => {
            return membership.id;
        });
    }
};

const addPromotion = () => {
    preparePromotionFormDetails();

    promotionForm.post(route('admin.promotions.store'), {
        preserveScroll: true,
        onError: () => showErrorNotification('There are input errors. Please fill out the required form fields on all tabs and try again.'),
    });
};

const showCartWideAsPerAmountPercentageDetails = () => {
    updateColumnDetails({
        column_name: 'discount_type_id',
        value: props.staticDetails.type_percentage
    });

    goToNext();
};

const setCartWideAsPerAmountTypeId = (value) => {
    updateColumnDetails({
        column_name: 'cart_wide_promotion_type_id',
        value: value
    });
};

const showCartWideAsPerAmountFlatDetails = () => {
    updateColumnDetails({
        column_name: 'discount_type_id',
        value: props.staticDetails.type_flat
    });

    goToNext();
};

const showAsPerSku = () => {
    state.displayAsPerSku = true;
    state.displayFreeItems = false;
    state.displayBundleBuy = false;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = false;
    state.displayAsPerQuantity = false;
    state.displayAsPerAmount = false;
    state.displayPercentageOff = false;
    state.displayFlatOff = false;
    promotionForm.item_wise_promotion_type_id = null;
};

const showAsPerQuantity = () => {
    state.displayAsPerSku = false;
    state.displayBundleBuy = false;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = false;
    state.displayAsPerQuantity = true;
    state.displayAsPerAmount = false;
    promotionForm.item_wise_promotion_type_id = null;
};
const showAsPerAmount = () => {
    state.displayAsPerSku = false;
    state.displayBundleBuy = false;
    state.displayFreeItems = false;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = false;
    state.displayAsPerQuantity = false;
    state.displayAsPerAmount = true;
    state.displayPercentageOff = false;
    state.displayFlatOff = false;
    promotionForm.item_wise_promotion_type_id = null;
};

const showFreeItems = () => {
    state.displayBundleBuy = false;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = false;
    state.displayFreeItems = true;
    state.displayPercentageOff = false;
    state.displayFlatOff = false;
};

const showPercentageOff = () => {
    state.displayBundleBuy = false;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = false;
    state.displayFreeItems = false;
    state.displayFlatOff = false;
    state.displayPercentageOff = true;
};

const showFlatOff = () => {
    state.displayBundleBuy = false;
    state.displayFreeItems = false;
    state.displayFlatOff = true;
    state.displayPercentageOff = false;
};

const showItemWiseGiftWithPurchaseDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.gift_with_purchase
    });

    goToNext();
};

const showDiscountTypeSelectionForLimitedByProduct = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_products
    });
};

const showDiscountTypeSelectionForLimitedByCategory = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_categories
    });
};

const showDiscountTypeSelectionForLimitedByBrand = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_brands
    });
};

const showDiscountTypeSelectionForLimitedByTag = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_tags
    });
};

const showDiscountTypeSelectionForLimitedByProductCollection = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_product_collection
    });
};

const showDiscountTypeSelectionForAsPerAmountLimitedByBrand = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.as_per_amount_limited_to_brands
    });
};

const showDiscountTypeSelectionForAsPerAmountLimitedToPrice = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.as_per_amount_limited_to_price
    });
};

const showDiscountTypeSelectionForAsPerAmountGetOffOnOthers = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.as_per_amount_get_off_on_others
    });
};

const updateDiscountType = (value) => {
    if (props.staticDetails.limited_to_products === promotionForm.item_wise_promotion_type_id) {
        showItemWiseLimitedByProductDetails(value);
        return;
    }

    if (props.staticDetails.limited_to_categories === promotionForm.item_wise_promotion_type_id) {
        showItemWiseLimitedByCategoryDetails(value);
        return;
    }

    if (props.staticDetails.limited_to_brands === promotionForm.item_wise_promotion_type_id) {
        showItemWiseLimitedByBrandDetails(value);
        return;
    }

    if (props.staticDetails.as_per_amount_get_off_on_others === promotionForm.item_wise_promotion_type_id) {
        showItemWiseAsPerAmountGetOffOnOthersDetails(value);
        return;
    }
    if (props.staticDetails.as_per_amount_limited_to_price === promotionForm.item_wise_promotion_type_id) {
        showItemWiseAsPerAmountLimitedToPriceDetails(value);
        return;
    }
    if (props.staticDetails.limited_to_tags === promotionForm.item_wise_promotion_type_id) {
        showItemWiseLimitedByTagDetails(value);
        return;
    }
    if (props.staticDetails.limited_to_product_collection === promotionForm.item_wise_promotion_type_id) {
        showItemWiseLimitedByProductCollectionDetails(value);
        return;
    }

    showItemWiseAsPerAmountLimitedByBrandDetails(value);
};

const showItemWiseLimitedByProductDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_products
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseLimitedByCategoryDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_categories
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseLimitedByBrandDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_brands
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseLimitedByTagDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_tags
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseLimitedByProductCollectionDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.limited_to_product_collection
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseAsPerAmountLimitedByBrandDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.as_per_amount_limited_to_brands
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseAsPerAmountLimitedToPriceDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.as_per_amount_limited_to_price
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showItemWiseAsPerAmountGetOffOnOthersDetails = (value) => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.as_per_amount_get_off_on_others
    });

    updateColumnDetails({
        column_name: 'discount_type_id',
        value
    });

    goToNext();
};

const showBuyTwoGetOneOrMoreDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_buy_three_get_one
    });

    goToNext();
};

const showBuyTwoGetFiftyOffDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_buy_two_get_50_off
    });

    goToNext();
};

const showBuyTwoGetRMFiftyOffDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_buy_two_get_RM_50_off
    });

    goToNext();
};

const showBuyAnyThreeOrMoreGetThirtyOffDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_buy_any_three_get_percentage_off
    });

    goToNext();
};

const showPercentageDiscountForNextItemDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.percentage_discount_for_next_item
    });

    goToNext();
};

const showBuyAnyThreeOrMoreGetRMThirtyOffDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_buy_any_three_get_flat_off
    });

    goToNext();
};

const showFlatDiscountForNextItemDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.flat_discount_for_next_item
    });

    goToNext();
};

const showItemWiseCheapestFreeDetails = () => {
    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_cheapest_free
    });

    goToNext();
};

const showItemWiseBundleBuyDetails = () => {
    state.displayBundleBuy = true;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = false;
    state.displayFreeItems = false;
    state.displayPercentageOff = false;
    state.displayFlatOff = false;

    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_bundle_buy
    });

    goToNext();
};

const showItemWiseBuyTwoAndGetTwoQuantityAtRMOneDetails = () => {
    state.displayBundleBuy = false;
    state.displayBuyTwoAndGetTwoQuantityAtRMOne = true;
    state.displayFreeItems = false;
    state.displayPercentageOff = false;

    updateColumnDetails({
        column_name: 'item_wise_promotion_type_id',
        value: props.staticDetails.quantity_buy_two_and_get_one_quantity_at_rm_one
    });

    goToNext();
};

const addNewTierDetails = () => {
    promotionForm.tiers.push({ buy_value: null, get_value: null });
};

const updateTierValueDetails = (details) => {
    promotionForm.tiers[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    promotionForm.tiers.splice(key, 1);
};

const updateSelectedMemberships = (details) => {
    promotionForm.memberships = details.memberships;
};

onMounted(() => {
    if (props.clonePromotion) {
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(promotionForm, JSON.parse(JSON.stringify(props.clonePromotion.data)));

        state.currentStep = state.stepPromotionDetails;
    }
});
</script>
