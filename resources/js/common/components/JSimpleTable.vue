<template>
    <div
        v-if="allowPaginationAndSorting || allowCsvExport || allowExcelExport || allowExtraHeaderDetails || allowSearch"
        :class="firstDivClass"
    >
        <div class="inline-block md:flex flex-col md:flex-row md:items-center xl:items-center">
            <select
                v-if="allowPaginationAndSorting"
                class="w-[4.25rem] sm:w-20 mt-0 mr-1 sm:mr-auto mb-2 md:mb-0 form-select box sm:mt-0 float-left sm:float-none"
                :value="state.perPage"
                @input="updatePerPage"
            >
                <option
                    v-for="perPageRecordLimit in state.perPageRecordLimits"
                    :key="'per-page-record-limit-' + perPageRecordLimit"
                    :value="perPageRecordLimit"
                >
                    {{ perPageRecordLimit }}
                </option>
            </select>

            <div class="items-center mt-0 inline-block sm:flex xl:mt-0 float-left sm:float-none ml-auto">
                <slot name="extra-header-data" />

                <PrimaryButton
                    v-if="allowPdfExport"
                    type="button"
                    text="PDF"
                    class="ml-2 mr-4 sm:mr-3 float-left sm:float-none"
                    @click="downloadPdfRecord"
                />

                <ExportDropDown
                    v-if="allowCsvExport || allowExcelExport"
                    class="mr-0 sm:mr-3 float-left sm:float-none"
                    :allow-csv-export="allowCsvExport"
                    :is-export-file-in-progress="state.isExportFileInProgress"
                    :allow-excel-export="allowExcelExport"
                    @update:export-csv-file="exportCsvRecord"
                    @update:export-excel-file="exportExcelRecord"
                />
            </div>

            <div
                v-if="allowSearch"
                class="w-full md:w-auto"
            >
                <input
                    v-model="state.searchText"
                    type="search"
                    class="rounded-md form-control md:w-40 2xl:w-full mt-0 pr-10"
                    placeholder="Search..."
                    autocomplete="off"
                >
            </div>
        </div>
    </div>

    <div class="intro-y col-span-12 overflow-auto mb-2 sm:overflow-auto sm:mb-2 md:overflow-auto md:mb-2 lg:overflow-auto lg:mb-2">
        <table :class="tableClasses">
            <thead>
                <tr>
                    <th
                        v-for="column in columns"
                        :key="'header-' + column.key"
                        :class="column.headerClass ?? 'text-center whitespace-nowrap'"
                    >
                        <div
                            :class="getClass(column)"
                            @click="column.sortable ? sortRecords(column) : ''"
                        >
                            <div :class="column.sortable ? 'text-left mr-auto inline-block' : ''">
                                {{ prepareColumnLabel(column) }}
                            </div>

                            <div
                                v-if=" column.sortable && allowPaginationAndSorting"
                                class="text-right ml-auto inline-block"
                                :class="column.key === state.sortBy ? 'text-gray-900' : 'text-gray-400'"
                            >
                                <ChevronUp
                                    v-if="state.sortDirection === 'asc' && column.key === state.sortBy"
                                    class="w-4 h-4"
                                />

                                <ChevronDown
                                    v-else
                                    class="w-4 h-4"
                                />
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody v-if="checkIsDataFetching()">
                <tr
                    v-for="n in state.perPage"
                    :key="'loading-table-content-' + n"
                >
                    <td
                        :colspan="columns.length"
                        class="cp"
                    >
                        <div class="animated-background" />
                    </td>
                </tr>
            </tbody>

            <tbody else>
                <tr
                    v-for="(record, index) in filteredRecords"
                    :key="'record-' + record.id"
                    :class="rowClasses"
                >
                    <td
                        v-for="column in columns"
                        :key="'body-' + column.key"
                        :class="column.bodyClass ?? ''"
                    >
                        <slot
                            :name="`${column.key}`"
                            :item="record"
                            :index="state.currentPage === 1 ?
                                index :
                                (index + (state.perPage * (state.currentPage -1)))"
                        >
                            <span v-if="column.counter">
                                {{ getCounterNumber(index + 1) }}
                            </span>

                            <span
                                v-else
                                :class="column.rowSpanClass"
                            >
                                {{ record[column.key] }}
                            </span>
                        </slot>
                    </td>
                </tr>

                <tr
                    v-if="ifIsDataFetching()"
                    class="intro-x"
                >
                    <td
                        :colspan="columns.length"
                        class="w-40 text-center"
                    >
                        There are no records to show.
                    </td>
                </tr>
            </tbody>

            <tfoot
                v-if="footerRecord"
            >
                <tr
                    :key="'footer-1'"
                    :class="rowClasses"
                >
                    <td
                        v-for="column in columns"
                        :key="'footer-' + column.key"
                        :class="column.bodyClass ?? ''"
                        class="font-bold"
                    >
                        <span>
                            {{ footerRecord[column.key] }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div
        v-if="allowPaginationAndSorting"
        class="block sm:flex flex-wrap items-center col-span-12 intro-y sm:flex-row sm:flex-nowrap"
    >
        <JPagination
            :current-page="state.currentPage"
            :per-page="state.perPage"
            :total-records="state.totalRecordsLength"
            @update:current-page="changeCurrentPage"
        />

        <div class="ml-auto block text-slate-500 mt-2 sm:mt-0">
            <slot
                :name="`totals`"
                :item="totals"
            />
            Records: {{ state.totalRecordsLength }}
        </div>
    </div>
</template>

<script setup>
import JPagination from '@commonComponents/JPagination.vue';
import { computed, reactive } from 'vue';
import {
    ChevronUp,
    ChevronDown
} from 'lucide-vue-next';
import ExportDropDown from '@commonComponents/ExportDropDown.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

const props = defineProps({
    records: {
        type: Array,
        required: true,
    },
    footerRecord: {
        type: Object,
        default: null
    },
    columns: {
        type: Array,
        required: true,
    },
    allowPaginationAndSorting: {
        type: Boolean,
        default: true,
    },
    allowSearch: {
        type: Boolean,
        default: false,
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
    totals: {
        type: Object,
        default: null
    },
    rowClasses: {
        type: String,
        default: 'intro-x shadow-md'
    },
    tableClasses: {
        type: String,
        default: 'table table-report -mt-2'
    },
    allowExtraHeaderDetails: {
        type: Boolean,
        default: false
    },
    isDataFetching: {
        type: Boolean,
        default: false
    },
    firstDivClass: {
        type: String,
        default: 'py-2 sm:py-5 mt-0 sm:mt-5 intro-y'
    },
    allowPdfExport: {
        type: Boolean,
        default: false,
    },
    exportPdfRecordsCallback: {
        type: Function,
        default: null,
    },
});

const state = reactive({
    perPageRecordLimits: ['5', '10', '25', '50', '100'],
    perPage: 10,
    currentPage: 1,
    searchText: '',
    isDataFetching: props.isDataFetching,

    sortAttribute: '',
    sortDirection: '',
    totalRecordsLength: props.records.length,
    isExportFileInProgress: false,
});

const getClass = (column) => {
    if (column.headerClass) {
        return 'cursor-pointer';
    }
    if (column.sortable) {
        return 'flex cursor-pointer';
    }
    return '';
};

const changeCurrentPage = (pageNumber) => {
    state.currentPage = pageNumber;
};

const updatePerPage = (event) => {
    state.perPage = parseInt(event.target.value);
    state.currentPage = 1;
};

const filteredRecords = computed(() => {
    let records = props.records;

    records = applyFiltersOnRecords(records);

    if (props.allowPaginationAndSorting) {
        records = applySortingOnRecords(records);

        const startRecordNumber = (state.currentPage * state.perPage) - state.perPage;
        return records.slice(startRecordNumber, state.currentPage * state.perPage);
    }

    return records;
});

const sortRecords = (column) => {
    state.sortAttribute = column.key;
    state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
};

const applySortingOnRecords = (records) => {
    if (!state.sortAttribute || !state.sortDirection) {
        return records;
    }

    return records.sort((firstRecord, secondRecord) => {
        let comparison = 0;
        const descending = -1;

        if (
            Object.prototype.hasOwnProperty.call(firstRecord, state.sortAttribute) &&
                firstRecord[state.sortAttribute] < secondRecord[state.sortAttribute]
        ) {
            comparison = descending;
        }

        if (
            Object.prototype.hasOwnProperty.call(firstRecord, state.sortAttribute) &&
                firstRecord[state.sortAttribute] > secondRecord[state.sortAttribute]
        ) {
            comparison = 1;
        }

        return state.sortDirection === 'desc' ? (comparison * descending) : comparison;
    });
};

const applyFiltersOnRecords = (records) => {
    if (!state.searchText) {
        state.totalRecordsLength = props.records.length;
        return records;
    }
    state.currentPage = 1;

    const filteredRecords = records.filter((record) => {
        return JSON.stringify(record).toLowerCase().includes(state.searchText.toLowerCase());
    });

    state.totalRecordsLength = filteredRecords.length;
    return filteredRecords;
};

const prepareColumnLabel = (column) => {
    if (column.label) {
        return column.label;
    }

    return column.key.split('_')
        .map((word) => {
            return word[0].toUpperCase() + word.substr(1).toLowerCase();
        }).join(' ');
};

const exportCsvRecord = async () => {
    state.isExportFileInProgress = true;
    props.exportCsvRecordsCallback().then(() => {
        state.isExportFileInProgress = false;
    });
};
const exportExcelRecord = async () => {
    state.isExportFileInProgress = true;
    props.exportExcelRecordsCallback().then(() => {
        state.isExportFileInProgress = false;
    });
};

const getCounterNumber = (index) => {
    return index + (state.perPage * (state.currentPage - 1));
};

const checkIsDataFetching = () => {
    if (!props.isDataFetching) {
        return false;
    }

    return Object.keys(props.records).length === 0;
};

const ifIsDataFetching = () => {
    if (props.isDataFetching) {
        return false;
    }

    return props.records.length === 0;
};

const downloadPdfRecord = () => {
    props.exportPdfRecordsCallback();
};
</script>
