<template>
    <Modal
        size="modal-xs"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Received Date
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
                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                    <JDatePicker
                        v-model:input-value="state.received_date"
                        :max-date="new Date()"
                        input-label="Received Date"
                        validation-field-name="received_at"
                    />
                </div>
            </div>
            <div class="mt-5">
                <PrimaryButton
                    type="submit"
                    text="Submit"
                    class="w-24"
                    @click="updateReceivedDate()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JDatePicker from '@commonComponents/JDatePicker.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { currentDate } from '@commonServices/helper';
import { showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import { router } from '@inertiajs/vue3';
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    stockTransferId: {
        type: Number,
        required: true,
    },
    routeUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    received_date: currentDate(),
});

const emits = defineEmits([
    'close-modal',
]);

const closeModal = () => {
    emits('close-modal');
};

const updateReceivedDate = () => {
    const timeoutDuration = 1000;
    router.post(route(props.routeUrl, props.stockTransferId), {
        received_date: state.received_date
    }, {
        onSuccess: () => setTimeout(() => {
            closeModal();
            showSuccessNotification('Status changed successfully.');
        }, timeoutDuration),
        onError: (error) => showErrorNotification(error.received_date),
    });
};
</script>
