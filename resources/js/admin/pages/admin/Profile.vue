<template>
    <PageTitle title="Edit Admin" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Admin
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
                    <form @submit.prevent="saveAdmin()">
                        <div class="p-5">
                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="adminForm.username"
                                        :required="true"
                                        input-name="username"
                                        input-label="Username"
                                    />
                                </div>
                            </div>
                            <div class="mt-5">
                                <Link :href="route('admin.dashboard')">
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
            </TabPanel>
            <TabPanel class="leading-relaxed ">
                <div class="intro-y box shadow-2xl bg-white rounded-lg">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="col-span-12 sm:col-span-6">
                                <h3 class="font-medium text-lg">
                                    Enable Google 2FA
                                </h3>
                                <JSwitch
                                    v-model:is-checked="adminForm.enable2FA"
                                    input-label="Enable Google 2FA"
                                    validation-field-name="enable2FA"
                                    @update:is-checked="enableOrDisable2Fa(adminForm.enable2FA)"
                                />
                            </div>
                            <div
                                v-if="adminForm.enable2FA"
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
                                            v-for="(recoveryCode, index) in adminForm.recovery_codes"
                                            :key="index"
                                        >
                                            {{ recoveryCode }}
                                        </li>
                                    </div>
                                    <div
                                        v-if="state.setUpSuccess && adminForm.recovery_codes"
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
    admin: {
        type: Object,
        default: null,
    },
    roles: {
        type: Object,
        default: null,
    }
});

const adminForm = useForm({
    username: null,
    enable2FA: false,
    employee_id: null,
    two_factor_secret: null,
    two_factor_qr_code: null,
    roles: [],
    role_ids: [],
    recovery_codes: null,
});

const state = reactive({
    qrCode: null,
    error: null,
    otp: null,
    setUpSuccess: false,
});

const saveAdmin = () => {
    prepareAdminFormDetails();
    adminForm.put(route('admin.update', props.admin.id));
    return;
};

const prepareAdminFormDetails = () => {
    adminForm.role_ids = props.roles.map((role) => {
        return role.id;
    });
};

const generateQrCode = () => {
    axios.post(route('admin.generate2fa', props.admin.id), {
        ...adminForm,
        two_factor_secret: adminForm.two_factor_secret,
    }).then((response) => {
        state.qrCode = response.data.qrCodeSvg;
        adminForm.two_factor_secret = response.data.secret;
        adminForm.recovery_codes = response.data.recoveryCodes;
    }).catch((error) => {
        adminForm.enable2FA = false;
        showErrorNotification(error.response.data?.error);
    });
};


const disable2FA = () => {
    axios.post(route('admin.disable2fa', props.admin.id), adminForm).then(() => {
        showSuccessNotification('2FA disabled successfully');
        state.qrCode = null;
        adminForm.two_factor_secret = null;
        adminForm.two_factor_qr_code = null;
        state.setUpSuccess = false;
        adminForm.recovery_codes = null;
    }).catch(() => {
        showErrorNotification('Failed to disable 2FA, please try again');
    });
};

const verifyOtp = () => {
    axios.post(route('admin.2fa.verify2fa', props.admin.id), { otp: state.otp, recovery_code: adminForm.recovery_codes }).then((response) => {
        if (response.data.success) {
            state.setUpSuccess = true;
            showSuccessNotification('2FA enabled successfully');
        }
    }).catch((error) => {
        adminForm.enable2FA = false;
        showErrorNotification(error.response.data?.error);
    });
};

onMounted(() => {
    if (props.admin) {
        Object.assign(adminForm, props.admin);

        if (props.admin.two_factor_secret) {
            adminForm.enable2FA = true;
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
