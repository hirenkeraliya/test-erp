<template>
    <div
        :class="firstDivClass"
    >
        <div class="block md:flex flex-col md:flex-row md:items-center xl:items-center">
            <select
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

            <div class="items-center mt-0 inline-block sm:flex xl:mt-0 float-left sm:float-none">
                <slot
                    name="extra-header-data"
                    :data="state.responseData"
                />

                <CustomizeColumnButton
                    v-if="allowColumnCustomization"
                    class="mr-2 float-left sm:float-none"
                    @clicked="state.isDisplayColumnManagementModal = true"
                >
                    <Tippy
                        v-if="anyColumnsHidden"
                        content="Some of the columns are hidden from this view."
                    >
                        <NoFilterSvg />
                    </Tippy>

                    <FilterSvg v-else />
                </CustomizeColumnButton>

                <PrimaryButton
                    v-if="allowPdfExport"
                    type="button"
                    text="PDF"
                    class="mr-1 sm:mr-2 float-left sm:float-none"
                    @click="downloadPdfRecord"
                />

                <ExportDropDown
                    v-if="allowCsvExport || allowExcelExport"
                    class="mr-0 sm:mr-3 float-left sm:float-none"
                    :is-export-file-in-progress="state.isExportFileInProgress"
                    :allow-csv-export="allowCsvExport"
                    :allow-excel-export="allowExcelExport"
                    @update:export-csv-file="exportCsvRecord"
                    @update:export-excel-file="exportExcelRecord"
                />
            </div>

            <div class="w-full md:w-auto">
                <input
                    type="search"
                    class="rounded-md form-control md:w-40 2xl:w-full mt-3 md:mt-0"
                    :class="searchTitle ? 'pr-10' : ''"
                    :value="state.searchText"
                    placeholder="Search..."
                    autocomplete="off"
                    @input="updateSearchText"
                >

                <Tippy
                    v-if="searchTitle"
                    class="absolute top-auto bottom-4 sm:bottom-7 md:inset-y-0 right-0 flex items-center pl-2 tooltip"
                    :content="searchTitle"
                >
                    <Info
                        class="mr-2 text-primary"
                        :size="20"
                    />
                </Tippy>
            </div>
        </div>
    </div>

    <div class="col-span-12 intro-y overflow-x-auto mb-2 sm:overflow-x-auto sm:mb-2 md:overflow-x-auto md:mb-2 lg:overflow-x-auto lg:mb-2 overflow-x">
        <table class="table -mt-2 table-report">
            <thead>
                <tr>
                    <th
                        v-for="(column, index) in filterColumns"
                        :key="'header-' + column.key"
                        :class="getHeaderClass(column, index)"
                    >
                        <div
                            v-if="!column.hidden"
                            :class="column.sortable ? 'cursor-pointer' : ''"
                            @click="column.sortable ? sortRecords(column) : ''"
                        >
                            <div :class="column.sortable ? 'text-left mr-auto inline-block' : ''">
                                {{ prepareColumnLabel(column) }}
                            </div>

                            <div
                                v-if="column.sortable"
                                class="inline-block ml-auto text-right"
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

            <tbody v-if="state.isDataFetching">
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

            <tbody v-else>
                <tr
                    v-for="(record, index) in state.records"
                    :key="'record-' + record.id"
                    :class="getTrClass(record)"
                >
                    <td
                        v-for="(column, columnIndex) in filterColumns"
                        :key="'body-' + column.key"
                        :class="getBodyClass(column, columnIndex)"
                    >
                        <slot
                            v-if="!column.hidden"
                            :name="`${column.key}`"
                            :item="record"
                            :index="state.currentPage === 1 ?
                                index :
                                (index + (state.perPage * (state.currentPage -1)))"
                        >
                            {{ record[column.key] }}
                        </slot>
                    </td>
                </tr>

                <tr
                    v-if="state.records.length === 0"
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
        </table>
    </div>

    <div class="block sm:flex flex-wrap items-center col-span-12 intro-y sm:flex-row sm:flex-nowrap">
        <JPagination
            :current-page="state.currentPage"
            :per-page="state.perPage"
            :total-records="state.totalRecords"
            @update:current-page="changeCurrentPage"
        />

        <div class="ml-auto block text-slate-500 mt-2 sm:mt-0">
            Showing {{ getFromRecordNumber() }} to {{ getToRecordNumber() }} of {{ state.totalRecords }} entries
        </div>
    </div>

    <ColumnManagement
        v-if="allowColumnCustomization"
        v-model:is-display-column-management-modal="state.isDisplayColumnManagementModal"
        :original-columns="state.customizedColumns"
        @update:column-status="updateColumnStatus"
        @update:column-fields="updateColumnFields"
    />
