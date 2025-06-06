<template>
    <PageTitle :title="employee ? 'Edit Employee' : 'Add Employee'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Employees
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ employee ? 'Edit' : 'Add' }} Employee
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveEmployee();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.first_name"
                                    input-name="first_name"
                                    input-label="First Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.last_name"
                                    input-name="last_name"
                                    input-label="Last Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="employeeForm.email"
                                        input-name="email"
                                        input-label="Email"
                                    />
                                    <Tippy
                                        v-if="employee ? !employee.is_email_verified && employeeForm.email : employeeForm.email"
                                        :content="'Your email will require verification.'"
                                    >
                                        <TriangleAlert
                                            class="text-red-400 ml-2 mt-7"
                                            :size="20"
                                        />
                                    </Tippy>
                                </div>
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.mobile_number"
                                    input-name="mobile_number"
                                    input-label="Mobile Number"
                                    :required="true"
                                    placeholder="60789456123"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="employeeForm.group_id"
                                    :records="employeeGroups"
                                    input-label="Employee Group"
                                    validation-field-name="group_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="employeeForm.job_type"
                                    :records="jobTypes"
                                    input-label="Job Type"
                                    validation-field-name="job_type"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="employeeForm.designation_id"
                                    :records="designations"
                                    input-label="Designation"
                                    validation-field-name="designation_id"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.staff_id"
                                    input-name="staff_id"
                                    input-label="Staff Id"
                                    :required="true"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.address_line_1"
                                    input-name="address_line_1"
                                    input-label="Address Line 1"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.address_line_2"
                                    input-name="address_line_2"
                                    input-label="Address Line 2"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.home_contact"
                                    input-name="home_contact"
                                    input-label="Home Contact"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.city"
                                    input-name="city"
                                    input-label="City"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.area_code"
                                    input-name="area_code"
                                    input-label="Area Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="employeeForm.date_of_joining"
                                    input-label="Date of Joining"
                                    :max-date="new Date()"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.primary_contact_name"
                                    input-name="primary_contact_name"
                                    input-label="Primary Contact Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.primary_contact_phone"
                                    input-name="primary_contact_phone"
                                    input-label="Primary Contact Phone"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="employeeForm.ic_number"
                                    input-name="ic_number"
                                    input-label="IC Number"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileCropUpload
                                    v-model:input-file="employeeForm.photo"
                                    input-label="Photo (300px X 300px)"
                                    validation-field-name="photo"
                                    :max-width="300"
                                    :max-height="300"
                                    @update:input-file="uploadImage"
                                />

                                <div
                                    v-if="employeeForm.photo_url"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <img
                                        :src="employeeForm.photo_url"
                                        :alt="employeeForm.photo_url"
                                        width="100"
                                    >
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="employeeForm.status"
                                    input-label="Status"
                                    title="If this employee is assigned an Admin account, he cannot login if the status is inactive."
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.employees.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="employee ? 'Update' : 'Submit'"
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
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { router, useForm } from '@inertiajs/vue3';
import JSwitch from '@commonComponents/JSwitch.vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    employee: {
        type: Object,
        default: null,
    },
    jobTypes: {
        type: Array,
        required: true,
    },
    designations: {
        type: Array,
        default: () => [],
    },
    companyId: {
        type: Number,
        default: null,
    },
    employeeGroups: {
        type: Array,
        required: true,
    },
});

const employeeForm = useForm({
    _method: props.employee ? 'put' : 'post',
    company_id: null,
    designation_id: null,
    first_name: null,
    last_name: null,
    email: null,
    mobile_number: null,
    home_contact: null,
    address_line_1: null,
    address_line_2: null,
    city: null,
    area_code: null,
    date_of_joining: null,
    primary_contact_name: null,
    primary_contact_phone: null,
    staff_id: null,
    ic_number: null,
    job_type: null,
    status: false,
    photo: null,
    photo_url: null,
    group_id: null,
    watchEnabled: true,
});

const uploadImage = (selectedImage) => {
    employeeForm.photo_url = URL.createObjectURL(selectedImage);
};

const saveEmployee = () => {
    employeeForm.watchEnabled = false;
    removeLocalStorage('employee');

    if (props.employee) {
        employeeForm.post(route('admin.employees.update', props.employee.id));
        return;
    }

    router.post(route('admin.employees.store'), employeeForm);
};

onMounted(() => {
    if (props.employee) {
        removeLocalStorage('employee');
        Object.assign(employeeForm, props.employee);
        employeeForm.photo_url = props.employee.image_url ? props.employee.image_url : null;
        return;
    } else {
        setLocalStorage('employee', employeeForm);
    }

    employeeForm.company_id = props.companyId;
});

const checkSaveLocalStorage = () => {
    if (!props.employee) {
        saveLocalStorage('employee', employeeForm);
    }
};

const clearFormData = () => {
    employeeForm.reset();
};

watch(employeeForm, () => {
    if (employeeForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
