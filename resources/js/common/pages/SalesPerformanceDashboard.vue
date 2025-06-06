<template>
    <div class="min-h-screen bg-slate-100 p-6">
        <div class="mx-auto">
            <header class="bg-white shadow rounded-lg mb-6">
                <div class="mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
                    <p class="inline-block text-xl font-medium capitalize">
                        {{ state.currentPeriod }} Data
                    </p>

                    <FormSelectBox
                        class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                        :selected-record="state.saleTargetId"
                        :records="saleTarget"
                        :placeholder="'Please select Target'"
                        @update:selected-record="getFilteredSaleTarget"
                    />
                    <OutlinePrimaryButton
                        :text="getPeriodText()"
                        :disabled="state.isDataFetching ? 'disabled' : null"
                        @click="togglePeriod"
                    />
                </div>
            </header>

            <div
                v-if="state.isDataFetching"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div class="cp">
                    <div class="animated-background !h-[550px] !rounded-xl" />
                </div>
                <div class="cp">
                    <div class="animated-background !h-[550px] !rounded-xl" />
                </div>
                <div class="cp">
                    <div class="animated-background !h-[550px] !rounded-xl" />
                </div>
            </div>
            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <SaleTargetKpiCard
                    v-for="(cardDetails, index) in state.cardData"
                    :key="index"
                    :company="cardDetails.target_type"
                    :type-id="index"
                    :click-switch="state.clickSwitch"
                    :filter="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'filter') ? cardDetails[state.currentPeriod].filter : []"
                    :locations="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'locations') ? cardDetails[state.currentPeriod].locations : []"
                    :promoters="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'promoters') ? cardDetails[state.currentPeriod].promoters : []"
                    :sale-target-id="state.saleTargetId"
                    :label="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'label') ? cardDetails[state.currentPeriod].label : ''"
                    :target="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'target') ? cardDetails[state.currentPeriod].target : 0"
                    :achieved="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'achieved') ? cardDetails[state.currentPeriod].achieved : 0"
                    :previous-target="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'previous_target') ? cardDetails[state.currentPeriod].previous_target : 0"
                    :previous-achieved="cardDetails[state.currentPeriod] && Object.hasOwn(cardDetails[state.currentPeriod], 'previous_achieved') ? cardDetails[state.currentPeriod].previous_achieved : 0"
                    :current-period="state.currentPeriod"
                    @show-charts="showChartsForKpi(index)"
                    @update:card-data="updateCardData"
                />
            </div>

            <transition name="fade">
                <div
                    v-if="state.selectedKpi !== null"
                    class="mt-6 space-y-6"
                >
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">
                                {{ currentViewTitle }}
                            </h3>
                            <button
                                class="bg-gray-200 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-300 transition duration-200"
                                @click="resetView(true)"
                            >
                                Reset
                            </button>
                        </div>

                        <SaleTargetBarChart
                            :labels="getChartLabels()"
                            :datasets="currentViewDatasets"
                            :is-loading="state.isChartDataFetching"
                            :show-bar-and-line-chart="true"
                            @bar-click="handleBarClick"
                        />
                    </div>
                </div>
            </transition>
        </div>
    </div>

    <div class="min-h-screen bg-slate-100 p-6">
        <div class="mx-auto">
            <div
                class="grid grid-cols-1 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2"
            >
                <TopSellingLocation
                    title="Top 10 Location"
                    title-color="text-cyan-700"
                    :type="state.currentPeriod"
                    :top-selling-locations="state.topTenLocation"
                />

                <TopSellingPromoter
                    title="Top 10 Promoters"
                    title-color="text-teal-700"
                    :type="state.currentPeriod"
                    :top-promoters="state.topTenPromoter"
                />

                <WorstSellingLocation
                    title="Worst 10 Location"
                    title-color="text-teal-700"
                    :type="state.currentPeriod"
                    :worst-selling-locations="state.worstTenLocation"
                />

                <WorstSellingPromoters
                    title="Worst 10 Promoters"
                    title-color="text-teal-700"
                    :type="state.currentPeriod"
                    :worst-promoters="state.worstTenPromoter"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, onMounted, ref } from 'vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import SaleTargetKpiCard from '@commonPages/SaleTargetKpiCard.vue';
