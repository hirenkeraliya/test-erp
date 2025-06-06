<template>
    <h1 class="text-xl font-medium">
        Yearly Sales (To date, Total)
    </h1>

    <div class="mt-5">
        <div
            class="grid grid-cols-3 gap-2 mt-5 intro box p-4 zoom-in font-medium"
        >
            <div class="my-auto font-bold sm:text-sm text-xs">
                Year
            </div>

            <Tippy
                tag="div"
                class="font-bold sm:text-sm text-xs sm:text-right"
                content="Formula: Sales Till Date - Sale Returns Till Date (By Each Year)"
            >
                Sales to date
                <Info
                    class="ml-1 text-primary inline-block"
                    :size="15"
                />
            </Tippy>

            <Tippy
                tag="div"
                class="font-bold sm:text-sm text-xs text-right"
                content="Formula: Sale - Sale Returns (Full Year)"
            >
                Sales
                <Info
                    class="ml-1 text-primary inline-block"
                    :size="15"
                />
            </Tippy>
        </div>

        <div
            v-if="yearlySalesData.length === 0"
        >
            <div
                v-for="n in 5"
                :key="'loading-yearly-sales-data-content-' + n"
            >
                <div class="animated-background mt-2 rounded" />
            </div>
        </div>

        <div>
            <div
                v-for="(yearlySale, index) in yearlySalesData"
                :key="index"
                class="grid grid-cols-3 gap-1 mt-3 intro box px-4 py-4"
            >
                <div class="my-auto font-bold sm:text-sm text-xs text-left">
                    {{ yearlySale.year }}
                </div>

                <div class="sm:text-sm text-xs text-right">
                    {{ currencySymbol }}{{ (currencyFormat(yearlySale.partial_sales)) }}
                </div>

                <div class="-ml-3 my-auto sm:text-sm text-xs text-right">
                    {{ currencySymbol }}{{ currencyFormat(yearlySale.full_year_sales) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { currencyFormat } from '@commonServices/helper';
import { usePage } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

defineProps({
    yearlySalesData: {
        type: Object,
        default: () => {},
    },
});
</script>
