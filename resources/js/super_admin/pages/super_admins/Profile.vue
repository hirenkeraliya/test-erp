<template>
    <PageTitle :title="superAdmin ? 'Edit Super Admin' : 'Add Super Admin'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Super Admins
        </h2>
    </div>

    <TabGroup>
        <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-2 items-center">
            <Tab
                class="w-full py-2 px-2 leading-none active"
                tag="button"
            >
                Profile
            </Tab>
            <Tab
                class="w-full py-2 px-2 leading-none"
                tag="button"
            >
                Google 2FA
            </Tab>
        </TabList>

        <TabPanels class="mt-3 overflow-x-auto">
            <TabPanel class="active">
                <div class="intro-y box shadow-2xl bg-white rounded-lg">
                    <div class="p-5">
                        <h3 class="font-medium text-lg">
                            Edit Profile
                        </h3>
                    </div>
                    <form @submit.prevent="saveSuperAdmin()">
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
                                    <FormInput
                                        v-model:input-value="superAdminForm.username"
                                        :required="true"
                                        input-name="username"
                                        input-label="Username"
                                    />
                                </div>
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="superAdminForm.email"
                                        :required="true"
                                        input-name="email"
                                        input-label="Email"
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
            </TabPanel>
            <TabPanel class="leading-relaxed">
                <div class="intro-y box shadow-2xl bg-white rounded-lg">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="col-span-12 sm:col-span-6">
                                <h3 class="font-medium text-lg">
                                    Enable Google 2FA
                                </h3>
                                <JSwitch
                                    v-model:is-checked="superAdminForm.enable2FA"
                                    input-label="Enable Google 2FA"
                                    validation-field-name="enable2FA"
                                    @update:is-checked="enableOrDisable2Fa(superAdminForm.enable2FA)"
                                />
                            </div>
                            <div
                                v-if="superAdminForm.enable2FA"
                                class="col-span-12 mt-5"
                            >
                                <div class="flex flex-col items-center">
                                    <div
                                        v-if="state.qrCode"
                                        v-html="state.qrCode"
                                    />
                                    <p class="mt-2 text-sm text-gray-600">
                                        {{ !state.setUpSuccess ? 'Scan this QR code with your Google Authenticator app to enable 2FA.' : 'You have successfully set up the 2FA.' }}
                                    </p>
                                    <div v-if="state.setUpSuccess">
                                        <li
                                            v-for="(recoveryCode, index) in superAdminForm.recovery_codes"
                                            :key="index"
                                        >
                                            {{ recoveryCode }}
                                        </li>
                                    </div>
                                    <div
                                        v-if="state.setUpSuccess && superAdminForm.recovery_codes"
                                        class="flex mt-6"
                                    >
                                        <p>Store this codes somewhere safe to recover your account.</p>
                                    </div>
                                </div>
                                <div
                                    v-if="!state.setUpSuccess"
                                    class="mt-4 flex flex-col items-center"
                                >
                                    <FormInput
                                        v-model:input-value="state.otp"
                                        :required="true"
                                        input-name="otp"
                                        input-label="Enter Authenticator code"
                                    />
                                    <p
                                        v-if="state.error"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ state.error }}
                                    </p>
                                    <PrimaryButton
                                        type="button"
                                        text="Verify Code"
                                        class="mt-2 w-32"
                                        @click="verifyOtp"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </TabPanel>
        </TabPanels>
    </TabGroup>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { onMounted, reactive } from 'vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import axios from 'axios';
import { Tab, TabGroup, TabList } from '@commonVendor/tab';
import { TabPanel, TabPanels } from '@commonVendor/tab';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';

const props = defineProps({
    superAdmin: {
        type: Object,
        default: null,
    }
});

const superAdminForm = useForm({
    name: null,
    username: null,
    email: null,
    enable2FA: false,
    two_factor_secret: null,
    two_factor_qr_code: null,
    recovery_codes: null,
});

const state = reactive({
    qrCode: null,
    error: null,
    otp: null,
    setUpSuccess: false,
});

const saveSuperAdmin = () => {
    superAdminForm.put(route('super_admin.super_admins.update', props.superAdmin.id));
    return;
};

const generateQrCode = () => {
    axios.post(route('super_admin.generate2fa', props.superAdmin.id), {
        ...superAdminForm,
        two_factor_secret: superAdminForm.two_factor_secret,
    }).then((response) => {
        state.qrCode = response.data.qrCodeSvg;
        superAdminForm.two_factor_secret = response.data.secret;
        superAdminForm.recovery_codes = response.data.recoveryCodes;
    }).catch((error) => {
        superAdminForm.enable2FA = false;
        showErrorNotification(error.response.data?.error);
    });
};

const disable2FA = () => {
    axios.post(route('super_admin.disable2fa', props.superAdmin.id), superAdminForm).then(() => {
        showSuccessNotification('2FA disabled successfully');
        state.qrCode = null;
        superAdminForm.two_factor_secret = null;
        superAdminForm.two_factor_qr_code = null;
        superAdminForm.recovery_codes = null;
        state.setUpSuccess = false;
    }).catch(() => {
        showErrorNotification('Failed to disable 2FA, please try again');
    });
};

const verifyOtp = () => {
    axios.post(route('super_admin.2fa.verify2fa', props.superAdmin.id), { otp: state.otp, recovery_code: superAdminForm.recovery_codes }).then((response) => {
        if (response.data.success) {
            showSuccessNotification('2FA enabled successfully');
            state.setUpSuccess = true;
        }
    }).catch((error) => {
        superAdminForm.enable2FA = false;
        showErrorNotification(error.response.data?.error);
    });
};

onMounted(() => {
    if (props.superAdmin) {
        Object.assign(superAdminForm, props.superAdmin);
        if (props.superAdmin.two_factor_secret) {
            superAdminForm.enable2FA = true;
            state.setUpSuccess = true;
        }
    }
});

const enableOrDisable2Fa = (val) => {
    if (!val) {
        disable2FA();
        return;
    }

    generateQrCode();
};
</script>
