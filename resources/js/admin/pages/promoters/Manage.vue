<template>
    <PageTitle :title="promoter ? 'Edit Promoter' : 'Add Promoter'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promoters
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ promoter ? 'Edit' : 'Add' }} Promoter
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form @submit.prevent="savePromoter();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="promoterForm.employee_id"
                                    :records="employees"
                                    input-label="Employee"
                                    :required="true"
                                    validation-field-name="employee_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="promoterForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="promoterForm.username"
                                    input-name="username"
                                    input-label="Username"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="!promoter
                                "
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="promoterForm.password"
                                    :required="true"
                                    type="password"
                                    input-name="password"
                                    input-label="Password"
                                />
                            </div>
                            <div
                                v-if="!promoter
                                "
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput

                                    v-model:input-value="promoterForm.password_confirmation"
                                    type="password"
                                    :required="true"
                                    input-name="password_confirmation"
                                    input-label="Confirm Password"
                                />
                            </div>
                            <div
                                v-if="company.commission_type_id === commissionTypes.by_promoter"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput

                                    v-model:input-value="promoterForm.monthly_sales_target"
                                    type="number"
                                    input-name="monthly_sales_target"
                                    input-label="Monthly Sales Target"
                                    :input-group-prefix="currencySymbol"
                                    title="Set to zero (0) if promoter has no monthly target"
                                />
                            </div>
                            <div
                                v-if="company.commission_type_id === commissionTypes.by_promoter"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="promoterForm.default_commission_amount_percentage"
                                    type="number"
                                    input-name="default_commission_amount_percentage"
                                    input-label="Default Commission Percentage"
                                    input-group-suffix="%"
                                    title="Promoter will receive this percent of item amount as commission irrespective of monthly sales target."
                                />
                            </div>
                            <div
                                v-if="company.commission_type_id === commissionTypes.by_promoter && promoterForm.monthly_sales_target > 0"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="promoterForm.monthly_target_commission_percentage"
                                    type="number"
                                    input-name="monthly_target_commission_percentage"
                                    input-label="Commission Percentage (For Monthly Sales Target)"
                                    input-group-suffix="%"
                                    title="Promoter will receive this percent of item amount as commission if monthly sales target is achieved."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="promoterForm.group_id"
                                    :records="promoterGroups"
                                    input-label="Promoter Group"
                                    validation-field-name="group_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="promoterForm.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    :required="true"
                                    validation-field-name="location_ids"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.promoters.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="promoter ? 'Update' : 'Submit'"
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
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    commissionTypes: {
        type: Object,
        default: null
    },
    promoter: {
        type: Object,
        default: null,
    },
    company: {
        type: Object,
        default: null,
    },
    locations: {
        type: Array,
        required: true,
    },
    employees: {
        type: Array,
        required: true,
    },
    promoterGroups: {
        type: Array,
        required: true,
    },
});

const promoterForm = useForm({
    employee_id: null,
    username: null,
    password: null,
    password_confirmation: null,
    monthly_sales_target: 0,
    default_commission_amount_percentage: 0.00,
    monthly_target_commission_percentage: null,
    code: null,
    location_ids: [],
    locations: [],
    group_id: null,
    watchEnabled: true,
});

const savePromoter = () => {
    preparePromoterFormDetails();

    promoterForm.watchEnabled = false;
    removeLocalStorage('promoter');

    if (props.promoter) {
        promoterForm.put(route('admin.promoters.update', props.promoter.id));
        return;
    }
    promoterForm.post(route('admin.promoters.store'));
};

const preparePromoterFormDetails = () => {
    promoterForm.location_ids = promoterForm.locations.map((location) => {
        return location.id;
    });
};

onMounted(() => {
    if (props.promoter) {
        removeLocalStorage('promoter');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(promoterForm, JSON.parse(JSON.stringify(props.promoter)));
    } else {
        setLocalStorage('promoter', promoterForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.promoter) {
        saveLocalStorage('promoter', promoterForm);
    }
};

const clearFormData = () => {
    promoterForm.reset();
};

watch(promoterForm, () => {
    if (promoterForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

</script>
