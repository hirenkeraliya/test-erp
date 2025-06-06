<template>
    <div class="bg-slate-200 rounded-xl p-5">
        <h1
            class="col-span-12 lg:col-span-12 md:col-span-12 font-bold text-xl mb-4"
            :class="titleColor"
        >
            {{ title }} ({{ type }})
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div
                v-if="props.worstSellingLocations.length === 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-2"
            >
                <div
                    v-for="n in 10"
                    :key="'loading-product-content-' + n"
                >
                    <div>
                        <div class="animated-background !h-[136.5px] !rounded-xl !p-0" />
                    </div>
                </div>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-2"
            >
                <div
                    v-for="(worstSellingLocation, index) in worstSellingLocations"
                    :key="index"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-lg text-slate-700">
                                {{ worstSellingLocation.name }}
                            </p>

                            <Tippy
                                tag="p"
                                class="mt-1 text-lg font-semibold flex items-center"
                                :content="'Formula: Total Sales = Sales - Returns'"
                            >
                                Sales:
                                {{ displayAmountWithCurrencySymbol(worstSellingLocation.total_sales) }}
                                <Info
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

import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { Info } from 'lucide-vue-next';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    titleColor: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        required: true,
    },
    worstSellingLocations: {
        type: Object,
        required: true,
    },
});
</script>
