<template>
    <PageTitle title="Product Ageing Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Product Ageing Report
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
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :age-categories="ageCategories"
        :export-permission="exportPermission"
        :product-collections="productCollections"
    />

    <ProductAgeingByMonthAndYearReport
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.productAgingReportByMonthAndYear"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :age-categories="ageCategories"
        :export-permission="exportPermission"
        :product-collections="productCollections"
    />

    <ProductAgeingBasedOnArticleNumber
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.productAgingBasedOnArticleNumber"
        :product-collections="productCollections"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :export-permission="exportPermission"
    />

    <ProductAgeingBasedOnUpc
        v-if="state.productAgeingReportType === staticProductAgeingReportTypes.productAgingBasedOnUpc"
        :product-collections="productCollections"
        :age-of-product-types="ageOfProductTypes"
        :static-age-of-product-types="staticAgeOfProductTypes"
        :export-permission="exportPermission"
    />
</template>

<script setup>
import { reactive } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import ProductAgeingBasicReport from '@storeManagerPages/reports/product_ageing_report/ProductAgeingBasicReport.vue';
import ProductAgeingBasedOnUpc from '@storeManagerPages/reports/product_ageing_report/ProductAgeingBasedOnUpc.vue';
import ProductAgeingBasedOnArticleNumber from '@storeManagerPages/reports/product_ageing_report/ProductAgeingBasedOnArticleNumber.vue';
import ProductAgeingByMonthAndYearReport from '@storeManagerPages/reports/product_ageing_report/ProductAgeingByMonthAndYearReport.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
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
});

const state = reactive({
    productAgeingReportType: null,
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
