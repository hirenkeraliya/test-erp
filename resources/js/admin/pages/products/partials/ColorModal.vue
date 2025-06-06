<template>
    <Modal
        size="modal-lg"
        :show="colorModalShow"
        @hidden="hideColorModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Add New Color
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideColorModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="colorForm.name"
                        input-name="name"
                        input-label="Name"
                        :required="true"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="colorForm.code"
                        input-name="code"
                        input-label="Code"
                    />
                </div>
            </div>
            <div class="text-left mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="saveColor"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { useForm } from '@inertiajs/vue3';

import axios from 'axios';

defineProps({
    colorModalShow: {
        type: Boolean,
        default: false,
    }
});

const emits = defineEmits([
    'update:hide-color-modal',
    'new:record',
]);

const hideColorModal = () => {
    emits('update:hide-color-modal', false);
};

const colorForm = useForm({
    name: null,
    code: null,
});

const saveColor = () => {
    axios.post(route('admin.colors.store_return'), colorForm)
        .then((response) => {
            emits('new:record', response.data.color);
            hideColorModal();
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};
</script>
