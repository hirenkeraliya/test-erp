<template>
    <PageTitle title="Consignment Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Consignment Report
        </h2>
    </div>

    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x products-report-filters"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    input-label="Date Range"
                    @update:input-value="updateDate($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.consignment_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        :allow-column-customization="true"
        local-storage-key="admin-consignment-reports-columns"
        search-title="Search by product"
    >
        <template #categories="data">
            <span v-if="data.item.categories.length">
                <span
                    v-for="(category, index) in data.item.categories"
                    :key="index"
                    class="inline-block"
                >
                    {{ category }}

                    <ChevronRight
                        v-if="index != data.item.categories.length - 1"
                        class="inline-block w-4 h-4 form-check text-slate-400"
                    />
                </span>
            </span>
            <span v-else>
                N/A
            </span>
        </template>
        <template
            v-if="pageProps.product_variant"
            #attributes="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.attributes"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>

        <template
            v-if="!pageProps.product_variant"
            #color="record"
        >
            {{ record.item.color }}
        </template>

        <template
            v-if="!pageProps.product_variant"
            #size="record"
        >
            {{ record.item.size }}
        </template>
        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    v-if="record.data.total_units_sold"
                    :label="'Units Sold: ' + record.data.total_units_sold"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    v-if="record.data.total_sales"
                    :label="'Sales: ' + displayAmountWithCurrencySymbol(record.data.total_sales)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    v-if="record.data.total_commission"
                    :label="'Commission: ' + displayAmountWithCurrencySymbol(record.data.total_commission)"
                    class="mb-1 lg:mb-1 xl:mb-0"
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
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
        </template>

        <template #price="data">
            {{ displayAmountWithCurrencySymbol(data.item.price) }}
        </template>

        <template #total="data">
            {{ displayAmountWithCurrencySymbol(data.item.total) }}
        </template>

        <template #commission="data">
            {{ displayAmountWithCurrencySymbol(data.item.commission) }}
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { ChevronRight } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime, printReport } from '@commonServices/helper';
import { reactive, computed } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JBadge from '@commonComponents/JBadge.vue';
import { router, usePage } from '@inertiajs/vue3';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    helpCenterMessages: {
        type: String,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
});
const state = reactive({
    columns: [
        {
            key: 'product',
            isDisplay: true,
        },
        {
            key: 'upc',
            isDisplay: true,
        },
        {
            key: 'article_number',
            isDisplay: true,
        },
        {
            key: 'vendor',
            isDisplay: true,
        },
        {
            key: 'categories',
            label: 'Category',
            isDisplay: true,
        },
        {
            key: 'brand',
            isDisplay: true,
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'attributes',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                    isDisplay: true,
                },
            ]
            : [
                {
                    key: 'color',
                    isDisplay: true,
                },
                {
                    key: 'size',
                    isDisplay: true,
                },
            ]),
        {
            key: 'unit_sold',
            isDisplay: true,
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'price',
            isDisplay: true,
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'total',
            isDisplay: true,
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'commission',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],
    refreshTableData: Math.random(),
    isClear: false,
    parameters: {
        date_range: currentDateTime(),
    },
});
const refreshTable = () => {
    state.refreshTableData = Math.random();
};
const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    refreshTable();
};
const refreshPage = () => {
    router.get(route('admin.consignments_report.index'));
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-consignment-report/',
        'consignment_report.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-consignment-report/',
        'consignment_report.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.consignment_report.print_consignment_report', params), props.exportPermission);
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
