<template>
    <PageTitle :title="cashback ? 'Edit Cashback' : 'Add Cashback'" />

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        1. Members cannot return items where cashback is given; they can only exchange them.<br>
        2. Cashback is applied across multiple tiers based on the members' purchases. For example, if the minimum spending is {{ currencySymbol }}50 and the flat cashback is {{ currencySymbol }}10, when a member purchases items worth {{ currencySymbol }}100, they will receive a cashback of {{ currencySymbol }}20.
    </InfoAlert>

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cashback
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="cashback">Edit Cashback</span>
                        <span v-else>Add Cashback</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveCashback();"
                >
                    <div>
                        <div class="p-5">
                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="cashbackForm.name"
                                        input-name="name"
                                        input-label="Name"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JMultiSelect
                                        v-model:selected-records="state.locations"
                                        :records="locations"
                                        input-label="Locations"
                                        :required="true"
                                        validation-field-name="location_ids"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="cashbackForm.minimum_spend_amount"
                                        type="number"
                                        input-name="minimum_spend_amount"
                                        input-label="Minimum spend"
                                        :input-group-prefix="currencySymbol"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormSelectBox
                                        :selected-record="cashbackForm.discount_type_id"
                                        :records="discountTypes"
                                        input-label="Discount Type"
                                        validation-field-name="discount_type_id"
                                        :required="true"
                                        @update:selected-record="updateDiscountType"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="cashbackForm.discount_value"
                                        type="number"
                                        input-name="discount_value"
                                        input-label="Discount Value"
                                        :input-group-prefix="updateDiscountTypePrefix()"
                                        :input-group-suffix="updateDiscountTypeSuffix()"
                                        :required="true"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60 w-full" />

                        <div class="p-5">
                            <InfoAlert
                                v-if="cashbackForm.exclude_by_type === excludeByTypeOptions.originalItemPrice"
                                color="primary"
                                class="mb-3 mt-5"
                            >
                                The original item price will be compared with the actual `retail price` of the item. If all conditions are true, the cashback will not be applied to the sale.
                            </InfoAlert>
                            <InfoAlert
                                v-if="cashbackForm.exclude_by_type === excludeByTypeOptions.discountItemPrice"
                                color="primary"
                                class="mb-3 mt-5"
                            >
                                The discount item price will be compared with the item price after the discount effect. If all conditions are true, the cashback will not be applied to the sale.
                            </InfoAlert>
                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormSelectBox
                                        v-model:selected-record="cashbackForm.exclude_by_type"
                                        :records="excludeByTypes"
                                        input-label="Exclude by type"
                                        validation-field-name="exclude_by_type"
                                        :required="true"
                                        @update:selected-record="clearValues"
                                    />
                                </div>

                                <div
                                    v-if="parseInt(cashbackForm.exclude_by_type) === excludeByTypeOptions.products"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-3"
                                >
                                    <FileUploadAndDisplayRecords
                                        :selected-products="state.selectedProducts"
                                        :unmatched-products="state.unmatchedProducts"
                                        product-upc-url="admin.products.get_matching_upc_and_is_selling_products"
                                        input-label="Exclude Products"
                                        validation-field-name="product-ids"
                                        file-path="/files/cashback-exclude-products-sample-file.xlsx"
                                        @display-selected-products-modal="openSelectedProductsModal"
                                        @update:column-details="updateColumnDetails"
                                        @display-unmatched-products-modal="openUnmatchedProductsModal"
                                    />
                                </div>

                                <div
                                    v-if="parseInt(cashbackForm.exclude_by_type) === excludeByTypeOptions.categories"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <JMultiSelect
                                        v-model:selected-records="state.categories"
                                        :records="categories"
                                        input-label="Categories"
                                        validation-field-name="category_ids"
                                    />
                                </div>
                            </div>
                            <div
                                v-if="parseInt(cashbackForm.exclude_by_type) === excludeByTypeOptions.originalItemPrice || parseInt(cashbackForm.exclude_by_type) === excludeByTypeOptions.discountItemPrice"
                            >
                                <PriceExcludeTiers
                                    :tiers="cashbackForm.tiers"
                                    :condition-types="conditionTypes"
                                    get-value-input-label="Amount"
                                    @update:column-details="updateColumnDetails"
                                    @update:tier-value-details="updateTierValueDetails"
                                    @add:new-tier-details="addNewTierDetails"
                                    @remove:tier-details-of="removeTierDetailsOf"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                        <div class="p-5">
                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JDatePicker
                                        v-model:input-value="cashbackForm.start_date"
                                        input-label="Start Date"
                                        validation-field-name="start_date"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JDatePicker
                                        v-model:input-value="cashbackForm.end_date"
                                        input-label="End Date"
                                        validation-field-name="end_date"
                                        :required="true"
                                    />
                                </div>
                            </div>

                            <div class="mt-5">
                                <Link :href="route('admin.cashbacks.index')">
                                    <SecondaryButton
                                        type="button"
                                        text="Cancel"
                                        class="w-24 mr-1"
                                    />
                                </Link>

                                <PrimaryButton
                                    type="submit"
                                    :text="cashback ? 'Update' : 'Submit'"
                                    class="w-24"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <SelectedProducts
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        :allow-to-clear-selected-products="true"
        :allow-to-download-selected-products="cashbackForm.hasOwnProperty('id')"
        @clear-selected-products="clearSelectedProducts"
        @download-selected-products="downloadExcelRecords"
        @close-modal="closeModal"
    >
        <template #color="record">
            {{ record.item.color ? record.item.color.name : record.item.color_name }}
        </template>
        <template #size="record">
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
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { clearSelectedProductData, exportRecords } from '@commonServices/helper';
import PriceExcludeTiers from '@adminPages/cashbacks/PriceExcludeTiers.vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);
const pageProps = computed(() => usePage().props);