import SaleTargetBarChart from '@commonPages/SaleTargetBarChart.vue';
import axios from 'axios';
import { route } from 'ziggy';
import TopSellingLocation from '@commonComponents/TopSellingLocation.vue';
import TopSellingPromoter from '@commonComponents/TopSellingPromoters.vue';
import WorstSellingLocation from '@commonComponents/WorstSellingLocation.vue';
import WorstSellingPromoters from '@commonComponents/WorstSellingPromoters.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

const props = defineProps({
    saleTargetTimeInterval: {
        type: Array,
        required: true,
    },
    staticSaleTargetTimeInterval: {
        type: Object,
        required: true,
    },
    staticSaleTargetTypes: {
        type: Object,
        required: true,
    },
    saleTargetTypes: {
        type: Array,
        required: true,
    },
    saleTarget: {
        type: Array,
        required: true,
    },
    saleTargetId: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    isDataFetching: false,
    isChartDataFetching: false,
    currentView: 'yearly',
    selectedMonth: null,
    selectedWeek: null,
    currentViewTitle: 'Yearly Performance',
    currentViewLabels: [],
    currentViewDatasets: [],
    selectedKpi: null,
    selectedTargetTypeChart: null,
    currentPeriod: 'yearly',
    weeksRecords: [],
    chartData: {},
    yearlySelectedMonthsWeeks: [],
    cardData: {},
    allChartData: {},
    clickSwitch: 0,
    saleTargetId: props.saleTargetId,
    saleTargetIds: [],
    topTenLocation: [],
    topTenPromoter: [],
    worstTenLocation: [],
    worstTenPromoter: [],
});

const periods = ['yearly', 'monthly', 'weekly', 'daily'];

const togglePeriod = async () => {
    const currentIndex = periods.indexOf(state.currentPeriod);
    const nextIndex = (currentIndex + 1) % periods.length;
    state.currentPeriod = periods[nextIndex];
    await getSaleTargetData(state.currentPeriod);
    updateTopAndWorstLocationAndPromoter();
    state.clickSwitch += 1;
    resetView();
};

const showChartsForKpi = (index) => {
    state.selectedKpi = index;
    state.selectedTargetTypeChart = state.cardData[index].target_type;

    resetView();
};

const handleBarClick = async (params) => {
    const clickHandlers = {
        yearly: handleYearlyBarClick,
        monthly: handleMonthlyBarClick,
        weekly: handleWeeklyBarClick
    };

    if (clickHandlers[state.currentPeriod]) {
        if (state.currentPeriod === 'weekly') {
            state.currentView = 'weekly';
        }
        await clickHandlers[state.currentPeriod](params);
    }
};

const resetView = (someFlag = false) => {
    state.currentView = state.currentPeriod === 'weekly' ? 'weekly' : 'monthly';
    state.selectedMonth = null;
    state.selectedWeek = null;
    if (someFlag) {
        state.selectedKpi = null;
    }
};

const viewTitles = {
    monthly: 'Monthly Performance',
    weekly: 'Weekly Performance',
    daily: 'Daily Performance',
    yearly: 'Yearly Performance'
};

const currentViewTitle = computed(() => viewTitles[state.currentView] || '');

const currentViewDatasets = computed(() => {
    return getChartData();
});

const periodTextMap = {
    monthly: 'Switch to Weekly',
    weekly: 'Switch to Daily',
    daily: 'Switch to Yearly',
    yearly: 'Switch to Monthly'
};

const getPeriodText = () => {
    state.selectedKpi = null;
    return periodTextMap[state.currentPeriod] || '';
};

