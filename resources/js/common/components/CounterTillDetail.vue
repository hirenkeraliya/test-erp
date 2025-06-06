<template v-slot="{ toggle }">
    <div class="accordion accordion-boxed">
        <div class="accordion-item">
            <div
                class="accordion-header flex flex-col sm:flex-row sm:items-end xl:items-start"
                @click="openDiv('closed-counter-till-details')"
            >
                <button
                    class="accordion-button"
                    :class="state.openTab !== 'closed-counter-till-details' ? 'collapsed' : ''"
                    type="button"
                    data-tw-toggle="collapse"
                    data-tw-target="#faq-accordion-collapse-5"
                >
                    Close Counter
                </button>

                <PrimaryButton
                    v-if="counterDetails.counter_till_details.length > 0"
                    type="button"
                    text="PDF"
                    class="btn-sm w-24 h-10 mt-3"
                    @click="exportCloseCounterTills"
                />
            </div>

            <div
                class="accordion-collapse collapse"
                :class="state.openTab === 'closed-counter-till-details' ? 'show' : ''"
            >
                <table
                    v-if="counterDetails.counter_till_details.length > 0"
                    class="table table-striped -mt-2"
                >
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Happened At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(counterTillDetail, index) in counterDetails.counter_till_details"
                            :key="index"
                            class="intro-x"
                        >
                            <td>
                                {{ index + 1 }}
                            </td>

                            <td>
                                {{ counterTillDetail.type }}
                            </td>

                            <td>
                                {{ counterTillDetail.happened_at }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <h3 class="text-center">
                        No Records Found.
                    </h3>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <div
                class="accordion-header flex flex-col sm:flex-row sm:items-end xl:items-start"
                @click="openDiv('take-break-detail')"
            >
                <button
                    class="accordion-button"
                    type="button"
                    data-tw-toggle="collapse"
                    :class="state.openTab !== 'take-break-detail' ? 'collapsed' : ''"
                >
                    Break
                </button>

                <PrimaryButton
                    v-if="(counterDetails.take_break_details).hasOwnProperty('data')"
                    type="button"
                    text="PDF"
                    class="btn-sm w-24 h-10 mt-3 mr-3"
                    @click="exportTakeBreak"
                />

                <JBadge
                    v-if="state.openTab === 'take-break-detail' && counterDetails.take_break_details.total_break"
                    :label="'Break: ' + counterDetails.take_break_details.total_break"
                    class="btn-sm w-44 h-10 mt-3"
                />
            </div>

            <div
                class="accordion-collapse collapse"
                :class="state.openTab === 'take-break-detail' ? 'show' : ''"
            >
                <table
                    v-if="(counterDetails.take_break_details).hasOwnProperty('data')"
                    class="table table-striped -mt-2"
                >
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Take A Break</th>
                            <th>Back From Break</th>
                            <th>Duration</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(counterBreakDetail, index) in counterDetails.take_break_details.data"
                            :key="index"
                            class="intro-x"
                        >
                            <td>
                                {{ index + 1 }}
                            </td>

                            <td>
                                {{ counterBreakDetail.take_a_break }}
                            </td>

                            <td>
                                {{ counterBreakDetail.back_from_break }}
                            </td>

                            <td>
                                {{ counterBreakDetail.duration }}
                            </td>
                        </tr>
                        <tr v-if="counterDetails.take_break_details.total_duration">
                            <td
                                colspan="3"
                                class="text-right"
                            >
                                <b>Duration</b>
                            </td>
                            <td><b>{{ counterDetails.take_break_details.total_duration }}</b></td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <h3 class="text-center">
                        No Records Found.
                    </h3>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <div
                class="accordion-header flex flex-col sm:flex-row sm:items-end xl:items-start"
                @click="openDiv('drawer-details')"
            >
                <button
                    class="accordion-button"
                    type="button"
                    data-tw-toggle="collapse"
                    :class="state.openTab !== 'drawer-details' ? 'collapsed' : ''"
                >
                    Drawer Details
                </button>

                <PrimaryButton
                    v-if="(counterDetails.drawer_details).hasOwnProperty('data')"
                    type="button"
                    text="PDF"
                    class="btn-sm w-24 h-10 mt-3 mr-3"
                    @click="exportDrawerDetails"
                />

                <JBadge
                    v-if="state.openTab === 'drawer-details' && counterDetails.drawer_details.total_drawer_open"
                    :label="'Open: ' + counterDetails.drawer_details.total_drawer_open"
                    class="btn-sm w-44 h-10 mt-3"
                />
            </div>

            <div
                class="accordion-collapse collapse"
                :class="state.openTab === 'drawer-details' ? 'show' : ''"
            >
                <table
                    v-if="(counterDetails.drawer_details).hasOwnProperty('data')"
                    class="table table-striped -mt-2"
                >
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Drawer Open</th>
                            <th>Drawer Close</th>
                            <th>Duration</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(counterDrawerDetail, index) in counterDetails.drawer_details.data"
                            :key="index"
                            class="intro-x"
                        >
                            <td>
                                {{ index + 1 }}
                            </td>

                            <td>
                                {{ counterDrawerDetail.drawer_open }}
                            </td>

                            <td>
                                {{ counterDrawerDetail.drawer_close }}
                            </td>

                            <td>
                                {{ counterDrawerDetail.duration }}
                            </td>
                        </tr>
                        <tr v-if="counterDetails.drawer_details.total_duration">
                            <td
                                colspan="3"
                                class="text-right"
                            >
                                <b>Duration</b>
                            </td>
                            <td><b>{{ counterDetails.drawer_details.total_duration }}</b></td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <h3 class="text-center">
                        No Records Found.
                    </h3>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <div
                class="accordion-header flex flex-col sm:flex-row sm:items-end xl:items-start"
                @click="openDiv('track-offline-mode')"
            >
                <button
                    class="accordion-button"
                    type="button"
                    data-tw-toggle="collapse"
                    :class="state.openTab !== 'track-offline-mode' ? 'collapsed' : ''"
                >
                    Offline Mode
                </button>

                <PrimaryButton
                    v-if="(counterDetails.track_offline_mode).hasOwnProperty('data')"
                    type="button"
                    text="PDF"
                    class="btn-sm w-24 h-10 mt-3 mr-3"
                    @click="exportTrackOfflineMode"
                />

                <JBadge
                    v-if="state.openTab === 'track-offline-mode' && counterDetails.track_offline_mode.total_offline"
                    :label="'Offline: ' + counterDetails.track_offline_mode.total_offline"
                    class="btn-sm w-44 h-10 mt-3"
                />
            </div>

            <div
                class="accordion-collapse collapse"
                :class="state.openTab === 'track-offline-mode' ? 'show' : ''"
            >
                <table
                    v-if="(counterDetails.track_offline_mode).hasOwnProperty('data')"
                    class="table table-striped -mt-2"
                >
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Goes Offline</th>
                            <th>Back Online</th>
                            <th>Duration</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(counterTrackDetail, index) in counterDetails.track_offline_mode.data"
                            :key="index"
                            class="intro-x"
                        >
                            <td>
                                {{ index + 1 }}
                            </td>

                            <td>
                                {{ counterTrackDetail.goes_offline }}
                            </td>

                            <td>
                                {{ counterTrackDetail.back_online }}
                            </td>

                            <td>
                                {{ counterTrackDetail.duration }}
                            </td>
                        </tr>
                        <tr v-if="counterDetails.track_offline_mode.total_duration">
                            <td
                                colspan="3"
                                class="text-right"
                            >
                                <b>Duration</b>
                            </td>
                            <td><b>{{ counterDetails.track_offline_mode.total_duration }}</b></td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <h3 class="text-center">
                        No Records Found.
                    </h3>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { route } from 'ziggy';
import { reactive } from 'vue';
import JBadge from '@commonComponents/JBadge.vue';
import { printReport } from '@commonServices/helper';

const props = defineProps({
    counterDetails: {
        type: Object,
        required: true,
    },
    counterUpdateId: {
        type: Number,
        default: null,
    },
    printCounterUpdateTillUrl: {
        type: String,
        required: true,
    },
    printTakeBreakUrl: {
        type: String,
        required: true,
    },
    printDrawerDetailUrl: {
        type: String,
        required: true,
    },
    printTrackOfflineModeUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    openTab: '',
});

const exportCloseCounterTills = () => {
    printReport(route(props.printCounterUpdateTillUrl, props.counterUpdateId));
};

const exportTakeBreak = () => {
    printReport(route(props.printTakeBreakUrl, props.counterUpdateId));
};

const exportTrackOfflineMode = () => {
    printReport(route(props.printTrackOfflineModeUrl, props.counterUpdateId));
};

const exportDrawerDetails = () => {
    printReport(route(props.printDrawerDetailUrl, props.counterUpdateId));
};

const openDiv = (tabName) => {
    state.openTab = tabName;
};
</script>
