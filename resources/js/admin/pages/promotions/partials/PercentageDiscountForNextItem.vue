<template>
    <InfoAlert
        color="primary"
        class="mt-5 mb-0"
    >
        <span class="flex">
            The Retail Price of all the selected products must be same.
        </span>
    </InfoAlert>

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
        get-input-label="Percentage"
        get-value-input-group-suffix="%"
        @update:tier-value-details="updateTierValueDetails"
        @add:new-tier-details="addNewTierDetails"
        @remove:tier-details-of="removeTierDetailsOf"
    />

    <div
        class="grid grid-cols-12 gap-0 sm:gap-6 mb-3"
    >
        <InfoAlert
            color="primary"
            class="mt-5 mb-0 col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <span class="flex">
                Promotion Example:
            </span>

            <a
                href="/images/percentage_discount_for_next_item.jpg"
                target="_blank"
            >
                <!-- Example added here - https://www.notion.so/Discount-for-Next-Item-Promotion-2e284e701ce546a69db61cded8169a95 -->
                <img
                    src="/images/percentage_discount_for_next_item.jpg"
                    width="200"
                    class="bg-gray-300 mt-2"
                >
            </a>
        </InfoAlert>
    </div>
</template>
<script setup>
import ProductSelection from '@adminPages/promotions/partials/ProductSelection.vue';
import Tiers from '@adminPages/promotions/partials/Tiers.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { clearSelectedProductData, exportRecords } from '@commonServices/helper';
import { route } from 'ziggy';

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
