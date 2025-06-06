<template>
    <PageTitle :title="reward ? 'Edit Rewards' : 'Add Rewards'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Rewards
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <form
                    @submit.prevent="saveReward();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="rewardForm.title"
                                    input-name="title"
                                    input-label="Title"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="state.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    validation-field-name="location_ids"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="rewardForm.type"
                                    :records="rewardTypes"
                                    input-label="Type"
                                    validation-field-name="type"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="rewardForm.status"
                                    input-label="Status"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                    validation-field-name="status"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div
                            v-if="rewardForm.type === staticRewardTypes.freeItem"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="rewardForm.target_type"
                                    :records="rewardTargetTypes"
                                    input-label="Target Type"
                                    validation-field-name="target_type"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="rewardForm.loyalty_point"
                                    type="number"
                                    input-name="loyalty_point"
                                    input-label="Loyalty Point"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="rewardForm.target_type === staticRewardTargetTypes.products"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 mt-4"
                            >
                                <FileUploadAndDisplayRecords
                                    :selected-products="state.selectedProducts"
                                    :unmatched-products="state.unmatchedProducts"
                                    product-upc-url="admin.products.get_matching_upc_inventory_products"
                                    get-record-name="quantity"
                                    input-label="Upload File"
                                    validation-field-name="uploaded_file"
                                    file-path="/files/reward-products-sample-file.xlsx"
                                    @display-selected-products-modal="openSelectedProductsModal"
                                    @update:column-details="updateColumnDetails"
                                    @display-unmatched-products-modal="openUnmatchedProductsModal"
                                />
                            </div>

                            <div
                                v-if="rewardForm.target_type === staticRewardTargetTypes.categories"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-8 xl:col-span-6"
                            >
                                <JMultiSelect
                                    v-model:selected-records="state.categories"
                                    input-label="Categories"
                                    validation-field-name="category_ids"
                                    placeholder="Please select categories"
                                    :required="true"
                                    :records="categories"
                                />
                            </div>

                            <div
                                v-if="rewardForm.target_type === staticRewardTargetTypes.brands"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="state.brands"
                                    input-label="Brands"
                                    validation-field-name="brand_ids"
                                    placeholder="Please select brands"
                                    :required="true"
                                    :records="brands"
                                />
                            </div>

                            <div
                                v-if="rewardForm.target_type === staticRewardTargetTypes.departments"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="state.departments"
                                    input-label="Departments"
                                    validation-field-name="department_ids"
                                    placeholder="Please select departments"
                                    :required="true"
                                    :records="departments"
                                />
                            </div>
                        </div>


                        <div
                            v-if="rewardForm.type === staticRewardTypes.discountOnEntireSale"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                v-if="rewardForm.type === staticRewardTypes.discountOnEntireSale"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="rewardForm.minimum_point"
                                    type="number"
                                    input-name="minimum_point"
                                    input-label="Reward Value"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="rewardForm.type === staticRewardTypes.discountOnEntireSale"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="rewardForm.maximum_point"
                                    type="number"
                                    input-name="maximum_point"
                                    input-label="Maximum Discount"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div
                            v-if="rewardForm.type === staticRewardTypes.discountOnEntireSale"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <OutlinePrimaryButton
                                    v-for="(discountType, index) in discountTypes"
                                    :key="'discount-type-'+index"
                                    :text="discountType.name"
                                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0 mt-4"
                                    :class="rewardForm.discount_type === discountType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                                    @click="updateDetails(discountType.id)"
                                />
                            </div>
                        </div>

                        <div
                            v-if="rewardForm.type === staticRewardTypes.discountOnEntireSale"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="rewardForm.discount"
                                    type="number"
                                    input-name="discount"
                                    input-label="Discount"
                                    :input-group-prefix="state.getValueInputGroupPrefix"
                                    :input-group-suffix="state.getValueInputGroupSuffix"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.rewards.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="reward ? 'Update' : 'Submit'"
                                class="w-24"
                            />
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
        @close-modal="closeModal"
    >
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
        <template #color="record">
            {{ record.item.color ? record.item.color.name : record.item.color_name }}
        </template>
        <template #size="record">
            {{ record.item.size ? record.item.size.name : record.item.size_name }}
        </template>
    </SelectedProducts>    

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        @close-modal="closeModal"
    />
</template>
<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { computed, reactive, onMounted } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';

const pageProps = computed(() => usePage().props);

const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    rewardTypes: {
        type: Array,
        required: true,
    },
    staticRewardTypes: {
        type: Object,
        required: true,
    },
    rewardTargetTypes: {
        type: Array,
        required: true,
    },
    staticRewardTargetTypes: {
        type: Object,
        required: true,
    },
    discountTypes: {
        type: Array,
        default: () => [],
    },
    staticDiscountTypes: {
        type: Object,
        required: true,
    },
    reward: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        required: true
    },
    brands: {
        type: Array,
        required: true
    },
    departments: {
        type: Array,
        required: true
    },
    locations: {
        type: Array,
        required: true,
    },
});

const rewardForm = useForm({
    title: null,
    status: true,
    type: null,
    target_type: null,
    discount_type: props.staticDiscountTypes.percentage,
    product_ids: [],
    category_ids: [],
    brand_ids: [],
    department_ids: [],
    location_ids: [],
    minimum_point: null,
    maximum_point: null,
    loyalty_point: null,
    discount: null,
});

const state = reactive({
    fields: [
        {
            key: 'name',
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
    getValueInputGroupPrefix: null,
    getValueInputGroupSuffix: '%',
    categories: [],
    brands: [],
    departments: [],
    locations: [],
});

const saveReward = () => {
    prepareRewardFormDetails();
    if (props.reward) {
        rewardForm.put(route('admin.rewards.update', props.reward.id));
        return;
    }
    rewardForm.post(route('admin.rewards.store'));
};

const prepareRewardFormDetails = () => {

    if (state.selectedProducts.length > 0) {
        rewardForm.product_ids = state.selectedProducts.map((product) => {
            return product.id;
        });
    }

    if (state.brands.length > 0) {
        rewardForm.brand_ids = state.brands.map((brand) => {
            return brand.id;
        });
    }

    if (state.categories.length > 0) {
        rewardForm.category_ids = state.categories.map((category) => {
            return category.id;
        });
    }

    if (state.departments.length > 0) {
        rewardForm.department_ids = state.departments.map((department) => {
            return department.id;
        });
    }

    if (state.locations.length > 0) {
        rewardForm.location_ids = state.locations.map((location) => {
            return location.id;
        });
    }
};

const updateDetails = (typeId) => {
    rewardForm.discount_type = typeId;

    state.getValueInputGroupPrefix = null;
    state.getValueInputGroupSuffix = null;

    if (props.staticDiscountTypes.flat === typeId) {
        state.getValueInputGroupPrefix = currencySymbol.value;
    }

    if (props.staticDiscountTypes.percentage === typeId) {
        state.getValueInputGroupSuffix = '%';
    }
};

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
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

onMounted(() => {
    if (props.reward) {
        Object.assign(rewardForm, props.reward);
        state.brands = props.reward.brands;
        state.locations = props.reward.locations;
        state.categories = props.reward.categories;
        state.departments = props.reward.departments;
        state.selectedProducts = props.reward.products;
    }
});
</script>
