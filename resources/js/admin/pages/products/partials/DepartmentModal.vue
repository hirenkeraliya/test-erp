<template>
    <Modal
        size="modal-lg"
        :show="departmentModalShow"
        @hidden="hideDepartmentModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Add New Department
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideDepartmentModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="departmentForm.name"
                        input-name="name"
                        input-label="Name"
                        :required="true"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                    <FormInput
                        v-model:input-value="departmentForm.code"
                        input-name="code"
                        input-label="Code"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                    <div class="mt-3">
                        <Tabs
                            :records="state.discountTypes"
                            :selected-record="departmentForm.discount_type"
                            :required="true"
                            input-label="Discount Type"
                            return-selected-record="id"
                            class="max-w-[100%!important]"
                            @update:selected-record="updateDiscountType"
                        >
                            <TabPanel
                                :v-if="departmentForm.discount_type === discountTypes.percentage"
                            >
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                    <FormInput
                                        v-if="company.commission_type_id === commissionTypes.by_department"
                                        v-model:input-value="departmentForm.commission_percentage"
                                        type="number"
                                        input-name="commission_percentage"
                                        input-label="Promoter Commission Percentage"
                                        input-group-suffix="%"
                                        title="Promoter will receive this percent of item amount as commission."
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel
                                :v-if="departmentForm.discount_type === discountTypes.flat"
                            >
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                    <FormInput
                                        v-if="company.commission_type_id === commissionTypes.by_department"
                                        v-model:input-value="departmentForm.flat_commission"
                                        type="number"
                                        input-name="flat_commission"
                                        input-label="Promoter Flat Commission"
                                        :input-group-prefix="currencySymbol"
                                        title="Promoter will receive this flat amount of item amount as commission."
                                    />
                                </div>
                            </TabPanel>
                        </Tabs>
                    </div>
                </div>
            </div>

            <div class="text-left mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="saveDepartment"
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
import Tabs from '@commonComponents/Tabs.vue';
import { TabPanel } from '@headlessui/vue';
import { reactive } from 'vue';

import axios from 'axios';

const props = defineProps({
    departmentModalShow: {
        type: Boolean,
        default: false,
    },
    discountTypes: {
        type: Array,
        required: true,
    },
    company: {
        type: Object,
        default: null,
    },
    commissionTypes: {
        type: Object,
        default: null
    },
});

const state = reactive({
    discountTypes: [
        { id: props.discountTypes.percentage, name: 'Percentage' },
        { id: props.discountTypes.flat, name: 'Flat' },
    ],
});

const emits = defineEmits([
    'update:hide-department-modal',
    'new:record',
]);

const hideDepartmentModal = () => {
    emits('update:hide-department-modal', false);
};

const departmentForm = useForm({
    name: null,
    code: null,
    commission_percentage: null,
    flat_commission: null,
    discount_type: props.discountTypes.percentage,
});

const updateDiscountType = (discountType) => {
    departmentForm.discount_type = discountType;
};

const saveDepartment = () => {
    axios.post(route('admin.departments.store_return'), departmentForm)
        .then((response) => {
            emits('new:record', response.data.department);
            hideDepartmentModal();
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};
</script>
