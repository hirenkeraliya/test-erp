<template>
    <Modal
        size="modal-lg"
        :show="regionModalShow"
        @hidden="hideRegionModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Add New Region
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideRegionModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="regionForm.name"
                        input-name="name"
                        input-label="Name"
                        :required="true"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="regionForm.code"
                        input-name="code"
                        input-label="Code"
                    />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="regionForm.manager_name"
                        input-name="manager_name"
                        input-label="Manager Name"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="regionForm.manager_email"
                        input-name="manager_email"
                        input-label="Manager Email"
                    />
                </div>
            </div>

            <div class="text-left mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="saveRegion"
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
    regionModalShow: {
        type: Boolean,
        default: false,
    }
});

const emits = defineEmits([
    'update:hide-region-modal',
    'new:record',
]);

const hideRegionModal = () => {
    emits('update:hide-region-modal', false);
};

const regionForm = useForm({
    name: null,
    code: null,
    manager_name: null,
    manager_email: null,
});

const saveRegion = () => {
    axios.post(route('admin.regions.store_from_location'), regionForm)
        .then((response) => {
            emits('new:record', response.data.region);
            hideRegionModal();
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};
</script>
