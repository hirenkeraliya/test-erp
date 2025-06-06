<template>
    <PageTitle :title="employeeGroup ? 'Edit Employee Group' : 'Add Employee Group'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Employee Groups
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="employeeGroup">Edit Employee Group</span>
                        <span v-else>Add Employee Group</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveEmployeeGroup();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeGroupForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeGroupForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="employeeGroupForm.purchase_limit_type_id"
                                    :records="purchaseLimitTypes"
                                    input-label="Purchase Limit Type"
                                    validation-field-name="purchase_limit_type_id"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="employeeGroupForm.purchase_limit_type_id"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="employeeGroupForm.item_purchase_limit"
                                    input-name="Item Purchase Limit"
                                    input-label="Item Purchase Limit"
                                    :title="getTitleText()"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="employeeGroupForm.limit_reset_type_id"
                                    :records="limitResetTypes"
                                    input-label="Limit Reset Type"
                                    validation-field-name="limit_reset_type_id"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="employeeGroupForm.limit_reset_type_id && employeeGroupForm.limit_reset_type_id === staticDetails.limit_reset_type_by_days"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="employeeGroupForm.limit_reset"
                                    input-name="Limit Reset"
                                    input-label="Limit Reset Timeline"
                                    validation-field-name="limit_reset"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="employeeGroupForm.limit_reset_type_id && employeeGroupForm.limit_reset_type_id === staticDetails.limit_reset_type_by_week"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="employeeGroupForm.limit_reset"
                                    :records="limitResetDays"
                                    input-label="Limit Reset Timeline"
                                    validation-field-name="limit_reset"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="employeeGroupForm.limit_reset_type_id && employeeGroupForm.limit_reset_type_id === staticDetails.limit_reset_type_by_month"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="employeeGroupForm.limit_reset"
                                    :records="state.monthlySelections"
                                    input-label="Limit Reset Timeline"
                                    validation-field-name="limit_reset"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.employee_groups.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="employeeGroup ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, reactive, watch } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    employeeGroup: {
        type: Object,
        default: null,
    },
    purchaseLimitTypes: {
        type: Array,
        required: true,
    },
    limitResetTypes: {
        type: Array,
        required: true,
    },
    limitResetDays: {
        type: Array,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    monthlySelections: [],
});

const employeeGroupForm = useForm({
    name: null,
    code: null,
    item_purchase_limit: 0,
    purchase_limit_type_id: null,
    limit_reset_type_id: null,
    limit_reset: null,
    watchEnabled: true,
});

const saveEmployeeGroup = () => {
    employeeGroupForm.watchEnabled = false;
    removeLocalStorage('employeeGroup');

    if (props.employeeGroup) {
        router.put(route('admin.employee_groups.update', props.employeeGroup.id), employeeGroupForm);
        return;
    }

    router.post(route('admin.employee_groups.store'), employeeGroupForm);
};

const getTitleText = () => {
    if (employeeGroupForm.purchase_limit_type_id === props.staticDetails.purchase_limit_by_items) {
        return 'It will be consider total buying quantities. Set Zero To Unlimited Purchase';
    }

    if (employeeGroupForm.purchase_limit_type_id === props.staticDetails.purchase_limit_by_amount) {
        return 'It will be consider total paid. Set Zero To Unlimited Purchase';
    }

    if (employeeGroupForm.purchase_limit_type_id === props.staticDetails.purchase_limit_by_sale) {
        return 'It will be consider orders. Set Zero To Unlimited Purchase';
    }
};

onMounted(() => {
    if (props.employeeGroup) {
        removeLocalStorage('employeeGroup');
        Object.assign(employeeGroupForm, props.employeeGroup);
    } else {
        setLocalStorage('employeeGroup', employeeGroupForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.employeeGroup) {
        saveLocalStorage('employeeGroup', employeeGroupForm);
    }
};

const clearFormData = () => {
    employeeGroupForm.reset();
};

watch(employeeGroupForm, () => {
    if (employeeGroupForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

const daysInMonth = 31;

for (let index = 1; index <= daysInMonth; index++) {
    state.monthlySelections.push({ id: index, name: index.toString() });
}
</script>
