<template>
    <PageTitle title="Product Ageing Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Product Ageing Report
        </h2>

        <h2 class="font-medium text-base">
            <div class="flex">
                <Tippy
                    content="Refresh Data"
                    class="btn btn-outline-primary"
                >
                    <button
                        :disabled="state.disableRefreshButton"
                        class="transition-opacity duration-200 ease-in-out"
                        :class="{'opacity-50 cursor-not-allowed': state.disableRefreshButton}"
                        @click="syncData"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </button>
                </Tippy>

                <p class="ml-2 text-xs">
                    <span class="text-sm font-medium">Last Update:</span><br>{{ aggregateProcessTracker.date }}
                </p>
            </div>
        </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
        <div>
            <FormSelectBox
                v-model:selected-record="state.productAgeingReportType"
                :records="productAgeingReportTypes"
                label-class="block mb-2 text-base font-medium text-primary-p3"
                input-label="Report Type"
                placeholder="Please select report type"
            />
        </div>
    </div>

    <ProductAgeingBasicReport
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.basicProductAgingReport"
        :locations="locations"
        :product-collections="productCollections"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :age-categories="ageCategories"
        :export-permission="exportPermission"
        :attributes="attributes"
    />

    <ProductAgeingByMonthAndYearReport
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.productAgingReportByMonthAndYear"
        :locations="locations"
        :product-collections="productCollections"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :age-categories="ageCategories"
        :export-permission="exportPermission"
        :attributes="attributes"
    />

    <ProductAgeingBasedOnArticleNumber
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.productAgingBasedOnArticleNumber"
        :product-collections="productCollections"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :export-permission="exportPermission"
        :attributes="attributes"
    />

    <ProductAgeingBasedOnUpc
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.productAgingBasedOnUpc"
        :product-collections="productCollections"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :export-permission="exportPermission"
        :attributes="attributes"
    />
</template>

<script setup>
import { reactive } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import ProductAgeingBasicReport from '@adminPages/reports/product_ageing_report/ProductAgeingBasicReport.vue';
import ProductAgeingBasedOnArticleNumber from '@adminPages/reports/product_ageing_report/ProductAgeingBasedOnArticleNumber.vue';
import ProductAgeingByMonthAndYearReport from '@adminPages/reports/product_ageing_report/ProductAgeingByMonthAndYearReport.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import ProductAgeingBasedOnUpc from '@adminPages/reports/product_ageing_report/ProductAgeingBasedOnUpc.vue';
import { RefreshCw } from 'lucide-vue-next';
import axios from 'axios';
import { route } from 'ziggy';
import { showSuccessNotification } from '@commonServices/notifier';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    ageOfProductTypes: {
        type: Object,
        required: true,
    },
    staticAgeOfProductTypes: {
        type: Object,
        required: true,
    },
    productAgeingReportTypes: {
        type: Object,
        required: true,
    },
    staticProductAgeingReportTypes: {
        type: Object,
        required: true,
    },
    ageCategories: {
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
    productCollections: {
        type: Array,
        required: true,
    },
    aggregateProcessTracker: {
        type: Object,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    productAgeingReportType: null,
    disableRefreshButton: false,
});

const syncData = () => {
    axios.get(route('admin.products_ageing_report.get_latest_data_sync'))
        .then((response) => {
            showSuccessNotification(response.data.message);
            state.disableRefreshButton = true;
        });
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
