<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Sale Target Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>
        <ModalBody class="sm:p-10">
            <div class="col-span-12 overflow-auto intro-y">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="p-5">
                            <table class="table table-striped -mt-2">
                                <tbody>
                                    <tr>
                                        <td>
                                            Name
                                        </td>
                                        <td>
                                            {{ saleTargetDetails.name }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Amount Type
                                        </td>
                                        <td>
                                            {{ saleTargetDetails.amount_type }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Target Type
                                        </td>
                                        <td>
                                            {{ saleTargetDetails.target_type }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Locations
                                        </td>
                                        <td>
                                            {{ saleTargetDetails.locations }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Promoters
                                        </td>
                                        <td>
                                            {{ saleTargetDetails.promoters }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Time Interval Type
                                        </td>
                                        <td>
                                            {{ saleTargetDetails.time_interval_type }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(timeIntervalValue, index) in saleTargetDetails.time_interval_value"
                                        :key="index"
                                    >
                                        <td
                                            class="font-medium"
                                            colspan="2"
                                        >
                                            {{ timeIntervalValue.range }} ({{ timeIntervalValue.value }})
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ModalBody>
    </Modal>
</template>
<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    saleTargetDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
