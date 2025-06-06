<template>
    <PageTitle :title="cashier ? 'Edit Cashier' : 'Add Cashier'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cashiers
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="cashier">Edit Cashier</span>
                        <span v-else>Add Cashier</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveCashier();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="cashierForm.employee_id"
                                    :records="employees"
                                    input-label="Employee"
                                    :required="true"
                                    validation-field-name="employee_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="cashierForm.username"
                                    input-name="username"
                                    input-label="Username"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="! cashier"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="cashierForm.pin"
                                    :required="true"
                                    type="password"
                                    input-name="pin"
                                    input-label="Pin(4 digits)"
                                    title="Used for POS"
                                />
                            </div>
                            <div
                                v-if="! cashier"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="cashierForm.pin_confirmation"
                                    :required="true"
                                    type="password"
                                    input-name="pin_confirmation"
                                    input-label="Pin Confirmation"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="cashierForm.cashier_group_id"
                                    :records="cashierGroups"
                                    input-label="Cashier group"
                                    validation-field-name="cashier_group_id"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="cashierForm.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    :required="true"
                                    validation-field-name="location_ids"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('store_manager.cashiers.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="cashier ? 'Update' : 'Submit'"
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
import { useForm, router } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    cashier: {
        type: Object,
        default: null,
    },
    employees: {
        type: Object,
        required: true,
    },
    cashierGroups: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    }
});

const cashierForm = useForm({
    username: null,
    pin: null,
    pin_confirmation: null,
    employee_id: null,
    cashier_group_id: null,
    location_ids: [],
    locations: [],
});

const saveCashier = () => {
    prepareCashierFormDetails();

    if (props.cashier) {
        router.put(route('store_manager.cashiers.update', props.cashier.id), cashierForm);
        return;
    }

    router.post(route('store_manager.cashiers.store'), cashierForm);
};

const prepareCashierFormDetails = () => {
    cashierForm.location_ids = cashierForm.locations.map((location) => {
        return location.id;
    });
};

onMounted(() => {
    if (props.cashier) {
        Object.assign(cashierForm, props.cashier);
    }
});
</script>
