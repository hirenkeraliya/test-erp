<template>
    <PageTitle :title="loyaltyCampaignConfiguration ? 'Edit Loyalty Campaign Configuration' : 'Add Loyalty Campaign Configuration'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Loyalty Campaign Configurations
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <form
                    @submit.prevent="saveLoyaltyCampaignConfiguration();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignConfigurationForm.description"
                                    input-name="description"
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
                                    :selected-record="loyaltyCampaignConfigurationForm.loyalty_campaign_type"
                                    :records="loyaltyCampaignTypes"
                                    input-label="Campaign Type"
                                    validation-field-name="loyalty_campaign_type"
                                    :required="true"
                                    @update:selected-record="updateLoyaltyCampaignType"
                                />
                            </div>
                            <div
                                v-if="staticLoyaltyCampaignTypes.productBrands === loyaltyCampaignConfigurationForm.loyalty_campaign_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="state.brands"
                                    :records="brands"
                                    input-label="Brands"
                                    validation-field-name="brand_ids"
                                />
                            </div>
                            <div
                                v-if="staticLoyaltyCampaignTypes.productCategories === loyaltyCampaignConfigurationForm.loyalty_campaign_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="state.categories"
                                    :records="categories"
                                    input-label="Categories"
                                    validation-field-name="category_ids"
                                />
                            </div>

                            <div
                                v-if="staticLoyaltyCampaignTypes.specificProducts === loyaltyCampaignConfigurationForm.loyalty_campaign_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FileUploadAndDisplayRecords
                                    :selected-products="state.selectedProducts"
                                    :unmatched-products="state.unmatchedProducts"
                                    product-upc-url="admin.products.get_matching_upc_and_is_selling_products"
                                    input-label="Products"
                                    validation-field-name="product-ids"
                                    file-path="/files/loyalty-campaign-configuration-products-sample-file.xlsx"
                                    @display-selected-products-modal="openSelectedProductsModal"
                                    @update:column-details="updateColumnDetails"
                                    @display-unmatched-products-modal="openUnmatchedProductsModal"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignConfigurationForm.point_earned"
                                    type="number"
                                    input-name="point_earned"
                                    input-label="Points Earned"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignConfigurationForm.minimum_purchase_amount"
                                    type="number"
                                    input-name="minimum_purchase_amount"
                                    input-label="Minimum Purchase"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="loyaltyCampaignConfigurationForm.expiration_type"
                                    :records="expirationTypes"
                                    input-label="Expire By"
                                    validation-field-name="expiration_type"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="loyaltyCampaignConfigurationForm.include_tax"
                                    input-label="Include Tax"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="loyaltyCampaignConfigurationForm.status"
                                    input-label="Status"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.loyalty_campaign_configurations.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="loyaltyCampaignConfiguration ? 'Update' : 'Submit'"
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
        :allow-to-clear-selected-products="false"
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
import { useForm, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';

const pageProps = computed(() => usePage().props);
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    loyaltyCampaignConfiguration: {
        type: Object,
        default: null,
    },
    brands: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    loyaltyCampaignTypes: {
        type: Array,
        required: true,
    },
    expirationTypes: {
        type: Array,
        required: true,
    },
    staticLoyaltyCampaignTypes: {
        type: Object,
        required: true,
    },
});

const loyaltyCampaignConfigurationForm = useForm({
    description: null,
    location_ids: null,
    loyalty_campaign_type: null,
    point_earned: null,
    minimum_purchase_amount: null,
    expiration_type: null,
    status: false,
    include_tax: false,
    brand_ids: [],
    category_ids: [],
    product_ids: [],

});

const state = reactive({
    brands: [],
    locations: [],
    categories: [],
    products: [],
    selectedProducts: [],
    unmatchedProducts: [],
    fields: [
        {
            key: 'id',
        }, {
            key: 'name',
        }, {
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
    displaySelectedProductsModal: false,
    displayUnmatchedProductsModal: false,
});

const saveLoyaltyCampaignConfiguration = () => {
    prepareLoyaltyCampaignConfigurationFormDetails();
    if (props.loyaltyCampaignConfiguration) {
        loyaltyCampaignConfigurationForm.put(route('admin.loyalty_campaign_configurations.update', props.loyaltyCampaignConfiguration.id));
        return;
    }
    loyaltyCampaignConfigurationForm.post(route('admin.loyalty_campaign_configurations.store'));
};

const prepareLoyaltyCampaignConfigurationFormDetails = () => {
    if (state.brands.length) {
        loyaltyCampaignConfigurationForm.brand_ids = state.brands.map((brand) => {
            return brand.id;
        });
    }

    if (state.locations.length > 0) {
        loyaltyCampaignConfigurationForm.location_ids = state.locations.map((location) => {
            return location.id;
        });
    }

    if (state.categories.length > 0) {
        loyaltyCampaignConfigurationForm.category_ids = state.categories.map((category) => {
            return category.id;
        });
    }

    if (state.selectedProducts.length > 0) {
        loyaltyCampaignConfigurationForm.product_ids = state.selectedProducts.map((product) => {
            return product.id;
        });
    }
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

const closeModal = () => {
    if (state.displaySelectedProductsModal) {
        state.displaySelectedProductsModal = false;
        return;
    }

    if (state.displayUnmatchedProductsModal) {
        state.displayUnmatchedProductsModal = false;
    }
};

const updateLoyaltyCampaignType = (loyaltyCampaignType) => {
    loyaltyCampaignConfigurationForm.loyalty_campaign_type = loyaltyCampaignType;

    loyaltyCampaignConfigurationForm.brand_ids = [];
    loyaltyCampaignConfigurationForm.category_ids = [];
    loyaltyCampaignConfigurationForm.product_ids = [];
    state.brands = [];
    state.categories = [];
    state.selectedProducts = [];
};

onMounted(() => {
    if (props.loyaltyCampaignConfiguration) {
        Object.assign(loyaltyCampaignConfigurationForm, props.loyaltyCampaignConfiguration);
        state.brands = props.loyaltyCampaignConfiguration.brands;
        state.locations = props.loyaltyCampaignConfiguration.locations;
        state.categories = props.loyaltyCampaignConfiguration.categories;
        state.selectedProducts = props.loyaltyCampaignConfiguration.products;
    }
});
</script>