const handleYearlyBarClick = async (params) => {
    state.chartData = {
        [state.selectedTargetTypeChart]: {
            'yearly': {
                current: {},
                previous: {}
            },
            'monthly': {
                current: {},
                previous: {}
            },
            'weekly': {
                current: {},
                previous: {}
            },
            'daily': {
                current: {},
                previous: {}
            }
        }
    };

    const currentPeriodData = state.cardData[state.selectedKpi][state.currentPeriod];
    const commonParams = {
        target_type: state.selectedTargetTypeChart,
        location_ids: currentPeriodData.location_ids,
        promoter_ids: currentPeriodData.promoter_ids,
    };

    if (state.currentView === 'monthly') {
        state.isChartDataFetching = true;
        await axios.get(route('admin.sale_target_weekly_sales', {
            month: params.dataIndex + 1,
            ...commonParams
        })).then((response) => {
            state.currentView = 'weekly';
            state.chartData = response.data.chartData;

            state.yearlySelectedMonthsWeeks = response.data.chartData[state.selectedTargetTypeChart][state.currentView].previous.weeks ?? response.data.chartData[state.selectedTargetTypeChart][state.currentView].current.weeks;
        });
        state.isChartDataFetching = false;
        state.selectedMonth = params.dataIndex;
    } else if (state.currentView === 'weekly') {
        state.isChartDataFetching = true;
        await axios.get(route('admin.sale_target_daily_sales', {
            week: state.yearlySelectedMonthsWeeks[params.dataIndex],
            ...commonParams
        })).then((response) => {
            state.chartData = response.data.chartData;
        });

        state.isChartDataFetching = false;
        state.selectedWeek = params.dataIndex;
        state.currentView = 'daily';
    } else {
        state.isChartDataFetching = true;
        await axios.get(route('admin.fetch_yearly_sale_target', {
            id: state.saleTargetId,
            month: params.dataIndex + 1,
            ...commonParams
        })).then((response) => {
            state.currentView = 'yearly';
            state.chartData = response.data.chartData;

            state.yearlySelectedMonthsWeeks = response.data.chartData[state.selectedTargetTypeChart][state.currentView].previous.weeks ?? response.data.chartData[state.selectedTargetTypeChart][state.currentView].current.years;
        });

        state.isChartDataFetching = false;
    }
};

const handleMonthlyBarClick = async (params) => {
    state.chartData = {
        [state.selectedTargetTypeChart]: {
            'yearly': {
                current: {},
                previous: {}
            },
            'monthly': {
                current: {},
                previous: {}
            },
            'weekly': {
                current: {},
                previous: {}
            },
            'daily': {
                current: {},
                previous: {}
            }
        }
    };

    const currentPeriodData = state.cardData[state.selectedKpi][state.currentPeriod];
    const commonParams = {
        target_type: state.selectedTargetTypeChart,
        location_ids: currentPeriodData.location_ids,
        promoter_ids: currentPeriodData.promoter_ids,
    };


    if (state.currentView === 'monthly') {
        state.isChartDataFetching = true;
        await axios.get(route('admin.sale_target_weekly_sales', {
            month: Object.values(currentPeriodData['months'])[params.dataIndex],
            ...commonParams
        })).then((response) => {
            state.currentView = 'weekly';

            state.weeksRecords = response.data.chartData[state.selectedTargetTypeChart][state.currentView]?.previous.weeks ?? response.data.chartData[state.selectedTargetTypeChart][state.currentView]?.current.weeks;
            state.chartData = response.data.chartData;
        }).catch(() => {
            state.currentView = 'monthly';
        });

        state.selectedMonth = params.dataIndex;
        state.isChartDataFetching = false;
    } else if (state.currentView === 'weekly') {

        state.isChartDataFetching = true;
        await axios.get(route('admin.sale_target_daily_sales', {
            week: state.weeksRecords[params.dataIndex],
            ...commonParams
        })).then((response) => {
            state.currentView = 'daily';
            state.chartData = response.data.chartData;
        }).catch(() => {
            state.currentView = 'weekly';
        });

        state.selectedWeek = params.dataIndex;
        state.isChartDataFetching = false;
    } else {
        state.isChartDataFetching = true;
        await axios.get(route('admin.fetch_monthly_sale_target', state.saleTargetId ?? 0)).then((response) => {
            state.chartData = response.data.chartData;
        });

        state.selectedWeek = params.dataIndex;
        state.currentView = 'daily';
        state.isChartDataFetching = false;
    }
};

