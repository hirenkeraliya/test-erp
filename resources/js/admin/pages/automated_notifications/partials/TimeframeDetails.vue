<template>
    <div>
        <span
            v-if="automatedNotificationTimeframeStaticDetails.limitByDayOfTheWeek === automatedNotificationForm.timeframe_type_id"
            class="pl-5"
        >
            <div class="intro-y flex text-base my-3 border-b">
                Choose Week Days
            </div>

            <ValidationError validation-field-name="week_days" />

            <div class="intro-y flex flex-col lg:flex-row -mx-3 pl-2">
                <FormCheckbox
                    v-for="(weeklySelection, index) in state.weeklySelections"
                    :key="index"
                    class="ml-2"
                    :check-label="weeklySelection.name"
                    :check-value="isWeekDaySelected(weeklySelection.id)"
                    @update:check-value="selectWeekDay($event, index, weeklySelection.id)"
                />
            </div>
        </span>

        <span
            v-if="automatedNotificationTimeframeStaticDetails.limitByDayOfTheMonth === automatedNotificationForm.timeframe_type_id"
            class="pl-5"
        >
            <div class="intro-y flex text-base my-3 border-b">
                Choose Month Dates
            </div>

            <ValidationError validation-field-name="month_dates" />

            <div class="grid grid-cols-12 gap-y-3">
                <FormCheckbox
                    v-for="(monthlySelection, index) in state.monthlySelections"
                    :key="index"
                    class="mx-3 input-form col-span-5 sm:col-span-3 md:col-span-2 lg:col-span-2 xl:col-span-1"
                    :check-label="monthlySelection.name"
                    :check-value="isMonthDateSelected(monthlySelection.id)"
                    @update:check-value="selectMonthDate($event, index, monthlySelection.id)"
                />
            </div>
        </span>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import ValidationError from '@commonComponents/ValidationError.vue';

const props = defineProps({
    automatedNotificationForm: {
        type: Object,
        required: true,
    },
    automatedNotificationTimeframeStaticDetails: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    weeklySelections: [
        { id: 1, name: 'Monday', check: false },
        { id: 2, name: 'Tuesday', check: false },
        { id: 3, name: 'Wednesday', check: false },
        { id: 4, name: 'Thursday', check: false },
        { id: 5, name: 'Friday', check: false },
        { id: 6, name: 'Saturday', check: false },
        { id: 0, name: 'Sunday', check: false },
    ],
    monthlySelections: [],
});

const emits = defineEmits([
    'add:new-week-day',
    'add:new-month-date',
    'remove:week-day',
    'remove:month-date',
    'clear:columns',
]);

const selectWeekDay = (event, index, weekDay) => {
    clearDateWiseMonthlyHourly();
    state.weeklySelections[index].check = event;
    if (state.weeklySelections[index].check) {
        emits('add:new-week-day', weekDay);
        return;
    }
    for (const key in props.automatedNotificationForm.week_days) {
        if (props.automatedNotificationForm.week_days[key] === weekDay) {
            emits('remove:week-day', key);
        }
    }
};

const isWeekDaySelected = (weekDay) => {
    for (const key in props.automatedNotificationForm.week_days) {
        if (props.automatedNotificationForm.week_days[key] === weekDay) {
            return true;
        }
    }
    return false;
};

const selectMonthDate = (value, index, monthDate) => {
    clearDateWiseWeeklyHourly();
    state.monthlySelections[index].check = value;

    if (state.monthlySelections[index].check) {
        emits('add:new-month-date', monthDate);
        return;
    }

    for (const key in props.automatedNotificationForm.month_dates) {
        if (props.automatedNotificationForm.month_dates[key] === monthDate) {
            emits('remove:month-date', key);
        }
    }
};

const isMonthDateSelected = (monthDate) => {
    for (const key in props.automatedNotificationForm.month_dates) {
        if (props.automatedNotificationForm.month_dates[key] === monthDate) {
            return true;
        }
    }
    return false;
};

const clearDateWiseWeeklyHourly = () => {
    emits('clear:columns', {
        week_days: [],
    });
};

const clearDateWiseMonthlyHourly = () => {
    emits('clear:columns', {
        month_dates: [],
    });
};

const daysInMonth = 31;

for (let index = 1; index <= daysInMonth; index++) {
    state.monthlySelections.push({ id: index, name: index.toString(), check: false });
}
</script>
