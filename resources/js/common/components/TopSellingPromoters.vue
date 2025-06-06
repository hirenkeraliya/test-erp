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
                v-if="topPromoters.length === 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2"
            >
                <div
                    v-for="n in 10"
                    :key="'loading-table-content-' + n"
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
                    v-for="(topPromoter, index) in topPromoters"
                    :key="index"
                    class="cursor-pointer"
                    @click="showPromoter(topPromoter.id, topPromoter.locations)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                {{ topPromoter.name }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ truncateDecimal(topPromoter.total_amount_sold) }}
                            </p>
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
import { route } from 'ziggy';

defineProps({
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
        default: 'today',
    },
    topPromoters: {
        type: Array,
        required: true,
    }
});

const showPromoter = (id, locations) => {
    if (id) {
        router.get(route('admin.sales_by_promoters.index'), {
            promoter_id: id,
            location_id: locations,
            date_range: ['2025-01-01', '2025-12-31'],
        });
    }
};
</script>
