<template>
    <PageTitle :title="superAdmin ? 'Edit Super Admin' : 'Add Super Admin'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Super Admins
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ superAdmin ? 'Edit' : 'Add' }} Super Admin
                    </h2>
                </div>
                <form @submit.prevent="saveSuperAdmin();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="superAdminForm.name"
                                    :required="true"
                                    input-name="name"
                                    input-label="Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="superAdminForm.email"
                                        :required="true"
                                        input-name="email"
                                        input-label="Email"
                                    />
                                    <Tippy
                                        v-if="superAdmin ? !superAdmin.is_email_verified && superAdminForm.email : superAdminForm.email"
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
                                    v-model:input-value="superAdminForm.username"
                                    :required="true"
                                    input-name="username"
                                    input-label="Username"
                                />
                            </div>
                            <div
                                v-if="!superAdmin"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="superAdminForm.password"
                                    :required="true"
                                    type="password"
                                    input-name="password"
                                    input-label="Password"
                                />
                            </div>
                            <div
                                v-if="!superAdmin"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="superAdminForm.password_confirmation"
                                    type="password"
                                    :required="true"
                                    input-name="password_confirmation"
                                    input-label="Confirm Password"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('super_admin.super_admins.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="superAdmin ? 'Update' : 'Submit'"
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
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    superAdmin: {
        type: Object,
        default: null,
    }
});
const superAdminForm = useForm({
    name: null,
    username: null,
    password: null,
    password_confirmation: null,
    email: null,
});

const saveSuperAdmin = () => {
    if (props.superAdmin) {
        superAdminForm.put(route('super_admin.super_admins.update', props.superAdmin.id));
        return;
    }
    superAdminForm.post(route('super_admin.super_admins.store'));
};

onMounted(() => {
    if (props.superAdmin) {
        Object.assign(superAdminForm, props.superAdmin);
    }
});

</script>
