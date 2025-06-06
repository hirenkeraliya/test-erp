<template>
    <div class="intro-y mb-10">
        <div
            :class="['relative zoom-in', 'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0']"
        >
            <div class="p-5 box">
                <div class="flex">
                    <Banknote
                        class="text-primary"
                        width="30"
                        height="30"
                    />
                    <div class="ml-auto">
                        <SalePercentageTippy
                            :sale-percentage="salesCount.todayTotalSalePercentage"
                            content="yesterday"
                        />
                    </div>
                </div>
                <div class="mt-6 text-base text-slate-500 md:text-sm">
                    Today's Sales {{ salesCount.todayDate }}
                </div>
                <div class="mt-1 text-xl font-medium leading-8 2xl:text-3xl md:text-xl sm:text-xl">
                    {{ displayAmountWithCurrencySymbol(salesCount.todayTotalSaleData) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SalePercentageTippy from '@commonComponents/SalePercentageTippy.vue';
import { Banknote } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';

defineProps({
    salesCount: {
        type: Object,
        required: true,
    },
});
</script>
