<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />
        <div class="content content--top-nav mr-5">
            <PageTitle title="Dashboard" />

            <Revenue
                :total-sales="totalSales"
                :total-units-sold="totalUnitsSold"
                :total-sales-by-location="totalSalesByLocation"
                :total-sales-by-brand="totalSalesByBrand"
                :total-sales-by-category="totalSalesByCategory"
                :total-sales-by-style="totalSalesByStyle"
                :total-sales-by-department="totalSalesByDepartment"
                :date="date"
                :brands="brands"
                :brand-id="brandId"
                :last-update="lastUpdate"
                get-revenue-url-name="admin.revenue_view"
                @update:update-date="updateDate"
                @update:get-brand-data="getBrandData"
            />

            <div class="col-span-12 overflow-x-auto intro-y bg-white rounded-xl p-6 mt-10">
                <p class="mx-auto text-xl font-medium">
                    Locations
                </p>

                <JSimpleTable
                    :columns="state.columns"
                    :records="salesData ?? []"
                    :footer-record="salesTotalData ?? []"
                    :allow-pdf-export="true"
                    :allow-csv-export="true"
                    :allow-excel-export="true"
                    :export-pdf-records-callback="downloadPdfStoreRecord"
                    :export-excel-records-callback="exportExcelRecords"
                    :export-csv-records-callback="exportCsvRecords"
                    table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                    row-classes="border-b-2 border-slate-300"
                >
                    <template #name="data">
                        <div
                            class="w-full cursor-pointer"
                            @click="getStoreWiseData(data.item.id)"
                        >
                            {{ data.item.name }} ({{ data.item.code }})
                        </div>
                    </template>

                    <template #total_sales="data">
                        <div
                            class="w-full cursor-pointer"
                            @click="getStoreWiseData(data.item.id)"
                        >
                            {{ displayAmountWithCurrencySymbol(data.item.total_sales) }}
                        </div>
                    </template>

                    <template #sales_count="data">
                        <div
                            class="w-full cursor-pointer"
                            @click="getStoreWiseData(data.item.id)"
                        >
                            {{ data.item.sales_count }}
                        </div>
                    </template>

                    <template #total_units_sold="data">
                        <div
                            class="w-full cursor-pointer"
                            @click="getStoreWiseData(data.item.id)"
                        >
                            {{ truncateDecimal(data.item.total_units_sold) }}
                        </div>
                    </template>

                    <template #unit_per_transaction="data">
                        <div
                            class="w-full cursor-pointer"
                            @click="getStoreWiseData(data.item.id)"
                        >
                            {{ truncateDecimal(data.item.unit_per_transaction) }}
                        </div>
                    </template>

                    <template #average_transaction_value="data">
                        <div
                            class="w-full cursor-pointer"
                            @click="getStoreWiseData(data.item.id)"
                        >
                            {{ displayAmountWithCurrencySymbol(data.item.average_transaction_value) }}
                        </div>
                    </template>
                </JSimpleTable>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import Revenue from '@adminPages/dashboards/Revenue.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { displayAmountWithCurrencySymbol, exportRecords, printReport, truncateDecimal } from '@commonServices/helper';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';

const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    totalSalesByLocation: {
        type: Object,
        required: true,
    },
    totalSalesByBrand: {
        type: Object,
        required: true,
    },
    totalSalesByCategory: {
        type: Object,
        required: true,
    },
    totalSalesByStyle: {
        type: Object,
        required: true,
    },
    totalSalesByDepartment: {
        type: Object,
        required: true,
    },
    salesData: {
        type: Object,
        required: true,
    },
    salesTotalData: {
        type: Object,
        default: null
    },
    totalSales: {
        type: Number,
        required: true,
    },
    totalUnitsSold: {
        type: Number,
        required: true,
    },
    date: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    brandId: {
        type: Number,
        default: 0,
    },
    lastUpdate: {
        type: String,
        required: true,
    }
});

const state = reactive({
    columns: [
        {
            key: 'name',
            bodyClass: 'border-0 border-none bg-slate-200',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            sortable: true,
            label: 'Locations (Code)',
        }, {
            key: 'total_sales',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
            label: 'Sales (' + currencySymbol.value + ')',
        }, {
            key: 'sales_count',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
            label: 'Sales (Count)',
        }, {
            key: 'total_units_sold',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
            label: 'Units Sold'
        }, {
            key: 'unit_per_transaction',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
            label: 'Unit Per Transaction'
        }, {
            key: 'average_transaction_value',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
            label: 'Average Transaction Value'
        },
    ],
    date_range: props.date,
    brand_id: props.brandId,
});

const getStoreWiseData = (locationId) => {
    router.get(route('admin.store_revenue', { location_id: locationId, date: props.date, }));
};

const updateDate = (date) => {
    state.date_range = date;
    router.get(route('admin.revenue_view', { date }));
};

const getBrandData = (brandId) => {
    router.get(route('admin.revenue_view', { date: props.date, brand_id: brandId }));
};

const downloadPdfStoreRecord = () => {
    printReport(route('admin.print_revenue_view_stores_sales', { date: state.date_range, brand_id: state.brand_id }));
};

const exportCsvRecords = () => {
    return exportRecords(
        'export-revenue-stores-sales/',
        'revenue-locations-sales.csv',
        { date: state.date_range, brand_id: state.brand_id }
    );
};

const exportExcelRecords = () => {
    return exportRecords(
        'export-revenue-stores-sales/',
        'revenue-locations-sales.xlsx',
        { date: state.date_range, brand_id: state.brand_id }
    );
};
</script>