const handleWeeklyBarClick = async (params) => {
    state.chartData = {
        [state.selectedTargetTypeChart]: {
            'yearly': {
                current: {},
                previous: {}
            },
            'monthly': {
                current: {},
                previous: {}
            },
            'weekly': {
                current: {},
                previous: {}
            },
            'daily': {
                current: {},
                previous: {}
            }
        }
    };

    const currentPeriodData = state.cardData[state.selectedKpi][state.currentPeriod];
    const commonParams = {
        target_type: state.selectedTargetTypeChart,
        location_ids: currentPeriodData.location_ids,
        promoter_ids: currentPeriodData.promoter_ids,
    };

    if (state.currentView === 'weekly') {
        state.isChartDataFetching = true;
        await axios.get(route('admin.sale_target_daily_sales', {
            week: state.weeksRecords[params.dataIndex],
            ...commonParams
        })).then((response) => {
            state.currentView = 'daily';
            state.chartData = response.data.chartData;
        }).catch(() => {
            state.currentView = 'weekly';
        });

        state.selectedWeek = params.dataIndex;
        state.isChartDataFetching = false;
    } else {
        state.isChartDataFetching = true;
        await axios.get(route('admin.sale_target_weekly_sales', {
            month: Object.values(currentPeriodData['months'])[params.dataIndex],
            ...commonParams
        })).then((response) => {
            state.currentView = 'weekly';

            state.weeksRecords = response.data.chartData[state.selectedTargetTypeChart][state.currentView]?.previous.weeks ?? response.data.chartData[state.selectedTargetTypeChart][state.currentView]?.current.weeks;
            state.chartData = response.data.chartData;
        }).catch(() => {
            state.currentView = 'monthly';
        });

        state.selectedMonth = params.dataIndex;
        state.isChartDataFetching = false;
    }
};

const updateCardData = (data, typeId, currentPeriod, company) => {
    state.allChartData = JSON.parse(localStorage.getItem('allChartData'));
    state.allChartData[company][currentPeriod] = data.chartData[company][currentPeriod];
    let cardData = data.cardData;
    state.cardData[typeId][currentPeriod].achieved = cardData.achieved;
    state.cardData[typeId][currentPeriod].location_ids = cardData.location_ids;
    state.cardData[typeId][currentPeriod].months = cardData.months;
    state.cardData[typeId][currentPeriod].previous_achieved = cardData.previous_achieved;
    state.cardData[typeId][currentPeriod].previous_target = cardData.previous_target;
    state.cardData[typeId][currentPeriod].promoter_ids = cardData.promoter_ids;
    state.cardData[typeId][currentPeriod].target = cardData.target;
};

const getChartLabels = () => {
    const chart = state.chartData[state.selectedTargetTypeChart];
    if (!chart) return [];

    if (chart.previous?.labels && chart.previous.labels.length > 0) {
        return chart.previous.labels;
    } else if (
        (chart[state.currentPeriod]?.previous?.labels || chart[state.currentView]?.previous?.labels) &&
                (chart[state.currentPeriod]?.previous.labels.length > 0 || chart[state.currentView]?.previous.labels.length > 0)
    ) {
        return chart[state.currentPeriod]?.previous.labels ?? chart[state.currentView]?.previous.labels;
    } else if (chart.current?.labels && chart.current.labels.length > 0) {
        return chart.current.labels;
    } else if (
        (chart[state.currentPeriod]?.current?.labels || chart[state.currentView]?.current?.labels) &&
                (chart[state.currentView].current.labels.length > 0 || chart[state.currentView]?.current.labels.length > 0)
    ) {
        return chart[state.currentPeriod]?.current.labels ?? chart[state.currentView]?.current.labels;
    }

    return [];
};

const getChartData = () => {
    const currentSalesArr = ref([]);
    const currentTargetArr = ref([]);
    const previousSalesArr = ref([]);
    const previousTargetArr = ref([]);

    if (typeof state.chartData[state.selectedTargetTypeChart]?.[state.currentPeriod] !== 'undefined') {
        currentSalesArr.value = state.chartData[state.selectedTargetTypeChart][state.currentPeriod]?.current.data || [];
        currentTargetArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentPeriod]?.current.target || [];
        previousSalesArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentPeriod]?.previous.data || [];
        previousTargetArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentPeriod]?.previous.target || [];
    } else {
        currentSalesArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentView]?.current.data || [];
        currentTargetArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentView]?.current.target || [];
        previousSalesArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentView]?.previous.data || [];
        previousTargetArr.value = state.chartData[state.selectedTargetTypeChart]?.[state.currentView]?.previous.target || [];
    }

    const currentTargetData = [];
    const currentSalesData = [];
    const previousTargetData = [];
    const previousSalesData = [];

    const numberOfMonths = 12;
    for (let i = 0; i < numberOfMonths; i++) {
        currentTargetData.push(currentTargetArr.value[i] || 0);
        currentSalesData.push(currentSalesArr.value[i] || 0);
        previousTargetData.push(previousTargetArr.value[i] || 0);
        previousSalesData.push(previousSalesArr.value[i] || 0);
    }

    return [currentTargetData, currentSalesData, currentSalesData, previousSalesData];
};

