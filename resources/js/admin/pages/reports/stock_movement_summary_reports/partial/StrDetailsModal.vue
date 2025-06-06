<template>
    <Modal
        size="modal-xl"
        :show="showStrDetailsModal"
        @hidden="hideStrDetailsModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideStrDetailsModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <JSimpleTable
                :columns="columns"
                :records="records"
                :allow-pdf-export="true"
                :allow-csv-export="true"
                :allow-excel-export="true"
                :export-pdf-records-callback="printPdfRecords"
                :export-excel-records-callback="exportExcelRecords"
                :export-csv-records-callback="exportCsvRecords"
                row-classes="border-b-2 border-slate-300 intro-x"
                table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                :allow-search="true"
                :is-data-fetching="isFetching"
            >
                <template #extra-header-data>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <JBadge
                            v-for="(badge, index) in badgeTotals"
                            :key="index"
                            :label="getShortLabel(index, badge)"
                            class="mb-1"
                        />
                    </div>
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import { route } from "ziggy";
import JBadge from "@commonComponents/JBadge.vue";

const props = defineProps({
    showStrDetailsModal: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    records: {
        type: Array,
        required: true,
    },
    isFetching: {
        type: Boolean,
        required: true,
    },
    parameters: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    exportUrl: {
        type: String,
        required: true,
    },
    printUrl: {
        type: String,
        required: true,
    },
    exportFileName: {
        type: String,
        required: true,
    },
    badgeTotals: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:hide-str-details-modal',
]);

const hideStrDetailsModal = () => {
    emits('update:hide-str-details-modal', false);
};

const printPdfRecords = () => {
    printReport(route(props.printUrl, props.parameters), props.exportPermission);
};

const exportExcelRecords = () => {
    return exportRecords(
        props.exportUrl,
        props.exportFileName + '.xlsx',
        props.parameters,
        props.exportPermission
    );
};

const exportCsvRecords = () => {
    return exportRecords(
        props.exportUrl,
        props.exportFileName + '.csv',
        props.parameters,
        props.exportPermission
    );
};

const keyShortNames = {
    goods_receive_note_in_balance: "GRN In",
    goods_receive_note_out_balance: "GRN Out",
    stock_adjustment_in_balance: "Adjustment In",
    stock_adjustment_out_balance: "Adjustment Out",
    stock_transfer_in_balance: "Transfer In",
    stock_transfer_out_balance: "Transfer Out",
    delivery_order_in_balance: "Delivery In",
    delivery_order_out_balance: "Delivery Out",
    foc_sold: "Foc Sold",
    sold: "Sold",
    return: "Return",
    balance: "Balance",
};

const getShortLabel = (key, value) => {
    return `${keyShortNames[key] || key}: ${value}`;
};
</script>
