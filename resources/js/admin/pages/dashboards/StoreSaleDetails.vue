<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ regionName }} Locations
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody
            class="p-5 sm:p-10 text-center"
        >
            <JSimpleTable
                :columns="state.columns"
                :records="sales"
            >
                <template #total_sales="data">
                    {{ displayAmountWithCurrencySymbol(data.item.total_sales) }}
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    regionName: {
        type: String,
        default: '',
    },
    sales: {
        type: Object,
        default: () => {},
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'total_sales',
            label: 'Sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }
    ],
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
