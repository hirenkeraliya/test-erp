<template>
    <Modal
        size="modal-xs"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ headerMessage }}
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
                    <FormTextarea
                        v-model:input-value="state.remarks"
                        input-name="remarks"
                        :required="true"
                        input-label="Remarks"
                    />
                </div>
            </div>
            <div class="mt-5">
                <PrimaryButton
                    type="submit"
                    text="Submit"
                    class="w-24"
                    @click="updateStatus()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import FormTextarea from '@commonComponents/FormTextarea.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
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
    goodsReceivedNoteId: {
        type: Number,
        required: true,
    },
    routeUrl: {
        type: String,
        required: true,
    },
    headerMessage: {
        type: String,
        required: true,
    },
});

const state = reactive({
    remarks: null,
});

const emits = defineEmits([
    'close-modal',
]);

const closeModal = () => {
    emits('close-modal');
};

const updateStatus = () => {
    const closeModalDelay = 1000;
    router.put(route(props.routeUrl, props.goodsReceivedNoteId), {
        remarks: state.remarks,
    }, {
        onSuccess: () => setTimeout(() => {
            closeModal();
        }, closeModalDelay)
    });
};
</script>
