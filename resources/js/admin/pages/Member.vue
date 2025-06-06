<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Member" />

        <div class="content content--top-nav mr-5">
            <div class="flex flex-col lg:flex-row mt-5">
                <div
                    class="col-span-12 lg:col-span-4 md:col-span-4"
                >
                    <FormSelectBox
                        v-model:selected-record="state.parameters.location_id"
                        class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                        :records="locations"
                        :placeholder="'Please select Locations'"
                        @update:selected-record="updateLocationId($event)"
                    />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-5 bg-slate-200 rounded-xl p-5">
                <div class="col-span-12">
                    <div
                        v-if="state.memberCountDetails === null"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-5 xl:grid-cols-5"
                    >
                        <div
                            v-for="index in 5"
                            :key="index"
                        >
                            <div class="mr-2.5">
                                <div class="cp">
                                    <div class="animated-background !h-[120px] !rounded-xl" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-2 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5"
                    >
                        <div
                            v-for="(memberCountDetail, index) in state.memberCountDetails"
                            :key="index"
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                        >
                            <div class="mr-2.5">
                                <p class="pb-5 text-xl font-medium">
                                    {{ memberCountDetail['label'] }}
                                </p>

                                <p class="mt-1 pb-5 text-xl font-medium">
                                    {{ memberCountDetail['value'] }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
                            >
                                <Users class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div class="mt-5 flex flex-wrap gap-3 items-start">
                        <div
                            v-if="state.memberAgeGroupDetails === null"
                            class="flex-1"
                        >
                            <div
                                v-for="n in 5"
                                :key="'loading-yearly-sales-data-content-' + n"
                                class="animated-background mt-2 rounded h-8 w-full"
                            />
                        </div>

                        <div
                            v-else
                            class="bg-white rounded-lg shadow p-6 flex-1 w-full md:w-3/4"
                        >
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="font-bold sm:text-sm text-xs text-left">
                                            Age Group
                                        </th>
                                        <th class="font-bold sm:text-sm text-xs text-right">
                                            Revenue
                                        </th>
                                        <th class="font-bold sm:text-sm text-xs text-right">
                                            Count
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="memberAgeGroupDetail in state.memberAgeGroupDetails"
                                        :key="memberAgeGroupDetail.age_group"
                                        class="border-t"
                                    >
                                        <td class="py-2">
                                            {{ memberAgeGroupDetail.age_group }}
                                        </td>
                                        <td
                                            class="py-2 text-right cursor-pointer"
                                            :title="'Revenue: ' + memberAgeGroupDetail.total_revenue"
                                        >
                                            {{ formatLabelForDashboardWithCurrencySymbol(memberAgeGroupDetail.total_revenue) }}
                                        </td>
                                        <td class="py-2 text-right">
                                            {{ memberAgeGroupDetail.count }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="state.memberGenderDetails === null"
                            class="space-y-4 w-full md:w-1/3"
                        >
                            <div
                                v-for="n in 3"
                                :key="'loading-gender-content-' + n"
                                class="animated-background rounded h-[148px]"
                            />
                        </div>

                        <div
                            v-else
                            class="space-y-4 w-full md:w-1/4"
                        >
                            <div
                                v-for="memberGenderDetail in state.memberGenderDetails"
                                :key="memberGenderDetail.gender"
                                class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-[94px]"
                            >
                                <div class="mr-2.5">
                                    <p class="py-5 text-xl font-medium">
                                        {{ memberGenderDetail.gender }}
                                    </p>

                                    <Tippy
                                        :content="'Revenue: ' + memberGenderDetail.total_revenue"
                                        class="flex mt-1 pb-5 text-xl font-medium items-center"
                                    >
                                        <p class="">
                                            {{ memberGenderDetail.count }}
                                        </p>
                                        <InfoIcon class="w-4 ml-2" />
                                    </Tippy>
                                </div>

                                <div
                                    class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
                                >
                                    <ManSvg v-if="memberGenderDetail.gender === 'Male'" />
                                    <GirlSvg v-if="memberGenderDetail.gender === 'Female'" />
                                    <Ban v-if="memberGenderDetail.gender === 'N/A'" />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="state.inactiveMembers90DaysCount === null && state.inactiveMembers180DaysCount === null"
                            class="space-y-4 w-full md:w-1/3"
                        >
                            <div
                                v-for="n in 2"
                                :key="'loading-gender-content-' + n"
                                class="animated-background rounded h-[148px]"
                            />
                        </div>

                        <div
                            v-else
                            class="space-y-4 w-full md:w-1/4"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-[148px]">
                                <Tippy
                                    tag="p"
                                    content="Formula: Current year member sales data includes Regular, Pending & Complete Layaway, and Pending & Complete Credit Sales."
                                >
                                    <div class="mr-2.5">
                                        <p class="pb-5 text-xl font-medium">
                                            Inactive Members - 90 days

                                            <Info
                                                class="ml-1 text-primary inline-block"
                                                :size="15"
                                            />
                                        </p>

                                        <p class="mt-1 pb-5 text-xl font-medium">
                                            {{ state.inactiveMembers90DaysCount }}
                                        </p>
                                    </div>
                                </Tippy>
                            </div>

                            <div class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-[148px]">
                                <Tippy
                                    tag="p"
                                    content="Formula: Current year member sales data includes Regular, Pending & Complete Layaway, and Pending & Complete Credit Sales."
                                >
                                    <div class="mr-2.5">
                                        <p class="pb-5 text-xl font-medium">
                                            Inactive Members - 180 days

                                            <Info
                                                class="ml-1 text-primary inline-block"
                                                :size="15"
                                            />
                                        </p>

                                        <p class="mt-1 pb-5 text-xl font-medium">
                                            {{ state.inactiveMembers180DaysCount }}
                                        </p>
                                    </div>
                                </Tippy>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-12 lg:col-span-12 md:col-span-12 mt-10"
                >
                    <MultiBarOrLineChart
                        chart-id="member-growth-trends-existing-vs-new-members"
                        title-of-chart="Member Growth Trends: Existing vs. New Members"
                        :datasets="state.newAndExistingMemberChartData !== null ? dataSets : []"
                        :labels="state.newAndExistingMemberChartData !== null ? state.newAndExistingMemberChartData.labels : []"
                        :show-bar-and-line-chart="true"
                        file-name="member_growth_trends_existing_vs_new_members"
                        :filters="filters"
                    />
                </div>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        class="grid grid-cols-1 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2"
                    >
                        <TopSellingMember
                            title="Top 10 members in this year"
                            title-color="text-teal-700"
                            type="year"
                            :top-selling-members="state.topTenMembersByYear"
                            :location-id="state.parameters.location_id"
                        />

                        <TopSellingMember
                            title="Top 10 members in this month"
                            title-color="text-cyan-700"
                            type="month"
                            :top-selling-members="state.topTenMembersByMonth"
                            :location-id="state.parameters.location_id"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import TopSellingMember from '@commonComponents/TopSellingMember.vue';
import GirlSvg from '@svg/GirlSvg.vue';
import ManSvg from '@svg/ManSvg.vue';
import axios from 'axios';
import { Ban, Info, InfoIcon, Users } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { formatLabelForDashboardWithCurrencySymbol } from '@commonServices/helper';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    }
});

