<template>
    <PageTitle title="Integrations" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Integrations
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.integrations.create')">
                <PrimaryButton
                    text="Add New Integration"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('super_admin.integrations.fetch')"
        :columns="state.columns"
    >
        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="updateStatus(data.item.id, $event)"
                />
            </div>
        </template>
        <template #action="data">
            <div class="flex justify-center items-center">
                <div
                    class="flex items-center mr-3 cursor-pointer"
                    @click="refreshAccessToken(data.item.id)"
                >
                    <RefreshCcw class="w-4 h-4 mr-2" />
                    Refresh Token
                </div>

                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.integrations.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>

    <div>
        <Modal
            v-if="state.displayAccessTokenModal"
            size="modal-lg"
            :show="state.displayAccessTokenModal"
            @hidden="hideAccessTokenModal"
        >
            <ModalHeader>
                <h2 class="font-medium text-base mr-auto pr-8">
                    Refreshed Access Token
                </h2>

                <a
                    class="absolute right-0 top-0 mt-2 mr-3"
                    href="javascript:;"
                    @click="hideAccessTokenModal"
                >
                    <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
                </a>
            </ModalHeader>

            <ModalBody class="py-5 sm:p-10 text-left">
                <InfoAlert
                    color="danger"
                    class="ml-0"
                >
                    Warning: Below is your new access token. Please ensure it remains secure and do not share it with anyone.
                </InfoAlert>

                <div class="mt-4 font-semibold">
                    <p>Token: {{ state.refreshedAccessToken }} </p>
                </div>
            </ModalBody>
        </Modal>
    </div>

    <div>
        <Modal
            v-if="state.verifyUserAuthorizationModal"
            size="modal-lg"
            :show="state.verifyUserAuthorizationModal"
            @hidden="hideVerifyUserAuthorizationModal"
        >
            <ModalHeader>
                <h2 class="font-medium text-base mr-auto pr-8">
                    Verify User
                </h2>

                <a
                    class="absolute right-0 top-0 mt-2 mr-3"
                    href="javascript:;"
                    @click="hideVerifyUserAuthorizationModal"
                >
                    <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
                </a>
            </ModalHeader>

            <ModalBody class="py-5 sm:p-10 text-left">
                <InfoAlert
                    color="primary"
                    class="mb-3"
                >
                    Super admin verification is required to perform this action. Please provide your credentials to proceed.
                </InfoAlert>

                <form @submit.prevent="verifyUserAuthorization()">
                    <div class="validate-form grid grid-cols-12 gap-0 sm:gap-6">
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                            <FormInput
                                v-model:input-value="superAdminForm.username"
                                input-name="username"
                                input-label="Username"
                                :required="true"
                            />
                        </div>
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                            <FormInput
                                v-model:input-value="superAdminForm.password"
                                type="password"
                                input-name="password"
                                input-label="Password"
                                :required="true"
                            />
                        </div>
                    </div>

                    <div class="mt-5">
                        <SecondaryButton
                            type="button"
                            text="Cancel"
                            class="w-24 mr-1"
                            @click="hideVerifyUserAuthorizationModal"
                        />

                        <PrimaryButton
                            type="submit"
                            text="Submit"
                            class="w-24"
                        />
                    </div>
                </form>
            </ModalBody>
        </Modal>
    </div>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, RefreshCcw, X } from 'lucide-vue-next';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { router, useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'name',
        }, {
            key: 'connection_type',
            sortable: true
        }, {
            key: 'url',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            sortable: false
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        },
    ],
    displayAccessTokenModal: false,
    verifyUserAuthorizationModal: false,
    refreshedAccessToken: null,
    connectionTypeName: null,
    integrationId: null,
});

const superAdminForm = useForm({
    username: null,
    password: null,
});

const refreshAccessToken = (integrationId) => {
    state.integrationId = integrationId;
    confirmDialogBox('Are you sure you want to refresh the access token?', () => {
        state.verifyUserAuthorizationModal = true;
    });
};

const verifyUserAuthorization = () => {
    axios.post(route('super_admin.integrations.refresh_access_token', state.integrationId), superAdminForm)
        .then((response) => {
            state.verifyUserAuthorizationModal = false;
            state.displayAccessTokenModal = true;
            state.refreshedAccessToken = response.data.access_token;
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const hideAccessTokenModal = () => {
    state.displayAccessTokenModal = false;
    state.refreshedAccessToken = null;
};

const hideVerifyUserAuthorizationModal = () => {
    state.verifyUserAuthorizationModal = false;
};

const updateStatus = (integrationId, status) => {
    router.post(route('super_admin.integrations.update_status', [integrationId, status ? 1 : 0]));
};
</script>
