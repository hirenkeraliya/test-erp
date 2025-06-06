<template>
    <div
        v-for="(tier, index) in assemblyChildMasterProducts"
        :key="'assembly-products' + index"
        class="grid grid-cols-12 gap-0 sm:gap-6"
    >
        <div class="input-form col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-8 xl:col-span-4">
            <JProductFilter
                :product-search-url="route('admin.get_filtered_regular_master_products')"
                get-product-url-name="admin.get_master_product"
                :selected-product-id="tier.child_master_product_id"
                :validation-field-name="'assembly_child_master_products.' + index + '.child_master_product_id'"
                input-label="Product"
                filter-button-class="mt-8"
                @update:product-selected="productSelected($event, index)"
                @update:display-product-filters="displayUpdateFilter(index)"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                input-label="Units"
                :input-value="tier.units"
                :validation-field-name="'assembly_child_master_products.' + index + '.units'"
                @update:input-value="updateTierAssemblyItemValueDetails($event, index, 'units')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                :disabled="assemblyChildMasterProducts.length <= 1"
                @click="removeTierAssemblyItemDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 pt-5">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
            <OutlinePrimaryButton
                text="+ Add New Child Item"
                type="button"
                class="border-dashed w-full"
                @click="addNewTierAssemblyItemDetails()"
            />
        </div>
    </div>

    <JProductFilterDetails
        v-if="state.displayInventoryUpdateFilterModal"
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('admin.get_filtered_regular_master_products_list')"
        :filtered-category-url="route('admin.categories.get_filtered_categories')"
        :filtered-brand-url="route('admin.brands.get_filtered_brands')"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import { route } from 'ziggy';
import { reactive } from 'vue';

defineProps({
    assemblyChildMasterProducts: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    displayInventoryUpdateFilterModal: false,
    productFilterModalIndex: null,
});

const emits = defineEmits([
    'update:tier-assembly-item-value-details',
    'add:new-tier-assembly-item-details',
    'remove:tier-assembly-item-details-of',
]);

const productSelected = (selectedProduct, index) => {
    emits('update:tier-assembly-item-value-details', {
        key: index,
        column_name: 'child_master_product_id',
        value: selectedProduct.id
    });
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;
    productSelected(selectedProduct, state.productFilterModalIndex);
    state.productFilterModalIndex = null;
};

const displayUpdateFilter = (index) => {
    state.productFilterModalIndex = index;
    state.displayInventoryUpdateFilterModal = true;
};

const updateTierAssemblyItemValueDetails = (event, itemIndex, columnName) => {
    emits('update:tier-assembly-item-value-details', {
        key: itemIndex,
        value: event,
        column_name: columnName,
    });
};

const addNewTierAssemblyItemDetails = () => {
    emits('add:new-tier-assembly-item-details');
};

const removeTierAssemblyItemDetailsOf = (index) => {
    emits('remove:tier-assembly-item-details-of', index);
};
</script>
