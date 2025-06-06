<template>
    <div class="col-span-12 2xl:col-span-4 xl:col-span-6 lg:col-span-6 md:col-span-12 sm:col-span-12 intro-y">
        <div
            :class="['relative zoom-in', 'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0']"
        >
            <div class="px-5 py-3 box">
                <p
                    v-if="header"
                    class="text-xl text-center divider border-b border-dashed border-slate-200 mb-3 pb-2"
                >
                    {{ header }}
                </p>

                <div
                    v-if="isDataFetching"
                >
                    <div class="cp">
                        <div class="animated-background !h-[120px] !rounded-xl" />
                    </div>
                </div>

                <div
                    v-else
                    class="block sm:flex pb-5 -mx-5 flex-row"
                >
                    <div class="flex-1 px-5 lg:mt-0 lg:pt-0 sm:px-5">
                        <div class="flex">
                            <Banknote
                                class="text-primary"
                                width="30"
                                height="30"
                            />
                            <div class="ml-auto">
                                <SalePercentageTippy
                                    :sale-percentage="salePercentage"
                                    :percentage-indicator="percentageIndicator"
                                    :content="tippyTitle"
                                />
                            </div>
                        </div>
                        <div class="mt-6 text-base text-slate-500 md:text-sm">
                            {{ title }}
                        </div>
                        <div class="mt-1 text-lg font-medium leading-8 2xl:text-xl md:text-lg sm:text-lg">
                            <Tippy
                                :content="firstNumberInfo"
                                class="flex items-center"
                            >
                                {{ displayAmountWithCurrencySymbol(saleAmount) }}
                                <Info
                                    v-if="firstNumberInfo"
                                    class="ml-1 text-primary"
                                    :size="15"
                                />
                            </Tippy>
                        </div>
                    </div>

                    <div class="flex-1 px-5 mt-6 pt-6 border-t sm:mt-0 sm:pt-0 sm:border-t-0 sm:border-l sm:px-5">
                        <div class="flex">
                            <Banknote
                                class="text-primary"
                                width="30"
                                height="30"
                            />

                            <div class="ml-auto">
                                <SalePercentageTippy
                                    :sale-percentage="secondSalePercentage"
                                    :percentage-indicator="percentageIndicator"
                                    :content="secondTippyTitle"
                                />
                            </div>
                        </div>

                        <div class="mt-6 text-base text-slate-500 md:text-sm">
                            {{ secondTitle }}
                        </div>
                        <div class="mt-1 text-lg font-medium leading-8 2xl:text-xl md:text-lg sm:text-lg">
                            <Tippy
                                :content="secondNumberInfo"
                                class="flex items-center"
                            >
                                {{ displayAmountWithCurrencySymbol(secondSaleAmount) }}
                                <Info
                                    v-if="secondNumberInfo"
                                    class="ml-1 text-primary"
                                    :size="15"
                                />
                            </Tippy>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SalePercentageTippy from '@commonComponents/SalePercentageTippy.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { Banknote, Info } from 'lucide-vue-next';

defineProps({
    header: {
        type: String,
        default: '',
    },
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
    secondTitle: {
        type: String,
        default: '',
    },
    secondTippyTitle: {
        type: String,
        default: '',
    },
    secondSaleAmount: {
        type: Number,
        default: 0,
    },
    secondSalePercentage: {
        type: Number,
        default: 0,
    },
    firstNumberInfo: {
        type: String,
        default: ''
    },
    secondNumberInfo: {
        type: String,
        default: ''
    },
    isDataFetching: {
        type: Boolean,
        required: true,
    },
    percentageIndicator: {
        type: Boolean,
        default: true
    },
});
</script>
