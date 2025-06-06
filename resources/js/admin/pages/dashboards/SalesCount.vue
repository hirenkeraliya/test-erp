<template>
    <div class="grid grid-cols-12 gap-6">
        <BusinessViewSalesAmount
            title="This Year Sales"
            tippy-title="last year"
            number-info="Formula: Current Year Sales Till Date - Current Year Sale Returns Till Date. Percentage will be displayed based on current year vs last year."
            :sale-amount="salesCount.currentYearSaleData"
            :sale-percentage="salesCount.currentYearSalePercentage"
            :is-data-fetching="salesCount.length === 0"
        />

        <BusinessViewSalesAmount
            title="Yearly Target"
            tippy-title="this year"
            number-info="Formula: new yearly sales target by increasing last year's sales with a percentage obtained from company data. Percentage will be displayed based on current year vs last year."
            :sale-amount="salesCount.yearlyTarget"
            :sale-percentage="salesCount.yearlyTargetPercentage"
            :is-data-fetching="salesCount.length === 0"
        />

        <BusinessViewSalesAmount
            title="Last Year To Date"
            tippy-title="this year to date"
            number-info="Formula: Last Year Sales Till Date - Last Year Sale Return Till Date. Percentage will be displayed based on current year vs last year."
            :sale-amount="salesCount.lastYearToDate"
            :sale-percentage="salesCount.lastYearToDatePercentage"
            :is-data-fetching="salesCount.length === 0"
        />

        <BusinessViewSalesAmount
            title="Last Year Sales"
            number-info="Formula: Last Year Sales - Last Year Sale Returns."
            tippy-title=""
            :sale-amount="salesCount.lastYearSaleData"
            :is-data-fetching="salesCount.length === 0"
        />
    </div>
</template>

<script setup>
import BusinessViewSalesAmount from '@commonComponents/BusinessViewSalesAmount.vue';

defineProps({
    salesCount: {
        type: Object,
        required: true,
    },
});
</script>
