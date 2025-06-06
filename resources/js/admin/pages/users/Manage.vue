<template>
    <PageTitle :title="user ? 'Edit User' : 'Add User'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Users
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ user ? 'Edit' : 'Add' }} Users
                    </h2>
                </div>
                <form @submit.prevent="saveUser();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="userForm.employee_id"
                                    :records="employees"
                                    input-label="Employee"
                                    :required="true"
                                    validation-field-name="employee_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="userForm.type_id"
                                    :records="userTypes"
                                    input-label="Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="userForm.username"
                                    :required="true"
                                    input-name="username"
                                    input-label="Username"
                                />
                            </div>
                            <div
                                v-if="!user"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="userForm.password"
                                    :required="true"
                                    type="password"
                                    input-name="password"
                                    input-label="Password"
                                />
                            </div>
                            <div
                                v-if="!user"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="userForm.password_confirmation"
                                    type="password"
                                    :required="true"
                                    input-name="password_confirmation"
                                    input-label="Confirm Password"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.users.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="user ? 'Update' : 'Submit'"
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
import { route } from 'ziggy';
import { onMounted } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    employees: {
        type: Object,
        required: true,
    },
    userTypes: {
        type: Array,
        required: true,
    },
});
const userForm = useForm({
    username: null,
    password: null,
    password_confirmation: null,
    employee_id: null,
    type_id: null,
});

const saveUser = () => {
    if (props.user) {
        userForm.put(route('admin.users.update', props.user.id));
        return;
    }
    userForm.post(route('admin.users.store'));
};

onMounted(() => {
    if (props.user) {
        Object.assign(userForm, props.user);
    }
});

</script>
