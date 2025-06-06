<template>
    <PageTitle title="Sales By Promoters" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sales By Promoters
        </h2>
    </div>

    <div
        v-if="state.displaySalesByPromotersFilter"
        class="mt-2 px-5 py-5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1">
            <div>
                <FormCustomCheckbox
                    check-box-label="Exclude By:"
                    :records="salesFilterTypes"
                    :selected-records="state.salesFilterTypes"
                    @update:check-values="updateSalesFilterTypes"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.promoter_id"
                    :records="promoters"
                    placeholder="Please select promoter"
                    input-label="Promoter"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updatePromoterId"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.brands"
                    :records="brands"
                    placeholder="Please select brand"
                    input-label="Brand"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateBrands"
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
                <JMultiSelect
                    :selected-records="state.departments"
                    :records="departments"
                    placeholder="Please select department"
                    input-label="Departments"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateDepartments"
                />
            </div>

            <div>
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
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
        :fetch-url="route('store_manager.sales_by_promoters.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-sales-by-promoters-reports-columns"
        search-title="Search by promoter"
    >
        <template #locations="data">
            {{ data.item.locations }}
        </template>

        <template #return_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.return_amount)) }}
        </template>

        <template #gross_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.gross_amount)) }}
        </template>

        <template #discount_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.discount_amount)) }}
        </template>

        <template #tax_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.tax_amount)) }}
        </template>

        <template #net_amount="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.net_amount)) }}
        </template>

        <template #per_sales_with_staff_help="data">
            {{ data.item.per_sales_with_staff_help + '%' }}
        </template>

        <template #average_transaction_value="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.average_transaction_value)) }}
        </template>

        <template #extra-header-data="data">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="'Net: ' + displayAmountWithCurrencySymbol(data.data.total_net_sales)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    :label="'Sales: ' + displayAmountWithCurrencySymbol(data.data.total_sales)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    :label="'Unit Sold: ' + data.data.total_units_sold"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    :label="'Units Returned: ' + data.data.total_units_returned"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    :label="'Returned: ' + displayAmountWithCurrencySymbol(data.data.total_returned_amount)"
                />
            </div>

            <p
                v-if="state.isClear"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Clear"
                    class="text-sm shadow-md"
                    @click="refreshPage"
                />
            </p>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displaySalesByPromotersFilter = !state.displaySalesByPromotersFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import { displayAmountWithCurrencySymbol, numberFormat, exportRecords, currentDateTime } from '@commonServices/helper';
import JTable from '@commonComponents/JTable.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import FormCustomCheckbox from '@commonComponents/FormCustomCheckbox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import { router } from '@inertiajs/vue3';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    promoters: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    departments: {
        type: Array,
        required: true,
    },
    salesFilterTypes: {
        type: Array,
        required: true,
    },
    defaultSelected: {
        type: Array,
        required: true,
    },
    promoterGroups: {
        type: Array,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
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

const state = reactive({
    columns: [
        {
            key: 'promoter',
            isDisplay: true,
        },{
            key: 'promoter_group',
            isDisplay: true,
        }, {
            key: 'locations',
            isDisplay: true,
        }, {
            key: 'units_sold',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'units_returned',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'per_sales_with_staff_help',
            label: 'Assisted Sales (%)',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'units_per_transaction',
            label: 'UPT',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'average_transaction_value',
            label: 'ATV',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'return_amount',
            label: 'Returns',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'gross_amount',
            label: 'Gross Sales',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'discount_amount',
            label: 'Discount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'tax_amount',
            label: 'Tax',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'net_amount',
            label: 'Net',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }
    ],
    refreshTableData: Math.random(),
    displaySalesByPromotersFilter: false,
    promoterGroups: [],
    parameters: {
        promoter_id: props.dashboardFilterData.promoter_id,
        brand_ids: [],
        department_ids: [],
        date_range: props.dashboardFilterData.dateRange,
        sales_filter_types: [],
        group_ids: [],
    },
    salesFilterTypes: null,
    defaultSelected: [],
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.promoter_id = null;
    state.parameters.date_range = currentDateTime();
    state.promoterGroups = null;
    state.parameters.group_ids = [];
    resetSalesFilterTypes();
    refreshTable();
};

const resetSalesFilterTypes = () => {
    state.defaultSelected = [];
    Object.assign(state.defaultSelected, props.defaultSelected);
    updateSalesFilterTypes(state.defaultSelected);
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};
const updatePromoterId = (promoterId) => {
    state.parameters.promoter_id = parseInt(promoterId);
    refreshTable();
};

const updateBrands = (brands) => {
    state.brands = brands;

    const brandIds = brands.map((brand) => {
        return brand.id;
    });

    state.parameters.brand_ids = brandIds;
    refreshTable();
};

const updateDepartments = (departments) => {
    state.departments = departments;

    const departmentIds = departments.map((department) => {
        return department.id;
    });

    state.parameters.department_ids = departmentIds;
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

const updateSalesFilterTypes = (salesFilterTypes) => {
    state.salesFilterTypes = salesFilterTypes;

    state.parameters.sales_filter_types = salesFilterTypes;
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'sales-by-promoters/',
        'sales_by_promoters.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'sales-by-promoters/',
        'sales_by_promoters.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

if (props.defaultSelected) {
    Object.assign(state.defaultSelected, props.defaultSelected);
}

updateSalesFilterTypes(state.defaultSelected);

const refreshPage = () => {
    router.get(route('store_manager.sales_by_promoters.index'));
};

onMounted(() => {
    if (props.dashboardFilterData.promoter_id) {
        state.isClear = true;
        state.displaySalesByPromotersFilter = true;
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
