<template>
    <PageTitle title="Promoters Commission" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promoters Commission
        </h2>
    </div>

    <div
        v-if="state.displayPromoterCommissionFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.promoters"
                    :records="promoters"
                    placeholder="Please select Promoter"
                    input-label="Promoters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updatePromoterId"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.promoterGroups"
                    :records="promoterGroups"
                    placeholder="Please select PromoterGroup"
                    input-label="Promoter Groups"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updatePromoterGroups"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedBrands"
                    :search-records="searchBrand"
                    :multi-select="true"
                    placeholder="Please type the name of the brand to search."
                    input-label="Brand"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="selectBrands"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedDepartments"
                    :search-records="searchDepartment"
                    :multi-select="true"
                    placeholder="Please type the name of the department to search."
                    input-label="Department"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="selectDepartments"
                />
            </div>

            <div>
                <JMonthPicker
                    :input-value="state.monthYear"
                    input-label="Month"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
                />
            </div>
        </div>

        <div class="mt-3">
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
        :fetch-url="route('store_manager.promoter_commission.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPdfPromoterCommission"
        :allow-column-customization="true"
        local-storage-key="store-manager-promoter-commission-reports-columns"
        search-title="Search by promoter"
    >
        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    v-if="record.data.total_sales_amount"
                    :label="'Sales: ' + displayAmountWithCurrencySymbol(record.data.total_sales_amount)"
                    class=" mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.commission_amount"
                    :label="'Commission: ' + displayAmountWithCurrencySymbol(record.data.commission_amount)"
                    class=" mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_records"
                    :label="'Records: ' + record.data.total_records"
                />
            </div>

            <p class="text-lg font-bold mr-2 -mt-2">
                <OutlinePrimaryButton
                    text="Filters"
                    class="mt-2 text-sm shadow-md"
                    @click="state.displayPromoterCommissionFilter = !state.displayPromoterCommissionFilter"
                />
            </p>
        </template>

        <template #commission_date="data">
            {{ formatDateAsMMMYYYY(data.item.commission_date).toUpperCase() }}
        </template>
        <template #monthly_sales_target="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.monthly_sales_target)) }}
        </template>
        <template #total_sales_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(parseFloat(data.item.total_sales_amount))) }}
        </template>
        <template #commission_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormatToFourDigit(data.item.commission_amount)) }}
        </template>
        <template #info="data">
            <div class="flex justify-center items-center">
                <Info
                    class="text-cyan-400 cursor-pointer"
                    @click="showCommissionDetailsModal(data.item)"
                />
            </div>
        </template>
    </JTable>

    <PromoterCommissionDetails
        v-if="state.displayPromoterCommissionModal"
        :modal-show="state.displayPromoterCommissionModal"
        :filters="state.parameters"
        :promoter-commission="state.selectedPromoterCommission"
        :export-permission="exportPermission"
        @close-modal="closePromoterCommissionDetailsModal"
    />
</template>

<script setup>
import { displayAmountWithCurrencySymbol, numberFormat, exportRecords, formatDateAsMMMYYYY, printReport, numberFormatToFourDigit } from '@commonServices/helper';
import JTable from '@commonComponents/JTable.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMonthPicker from '@commonComponents/JMonthPicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import { Info } from 'lucide-vue-next';
import PromoterCommissionDetails from '@storeManagerPages/reports/promoter_commission/PromoterCommissionDetails.vue';
import JBadge from '@commonComponents/JBadge.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    promoters: {
        type: Array,
        required: true,
    },
    company: {
        type: Object,
        required: true,
    },
    commissionTypes: {
        type: Object,
        required: true,
    },
    promoterGroups: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const date = new Date();

const defaultMonth = {
    month: date.getMonth() - 1,
    year: date.getFullYear()
};

