<template>
    <div class="font-medium text-lg sm:text-xl py-2 px-5 sm:p-5 border-b">
        Promotion Details
    </div>

    <div
        v-if="promotionForm.cart_wide_promotion_type_id === null && promotionForm.item_wise_promotion_type_id === null"
        class="intro-y text-base sm:text-lg p-5"
    >
        Please provide details in the previous step(s) first.
    </div>

    <div
        v-else
        class="p-5"
    >
        <div v-if="[staticDetails.cart_type_as_per_amount, staticDetails.cart_type_as_per_payment_type].includes(promotionForm.cart_wide_promotion_type_id)">
            <div
                v-if="staticDetails.cart_type_as_per_payment_type === promotionForm.cart_wide_promotion_type_id"
                class="w-full lg:w-1/2 px-3 pl-0"
            >
                <JMultiSelect
                    :required="false"
                    :records="paymentTypes"
                    input-label="Payment Types"
                    validation-field-name="payment_type_ids"
                    :selected-records="selectedPaymentTypes"
                    @update:selected-records="updatePaymentTypes"
                />
            </div>
            <Tiers
                :tiers="promotionForm.tiers"
                buy-input-label="Minimum Spend"
                buy-input-title="The minimum spending is the sum of cart items after applying Dream Price, Price Override, and Item Wise promotions."
                :get-input-label="staticDetails.type_percentage === promotionForm.discount_type_id ? 'Percentage' : 'Flat'"
                :get-value-input-group-prefix="staticDetails.type_flat === promotionForm.discount_type_id ? currencySymbol : null"
                :get-value-input-group-suffix="staticDetails.type_percentage === promotionForm.discount_type_id ? '%' : null"
                :buy-value-input-group-prefix="currencySymbol"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
            />
            <InfoAlert
                color="primary"
                class="mt-5 mb-0"
            >
                <span
                    v-if="staticDetails.type_percentage !== promotionForm.discount_type_id"
                    class="flex"
                >
                    Spend a minimum of the “{{ currencySymbol }}XX” amount and get an “{{ currencySymbol }}XX” discount on the cart amount.
                </span>

                <span
                    v-else
                    class="flex"
                >
                    Spend a minimum of the “{{ currencySymbol }}XX” amount and get an “X%” discount on the cart amount.
                </span>
            </InfoAlert>
        </div>

        <span v-if="staticDetails.gift_with_purchase === promotionForm.item_wise_promotion_type_id">
            <ProductSelection
                :promotion-form="promotionForm"
                :static-product-upload-types="staticProductUploadTypes"
                :edit-selected-products="promotionForm.regular_products"
                column-name="regular_products"
                validation-field-name="regular_product_ids"
                :allow-to-clear-selected-products="true"
                :allow-to-download-selected-products="promotionForm.hasOwnProperty('id')"
                @update:product-ids="updateColumnsDetails"
                @clear-selected-products="clearSelectedProducts"
                @download-selected-products="downloadExcelRecords"
            />

            <Tiers
                :tiers="promotionForm.tiers"
                buy-input-label="Minimum Spend"
                buy-input-title="The minimum spending is a sum of cart items after applying Dream Price, Price Override, and Item Wise promotions. It does not include the price of complementary items."
                get-input-label="Free Quantity"
                :buy-value-input-group-prefix="currencySymbol"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
            />
            <InfoAlert
                color="primary"
                class="mt-5 mb-0"
            >
                <span class="flex">
                    Spend a minimum of “{{ currencySymbol }}XX” amount and get “X” quantity free from the uploaded products.
                </span>
            </InfoAlert>
        </span>

        <span
            v-if="staticDetails.limited_to_categories === promotionForm.item_wise_promotion_type_id ||
                staticDetails.limited_to_products === promotionForm.item_wise_promotion_type_id ||
                staticDetails.limited_to_brands === promotionForm.item_wise_promotion_type_id || staticDetails.limited_to_tags === promotionForm.item_wise_promotion_type_id || staticDetails.limited_to_product_collection === promotionForm.item_wise_promotion_type_id"
        >
            <ProductSelection
                v-if="staticDetails.limited_to_products === promotionForm.item_wise_promotion_type_id"
                :promotion-form="promotionForm"
                :edit-selected-products="promotionForm.regular_products"
                column-name="regular_products"
                validation-field-name="regular_product_ids"
                :allow-to-clear-selected-products="true"
                :allow-to-download-selected-products="promotionForm.hasOwnProperty('id')"
                @update:product-ids="updateColumnsDetails"
                @clear-selected-products="clearSelectedProducts"
                @download-selected-products="downloadExcelRecords"
            />

            <span v-if="staticDetails.limited_to_categories === promotionForm.item_wise_promotion_type_id">
                <InfoAlert
                    color="primary"
                    class="mb-3"
                >
                    Only selected categories are checked to determine which items receive the discount. If an item is assigned a sub-category, it will not be included. Therefore, please ensure that you select all the relevant categories and subcategories that you would like the discount to apply to..
                </InfoAlert>

                <JMultiSelect
                    input-label="Categories"
                    validation-field-name="category_ids"
                    placeholder="Please select categories"
                    :required="true"
                    :records="categories"
                    :selected-records="promotionForm.categories"
                    @update:selected-records="updateCategories('categories', $event)"
                />
            </span>

            <span v-if="staticDetails.limited_to_brands === promotionForm.item_wise_promotion_type_id">
                <JMultiSelect
                    input-label="Brands"
                    validation-field-name="brand_ids"
                    placeholder="Please select brands"
                    :required="true"
                    :records="brands"
                    :selected-records="promotionForm.brands"
                    @update:selected-records="updateBrands('brands', $event)"
                />
            </span>

            <span v-if="staticDetails.limited_to_tags === promotionForm.item_wise_promotion_type_id">
                <JMultiSelect
                    input-label="Tags"
                    validation-field-name="tag_ids"
                    placeholder="Please select tags"
                    :required="true"
                    :records="tags"
                    :selected-records="promotionForm.tags"
                    @update:selected-records="updateTags('tags', $event)"
                />
            </span>
            <span v-if="staticDetails.limited_to_product_collection === promotionForm.item_wise_promotion_type_id">
                <JMultiSelect
                    input-label="Product Collection"
                    validation-field-name="product_collection_ids"
                    placeholder="Please select Collection"
                    :required="true"
                    :records="productCollections"
                    :selected-records="promotionForm.productCollections"
                    @update:selected-records="updateProductCollection('productCollections', $event)"
                />
            </span>

            <PromotionTypeSelection
                :promotion-form="promotionForm"
                :static-details="staticDetails"
                @update:column-details="updateColumnsDetails"
            />
        </span>

        <span
            v-if="staticDetails.as_per_amount_limited_to_brands === promotionForm.item_wise_promotion_type_id"
        >
            <JMultiSelect
                input-label="Brands"
                validation-field-name="brand_ids"
                placeholder="Please select brands"
                :required="true"
                :records="brands"
                :selected-records="promotionForm.brands"
                @update:selected-records="updateBrands('brands', $event)"
            />

            <Tiers
                :tiers="promotionForm.tiers"
                buy-input-label="Minimum Spend"
                buy-input-title="The minimum spending is the sum of brand items after applying Dream Price and Price Override."
                :get-input-label="staticDetails.type_percentage === promotionForm.discount_type_id ? 'Percentage' : 'Flat'"
                :get-value-input-group-prefix="staticDetails.type_flat === promotionForm.discount_type_id ? currencySymbol : null"
                :get-value-input-group-suffix="staticDetails.type_percentage === promotionForm.discount_type_id ? '%' : null"
                :buy-value-input-group-prefix="currencySymbol"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
            />

            <InfoAlert
                color="primary"
                class="mt-5 mb-0"
            >
                <span
                    v-if="staticDetails.type_percentage !== promotionForm.discount_type_id"
                    class="flex"
                >
                    Spend a minimum of “{{ currencySymbol }}XX” on the selected Brands and get a “{{ currencySymbol }}XX” discount on the Amount of the selected Brands
                </span>

                <span
                    v-else
                    class="flex"
                >
                    Spend a minimum of “{{ currencySymbol }}XX” on the selected Brands and get a “X%” discount on the Amount of the selected Brands
                </span>
            </InfoAlert>
        </span>

        <span
            v-if="staticDetails.as_per_amount_limited_to_price === promotionForm.item_wise_promotion_type_id"
        >
            <Tiers
                :tiers="promotionForm.tiers"
                buy-input-label="Minimum Product Price"
                buy-input-title="The minimum product price after applying Dream Price and Price Override."
                :get-input-label="staticDetails.type_percentage === promotionForm.discount_type_id ? 'Percentage' : 'Flat'"
                :get-value-input-group-prefix="staticDetails.type_flat === promotionForm.discount_type_id ? currencySymbol : null"
                :get-value-input-group-suffix="staticDetails.type_percentage === promotionForm.discount_type_id ? '%' : null"
                max-value-input-label="Maximum Product Price"
                max-value-input-title="The maximum product price after applying Dream Price and Price Override.."
                :max-value-input-group-prefix="currencySymbol"
                :buy-value-input-group-prefix="currencySymbol"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
            />

            <InfoAlert
                color="primary"
                class="mt-5 mb-0"
            >
                <span
                    v-if="staticDetails.type_percentage !== promotionForm.discount_type_id"
                    class="flex"
                >
                    Buy a product that's price is in the “{{ currencySymbol }}XX” to "{{ currencySymbol }}XX" range and get an “{{ currencySymbol }}XX” flat discount on top of the product's price.
                </span>

                <span
                    v-else
                    class="flex"
                >
                    Buy a product that's price is in the “{{ currencySymbol }}XX” to "{{ currencySymbol }}XX" range and get an “X%” discount on top of the product's price.
                </span>
            </InfoAlert>
        </span>

        <QuantityBuyThreeGetOne
            v-if="staticDetails.quantity_buy_three_get_one === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <AsPerAmountGetOffOnOthers
            v-if="staticDetails.as_per_amount_get_off_on_others === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-details="staticDetails"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <QuantityBuyAnyThreeGetPercentageOff
            v-if="staticDetails.quantity_buy_any_three_get_percentage_off === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <PercentageDiscountForNextItem
            v-if="staticDetails.percentage_discount_for_next_item === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <FlatDiscountForNextItem
            v-if="staticDetails.flat_discount_for_next_item === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <QuantityBuyTwoGetFiftyOff
            v-if="staticDetails.quantity_buy_two_get_50_off === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <QuantityBuyAnyThreeGetFlatOff
            v-if="staticDetails.quantity_buy_any_three_get_flat_off === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <QuantityBuyTwoGetRMFiftyOff
            v-if="staticDetails.quantity_buy_two_get_RM_50_off === promotionForm.item_wise_promotion_type_id"
            :promotion-form="promotionForm"
            :static-product-upload-types="staticProductUploadTypes"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
            @update:product-ids="updateColumnsDetails"
        />

        <div v-if="staticDetails.quantity_cheapest_free === promotionForm.item_wise_promotion_type_id">
            <CheapestFree
                :promotion-form="promotionForm"
                :static-product-upload-types="staticProductUploadTypes"
                @update:product-ids="updateColumnsDetails"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
            />
            <InfoAlert
                color="primary"
                class="my-4 mb-0"
            >
                <span class="flex">
                    Buy “X” products and get “X“ quantity of the cheapest product for free.<br>
                    Example: Buy 3 Get 1 Cheapest Free -> 3rd, 6th, 9th, ... will be free
                </span>
            </InfoAlert>
        </div>

        <div v-if="staticDetails.quantity_bundle_buy === promotionForm.item_wise_promotion_type_id">
            <BundleBuy
                :promotion-form="promotionForm"
                :static-product-upload-types="staticProductUploadTypes"
                :allow-to-download-selected-products="promotionForm.hasOwnProperty('id')"
                @update:product-ids="updateColumnsDetails"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
                @download-selected-products="downloadExcelRecords"
            />
            <InfoAlert
                color="primary"
                class="my-4 mb-0"
            >
                <span class="flex">
                    Buy “X” number of products and pay only “{{ currencySymbol }}XX”.
                </span>
            </InfoAlert>
        </div>

        <div v-if="staticDetails.quantity_buy_two_and_get_one_quantity_at_rm_one === promotionForm.item_wise_promotion_type_id">
            <QuantityBuyTwoAndGetOneQuantityAtRmOne
                :promotion-form="promotionForm"
                :static-product-upload-types="staticProductUploadTypes"
                @update:product-ids="updateColumnsDetails"
                @update:tier-value-details="updateTierValueDetails"
                @add:new-tier-details="addNewTierDetails"
                @remove:tier-details-of="removeTierDetailsOf"
            />
            <InfoAlert
                color="primary"
                class="my-4 mb-0"
            >
                <span class="flex">
                    Buy “X” number of products and get "X" number of products at “{{ currencySymbol }}XX”.
                </span>
            </InfoAlert>
        </div>
    </div>