const state = reactive({
    parameters: {
        location_id: 0,
    },
    memberCountDetails: null,
    newAndExistingMemberChartData: null,
    memberGenderDetails: null,
    memberAgeGroupDetails: null,
    topTenMembersByYear: [],
    topTenMembersByMonth: [],
    inactiveMembers90DaysCount: null,
    inactiveMembers180DaysCount: null,
});

const filters = reactive({
    location: { name: props.locations.find(location => state.parameters.location_id === location.id)?.name || 'All' },
});

const getMemberCountDetails = () => {
    clearData();
    axios.get(route('admin.get_member_count_details', { ...state.parameters }))
        .then((response) => {
            state.memberCountDetails = response.data.member_details;
        });

    axios.get(route('admin.get_new_and_existing_member_in_chart_data', { ...state.parameters }))
        .then((response) => {
            state.newAndExistingMemberChartData = response.data.member_details;
        });

    axios.get(route('admin.get_member_gender_details', { ...state.parameters }))
        .then((response) => {
            state.memberGenderDetails = response.data.gender_details;
        });

    axios.get(route('admin.get_member_age_group_details', { ...state.parameters }))
        .then((response) => {
            state.memberAgeGroupDetails = response.data.age_group_details;
        });

    axios.get(route('admin.get_top_ten_members_by_year', { ...state.parameters }))
        .then((response) => {
            state.topTenMembersByYear = response.data.top_ten_members_by_year;
        });

    axios.get(route('admin.get_top_ten_members_by_month', { ...state.parameters }))
        .then((response) => {
            state.topTenMembersByMonth = response.data.top_ten_members_by_month;
        });

    axios.get(route('admin.get_inactive_members_counts', { ...state.parameters }))
        .then((response) => {
            state.inactiveMembers90DaysCount = response.data.inactive_members_90_days_count ?? 0;
            state.inactiveMembers180DaysCount = response.data.inactive_members_180_days_count ?? 0;
        });
};

const clearData = () => {
    state.memberCountDetails = null;
    state.newAndExistingMemberChartData = null;
    state.memberGenderDetails = null;
    state.memberAgeGroupDetails = null;
    state.topTenMembersByYear = [];
    state.topTenMembersByMonth = [];
    state.inactiveMembers90DaysCount = null;
    state.inactiveMembers180DaysCount = null;
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = locationId;
    getMemberCountDetails();
};

onMounted(() => {
    getMemberCountDetails();
});

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const dataSets = computed(() => {
    return [
        {
            name: 'New Member',
            type: 'bar',
            data: isNotEmpty(state.newAndExistingMemberChartData.new_member_month_wise_data) ? state.newAndExistingMemberChartData.new_member_month_wise_data : [0],
        }, {
            name: 'Existing Member',
            type: 'bar',
            data: isNotEmpty(state.newAndExistingMemberChartData.existing_member_month_wise_data) ? state.newAndExistingMemberChartData.existing_member_month_wise_data : [0],
        }
    ];
});
</script>
