<template>
    <div
        class="relative mt-0 intro-y sm:mt-0 before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-xl before:mx-auto before:inset-x-0 h-[95%]"
    >
        <div class="box rounded-xl">
            <div class="intro-y">
                <h2 class="pt-3 pb-5 pl-5 text-xl font-medium text-left">
                    {{ title }}
                </h2>
            </div>

            <div
                v-if="salesDate.length === 0"
                class="grid px-5 py-0 border-t border-dashed 2xl:px-5 xl:px-5 lg:px-5 md:px-5 sm:px-5 border-slate-200"
            >
                <div>
                    <div class="animated-background !h-[300px] !rounded-xl !m-2" />
                </div>
            </div>

            <div
                v-else
                class="grid px-5 py-0 border-t border-dashed 2xl:px-5 xl:px-5 lg:px-5 md:px-5 sm:px-5 border-slate-200"
            >
                <div class="w-full pt-2 border-b pb-5">
                    <div class="mt-1 text-base text-slate-500">
                        Transaction (Count)
                    </div>

                    <div class="mt-1.5 flex items-center">
                        <Tippy
                            tag="div"
                            class="text-base font-normal leading-none 2xl:text-xl xl:text-xl lg:text-xl md:text-base sm:text-base flex items-center"
                            :content="totalReceiptInfo"
                        >
                            {{ currencyFormat(salesDate.totalSale) }}
                            <Info
                                v-if="totalReceiptInfo"
                                class="ml-1 text-primary"
                                :size="15"
                            />
                        </Tippy>
                    </div>
                </div>

                <div class="w-full pb-5 border-b pb-5">
                    <div class="mt-3 text-base text-slate-500">
                        Units Sold
                    </div>

                    <div class="mt-1.5 flex items-center">
                        <Tippy
                            tag="div"
                            class="text-base font-normal leading-none 2xl:text-xl xl:text-xl lg:text-xl md:text-base sm:text-base flex items-center"
                            :content="totalUnitSoldInfo"
                        >
                            {{ currencyFormat(salesDate.totalUnitsSold) }}
                            <Info
                                v-if="totalUnitSoldInfo"
                                class="ml-1 text-primary"
                                :size="15"
                            />
                        </Tippy>
                    </div>
                </div>

                <div class="w-full border-b pb-5">
                    <div class="mt-3 text-base text-slate-500">
                        Units Per Transaction
                    </div>

                    <div class="mt-1.5 flex items-center">
                        <Tippy
                            tag="div"
                            class="text-base font-normal leading-none 2xl:text-xl xl:text-xl lg:text-xl md:text-base sm:text-base flex items-center"
                            :content="uptInfo"
                        >
                            {{ salesDate.upt }}
                            <Info
                                v-if="uptInfo"
                                class="ml-1 text-primary"
                                :size="15"
                            />
                        </Tippy>
                    </div>
                </div>

                <div class="pb-5">
                    <div class="mt-3 text-base text-slate-500">
                        Average Transaction Value
                    </div>

                    <div class="flex items-center my-1 mb-3">
                        <Tippy
                            tag="div"
                            class="text-base font-normal leading-none 2xl:text-xl xl:text-xl lg:text-xl md:text-base sm:text-base flex items-center"
                            :content="atvInfo"
                        >
                            {{ displayAmountWithCurrencySymbol(salesDate.atv) }}
                            <Info
                                v-if="atvInfo"
                                class="ml-1 text-primary"
                                :size="15"
                            />
                        </Tippy>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { displayAmountWithCurrencySymbol, currencyFormat } from '@commonServices/helper';
import { Info } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: '',
    },
    salesDate: {
        type: Object,
        required: true,
    },
    totalReceiptInfo: {
        type: String,
        default: ''
    },
    totalUnitSoldInfo: {
        type: String,
        default: ''
    },
    uptInfo: {
        type: String,
        default: ''
    },
    atvInfo: {
        type: String,
        default: ''
    },
});
</script>