</template>

<script setup>
import Tiers from '@adminPages/promotions/partials/Tiers.vue';
import ProductSelection from '@adminPages/promotions/partials/ProductSelection.vue';
import PromotionTypeSelection from '@adminPages/promotions/partials/PromotionTypeSelection.vue';
import QuantityBuyAnyThreeGetPercentageOff from '@adminPages/promotions/partials/QuantityBuyAnyThreeGetPercentageOff.vue';
import PercentageDiscountForNextItem from '@adminPages/promotions/partials/PercentageDiscountForNextItem.vue';
import FlatDiscountForNextItem from '@adminPages/promotions/partials/FlatDiscountForNextItem.vue';
import QuantityBuyAnyThreeGetFlatOff from '@adminPages/promotions/partials/QuantityBuyAnyThreeGetFlatOff.vue';
import QuantityBuyThreeGetOne from '@adminPages/promotions/partials/QuantityBuyThreeGetOne.vue';
import AsPerAmountGetOffOnOthers from '@adminPages/promotions/partials/AsPerAmountGetOffOnOthers.vue';
import QuantityBuyTwoGetFiftyOff from '@adminPages/promotions/partials/QuantityBuyTwoGetFiftyOff.vue';
import QuantityBuyTwoGetRMFiftyOff from '@adminPages/promotions/partials/QuantityBuyTwoGetRMFiftyOff.vue';
import CheapestFree from '@adminPages/promotions/partials/CheapestFree.vue';
import BundleBuy from '@adminPages/promotions/partials/BundleBuy.vue';
import QuantityBuyTwoAndGetOneQuantityAtRmOne from '@adminPages/promotions/partials/QuantityBuyTwoAndGetOneQuantityAtRmOne.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { clearSelectedProductData, exportRecords } from '@commonServices/helper';
import { route } from 'ziggy';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    promotionForm: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    brands: {
        type: Array,
        default: () => [],
    },
    tags: {
        type: Array,
        default: () => [],
    },
    productCollections: {
        type: Array,
        default: () => [],
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    staticProductUploadTypes: {
        type: Object,
        required: true,
    },
    paymentTypes: {
        type: Array,
        required: true
    },
    selectedPaymentTypes: {
        type: Array,
        default: () => [],
    },
});

