<template>
    <Modal
        size="modal-lg"
        :show="sizeModalShow"
        @hidden="hideSizeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Add New Size
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideSizeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="sizeForm.name"
                        input-name="name"
                        input-label="Name"
                        :required="true"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="sizeForm.code"
                        input-name="code"
                        input-label="Code"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormSelectBox
                        :selected-record="sizeForm.sort_order"
                        :records="state.sizes"
                        :required="state.sizes.length ? true : false"
                        validation-field-name="sort_order"
                        input-label="Create After"
                        @update:selected-record="updateSortOrder"
                    />
                </div>
            </div>
            <div class="text-left mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="saveSize"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { reactive } from 'vue';

import axios from 'axios';

const props = defineProps({
    sizeModalShow: {
        type: Boolean,
        default: false,
    },
    records: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    sizes: props.records,
});

const emits = defineEmits([
    'update:hide-size-modal',
    'new:record',
]);

const hideSizeModal = () => {
    emits('update:hide-size-modal', false);
};

const sizeForm = useForm({
    name: null,
    code: null,
    sort_order: null,
});

const updateSortOrder = (sizeSortOrder) => {
    sizeForm.sort_order = sizeSortOrder;
};

const saveSize = () => {
    axios.post(route('admin.sizes.store_return'), sizeForm)
        .then((response) => {
            emits('new:record', response.data.size);
            hideSizeModal();
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};
</script>
