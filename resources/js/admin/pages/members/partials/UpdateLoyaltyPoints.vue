<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4">
                    Last Manually Updated Points : {{ member.last_update_loyalty_points.point }}
                </div>

                <div class="col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4">
                    Last Manually Update Date : {{ member.last_update_loyalty_points.date }}
                </div>

                <div class="col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4">
                    Last Manually Update Reason : {{ member.last_update_loyalty_points.reason }}
                </div>
            </div>

            <form
                @submit.prevent="saveLoyaltyPoints();"
            >
                <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                        <label
                            for="loyalty_points"
                            class="font-bold"
                        >
                            Current Loyalty Points : {{ member.loyalty_points }}
                        </label>
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="loyaltyPointForm.loyalty_points"
                            type="number"
                            input-name="loyalty_points"
                            input-label="New Loyalty Points"
                            :required="true"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="loyaltyPointForm.remarks"
                            input-name="remarks"
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
                        text="Update"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { showSuccessNotification } from '@commonServices/notifier';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    member: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        default: 'Update Loyalty Points'
    },
});

const loyaltyPointForm = useForm({
    loyalty_points: null,
    remarks: null,
});

const saveLoyaltyPoints = () => {
    loyaltyPointForm.put(route('admin.members.update_loyalty_points', props.member.id), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            showSuccessNotification('Member Loyalty Point updated successfully.');
            closeModal();
        },
    });
};

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal', true);
};
</script>
