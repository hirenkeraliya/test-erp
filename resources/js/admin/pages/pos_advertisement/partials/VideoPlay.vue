<template>
    <Modal
        size="modal-lg"
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

        <ModalBody class="p-10">
            <video
                autoplay
                controls
                class="w-full"
            >
                <source :src="videoUrl">
            </video>
            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Cancel"
                    class="w-24 mr-1"
                    @click="closeModal"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Video'
    },
    videoUrl: {
        type: String,
        required: true
    },
});

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal');
};
</script>
