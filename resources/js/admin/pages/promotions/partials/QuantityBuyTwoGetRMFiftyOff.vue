<template>
    <div class="intro-y flex flex-col xl:flex-row -mx-3">
        <div class="w-full xl:w-1/2 px-3 mb-3 border-b xl:border-b-0 border-r-0 xl:border-r">
            <ProductSelection
                label="Upload Buy Products"
                :promotion-form="promotionForm"
                :edit-selected-products="promotionForm.buy_products"
                validation-field-name="buy_product_ids"
                column-name="buy_products"
                :allow-to-clear-selected-products="true"
                :allow-to-download-selected-products="promotionForm.hasOwnProperty('id')"
                @update:product-ids="updateProductIds"
                @clear-selected-products="clearSelectedProducts(staticProductUploadTypes.buyProductUploadType)"
                @download-selected-products="downloadExcelRecords(staticProductUploadTypes.buyProductUploadType)"
            />
        </div>

        <div class="w-full xl:w-1/2 px-3">
            <ProductSelection
                label="Upload Get Products"
                :promotion-form="promotionForm"
                :edit-selected-products="promotionForm.get_products"
                validation-field-name="get_product_ids"
                column-name="get_products"
                :allow-to-clear-selected-products="true"
                :allow-to-download-selected-products="promotionForm.hasOwnProperty('id')"
                @update:product-ids="updateProductIds"
                @clear-selected-products="clearSelectedProducts(staticProductUploadTypes.getProductUploadType)"
                @download-selected-products="downloadExcelRecords(staticProductUploadTypes.getProductUploadType)"
            />
        </div>
    </div>

    <Tiers
        :tiers="promotionForm.tiers"
        buy-input-label="Buy Quantity"
        get-input-label="Flat"
        :get-value-input-group-prefix="currencySymbol"
        @update:tier-value-details="updateTierValueDetails"
        @add:new-tier-details="addNewTierDetails"
        @remove:tier-details-of="removeTierDetailsOf"
    />

    <InfoAlert
        color="primary"
        class="mt-5 mb-0"
    >
        <span class="flex">
            Buy “X” quantity from the Buy Products list and get Flat {{ currencySymbol }}“XX” OFF on the uploaded get products list.
            Example: Buy 3 Shirts Get Flat {{ currencySymbol }}10 on Pants -> For every 3 quantities of a shirt, Flat {{ currencySymbol }}10 will be given to 1 pant
        </span>
    </InfoAlert>
</template>

<script setup>
import ProductSelection from '@adminPages/promotions/partials/ProductSelection.vue';
import Tiers from '@adminPages/promotions/partials/Tiers.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { clearSelectedProductData, exportRecords } from '@commonServices/helper';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    promotionForm: {
        type: Object,
        required: true,
    },
    staticProductUploadTypes: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:tier-value-details',
    'add:new-tier-details',
    'remove:tier-details-of',
    'update:product-ids',
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

const updateProductIds = (productDetails) => {
    emits('update:product-ids', productDetails);
};

const clearSelectedProducts = (productType) => {
    clearSelectedProductData(route('admin.promotions.remove_selected_products'), props.promotionForm.id, productType);
};

const downloadExcelRecords = (productType) => {
    const params = {
        type: productType,
    };
    return exportRecords(
        'export-promotions-products-details/',
        'promotions-products-details.xlsx',
        params
    );
};
</script>
