<template>
    <div class="intro-y bg-slate-50">
        <div class="">
            <div class="font-medium text-lg p-5 border-b">
                Exclusions
            </div>
            <div class="p-5">
                <InfoAlert
                    v-if="voucherConfigurationForm.exclude_by_type !== null && voucherConfigurationForm.exclude_by_type !== staticDetails.exclude_by_none"
                    color="primary"
                    class="my-0"
                >
                    <span v-if="voucherConfigurationForm.exclude_by_type === staticDetails.exclude_by_categories">
                        When one or more products of the specified categories are part of the sale, the system will not count their amount for calculating minimum spend amount. This applies to issue/generation of the vouchers as well as redemption.
                    </span>
                    <span v-else-if="voucherConfigurationForm.exclude_by_type === staticDetails.exclude_by_products">
                        When one or more of the specified products are part of the sale, the system will not count their amount for calculating minimum spend amount. This applies to issue/generation of the vouchers as well as redemption.
                    </span>
                </InfoAlert>
            </div>
            <div class="p-5 pt-0">
                <OutlinePrimaryButton
                    v-for="(excludeByType, index) in excludeByTypes"
                    :key="'exclude-by-type-'+index"
                    :text="excludeByType.name"
                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                    :class="voucherConfigurationForm.exclude_by_type === excludeByType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                    @click="selectExcludeByType(excludeByType)"
                />
            </div>
        </div>

        <div
            v-if="voucherConfigurationForm.exclude_by_type === staticDetails.exclude_by_categories"
            class="p-5 pt-0"
        >
            <div class="font-medium text-base py-5 border-b mb-4">
                Exclude By Categories
            </div>

            <JMultiSelect
                input-label="Categories"
                validation-field-name="category_ids"
                placeholder="Please select categories"
                :required="true"
                :records="categories"
                :selected-records="selectedCategories"
                @update:selected-records="updateCategories"
            />
        </div>

        <div
            v-if="voucherConfigurationForm.exclude_by_type === staticDetails.exclude_by_products"
            class="p-5 pt-0"
        >
            <div class="font-medium text-base py-5 border-b mb-4">
                Exclude By Products
            </div>

            <FileUploadAndDisplayRecords
                :selected-products="state.selectedProducts"
                :unmatched-products="state.unmatchedProducts"
                product-upc-url="admin.products.get_matching_upc_and_is_selling_products"
                input-label="Upload Products"
                validation-field-name="products-uploads"
                file-path="/files/voucher-configuration-products-sample-file.xlsx"
                @display-selected-products-modal="openSelectedProductsModal"
                @update:column-details="updateColumnDetails"
                @display-unmatched-products-modal="openUnmatchedProductsModal"
            />
        </div>

        <div class="p-5 pt-0">
            <div class="font-medium text-base py-5 border-b mb-4">
                Restrictions
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-4">
                <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4">
                    <JSwitch
                        input-label="Applicable When Dream Price Is Applied"
                        :is-checked="voucherConfigurationForm.dream_price_applicable"
                        class="mb-2 sm:mb-0"
                        @update:is-checked="updateTheColumn('dream_price_applicable', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4">
                    <JSwitch
                        input-label="Applicable When Item Wise Promotion Is Applied"
                        :is-checked="voucherConfigurationForm.item_wise_promotion_applicable"
                        class="mb-2 sm:mb-0"
                        @update:is-checked="updateTheColumn('item_wise_promotion_applicable', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4">
                    <JSwitch
                        input-label="Applicable When Cart Wide Promotion Is Applied"
                        :is-checked="voucherConfigurationForm.cart_wide_promotion_applicable"
                        class="mb-2 sm:mb-0"
                        @update:is-checked="updateTheColumn('cart_wide_promotion_applicable', $event)"
                    />
                </div>
            </div>
        </div>
    </div>

    <SelectedProducts
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        :allow-to-clear-selected-products="true"
        @close-modal="closeModal"
        @clear-selected-products="clearSelectedProducts"
    >
        <template 
            v-if="! pageProps.product_variant"
            #color="record"
        >
            {{ record.item.color ? record.item.color.name : record.item.color_name }}
        </template>
        <template 
            v-if="! pageProps.product_variant"
            #size="record"
        >
            {{ record.item.size ? record.item.size.name : record.item.size_name }}
        </template>
        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>
    </SelectedProducts>

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        @close-modal="closeModal"
    />
</template>

<script setup>
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import { clearSelectedProductData } from '@commonServices/helper';
import { route } from 'ziggy';
import { onMounted, onUpdated, reactive, computed } from 'vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    excludeByTypes: {
        type: Array,
        default: () => [],
    },
    voucherConfigurationForm: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    selectedProducts: {
        type: Array,
        default: () => [],
    },
    selectedCategories: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emits = defineEmits([
    'update:column-details',
    'clear:columns',
    'click:go-to-next',
    'update:selected-categories',
    'update:selected-products',
    'clear:state-columns',
]);

const state = reactive({
    fields: [
        {
            key: 'id',
        },
        {
            key: 'name',
        },
        {
            key: 'upc'
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'color',
                },
                {
                    key: 'size',
                },
            ]),
    ],

    selectedProducts: [],
    unmatchedProducts: [],
    displayUnmatchedProductsModal: false,
    displaySelectedProductsModal: false,
});

const selectExcludeByType = (excludeByType) => {
    emits('clear:columns', {
        category_ids: [],
        product_ids: []
    });

    emits('clear:state-columns', {
        selectedCategories: [],
        selectedProducts: []
    });

    emits('update:column-details', {
        column_name: 'exclude_by_type',
        value: excludeByType.id,
    });

    if (props.voucherConfigurationForm.exclude_by_type === props.staticDetails.exclude_by_none) {
        goToNext();
    }
};

const closeModal = () => {
    if (state.displaySelectedProductsModal) {
        state.displaySelectedProductsModal = false;
        return;
    }

    if (state.displayUnmatchedProductsModal) {
        state.displayUnmatchedProductsModal = false;
    }
};

const updateCategories = (categories) => {
    emits('clear:columns', {
        product_ids: [],
    });

    emits('update:selected-categories', {
        categories
    });
};

const updateProducts = (products) => {
    emits('clear:columns', {
        category_ids: [],
    });

    emits('update:selected-products', {
        products
    });
};

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
};

const goToNext = () => {
    emits('click:go-to-next');
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const updateTheColumn = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const clearSelectedProducts = () => {
    clearSelectedProductData(route('admin.vouchers_configuration.remove_selected_products'), props.voucherConfigurationForm.id, null);
};

onMounted(() => {
    if (props.selectedProducts) {
        state.selectedProducts = props.selectedProducts;
    }
});

onUpdated(() => {
    if (state.selectedProducts) {
        updateProducts(state.selectedProducts);
    }
});
</script>
