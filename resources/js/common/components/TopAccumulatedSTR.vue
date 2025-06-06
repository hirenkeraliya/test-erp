<template>
    <div class="bg-slate-200 rounded-xl p-5">
        <h1 class="col-span-12 lg:col-span-12 md:col-span-12 font-bold text-xl text-cyan-700 mb-4">
            Top 10 Accumulated STR (Current Year)
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div
                v-if="topRankingProducts.length === 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-2"
            >
                <div
                    v-for="n in 10"
                    :key="'loading-accumulated-str-content-' + n"
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
                    v-for="(topRankingProduct, index) in topRankingProducts"
                    :key="index"
                    class="cursor-pointer"
                    @click="showTopRankingProduct(topRankingProduct.id, topRankingProduct.article_number)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-lg text-slate-700">
                                {{ topRankingProduct.name }}
                            </p>
                            <p class="mt-1 text-lg font-semibold">
                                Sell Through (%):
                                {{ truncateDecimal(parseFloat(topRankingProduct.sell_through)) }}
                            </p>
                        </div>

                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTop10Products(index)"
                        >
                            <Package
                                class="w-4 h-4 lg:h-6 lg:w-6"
                                :class="getIconColorForTop10Products(index)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { truncateDecimal } from '@commonServices/helper';
import { router } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';
import { route } from 'ziggy';

import { getBackgroundColorForTop10Products, getIconColorForTop10Products } from '@commonServices/top10ProductsHelper.js';

const props = defineProps({
    topRankingProducts: {
        type: Object,
        required: true,
    },
    locationId: {
        type: Number,
        default: 0,
    },
    accumulatedSaleThroughReportUrl: {
        type: String,
        default: null,
    }
});

const showTopRankingProduct = (productId, articleNumber) => {
    if (props.accumulatedSaleThroughReportUrl) {
        router.get(route(props.accumulatedSaleThroughReportUrl, { location_id: props.locationId, product_id: productId, article_number: articleNumber }));
    }
};
</script>
