<template>
    <PageTitle title="Activities" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Activities
        </h2>
    </div>

    <div
        v-if="state.displayActivityReportFilter"
        class="mt-2 px-5 py-5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedEmployee"
                    :search-records="searchEmployees"
                    placeholder="Employee Name to search..."
                    input-label="Employee"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEmployee"
                />
            </div>

            <div>
                <FormSelectBox
                    v-model:selected-record="state.parameters.module_type"
                    :records="modules"
                    :required="true"
                    input-label="Modules"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateModule"
                />
            </div>

            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
                />
            </div>
        </div>
        <div class="mt-3 mx-1">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.activities.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        :allow-pdf-export="true"
        :export-pdf-records-callback="exportPDFRecords"
        local-storage-key="admin-activities-reports-columns"
        search-title="Search by module, first name, or staff id"
    >
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayActivityReportFilter = !state.displayActivityReportFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { exportRecords, currentDate, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import axios from 'axios';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { ActivitiesReportHelpText } from '@commonStores/documentation';

const props = defineProps({
    modules: {
        type: Object,
        default: null,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    defaultModuleType: {
        type: Number,
        required: true,
    }
});

const state = reactive({
    columns: [{
        key: 'date',
        isDisplay: true,
    }, {
        key: 'module',
        isDisplay: true,
    }, {
        key: 'user',
        isDisplay: true,
    }, {
        key: 'event',
        isDisplay: true,
    }, {
        key: 'description',
        isDisplay: true,
    },
    ],
    refreshTableData: Math.random(),
    parameters: {
        date_range: [currentDate(), currentDate()],
        employee_id: null,
        module_type: props.defaultModuleType,
    },
    displayActivityReportFilter: false,
    selectedEmployee: null,
    modules: [],
});

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.activities.print_activities', params), props.exportPermission);
};

const updateEmployee = (selectEmployee) => {
    state.selectedEmployee = selectEmployee;
    state.parameters.employee_id = null;
    if (selectEmployee !== null) {
        state.parameters.employee_id = selectEmployee.id;
    }
    refreshTable();
};

const updateModule = (module) => {
    state.parameters.module_type = module;
    refreshTable();
};

const searchEmployees = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.get(route('admin.employees.get_filtered_employees', filterData)).then((response) => {
        componentState.records = response.data.employees;
        componentState.isLoading = false;
    });
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.employee_id = null;
    state.parameters.module_type = props.defaultModuleType;
    state.selectedEmployee = null;
    state.modules = [];
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-activities/',
        'activities.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-activities/',
        'activities.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(ActivitiesReportHelpText());
</script>