const state = reactive({
    columns: [
        {
            key: 'id',
            label: 'Id',
            isDisplay: true,
        }, {
            key: 'promoter',
            label: 'Promoter',
            isDisplay: true,
        }, {
            key: 'locations',
            isDisplay: true,
        }, {
            key: 'staff_id',
            label: 'Staff ID',
            isDisplay: true,
        }, {
            key: 'designation',
            label: 'Designation',
            isDisplay: true,
        }, {
            key: 'commission_date',
            isDisplay: true,
        }, {
            key: 'monthly_sales_target',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            hidden: true,
            isDisplay: true,
        }, {
            key: 'total_sales_amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            label: 'Sales',
            isDisplay: true,
        }, {
            key: 'commission_amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            label: 'Commission',
            isDisplay: true,
        },
        {
            key: 'info',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    promoters: null,
    displayPromoterCommissionModal: false,
    selectedPromoterCommission: null,
    displayPromoterCommissionFilter: false,
    selectedBrands: null,
    selectedDepartments: null,
    promoterGroups: [],
    monthYear: {},
    parameters: {
        promoter_ids: null,
        group_ids: [],
        brand_ids: null,
        department_ids: null,
        month_range: [defaultMonth.month + 1, defaultMonth.year],
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.monthYear = defaultMonth;
    state.parameters.promoter_ids = null;
    state.promoters = null;
    state.promoterGroups = null;
    state.selectedBrands = null;
    state.selectedDepartments = null;
    state.parameters.brand_ids = null;
    state.parameters.department_ids = null;
    state.parameters.month_range = [defaultMonth.month + 1, defaultMonth.year];
    state.parameters.group_ids = [];
    refreshTable();
};
const updateDate = (date) => {
    state.parameters.month_range = null;
    state.monthYear = date;

    if (date === null) {
        refreshTable();
        return;
    }

    const monthData = Object.values(state.monthYear);
    monthData[0] += 1;
    state.parameters.month_range = monthData;
    refreshTable();
};
const updatePromoterId = (promoters) => {
    state.promoters = promoters;
    const promoterIds = promoters.map((promoter) => {
        return promoter.id;
    });
    state.parameters.promoter_ids = promoterIds;
    refreshTable();
};

const updatePromoterGroups = (promoterGroups) => {
    state.promoterGroups = promoterGroups;

    const promoterGroupIds = promoterGroups.map((promoterGroup) => {
        return promoterGroup.id;
    });

    state.parameters.group_ids = promoterGroupIds;
    refreshTable();
};

const selectBrands = (selectedBrands) => {
    state.selectedBrands = selectedBrands;
    if (selectedBrands !== null) {
        state.parameters.brand_ids = selectedBrands.map(function (brand) {
            return brand.id;
        });
    }
    refreshTable();
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
    refreshTable();
};

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('store_manager.brands.get_filtered_brands'), filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('store_manager.departments.get_filtered_departments'), filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const showCommissionDetailsModal = (promoterCommission) => {
    state.displayPromoterCommissionModal = true;
    state.selectedPromoterCommission = {
        id: promoterCommission.id,
        name: promoterCommission.promoter,
        amount: promoterCommission.total_sales_amount,
        commission_amount: promoterCommission.commission_amount
    };
};

const closePromoterCommissionDetailsModal = () => {
    state.displayPromoterCommissionModal = false;
    state.selectedPromoterCommission = null;
};

const exportCsvRecords = (parameters, columns) => {
    return exportRecords(
        'export-promoter-commission/',
        'promoter_commission.csv',
        parameters,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (parameters, columns) => {
    return exportRecords(
        'export-promoter-commission/',
        'promoter_commission.xlsx',
        parameters,
        props.exportPermission,
        columns
    );
};

const exportPdfPromoterCommission = (parameters, columns) => {
    parameters['export_columns'] = columns;
    printReport(route('store_manager.promoter_commission.print_promoter_commission', parameters), props.exportPermission);
};

onMounted(() => {
    state.columns.forEach((column) => {
        if (column.key === 'monthly_sales_target') {
            column.hidden = props.company.commission_type_id === props.commissionTypes.by_department;
        }
    });

    state.parameters.month_range = [defaultMonth.month + 1, defaultMonth.year];
    state.monthYear = defaultMonth;
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
