<template>
    <PageTitle :title="loyaltyCampaign ? 'Edit Loyalty Campaign' : 'Add Loyalty Campaign'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Loyalty Campaigns
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="loyaltyCampaign">Edit Loyalty Campaign</span>
                        <span v-else>Add Loyalty Campaign</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveLoyaltyCampaign();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignForm.minimum_spend_amount"
                                    type="number"
                                    input-name="minimum_spend_amount"
                                    input-label="Minimum Spend"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignForm.loyalty_points"
                                    type="number"
                                    input-name="loyalty_points"
                                    input-label="Loyalty Points"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="loyaltyCampaignForm.loyalty_point_expiration_days"
                                    input-name="loyalty_point_expiration_days"
                                    input-label="Loyalty Points Expiration Days"
                                    title="1) Set Zero (0) if you don't want to set a limit. 2) Cannot be used after expiry."
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="state.brands"
                                    :records="brands"
                                    input-label="Exclude by Brands"
                                    validation-field-name="excluded_brand_ids"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="loyaltyCampaignForm.start_date"
                                    input-label="Start Date"
                                    validation-field-name="start_date"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="loyaltyCampaignForm.end_date"
                                    input-label="End Date"
                                    validation-field-name="end_date"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.loyalty_campaigns.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="loyaltyCampaign ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    loyaltyCampaign: {
        type: Object,
        default: null,
    },
    brands: {
        type: Array,
        required: true,
    },
});

const loyaltyCampaignForm = useForm({
    name: null,
    minimum_spend_amount: null,
    loyalty_points: null,
    loyalty_point_expiration_days: null,
    excluded_brand_ids: [],
    start_date: null,
    end_date: null,

});

const state = reactive({
    brands: [],
});

const saveLoyaltyCampaign = () => {
    prepareLoyaltyCampaignFormDetails();
    if (props.loyaltyCampaign) {
        loyaltyCampaignForm.put(route('admin.loyalty_campaigns.update', props.loyaltyCampaign.id));
        return;
    }
    loyaltyCampaignForm.post(route('admin.loyalty_campaigns.store'));
};

const prepareLoyaltyCampaignFormDetails = () => {
    loyaltyCampaignForm.excluded_brand_ids = state.brands.map((brand) => {
        return brand.id;
    });
};

onMounted(() => {
    if (props.loyaltyCampaign) {
        Object.assign(loyaltyCampaignForm, props.loyaltyCampaign);
        state.brands = props.loyaltyCampaign.excluded_brands;
    }
});
</script>