</template>

<script setup>
import ColumnManagement from '@commonComponents/ColumnManagement.vue';
import CustomizeColumnButton from '@commonComponents/CustomizeColumnButton.vue';
import ExportDropDown from '@commonComponents/ExportDropDown.vue';
import JPagination from '@commonComponents/JPagination.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { areColumnsCustomized, capitalize, getDisplayableColumns } from '@commonServices/helper';
import { confirmDialogBox } from '@commonServices/notifier';
import FilterSvg from '@svg/FilterSvg.vue';
import NoFilterSvg from '@svg/NoFilterSvg.vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { ChevronDown, ChevronUp, Info } from 'lucide-vue-next';
import { onUnmounted } from 'vue';
import { computed, onMounted, reactive, watch } from 'vue';

const props = defineProps({
    isModalTable: {
        type: Boolean,
        default: false,
    },
    columns: {
        type: Array,
        required: true,
    },
    fetchUrl: {
        type: String,
        default: null,
    },
    refreshTableData: {
        type: Number,
        default: null,
    },
    additionalQueryParams: {
        type: Object,
        default: null,
    },
    searchTitle: {
        type: String,
        default: null,
    },
    allowCsvExport: {
        type: Boolean,
        default: false,
    },
    allowExcelExport: {
        type: Boolean,
        default: false,
    },
    allowPdfExport: {
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
    exportPdfRecordsCallback: {
        type: Function,
        default: null,
    },
    allowColumnCustomization: {
        type: Boolean,
        default: false,
    },
    localStorageKey: {
        type: String,
        default: null,
    },
    sortDirection: {
        type: String,
        default: 'desc',
    },
    sortBy: {
        type: String,
        default: null,
    },
    firstDivClass: {
        type: String,
        default: 'py-2 sm:py-5 mt-0 sm:mt-5 intro-y',
    },
    searchValue: {
        type: String,
        default: null,
    },
    confirmationExport: {
        type: Boolean,
        default: false,
    },
    rowKeyBackgroundColor: {
        type: String,
        default: '',
    },
    rowKeyForBackgroundColor: {
        type: String,
        default: '',
    },
    tokenController: {
        type: AbortController,
        default: null,
    },
});

const emits = defineEmits([
    'update:columns',
    'get-search-text',
    'get-total-records',
    'update:get-cancel-controller',
    'get-filter-columns',
]);

const state = reactive({
    isDataFetching: false,
    perPageRecordLimits: ['10', '25', '50', '100'],
    perPage: 10,
    sortDirection: props.sortDirection,
    sortBy: props.sortBy,
    searchText: props.searchValue ?? null,
    totalRecords: null,
    records: [],
    customizedColumns: [],
    currentPage: 1,
    responseData: [],
    cancelToken: null,
    cancelController: props.tokenController ?? new AbortController(),
    isExportFileInProgress: false,
    confirmationExport: props.confirmationExport,
});

const sortRecords = (column) => {
    state.sortBy = column.key;
    state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
    state.currentPage = 1;

    fetchRecords();
};

const prepareColumnLabel = (column) => {
    if (column.label) {
        return column.label;
    }

    return capitalize(column.key);
};

const anyColumnsHidden = computed(() => {
    return state.customizedColumns.filter(function (column) {
        return column.isDisplay === false;
    }).length > 0;
});

const filterColumns = computed(() => {
    if (props.allowColumnCustomization) {
        const displayedColumns =  state.customizedColumns.filter((column) => {
            return column.isDisplay === true;
        });
        emits('get-filter-columns', displayedColumns);
        return displayedColumns;
    }

    return props.columns;
});

const getFromRecordNumber = () => {
    return (state.perPage * state.currentPage) - state.perPage + 1;
};

const getToRecordNumber = () => {
    const toRecordNumber = state.perPage * state.currentPage;

    if (toRecordNumber > state.totalRecords) {
        return state.totalRecords;
    }

    return toRecordNumber;
};

const fetchRecords = () => {
    state.isDataFetching = true;
    state.records = [];

    if (state.cancelToken !== null) {
        state.cancelController.abort();
        state.cancelController = new AbortController();
    }

    state.cancelToken = state.cancelController.signal;

    emits('update:get-cancel-controller', state.cancelController);

    axios.get(props.fetchUrl, {
        params: getParameters(),
        signal: state.cancelToken
    }).then((response) => {
        state.isDataFetching = false;
        state.records = response.data.data;
        state.totalRecords = response.data.total_records;
        state.responseData = response.data;
        emits('get-total-records', response.data.total_records);
    }).catch((error) => {
        if (error.message === 'canceled') {
            state.isDataFetching = true;
            return;
        }
        state.isDataFetching = false;
    });
};

const getParameters = () => {
    const defaultQueryParams = {
        per_page: state.perPage,
        page: state.currentPage,
        sort_direction: state.sortDirection,
        sort_by: state.sortBy,
        search_text: state.searchText,
    };

    if (props.additionalQueryParams) {
        return Object.assign(props.additionalQueryParams, defaultQueryParams);
    }

    return defaultQueryParams;
};

const changeCurrentPage = (pageNumber) => {
    state.currentPage = pageNumber;

    fetchRecords();
};

const updatePerPage = (event) => {
    state.perPage = parseInt(event.target.value);
    state.currentPage = 1;

    fetchRecords();
};

const debounceDelay = 1000;

const updateSearchText = debounce((event) => {
    state.searchText = event.target.value;
    state.currentPage = 1;
    emits('get-search-text', event.target.value);

    fetchRecords();
}, debounceDelay);

fetchRecords();

const exportCsvRecord = () => {
    if (state.confirmationExport) {
        confirmExport('csv');
        return;
    }

    state.isExportFileInProgress = true;
    props.exportCsvRecordsCallback(getParameters(), filterColumns.value).then(() => {
        state.isExportFileInProgress = false;
    });

    if (props.confirmationExport) {
        state.confirmationExport = true;
    }
};

const exportExcelRecord = async () => {
    if (state.confirmationExport) {
        confirmExport('excel');
        return;
    }

    state.isExportFileInProgress = true;
    props.exportExcelRecordsCallback(getParameters(), filterColumns.value).then(() => {
        state.isExportFileInProgress = false;
    });

    if (props.confirmationExport) {
        state.confirmationExport = true;
    }
};

const confirmExport = (type) => {
    const message = 'Are you sure you want to export ' + state.totalRecords + ' records?';
    confirmDialogBox(message, () => {
        state.confirmationExport = false;
        if (type === 'csv') {
            exportCsvRecord();
        }

        if (type === 'excel') {
            exportExcelRecord();
        }
    });
};

const downloadPdfRecord = () => {
    props.exportPdfRecordsCallback(getParameters(), filterColumns.value);
};

const updateColumnStatus = (columnKey) => {
    const columns = Object.values(state.customizedColumns);

    for (const key in columns) {
        if (columns[key].key === columnKey) {
            columns[key].isDisplay = !columns[key].isDisplay;
        }
    }
    emits('update:columns', columns);
    localStorage.setItem(props.localStorageKey, JSON.stringify(state.customizedColumns));
};

const updateColumnFields = () => {
    localStorage.setItem(props.localStorageKey, JSON.stringify(state.customizedColumns));
};

const compareAndReassignColumns = () => {
    const columns = JSON.parse(localStorage.getItem(props.localStorageKey));

    if (columns && !areColumnsCustomized(state.customizedColumns, columns)) {
        state.customizedColumns = columns;
        emits('update:columns', getDisplayableColumns(columns));

        return;
    }

    emits('update:columns', props.columns);
};

const getHeaderClass = (column, index) => {
    let cssClasses = '';

    if (index === 0) {
        cssClasses = 'sticky top-0 left-0 bg-slate-100';

        if (props.isModalTable) {
            cssClasses = 'sticky top-0 left-0 bg-white';
        }
    }

    if (column.headerClass) {
        cssClasses = cssClasses + ' ' + column.headerClass + ' whitespace-nowrap';
    } else {
        cssClasses = cssClasses + ' text-left whitespace-nowrap';
    }

    return cssClasses;
};

const getBodyClass = (column, index) => {
    let cssClasses = '';

    if (index === 0) {
        cssClasses = 'sticky top-0 left-0 box-shadow-none';
    }

    if (column.bodyClass) {
        cssClasses = cssClasses + ' ' + column.bodyClass;
    }

    return cssClasses;
};

const getTrClass = (record) => {
    if (props.rowKeyForBackgroundColor === 'match_count' && record.match_count > 0) {
        return 'intro-x' + ' ' + props.rowKeyBackgroundColor;
    }

    return 'intro-x bg-white';
};

watch(() => props.refreshTableData,
    () => {
        state.currentPage = 1;
        fetchRecords();
    }
);

onMounted(() => {
    state.customizedColumns = props.columns;
    compareAndReassignColumns();
});

onUnmounted(() => {
    state.cancelController.abort();
});
</script>
