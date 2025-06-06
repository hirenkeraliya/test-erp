<template>
    <div class="bg-slate-200 rounded-xl p-5">
        <div class="flex items-center">
            <h1 class="col-span-12 lg:col-span-12 md:col-span-12 font-bold text-xl text-teal-700 mr-5 truncate">
                {{ heading }}
            </h1>

            <BarChart2
                :size="22"
                class="w-6 h-6 sm:w-8 md:w-8 lg:w-8 xl:w-8 2xl:w-8 sm:h-8 md:h-8 lg:h-8 xl:h-8 2xl:h-8 p-1 cursor-pointer ml-auto bg-primary text-white rounded-md flex-none"
                @click="showPromoter()"
            />
        </div>

        <div
            class="col-span-12 lg:col-span-12 md:col-span-12 mt-6"
        >
            <div
                v-if="isDataFetching"
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
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2"
            >
                <div
                    v-for="(topPromoter, index) in topPromoters"
                    :key="index"
                    class="cursor-pointer"
                    @click="showPromoter(topPromoter.id)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                {{ topPromoter.name }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ truncateDecimal(topPromoter.units_sold) }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ displayAmountWithCurrencySymbol(topPromoter.amount_sold) }}
                            </p>
                        </div>
                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTopPromoter(index)"
                        >
                            <Package
                                class="w-4 h-4 lg:h-5 lg:w-5"
                                :class="getIconColorForTopPromoter(index)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { displayAmountWithCurrencySymbol, truncateDecimal } from '@commonServices/helper';
import { router } from '@inertiajs/vue3';
import { BarChart2, Package } from 'lucide-vue-next';
import { route } from 'ziggy';

const props = defineProps({
    heading: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'today',
    },
    topPromoters: {
        type: Array,
        required: true,
    },
    locationId: {
        type: Number,
        default: null,
    },
    date: {
        type: String,
        required: true,
    },
    routeUrl: {
        type: String,
        required: true,
    },
    isDataFetching: {
        type: Boolean,
        required: true,
    }
});

const showPromoter = (promoterId) => {
    router.get(route(props.routeUrl, { location_id: props.locationId, date: props.date, promoter_id: promoterId, type: props.type }));
};

const getIconColorForTopPromoter = (index) => {
    const colorClasses = [
        'text-indigo-700',
        'text-red-700',
        'text-yellow-700',
        'text-green-700',
        'text-pink-700',
        'text-sky-700',
        'text-fuchsia-700',
        'text-orange-700',
        'text-purple-700',
        'text-lime-700'
    ];

    return colorClasses[index] || 'text-indigo-700';
};

const getBackgroundColorForTopPromoter = (index) => {
    const backgroundColors = [
        'bg-indigo-50 border-indigo-100',
        'bg-red-50 border-red-100',
        'bg-yellow-50 border-yellow-100',
        'bg-green-50 border-green-100',
        'bg-pink-50 border-pink-100',
        'bg-sky-50 border-sky-100',
        'bg-fuchsia-50 border-fuchsia-100',
        'bg-orange-50 border-orange-100',
        'bg-purple-50 border-purple-100',
        'bg-lime-50 border-lime-100',
    ];

    return backgroundColors[index] || backgroundColors[0];
};
</script>
