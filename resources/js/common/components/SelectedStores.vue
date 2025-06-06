<template>
    <Modal
        size="modal-xl"
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

        <ModalBody class="sm:p-10 sm:pt-1">
            <div
                v-if="allowToClearSelectedLocations || allowToDownloadSelectedLocations"
                class="row text-right mb-2"
            >
                <OutlineDangerButton
                    v-if="allowToClearSelectedLocations"
                    type="button"
                    text="Clear Locations"
                    class="btn-sm w-30 h-10 mt-3 mr-2"
                    @click="clearRecords"
                />

                <OutlinePrimaryButton
                    v-if="allowToDownloadSelectedLocations"
                    type="button"
                    text="Download Locations"
                    class="btn-sm w-30 h-10 mt-3"
                    @click="downloadExcelRecords"
                />
            </div>

            <JSimpleTable
                :allow-search="true"
                :columns="columns"
                :records="records"
                :totals="totals"
                :allow-pagination-and-sorting="allowPaginationAndSorting"
                :allow-csv-export="allowCsvExport"
                :allow-excel-export="allowExcelExport"
                :export-csv-records-callback="exportCsvRecords"
                :export-excel-records-callback="exportExcelRecords"
                first-div-class="pb-2 sm:pb-5 mt-0 intro-y"
            >
                <template
                    v-for="column in columns"
                    :key="column.key"
                    #[column.key]="record"
                >
                    <slot
                        :name="column.key"
                        :item="record.item"
                    />
                </template>

                <template
                    #totals="data"
                >
                    <span v-if="data.item">
                        Quantities ({{ objectArrayToString(data.item,", ") }})
                    </span>
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
import { objectArrayToString } from '@commonServices/helper';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    allowPaginationAndSorting: {
        type: Boolean,
        default: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    records: {
        type: Array,
        required: true,
    },
    totals: {
        type: Object,
        default: null,
    },
    title: {
        type: String,
        default: 'Selected Locations'
    },
    allowCsvExport: {
        type: Boolean,
        default: false,
    },
    allowExcelExport: {
        type: Boolean,
        default: false,
    },
    exportCsvRecordsCallback: {
        type: Function,
        default: null,
    },
    exportExcelRecordsCallback: {
        type: Function,
        default: null,
    },
    parameters: {
        type: Object,
        default: null,
    },
    allowToClearSelectedLocations: {
        type: Boolean,
        default: false,
    },
    allowToDownloadSelectedLocations: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits([
    'close-modal',
    'clear-selected-locations',
    'download-selected-locations',
]);

const closeModal = () => {
    emits('close-modal');
};

const exportCsvRecords = () => {
    return props.exportCsvRecordsCallback();
};

const exportExcelRecords = () => {
    return props.exportExcelRecordsCallback();
};

const clearRecords = () => {
    emits('close-modal');
    emits('clear-selected-locations');
};

const downloadExcelRecords = () => {
    emits('download-selected-locations');
};
</script>
