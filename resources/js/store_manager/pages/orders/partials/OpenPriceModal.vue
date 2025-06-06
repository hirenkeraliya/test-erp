<template>
    <Modal
        size="modal-smd"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Open Price For Non Regular Products
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
            <FormInput
                v-model:input-value="state.openPrice"
                type="number"
                input-label="Open Price"
                label-class="block font-medium text-base text-primary-p3 mb-2"
            />
            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="addOpenPrice"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormInput from '@commonComponents/FormInput.vue';
import { X } from 'lucide-vue-next';
import { showErrorNotification } from '@commonServices/notifier';
import { reactive } from 'vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    productMinimumPrice: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    openPrice: 0,
});

const emits = defineEmits(['close-open-price-modal', 'add-open-price']);

const closeModal = () => {
    emits('close-open-price-modal');
};

const addOpenPrice = () => {
    if (state.openPrice < props.productMinimumPrice) {
        return showErrorNotification('Open Price Cannot Be Less Than Minimum Price');
    }

    emits('add-open-price', state.openPrice);
};
</script>
