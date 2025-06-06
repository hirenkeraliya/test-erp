<template>
    <div class="col-span-12 2xl:col-span-6 xl:col-span-6 lg:col-span-6 md:col-span-6 sm:col-span-12 intro-y">
        <div
            :class="['relative zoom-in', 'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0']"
        >
            <div v-if="isDataFetching">
                <div class="animated-background !h-[148px] !rounded-xl" />
            </div>

            <div
                v-else
                class="p-5 box"
            >
                <div class="flex">
                    <Banknote
                        class="text-primary"
                        width="30"
                        height="30"
                    />
                    <div class="ml-auto">
                        <SalePercentageTippy
                            v-if="tippyTitle"
                            :sale-percentage="salePercentage"
                            :content="tippyTitle"
                        />
                    </div>
                </div>
                <div class="mt-6 text-base text-slate-500 md:text-sm">
                    {{ title }}
                </div>
                <Tippy
                    tag="div"
                    class="mt-1 text-lg font-medium leading-8 2xl:text-2xl md:text-lg sm:text-lg flex items-center"
                    :content="numberInfo"
                >
                    {{ displayAmountWithCurrencySymbol(saleAmount) }}
                    <Info
                        v-if="numberInfo"
                        class="ml-1 text-primary"
                        :size="15"
                    />
                </Tippy>
            </div>
        </div>
    </div>
</template>

<script setup>
import SalePercentageTippy from '@commonComponents/SalePercentageTippy.vue';
import { Banknote, Info } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';

defineProps({
    title: {
        type: String,
        default: '',
    },
    tippyTitle: {
        type: String,
        default: '',
    },
    saleAmount: {
        type: Number,
        default: 0,
    },
    salePercentage: {
        type: Number,
        default: 0,
    },
    numberInfo: {
        type: String,
        default: '',
    },
    isDataFetching: {
        type: Boolean,
        required: true,
    },
});
</script>
