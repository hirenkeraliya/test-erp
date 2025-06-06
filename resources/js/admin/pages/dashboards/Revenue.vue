<template>
    <div class="items-center block my-auto mt-5  xl:mt-4 2xl:flex xl:block lg:block md:block sm:block intro-y">
        <div class="block sm:flex flex-wrap mr-auto justify-items-start lg:mt-1">
            <div
                class="sm:flex sm:flex-wrap ml-0 2xl:ml-2 xl:ml-0 lg:ml-0 md:ml-0 sm:ml-0 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-0 sm:mt-0"
            >
                <FormSelectBox
                    class="w-full mt-0 mr-2 2xl:w-96 md:w-full lg:w-64 sm:w-56"
                    :selected-record="brandId"
                    :records="brands"
                    :placeholder="'Please select Brand'"
                    @update:selected-record="getBrandData"
                />

                <JDatePicker
                    class="w-full mt-0 mr-2 2xl:w-96 md:w-full lg:w-64 sm:w-56"
                    :range-picker="true"
                    :input-value="date"
                    input-label="Date Range"
                    label-class="hidden"
                    @update:input-value="updateDate($event)"
                />
            </div>

            <div class="mt-6 lg:mt-0 ml-0 flex w-full mb-3 sm:ml-3 sm:w-auto">
                <div class="flex">
                    <div>
                        <div class="mt-0.5 text-slate-800">
                            Revenue
                        </div>
                        <div class="text-lg font-medium text-primary dark:text-slate-300 xl:text-xl">
                            {{ displayAmountWithCurrencySymbol(totalSales) }}
                        </div>
                    </div>

                    <div
                        class="w-px h-12 mx-4 border border-r border-dashed border-slate-400 dark:border-darkmode-300 xl:mx-5"
                    />

                    <div>
                        <div class="mt-0.5 text-slate-800">
                            Units Sold
                        </div>
                        <div class="text-lg font-medium text-primary dark:text-slate-300 xl:text-xl">
                            {{ truncateDecimal(totalUnitsSold) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex py-6 md:ml-3 lg:my-0 lg:py-0 lg:mt-1">
            <Tippy
                content="Refresh Data"
                class="btn btn-outline-primary"
                @click="refresh()"
            >
                <RefreshCw class="text-primary w-5" />
            </Tippy>
            <p class="ml-2 text-xs">
                <span class="text-sm font-medium">Last Update:</span><br>{{ lastUpdate }}
            </p>
        </div>
    </div>

    <div class="clear-both" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-0 lg:gap-10 lg:mt-3 lg:pb-3">
        <PieChart
            chart-id="by-locations"
            title-of-chart="Locations"
            :labels="!isEmpty(totalSalesByLocation.total_sales) ? totalSalesByLocation.labels : ['No data available']"
            :data="!isEmpty(totalSalesByLocation.total_sales) ? totalSalesByLocation.total_sales : [10, 20, 30, 40, 50]"
            :filters="filters"
            :background-color="!isEmpty(totalSalesByLocation.total_sales)"
            :dataset-label="'Sales('+currencySymbol+')'"
        />

        <PieChart
            chart-id="by-brands"
            title-of-chart="Brands"
            :labels="!isEmpty(totalSalesByBrand.total_sales) ? totalSalesByBrand.labels : ['No data available']"
            :data="!isEmpty(totalSalesByBrand.total_sales) ? totalSalesByBrand.total_sales : [10, 20, 30, 40, 50]"
            :background-color="!isEmpty(totalSalesByBrand.total_sales)"
            :dataset-label="'Sales('+currencySymbol+')'"
            :filters="filters"
        />

        <PieChart
            chart-id="by-categories"
            title-of-chart="Categories"
            :labels="!isEmpty(totalSalesByCategory.total_sales) ? totalSalesByCategory.labels : ['No data available']"
            :data="!isEmpty(totalSalesByCategory.total_sales) ? totalSalesByCategory.total_sales : [10, 20, 30, 40, 50]"
            :background-color="!isEmpty(totalSalesByCategory.total_sales)"
            :dataset-label="'Sales('+currencySymbol+')'"
            :filters="filters"
        />

        <PieChart
            chart-id="by-styles"
            title-of-chart="Styles"
            :labels="!isEmpty(totalSalesByStyle.total_sales) ? totalSalesByStyle.labels : ['No data available']"
            :data="!isEmpty(totalSalesByStyle.total_sales) ? totalSalesByStyle.total_sales : [10, 20, 30, 40, 50]"
            :background-color="!isEmpty(totalSalesByStyle.total_sales)"
            :dataset-label="'Sales('+currencySymbol+')'"
            :filters="filters"
        />

        <PieChart
            chart-id="by-departments"
            title-of-chart="Departments"
            :labels="!isEmpty(totalSalesByDepartment.total_sales) ? totalSalesByDepartment.labels : ['No data available']"
            :data="!isEmpty(totalSalesByDepartment.total_sales) ? totalSalesByDepartment.total_sales : [10, 20, 30, 40, 50]"
            :background-color="!isEmpty(totalSalesByDepartment.total_sales)"
            :dataset-label="'Sales('+currencySymbol+')'"
            :filters="filters"
        />
    </div>
</template>

<script setup>
import PieChart from '@commonComponents/PieChart.vue';
import { displayAmountWithCurrencySymbol, truncateDecimal } from '@commonServices/helper';
import { usePage, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { RefreshCw } from 'lucide-vue-next';
import { route } from 'ziggy';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const currencySymbol = computed(() => usePage().props.currency_symbol);

const helpStore = useHelpCenterStore();
const helpInformation = `
    <ul class='list-disc pl-5'>
        <li class='text-justify'>
            All kind of sales and returns are included to achieve this number excluding Void and cancelled layaway sale. We are taking regular, pending layaway, completed layaway, pending credit, completed credit sales, returns, and exchanges. We are showing this data based on the shift date not based on the sale date.
        </li>
    </ul>
`;

helpStore.setHelpData(helpInformation);

const props = defineProps({
    totalSales: {
        type: Number,
        required: true,
    },
    totalUnitsSold: {
        type: Number,
        required: true,
    },
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
    },
    getRevenueUrlName: {
        type: String,
        required: true,
    },
});

const filters = reactive({
    brand: { name: props.brands.find(brand => props.brandId === brand.id)?.name || 'All' },
    date: { name: props.date || null },
});

const isEmpty = (object) => {
    return Object.keys(object).length === 0;
};

const emits = defineEmits(['update:update-date', 'update:get-brand-data']);

const updateDate = (date) => {
    emits('update:update-date', date);
};

const getBrandData = (brandId) => {
    emits('update:get-brand-data', brandId);
};

const refresh = () => {
    router.get(route(props.getRevenueUrlName, { date: props.date, refresh: true }));
};
</script>
