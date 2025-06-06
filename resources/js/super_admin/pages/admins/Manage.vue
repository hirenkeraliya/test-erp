<template>
    <PageTitle :title="admin ? 'Edit Admin' : 'Add Admin'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Admins
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ admin ? 'Edit' : 'Add' }} Admin
                    </h2>
                </div>
                <form @submit.prevent="saveAdmin();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :records="companies"
                                    :selected-record="adminForm.company_id"
                                    input-label="Company"
                                    validation-field-name="company_id"
                                    :required="true"
                                    :disabled="admin ? true : false"
                                    :title="admin ? 'Company of an existing admin cannot be changed.' : ''"
                                    @update:selected-record="adminForm.company_id = parseInt($event); getEmployees($event);"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="adminForm.employee_id"
                                    :records="state.employees"
                                    input-label="Employee"
                                    validation-field-name="employee_id"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="adminForm.username"
                                    :required="true"
                                    input-name="username"
                                    input-label="Username"
                                />
                            </div>
                            <div
                                v-if="!admin"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="adminForm.password"
                                    :required="true"
                                    type="password"
                                    input-name="password"
                                    input-label="Password"
                                />
                            </div>
                            <div
                                v-if="!admin"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="adminForm.password_confirmation"
                                    type="password"
                                    :required="true"
                                    input-name="password_confirmation"
                                    input-label="Confirm Password"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="adminForm.roles"
                                    :records="roles"
                                    input-label="Roles"
                                    placeholder="Please select Roles"
                                    validation-field-name="role_ids"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('super_admin.admins.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="admin ? 'Update' : 'Submit'"
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
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

import axios from 'axios';

const state = reactive({
    employees: [],
});

const props = defineProps({
    admin: {
        type: Object,
        default: null,
    },
    companies: {
        type: Array,
        required: true,
    },
    roles: {
        type: Object,
        required: true,
    },
});

const adminForm = useForm({
    _method: props.admin ? 'put' : 'post',
    username: null,
    password: null,
    password_confirmation: null,
    company_id: null,
    employee_id: null,
    roles: [],
    role_ids: [],
});

const saveAdmin = () => {
    prepareAdminFormDetails();

    if (props.admin) {
        adminForm.post(route('super_admin.admins.update', props.admin.id));
        return;
    }
    adminForm.post(route('super_admin.admins.store'));
};

const getEmployees = (companyId) => {
    axios.get(route('super_admin.employees.get_company_employees', companyId))
        .then((response) => {
            state.employees = response.data.data;
        });
};

const prepareAdminFormDetails = () => {
    adminForm.role_ids = adminForm.roles.map((role) => {
        return role.id;
    });
};

onMounted(() => {
    const timeoutDelay = 800;
    if (props.admin) {
        getEmployees(props.admin.employee.company_id);

        setTimeout(() => {
            Object.assign(adminForm, props.admin);
            adminForm.company_id = parseInt(props.admin.employee.company_id);
        }, timeoutDelay);
    }
});
</script>