const props = defineProps({
    cashback: {
        type: Object,
        default: null,
    },

    categories: {
        type: Array,
        required: true,
    },

    locations: {
        type: Array,
        required: true,
    },

    excludeByTypes: {
        type: Array,
        default: () => [],
    },

    conditionTypes: {
        type: Array,
        required: true,
    },

    excludeByTypeOptions: {
        type: Object,
        default: null,
    },

    discountTypes: {
        type: Array,
        required: true,
    },

    discountStaticTypes: {
        type: Object,
        required: true,
    },
});

const cashbackForm = useForm({
    name: null,
    exclude_by_type: null,
    discount_type_id: null,
    discount_value: null,
    minimum_spend_amount: null,
    start_date: null,
    end_date: null,
    location_ids: [],
    product_ids: [],
    category_ids: [],
    tiers: [
        {
            condition_operator_type_id: null,
            amount: null,
        }
    ],
});

const state = reactive({
    locations: [],
    categories: [],
    selectedProducts: [],
    unmatchedProducts: [],
    records: [],
    fields: [
        {
            key: 'id',
        }, {
            key: 'name',
        }, {
            key: 'upc'
        }, ...(pageProps.value.product_variant
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

    displaySelectedProductsModal: false,
    displayUnmatchedProductsModal: false,
});

const saveCashback = () => {
    prepareCashbackFormDetails();

    if (props.cashback) {
        router.put(route('admin.cashbacks.update', props.cashback.id), cashbackForm);
        return;
    }

    router.post(route('admin.cashbacks.store'), cashbackForm);
};

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const updateTierValueDetails = (details) => {
    cashbackForm.tiers[details.key][details.column_name] = details.value;
};

const addNewTierDetails = () => {
    cashbackForm.tiers.push({ condition_operator_type_id: null, amount: null });
};

const removeTierDetailsOf = (key) => {
    cashbackForm.tiers.splice(key, 1);
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

const prepareCashbackFormDetails = () => {
    cashbackForm.location_ids = state.locations.map((location) => {
        return location.id;
    });

    cashbackForm.product_ids = state.selectedProducts.map((product) => {
        return product.id;
    });

    cashbackForm.category_ids = state.categories.map((category) => {
        return category.id;
    });
};

const clearSelectedProducts = () => {
    clearSelectedProductData(route('admin.cashbacks.remove_selected_products'), cashbackForm.id, null);
};

const updateDiscountType = (discountType) => {
    cashbackForm.discount_type_id = discountType;
    cashbackForm.discount_value = null;
};

const updateDiscountTypePrefix = () => {
    if (parseInt(cashbackForm.discount_type_id) === props.discountStaticTypes.flat) {
        return currencySymbol.value;
    }

    return null;
};

const updateDiscountTypeSuffix = () => {
    if (parseInt(cashbackForm.discount_type_id) === props.discountStaticTypes.percentage) {
        return '%';
    }

    return null;
};

const clearValues = () => {
    state.categories = [];
    state.selectedProducts = [];
    state.unmatchedProducts = [];
    cashbackForm.tiers = [];
};

onMounted(() => {
    if (props.cashback) {
        Object.assign(cashbackForm, props.cashback);
        state.locations = props.cashback.locations;
        state.selectedProducts = props.cashback.products;
        state.categories = props.cashback.categories;
    }
});

const downloadExcelRecords = () => {
    return exportRecords(
        'export-cashback-products/',
        'cashback-selected-products.xlsx',
        { id: cashbackForm.id }
    );
};
</script>
