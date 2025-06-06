<template>
    <PageTitle :title="warehouseManager ? 'Edit Warehouse Manager' : 'Add Warehouse Manager'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Warehouse Managers
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="warehouseManager">Edit Warehouse Manager</span>
                        <span v-else>Add Warehouse Manager</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form
                    @submit.prevent="saveWarehouseManager();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="warehouseManagerForm.employee_id"
                                    :records="employees"
                                    input-label="Employee"
                                    :required="true"
                                    validation-field-name="employee_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="warehouseManagerForm.username"
                                    input-name="username"
                                    input-label="Username"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="!warehouseManager"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="!warehouseManager"
                                    v-model:input-value="warehouseManagerForm.password"
                                    :required="true"
                                    type="password"
                                    title="To login to the Warehouse Manager panel"
                                    input-name="password"
                                    input-label="Password"
                                />
                            </div>
                            <div
                                v-if="!warehouseManager"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="!warehouseManager"
                                    v-model:input-value="warehouseManagerForm.password_confirmation"
                                    type="password"
                                    :required="true"
                                    input-name="password_confirmation"
                                    input-label="Confirm Password"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="warehouseManagerForm.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    :required="true"
                                    title="For Warehouse Manager panel"
                                    validation-field-name="location_ids"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="warehouseManagerForm.roles"
                                    :records="roles"
                                    input-label="Roles"
                                    placeholder="Please select Roles"
                                    validation-field-name="role_ids"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.warehouse_managers.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="warehouseManager ? 'Update' : 'Submit'"
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
import { useForm } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    warehouseManager: {
        type: Object,
        default: null,
    },
    employees: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    roles: {
        type: Object,
        required: true,
    },
});

const warehouseManagerForm = useForm({
    employee_id: null,
    username: null,
    password: null,
    password_confirmation: null,
    location_ids: [],
    locations: [],
    watchEnabled: true,
    roles: [],
    role_ids: [],
});

const saveWarehouseManager = () => {
    prepareWarehouseManagerFormDetails();

    warehouseManagerForm.watchEnabled = false;
    removeLocalStorage('warehouseManager');

    if (props.warehouseManager) {
        warehouseManagerForm.put(route('admin.warehouse_managers.update', props.warehouseManager.id));
        return;
    }
    warehouseManagerForm.post(route('admin.warehouse_managers.store'));
};

const prepareWarehouseManagerFormDetails = () => {
    warehouseManagerForm.location_ids = warehouseManagerForm.locations.map((location) => {
        return location.id;
    });
    warehouseManagerForm.role_ids = warehouseManagerForm.roles.map((role) => {
        return role.id;
    });
};

onMounted(() => {
    if (props.warehouseManager) {
        removeLocalStorage('warehouseManager');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(warehouseManagerForm, JSON.parse(JSON.stringify(props.warehouseManager)));
    } else {
        setLocalStorage('warehouseManager', warehouseManagerForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.warehouseManager) {
        saveLocalStorage('warehouseManager', warehouseManagerForm);
    }
};

const clearFormData = () => {
    warehouseManagerForm.reset();
};

watch(warehouseManagerForm, () => {
    if (warehouseManagerForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
