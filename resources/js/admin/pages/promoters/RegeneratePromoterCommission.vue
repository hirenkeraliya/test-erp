<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Authorization
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody
            class="p-5"
        >
            <div>
                <InfoAlert
                    color="primary"
                    class="mb-3"
                >
                    Super admin verification is required to perform this action. Please provide your credentials to proceed.
                </InfoAlert>

                <form @submit.prevent="regenerateCommission()">
                    <div class="validate-form grid grid-cols-12 gap-0 sm:gap-6">
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="superAdminForm.username"
                                input-name="username"
                                input-label="Username"
                                :required="true"
                            />
                        </div>
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="superAdminForm.password"
                                type="password"
                                input-name="password"
                                input-label="Password"
                                :required="true"
                            />
                        </div>
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="superAdminForm.reason"
                                input-name="reason"
                                input-label="Reason"
                                :required="true"
                            />
                        </div>
                    </div>

                    <div class="mt-5">
                        <SecondaryButton
                            type="button"
                            text="Cancel"
                            class="w-24 mr-1"
                            @click="closeModal"
                        />

                        <PrimaryButton
                            type="submit"
                            text="Submit"
                            class="w-24"
                        />
                    </div>
                </form>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import InfoAlert from '@commonComponents/InfoAlert.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
});

const superAdminForm = useForm({
    username: null,
    password: null,
    reason: null,
});

const regenerateCommission = () => {
    superAdminForm.post(route('admin.promoters.regenerate_commission'), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            closeModal();
        },
    });
};

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