const getFilteredSaleTarget = async (saleTargetId) => {
    state.saleTargetId = saleTargetId;

    await getSaleTargetData(state.currentView);
    updateTopAndWorstLocationAndPromoter();
};

const getSaleTargetData = async(currentView) => {
    state.isDataFetching = true;
    state.cardData = {};
    state.chartData = {};

    if (currentView === 'yearly') {
        await axios.get(route('admin.fetch_yearly_sale_target', state.saleTargetId ?? 0)).then((response) => {
            state.cardData = response.data.cardData;
            state.chartData = response.data.chartData;
            state.saleTargetIds = response.data.sale_target_ids;
        });
        getChartData();
    }

    if (currentView === 'monthly') {
        await axios.get(route('admin.fetch_monthly_sale_target', state.saleTargetId)).then((response) => {
            state.cardData = response.data.cardData;
            state.chartData = response.data.chartData;
            state.saleTargetIds = response.data.sale_target_ids;
        });
        getChartData();
    }

    if (currentView === 'weekly') {
        await axios.get(route('admin.fetch_weekly_sale_target', state.saleTargetId ?? 0)).then((response) => {
            state.cardData = response.data.cardData;
            state.chartData = response.data.chartData;
            state.saleTargetIds = response.data.sale_target_ids;
        });
        getChartData();
    }

    if (currentView === 'daily') {
        await axios.get(route('admin.fetch_daily_sale_target', state.saleTargetId ?? 0)).then((response) => {
            state.cardData = response.data.cardData;
            state.chartData = response.data.chartData;
            state.saleTargetIds = response.data.sale_target_ids;
        });
        getChartData();
    }

    localStorage.setItem('cardData', JSON.stringify(state.cardData));
    localStorage.setItem('allChartData', JSON.stringify(state.chartData));
    state.isDataFetching = false;
};

const getTopLocation = async () => {
    state.topTenLocation = [];
    if (state.saleTargetIds.length === 0) {
        state.topTenLocation = [];
        return;
    }

    await axios.get(route('admin.get_top_ten_location', {
        'current_view': state.currentPeriod ?? state.currentView,
        'target_type': state.selectedTargetTypeChart,
        'target_id': state.saleTargetId,
        'sale_target_ids': state.saleTargetIds,
    })).then((response) => {
        state.topTenLocation = response.data.top_ten_location;
    });
};

const getTopPromoter = async () => {
    state.topTenPromoter = [];
    if (state.saleTargetIds.length === 0) {
        state.topTenLocation = [];
        return;
    }

    await axios.get(route('admin.get_top_ten_promoter', {
        'current_view': state.currentPeriod ?? state.currentView,
        'target_type': state.selectedTargetTypeChart,
        'target_id': state.saleTargetId,
        'sale_target_ids': state.saleTargetIds,
    })).then((response) => {
        state.topTenPromoter = response.data.top_ten_promoter;
    });
};

const getWorstLocation = async () => {
    state.worstTenLocation = [];
    if (state.saleTargetIds.length === 0) {
        state.topTenLocation = [];
        return;
    }

    await axios.get(route('admin.get_worst_ten_location', {
        'current_view': state.currentPeriod ?? state.currentView,
        'target_type': state.selectedTargetTypeChart,
        'target_id': state.saleTargetId,
        'sale_target_ids': state.saleTargetIds,
    })).then((response) => {
        state.worstTenLocation = response.data.worst_ten_location;
    });
};

const getWorstPromoter = async () => {
    state.worstTenPromoter = [];
    if (state.saleTargetIds.length === 0) {
        state.topTenLocation = [];
        return;
    }

    await axios.get(route('admin.get_worst_ten_promoter', {
        'current_view': state.currentPeriod ?? state.currentView,
        'target_type': state.selectedTargetTypeChart,
        'target_id': state.saleTargetId,
        'sale_target_ids': state.saleTargetIds,
    })).then((response) => {
        state.worstTenPromoter = response.data.worst_ten_promoter;
    });
};

const updateTopAndWorstLocationAndPromoter = async () => {
    await getTopLocation();
    await getTopPromoter();
    await getWorstLocation();
    await getWorstPromoter();
};

onMounted(async () => {
    await getSaleTargetData(state.currentView);
    updateTopAndWorstLocationAndPromoter();
});

</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
