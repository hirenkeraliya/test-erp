<template>
    <PageTitle :title="voucherConfiguration ? 'Edit Voucher Configuration' : 'Add Voucher Configuration'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Vouchers Configuration
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>{{ voucherConfiguration ? 'Update' : 'Add' }} Voucher Configuration</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="voucherConfiguration ? updateVoucherConfiguration() : addVoucherConfiguration();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 intro-y">
                            <div class="col-span-12 sm:col-span-12 md:col-span-4 lg:col-span-3 xl:col-span-3 2xl:col-span-2">
                                <div
                                    v-for="(step, index) in state.steps"
                                    :key="step.key"
                                    class="intro-x flex items-center mb-5"
                                >
                                    <button
                                        type="button"
                                        class="w-10 h-10 rounded-full btn"
                                        :class="[ step.key === state.currentStep ? 'btn-primary' : 'text-slate-500 bg-slate-100' ]"
                                        @click="goToStep(step)"
                                    >
                                        {{ index + 1 }}
                                    </button>

                                    <div
                                        class="text-base ml-3"
                                        :class="[ step.key === state.currentStep ? 'font-medium ' : 'text-slate-600' ]"
                                    >
                                        <button
                                            type="button"
                                            @click="goToStep(step)"
                                        >
                                            {{ step.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 sm:col-span-12 md:col-span-8 lg:col-span-9 xl:col-span-9 2xl:col-span-10">
                                <div v-if="state.currentStep === state.stepVoucherType">
                                    <VoucherType
                                        :voucher-types="voucherTypes"
                                        :voucher-configuration-form="voucherConfigurationForm"
                                        :static-details="staticDetails"
                                        :birthday-voucher-id="birthdayVoucherId"
                                        :welcome-member-voucher-id="welcomeMemberVoucherId"
                                        :voucher-configuration="voucherConfiguration"
                                        :restricted-by-types="restrictedByTypes"
                                        @update:column-details="updateColumnDetails"
                                        @add:new-tier-details="addNewTierDetails"
                                        @clear:columns="clearColumns"
                                        @click:go-to-next="goToNext"
                                    />
                                </div>

                                <div v-if="state.currentStep === state.stepCriteria">
                                    <Criteria
                                        :voucher-configuration-form="voucherConfigurationForm"
                                        :static-details="staticDetails"
                                        :discount-types="discountTypes"
                                        :memberships="memberships"
                                        :selected-memberships="state.selectedMemberships"
                                        @update:column-details="updateColumnDetails"
                                        @update:tier-value-details="updateTierValueDetails"
                                        @add:new-tier-details="addNewTierDetails"
                                        @remove:tier-details-of="removeTierDetailsOf"
                                        @update:selected-memberships="updateSelectedMemberships"
                                        @clear:columns="clearColumns"
                                    />
                                </div>

                                <div v-if="state.currentStep === state.stepExclusions">
                                    <Exclusions
                                        :exclude-by-types="excludeByTypes"
                                        :voucher-configuration-form="voucherConfigurationForm"
                                        :static-details="staticDetails"
                                        :categories="categories"
                                        :selected-categories="state.selectedCategories"
                                        :selected-products="state.selectedProducts"
                                        @update:selected-categories="updateSelectedCategories"
                                        @update:selected-products="updateSelectedProducts"
                                        @update:column-details="updateColumnDetails"
                                        @clear:state-columns="clearStateColumns"
                                        @clear:columns="clearColumns"
                                        @click:go-to-next="goToNext"
                                    />
                                </div>

                                <div v-if="state.currentStep === state.stepEffectiveDates">
                                    <EffectiveDates
                                        :voucher-configuration-form="voucherConfigurationForm"
                                        :static-details="staticDetails"
                                        @update:column-details="updateColumnDetails"
                                        @clear:columns="clearColumns"
                                    />
                                </div>

                                <div v-if="state.currentStep === state.stepBasicDetails">
                                    <BasicDetails
                                        :voucher-configuration-form="voucherConfigurationForm"
                                        :sale-channels="saleChannels"
                                        :static-details="staticDetails"
                                        @update:column-details="updateColumnDetails"
                                        @clear:columns="clearColumns"
                                    />
                                </div>
                            </div>

                            <div class="intro-y col-span-12 flex items-center mt-5 justify-end">
                                <Link :href="route('admin.vouchers_configuration.index')">
                                    <SecondaryButton
                                        type="button"
                                        text="Cancel"
                                        class="w-24 mr-1"
                                    />
                                </Link>

                                <SecondaryButton
                                    v-if="state.currentStep !== state.stepVoucherType"
                                    type="button"
                                    text="Previous"
                                    class="w-24 mr-1"
                                    @click="goToPrevious()"
                                />

                                <PrimaryButton
                                    v-if="state.currentStep === state.stepBasicDetails"
                                    type="submit"
                                    :text="voucherConfiguration ? 'Update' : 'Save'"
                                    class="w-24 mr-1"
                                />

                                <PrimaryButton
                                    v-else
                                    type="button"
                                    text="Next"
                                    class="w-24"
                                    @click="goToNext()"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { route } from 'ziggy';
import { onMounted, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import VoucherType from '@adminPages/voucher_configurations/VoucherType.vue';
import Exclusions from '@adminPages/voucher_configurations/Exclusions.vue';
import BasicDetails from '@adminPages/voucher_configurations/BasicDetails.vue';
import Criteria from '@adminPages/voucher_configurations/Criteria.vue';
import EffectiveDates from '@adminPages/voucher_configurations/EffectiveDates.vue';
import { showErrorNotification } from '@commonServices/notifier';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    excludeByTypes: {
        type: Array,
        default: () => [],
    },
    voucherTypes: {
        type: Array,
        default: () => [],
    },
    discountTypes: {
        type: Array,
        default: () => [],
    },
    restrictedByTypes: {
        type: Array,
        default: () => [],
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    voucherConfiguration: {
        type: Object,
        default: null,
    },
    birthdayVoucherId: {
        type: Number,
        default: null,
    },
    welcomeMemberVoucherId: {
        type: Number,
        default: null,
    },
    memberships: {
        type: Array,
        default: () => [],
    },
});

const state = reactive({
    steps: [
        {
            key: 'voucher-type',
            label: 'Type',
        }, {
            key: 'criteria',
            label: 'Criteria',
        }, {
            key: 'exclusions',
            label: 'Exclusions',
        }, {
            key: 'effective-dates',
            label: 'Effective Period',
        }, {
            key: 'basic-details',
            label: 'Basic Details',
        },
    ],

    currentStep: 'voucher-type',
    stepVoucherType: 'voucher-type',
    stepExclusions: 'exclusions',
    stepCriteria: 'criteria',
    stepEffectiveDates: 'effective-dates',
    stepBasicDetails: 'basic-details',

    selectedCategories: null,
    selectedProducts: null,
    selectedMemberships: null,

});

const voucherConfigurationForm = useForm({
    _method: props.voucherConfiguration ? 'put' : 'post',
    restricted_by_type: null,
    voucher_type: null,
    exclude_by_type: null,
    issue_minimum_spend_amount: 0,
    use_minimum_spend_amount: null,
    validity_days: 0,
    discount_type: null,
    get_value: null,
    start_date: null,
    end_date: null,
    redemption_foot_note: null,
    handover_foot_note: null,
    category_ids: [],
    product_ids: [],
    tiers: [],
    dream_price_applicable: true,
    item_wise_promotion_applicable: true,
    cart_wide_promotion_applicable: true,
    membership_ids: [],
    title: null,
    description: null,
    terms_and_conditions: null,
    image: null,
    image_url: null,
    thumbnail: null,
    thumbnail_url: null,
    is_available_in_ecommerce: false,
    sale_channels: [],
    sale_channel_ids: [],
});

const goToStep = (step) => {
    state.currentStep = step.key;
};

const goToNext = () => {
    for (const key in state.steps) {
        if (state.steps[key].key === state.currentStep) {
            state.currentStep = state.steps[parseInt(key) + 1].key;
            return;
        }
    }
};

const goToPrevious = () => {
    for (const key in state.steps) {
        if (state.steps[key].key === state.currentStep) {
            state.currentStep = state.steps[parseInt(key) - 1].key;
            return;
        }
    }
};

const prepareVoucherConfigurationFormDetails = () => {
    if (state.selectedCategories) {
        voucherConfigurationForm.category_ids = state.selectedCategories.map((category) => {
            return category.id;
        });
    }

    if (state.selectedProducts) {
        voucherConfigurationForm.product_ids = state.selectedProducts.map((product) => {
            return product.id;
        });
    }

    if (state.selectedMemberships) {
        voucherConfigurationForm.membership_ids = state.selectedMemberships.map((membership) => {
            return membership.id;
        });
    }

    if (voucherConfigurationForm.sale_channels) {
        voucherConfigurationForm.sale_channel_ids = voucherConfigurationForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }
};

const addVoucherConfiguration = () => {
    prepareVoucherConfigurationFormDetails();

    voucherConfigurationForm.post(route('admin.vouchers_configuration.store'), {
        preserveScroll: true,
        onError: () => showErrorNotification('There are some input errors. Please ensure that all required form fields on all tabs are filled and try again.'),
    });
};

const updateVoucherConfiguration = () => {
    prepareVoucherConfigurationFormDetails();

    voucherConfigurationForm.post(route('admin.vouchers_configuration.update', props.voucherConfiguration.id), {
        preserveScroll: true,
        onError: () => showErrorNotification('There are some input errors. Please ensure that all required form fields on all tabs are filled and try again.'),
    });
};

const updateColumnDetails = (details) => {
    voucherConfigurationForm[details.column_name] = details.value;
};

const clearColumns = (columnDetails) => {
    for (const key in columnDetails) {
        voucherConfigurationForm[key] = columnDetails[key];
    }
};

const clearStateColumns = (columnDetails) => {
    for (const key in columnDetails) {
        state[key] = columnDetails[key];
    }
};

const updateSelectedCategories = (details) => {
    state.selectedCategories = details.categories;
};

const updateSelectedProducts = (details) => {
    state.selectedProducts = details.products;
};

const updateSelectedMemberships = (details) => {
    state.selectedMemberships = details.memberships;
};

const addNewTierDetails = () => {
    voucherConfigurationForm.tiers.push({ minimum_spend_amount: null, maximum_spend_amount: null, get_value: null });
};

const updateTierValueDetails = (details) => {
    voucherConfigurationForm.tiers[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    voucherConfigurationForm.tiers.splice(key, 1);
};

onMounted(() => {
    if (props.voucherConfiguration) {
        Object.assign(voucherConfigurationForm, props.voucherConfiguration);
        voucherConfigurationForm.image_url = props.voucherConfiguration.image_url ? props.voucherConfiguration.image_url : null;
    }

    if (props.voucherConfiguration && props.voucherConfiguration.voucher_configuration_tiers) {
        voucherConfigurationForm.tiers = props.voucherConfiguration.voucher_configuration_tiers;
    }

    if (props.voucherConfiguration && props.voucherConfiguration.products) {
        state.selectedProducts = props.voucherConfiguration.products;
    }

    if (props.voucherConfiguration && props.voucherConfiguration.categories) {
        state.selectedCategories = props.voucherConfiguration.categories;
    }

    if (props.voucherConfiguration && props.voucherConfiguration.memberships) {
        state.selectedMemberships = props.voucherConfiguration.memberships;
    }
});
</script>
