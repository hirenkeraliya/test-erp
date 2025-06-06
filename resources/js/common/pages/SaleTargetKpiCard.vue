<template>
    <div
        v-if="state.isDataFetching"
    >
        <div class="cp">
            <div class="animated-background !h-[550px] !rounded-xl" />
        </div>
    </div>
    <div
        v-else
        class="bg-white rounded-lg shadow-md p-4 w-full transition duration-300 ease-in-out hover:shadow-xl relative overflow-hidden"
    >
        <div v-if="hasData || hasPreviousData">
            <div class="absolute top-0 left-0 w-full h-1" />

            <div class="flex flex-col space-y-2">
                <h2 class="text-xl font-medium capitalize text-center">
                    {{ company }}
                </h2>

                <JMultiSelect
                    v-if="filter.length > 0"
                    class="w-full mb-4 2xl:w-96 md:w-72 sm:w-60"
                    :selected-records="state.filterIds[typeId]"
                    :records="filter"
                    :input-label="label"
                    :placeholder="'Please select'"
                    @update:selected-records="updateFilterIds"
                />

                <JMultiSelect
                    v-if="locations.length > 0"
                    class="w-full mb-4 2xl:w-96 md:w-72 sm:w-60"
                    :selected-records="state.locationIds[typeId]"
                    :records="locations"
                    input-label="Locations"
                    :placeholder="'Please select locations'"
                    @update:selected-records="updateLocationIds"
                />

                <JMultiSelect
                    v-if="promoters.length > 0"
                    class="w-full mb-4 2xl:w-96 md:w-72 sm:w-60"
                    :selected-records="state.promoterIds[typeId]"
                    :records="promoters"
                    input-label="Promoters"
                    :placeholder="'Please select promoters'"
                    @update:selected-records="updatePromoterIds"
                />
                <div class="flex justify-between items-center">
                    <div class="flex space-x-8 items-center">
                        <Tippy
                            v-if="previousTrendPercentage"
                            class="tooltip"
                            content="Formula: (Current Sale - Previous Sale) / Previous Sale * 100"
                        >
                            <TrendIndicator
                                :value="previousTrend"
                                :percentage="previousTrendPercentage"
                                label="Prev"
                            />
                        </Tippy>

                        <Tippy
                            v-if="currentTrendPercentage"
                            class="tooltip"
                            content="Formula: Current Sale / Current Target * 100"
                        >
                            <TrendIndicator
                                :value="currentTrend"
                                :percentage="currentTrendPercentage"
                                label="Curr"
                            />
                        </Tippy>
                    </div>

                    <div>
                        <button
                            class="text-gray-500 text-primary hover:text-slate-600 transition-colors duration-200 mr-3"
                            @click.stop="redirectToSaleAchievementReport"
                        >
                            <Table />
                        </button>

                        <button
                            class="text-gray-500 text-primary hover:text-slate-600 transition-colors duration-200"
                            @click.stop="showCharts"
                        >
                            <ChartArea />
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 mt-3">
                <div>
                    <h3 class="text-lg font-medium text-gray-800 mb-3">
                        Current Period
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">
                                Target
                            </p>
                            <p class="text-lg font-bold text-gray-800">
                                {{ formatLabelForDashboardWithCurrencySymbol(target) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1 text-right">
                                Achieved
                            </p>
                            <p
                                class="text-lg font-bold text-right"
                                :class="{ 'text-success': achieved >= target, 'text-danger': achieved < target }"
                            >
                                {{ formatLabelForDashboardWithCurrencySymbol(achieved) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="h-[85px]">
                    <h3 class="text-lg font-medium text-gray-800 mb-3">
                        Previous Period
                    </h3>
                    <div
                        v-if="hasPreviousData"
                        class="grid grid-cols-2 gap-4"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">
                                Previous Target
                            </p>
                            <p class="text-lg font-bold text-gray-700">
                                {{ formatLabelForDashboardWithCurrencySymbol(previousTarget) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1 text-right">
                                Achieved
                            </p>
                            <p
                                class="text-lg font-bold text-right"
                                :class="{ 'text-success': previousAchieved >= previousTarget, 'text-danger': previousAchieved < previousTarget }"
                            >
                                {{ formatLabelForDashboardWithCurrencySymbol(previousAchieved) }}
                            </p>
                        </div>
                    </div>

                    <div v-else>
                        <p class="inline-block text-md font-medium capitalize text-gray-500 items-center text-center w-full">
                            No previous period data available
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4 mt-3">
                <div class="flex justify-between items-center mb-1">
                    <p class="text-sm font-medium text-gray-500">
                        <Tippy
                            tag="div"
                            class="text-sm font-medium leading-8 flex items-center"
                            content="Formula: The smaller of the two values ((Achieved Amount / Target Amount) * 100) or 100 will be displayed."
                        >
                            Progress
                            <Info
                                class="ml-1 text-primary"
                                :size="15"
                            />
                        </Tippy>
                    </p>
                    <p class="text-sm font-semibold text-gray-700">
                        {{ progressPercentage }}%
                    </p>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="h-2 rounded-full transition-all duration-500 ease-out"
                        :class="{ 'bg-gradient-to-r from-green-200 to-green-600': progressPercentage >= 100, 'bg-gradient-to-r from-red-200 to-red-600': progressPercentage < 100 }"
                        :style="{ width: `${progressPercentage}%` }"
                    />
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex flex-col justify-center items-center h-full"
        >
            <a
                class="flex flex-col items-center justify-center w-full h-full text-center no-underline"
                :class="props.saleTargetId ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                :disabled="props.saleTargetId ? 'disabled' : null"
                :href="props.saleTargetId ? 'javascript:void(0);' : route('admin.sale_targets.create', { target_type: props.company, time_interval_selection: props.currentPeriod })"
            >
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Setup {{ company }} Details</h2>
                <span class="text-6xl text-gray-400 hover:text-gray-600 transition-colors duration-300 ease-in-out">
                    <Plus size="100" />
                </span>
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import TrendIndicator from '@commonPages/TrendIndicator.vue';
import { Plus, ChartArea, Table } from 'lucide-vue-next';
import { route } from 'ziggy';
import { formatLabelForDashboardWithCurrencySymbol } from '@commonServices/helper';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import { Info } from 'lucide-vue-next';
const emits = defineEmits([
    'showCharts',
    'update:card-data'
]);

const props = defineProps({
    company: {
        type: String,
        required: true
    },
    target: {
        type: Number,
        required: true
    },
    achieved: {
        type: Number,
        required: true
    },
    currentPeriod: {
        type: String,
        required: true
    },
    previousTarget: {
        type: Number,
        required: true
    },
    previousAchieved: {
        type: Number,
        required: true
    },
    filter: {
        type: Array,
        required: true
    },
    locations: {
        type: Array,
        required: true
    },
    promoters: {
        type: Array,
        required: true
    },
    saleTargetId: {
        type: [null, Number],
        required: true
    },
    label: {
        type: String,
        required: true
    },
    typeId: {
        type: String,
        required: true
    },
    resetFilter: {
        type: Array,
        default: Array,
    },
    clickSwitch: {
        type: Number,
        required: true
    },
});

const state = reactive({
    filterIds: [],
    locationIds: [],
    promoterIds: [],
    saleTargetId: null,
    parameters: {
        filter_ids: [],
        location_ids: [],
        promoter_ids: [],
    },
    isDataFetching: false,
});

const hasData = computed(() => props.target !== 0 || props.achieved !== 0);
const hasPreviousData = computed(() => props.previousTarget !== 0 && props.previousAchieved !== 0);

const percentage = 100;

const progressPercentage = computed(() => {
    if (props.target === 0 || props.achieved === 0) return 0;
    return Math.min(Math.round((props.achieved / props.target) * percentage), percentage);
});

const previousTrend = computed(() => {
    if (props.previousAchieved === 0) return 0;
    return (props.achieved - props.previousAchieved) / props.previousAchieved;
});

const currentTrend = computed(() => {
    if (props.target === 0) return 0;
    return props.achieved / props.target;
});

const calculateTrendPercentage = (value) => {
    return (value.value) * percentage;
};

const decimalPlaces = 2;

const previousTrendPercentage = computed(() => {
    return parseFloat(Math.round(calculateTrendPercentage(previousTrend)).toFixed(decimalPlaces));
});

const currentTrendPercentage = computed(() => {
    return parseFloat((Math.round(calculateTrendPercentage(currentTrend))).toFixed(decimalPlaces));
});

const redirectToSaleAchievementReport = () => {
    window.location.href = route('admin.sale_achieved_targets.index', {
        target_type: props.company,
        time_interval_selection: props.currentPeriod,
        timeframe_ids: state.parameters.filter_ids
    });
};

const updateFilterIds = (filterIds) => {
    state.filterIds[props.typeId] = filterIds;
    state.parameters.filter_ids = state.filterIds[props.typeId].map((filter) => {
        return filter.id;
    });

    if (filterIds.length === 0) {
        state.parameters.filter_ids = props.filter.map((filter) => {
            return filter.id;
        });
    }

    getCardData();
};

const updateLocationIds = (locationIds) => {
    state.locationIds[props.typeId] = locationIds;

    state.parameters.location_ids = state.locationIds[props.typeId].map((location) => {
        return location.id;
    });

    if (state.parameters.filter_ids.length === 0) {
        state.parameters.filter_ids = props.filter.map((filter) => {
            return filter.id;
        });
    }

    getCardData();
};

const updatePromoterIds = (promoterIds) => {
    state.promoterIds[props.typeId] = promoterIds;

    state.parameters.promoter_ids = state.promoterIds[props.typeId].map((promoter) => {
        return promoter.id;
    });

    if (state.parameters.filter_ids.length === 0) {
        state.parameters.filter_ids = props.filter.map((filter) => {
            return filter.id;
        });
    }

    getCardData();
};

const getCardData = () => {
    state.isDataFetching = true;
    axios.get(route('admin.sale_target_get_card_data', {
        timeframe_ids: state.parameters.filter_ids,
        location_ids: state.parameters.location_ids,
        promoter_ids: state.parameters.promoter_ids,
        target_type: props.company,
        time_interval_selection: props.currentPeriod,
    })).then((response) => {
        emits('update:card-data', response.data, props.typeId, props.currentPeriod, props.company);
        state.isDataFetching = false;
    });
};

const showCharts = () => {
    emits('showCharts');
};

watch(() => props.clickSwitch, () => {
    if (state.filterIds[props.typeId]?.length > 0) {
        state.filterIds[props.typeId] = [];
    }

    if (state.locationIds[props.typeId]?.length > 0) {
        state.locationIds[props.typeId] = [];
    }

    if (state.promoterIds[props.typeId]?.length > 0) {
        state.promoterIds[props.typeId] = [];
    }

    if (state.saleTargetId?.length > 0) {
        state.saleTargetId = null;
    }
});
</script>

<style>
.tippy-box {
  font-size: 0.8rem;
  white-space: pre-wrap;
}
</style>