const emits = defineEmits([
    'update:column-details',
    'update:tier-value-details',
    'add:new-tier-details',
    'remove:tier-details-of',
    'update:selected-payment-types'
]);

const updateTierValueDetails = (details) => {
    emits('update:tier-value-details', {
        key: details.key,
        value: details.value,
        column_name: details.column_name,
    });
};

const addNewTierDetails = () => {
    emits('add:new-tier-details');
};

const removeTierDetailsOf = (index) => {
    emits('remove:tier-details-of', index);
};

const updateColumnsDetails = (promotionTypeDetails) => {
    emits('update:column-details', promotionTypeDetails);
};

const updatePaymentTypes = (paymentTypes) => {
    emits('update:selected-payment-types', paymentTypes);
};

const updateCategories = (columnName, data) => {
    updateColumnsDetails({
        column_name: columnName,
        value: data
    });
};

const updateBrands = (columnName, data) => {
    updateColumnsDetails({
        column_name: columnName,
        value: data
    });
};

const updateTags = (columnName, data) => {
    updateColumnsDetails({
        column_name: columnName,
        value: data
    });
};

const updateProductCollection = (columnName, data) => {
    updateColumnsDetails({
        column_name: columnName,
        value: data
    });
};

const clearSelectedProducts = () => {
    clearSelectedProductData(route('admin.promotions.remove_selected_products'), props.promotionForm.id, props.staticProductUploadTypes.regularProductUploadType);
};

const downloadExcelRecords = () => {
    const params = {
        type: props.staticProductUploadTypes.regularProductUploadType,
    };
    return exportRecords(
        'export-promotions-products-details/',
        'promotions-products-details.xlsx',
        params
    );
};
</script>
