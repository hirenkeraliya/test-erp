<template>
    <div>
        <p class="text-lg font-medium p-5">
            Child Products
        </p>
    </div>

    <div
        v-for="(tier, index) in assemblyChildProducts"
        :key="'assembly-products' + index"
        class="grid grid-cols-12 gap-0 sm:gap-6 px-5"
    >
        <div class="input-form col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-8 xl:col-span-4">
            <JProductFilter
                :product-search-url="route('admin.get_filtered_regular_products')"
                get-product-url-name="admin.get_product"
                :selected-product-id="tier.child_product_id"
                :validation-field-name="'assembly_child_products.' + index + '.child_product_id'"
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
                :validation-field-name="'assembly_child_products.' + index + '.units'"
                @update:input-value="updateTierAssemblyProductValueDetails($event, index, 'units')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                :disabled="assemblyChildProducts.length <= 1"
                @click="removeTierAssemblyProductDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 p-5 pb-0">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
            <OutlinePrimaryButton
                text="+ Add New Child Product"
                type="button"
                class="border-dashed w-full"
                @click="addNewTierAssemblyProductDetails()"
            />
        </div>
    </div>

    <JProductFilterDetails
        v-if="state.displayInventoryUpdateFilterModal"
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('admin.get_filtered_regular_products_list')"
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
    assemblyChildProducts: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    displayInventoryUpdateFilterModal: false,
    productFilterModalIndex: null,
});

const emits = defineEmits([
    'update:tier-assembly-product-value-details',
    'add:new-tier-assembly-product-details',
    'remove:tier-assembly-product-details-of',
]);

const productSelected = (selectedProduct, index) => {
    emits('update:tier-assembly-product-value-details', {
        key: index,
        column_name: 'child_product_id',
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

const updateTierAssemblyProductValueDetails = (event, itemIndex, columnName) => {
    emits('update:tier-assembly-product-value-details', {
        key: itemIndex,
        value: event,
        column_name: columnName,
    });
};

const addNewTierAssemblyProductDetails = () => {
    emits('add:new-tier-assembly-product-details');
};

const removeTierAssemblyProductDetailsOf = (index) => {
    emits('remove:tier-assembly-product-details-of', index);
};
</script>
