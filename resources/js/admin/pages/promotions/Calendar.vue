<template>
    <FullCalendar
        :options="{
            plugins: [dayGridPlugin, interactionPlugin, rrulePlugin],
            initialView: 'dayGridMonth',
            timeZone: 'local',
            events: state.events,
            buttonText: {
                today: 'Go to Today'
            },
            eventClick: handleEventClick,
        }"
    >
        <template #eventContent="arg">
            <div class="rounded-lg bg-primary py-1.5 px-4 mx-0 mb-1 text-white text-sm hover:bg-primary/80 overflow-auto">
                <p>{{ arg.timeText }}</p>
                <div class="text-white">
                    {{ arg.event.title }}
                </div>
            </div>
        </template>
    </FullCalendar>
</template>

<script setup>
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import rrulePlugin from '@fullcalendar/rrule';
import { onMounted, reactive } from 'vue';

const props = defineProps({
    promotionLimitedDates: {
        type: Object,
        required: true,
    },
    promotionWeekly: {
        type: Object,
        required: true,
    },
    promotionMonthly: {
        type: Object,
        required: true,
    },
    promotionHourOfTheDay: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    events: [],
});

onMounted(() => {
    if (props.promotionLimitedDates) {
        props.promotionLimitedDates.data.forEach((promotionLimitedDate) => {
            state.events.push({
                title: promotionLimitedDate.name,
                start: promotionLimitedDate.start_date,
                end: promotionLimitedDate.end_date,
                id: promotionLimitedDate.id,
                url: promotionLimitedDate.link
            });
        });
    }

    if (props.promotionWeekly) {
        props.promotionWeekly.data.forEach((promotionWeekly) => {
            state.events.push({
                title: promotionWeekly.name,
                rrule: {
                    freq: 'weekly',
                    interval: 1,
                    byweekday: promotionWeekly.timeframe_type,
                },
                id: promotionWeekly.id,
                url: promotionWeekly.link
            });
        });
    }

    if (props.promotionMonthly) {
        props.promotionMonthly.data.forEach((promotionMonthly) => {
            state.events.push({
                title: promotionMonthly.name,
                rrule: {
                    freq: 'monthly',
                    interval: 1,
                    bymonthday: promotionMonthly.month_date,
                },
                id: promotionMonthly.id,
                url: promotionMonthly.link
            });
        });
    }

    if (props.promotionHourOfTheDay) {
        props.promotionHourOfTheDay.data.forEach((promotionHourOfTheDay) => {
            state.events.push({
                title: promotionHourOfTheDay.name,
                start: promotionHourOfTheDay.start_datetime,
                end: promotionHourOfTheDay.end_datetime,
                id: promotionHourOfTheDay.id,
                url: promotionHourOfTheDay.link
            });
        });
    }
});

const handleEventClick = (clickInfo) => {
    const event = clickInfo.event;
    if (event.extendedProps.url) {
        window.location.href = event.extendedProps.url;
    }
};

</script>

<style>
.fc-event {
    cursor: pointer;
}

.fc-h-event {
    background-color: none;
    border: 0;
}

.fc .fc-button.fc-today-button {
    background-color: rgb(var(--color-primary) / var(--tw-bg-opacity)) !important;
    color: white;
}

.fc .fc-button.fc-today-button:active, .fc .fc-button.fc-today-button:hover, .fc .fc-button.fc-today-button:focus {
    background-color: rgb(var(--color-primary) / var(--tw-bg-opacity)) !important;
    color: white;
    box-shadow: none !important;
}

.fc .fc-prev-button {
    background-color: rgb(var(--color-primary) / var(--tw-bg-opacity)) !important;
    color: white;
}

.fc .fc-prev-button:active, .fc .fc-prev-button:hover, .fc .fc-prev-button:focus {
    background-color: rgb(var(--color-primary) / var(--tw-bg-opacity)) !important;
    color: white;
    box-shadow: none !important;
}

.fc .fc-next-button {
    background-color: rgb(var(--color-primary) / var(--tw-bg-opacity)) !important;
    color: white;
}

.fc .fc-next-button:active, .fc .fc-next-button:hover, .fc .fc-next-button:focus {
    background-color: rgb(var(--color-primary) / var(--tw-bg-opacity)) !important;
    color: white;
    box-shadow: none !important;
}

.fc th .fc-col-header-cell-cushion {
    padding: 0.625rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: block;
    background-color: rgb(241 245 249);
}

.fc .fc-scrollgrid-section-liquid > td {
    padding: 0px !important;
}

.fc .fc-toolbar {
    @media (max-width: 480px) {
        display: block;
    }
}

.fc .fc-toolbar-title {
    @media (max-width: 480px) {
        margin-bottom: 20px;
    }
}

.fc-view-harness {
    overflow: auto;
}

.fc-daygrid {
    min-width: 450px;
}
</style>
