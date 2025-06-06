<template>
    <div>
        <ProductSelection
            :promotion-form="promotionForm"
            :edit-selected-products="promotionForm.regular_products"
            validation-field-name="regular_product_ids"
            column-name="regular_products"
            :allow-to-clear-selected-products="true"
            :allow-to-download-selected-products="promotionForm.hasOwnProperty('id')"
            @update:product-ids="updateProductIds"
            @clear-selected-products="clearSelectedProducts"
            @download-selected-products="downloadExcelRecords"
        />

        <Tiers
            :tiers="promotionForm.tiers"
            buy-input-label="Buy Quantity"
            get-input-label="Amount"
            :get-value-input-group-prefix="currencySymbol"
            @update:tier-value-details="updateTierValueDetails"
            @add:new-tier-details="addNewTierDetails"
            @remove:tier-details-of="removeTierDetailsOf"
        />
    </div>
</template>

<script setup>
import ProductSelection from '@adminPages/promotions/partials/ProductSelection.vue';
import Tiers from '@adminPages/promotions/partials/Tiers.vue';
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
    'update:product-ids',
    'update:tier-value-details',
    'add:new-tier-details',
    'remove:tier-details-of',
    'download-selected-products',
]);

const updateProductIds = (productDetails) => {
    emits('update:product-ids', productDetails);
};

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
