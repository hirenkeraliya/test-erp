<template>
    <div class="p-5 bg-slate-50">
        <div class="intro-y w-full sm:w-auto block 2xl:flex">
            <OutlinePrimaryButton
                v-for="(timeFrame, index) in timeFrames"
                :key="'promotion-timeframe-type-'+index"
                :text="timeFrame.name"
                class="shadow-md text-sm mr-2 mb-2 2xl:mb-0"
                :class="promotionForm.timeframe_type_id === timeFrame.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                @click="selectTimeFrameType(timeFrame)"
            />
        </div>

        <span
            v-if="staticDetails.timeframe_date_wise === promotionForm.timeframe_type_id"
            class="pl-5"
        >
            <div class="intro-y flex text-base mt-3 border-b">
                Promotion Dates
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <JDatePicker
                        required
                        :input-value="promotionForm.start_date"
                        input-label="Start Date"
                        validation-field-name="start_date"
                        @update:input-value="updateColumnDetails('start_date', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <JDatePicker
                        required
                        :input-value="promotionForm.end_date"
                        input-label="End Date"
                        validation-field-name="end_date"
                        @update:input-value="updateColumnDetails('end_date', $event)"
                    />
                </div>
            </div>
        </span>

        <span
            v-if="staticDetails.timeframe_every_week_day === promotionForm.timeframe_type_id"
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
            v-if="staticDetails.timeframe_every_month_day === promotionForm.timeframe_type_id"
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

        <span
            v-if="staticDetails.timeframe_every_hour_day === promotionForm.timeframe_type_id"
            class="pl-5"
        >
            <div class="intro-y flex text-base mt-3 border-b">
                Choose Date And Hours
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <JDatePicker
                        required
                        :input-value="promotionForm.start_date"
                        input-label="Date"
                        validation-field-name="start_date"
                        @update:input-value="updateColumnDetails('start_date', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <FormInput
                        required
                        type="time"
                        input-name="start_time"
                        input-label="Start Time"
                        :input-value="promotionForm.start_time"
                        @update:input-value="updateColumnDetails('start_time', $event); clearDateWiseWeeklyMonthly()"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <FormInput
                        required
                        type="time"
                        input-name="end_time"
                        input-label="End Time"
                        :input-value="promotionForm.end_time"
                        @update:input-value="updateColumnDetails('end_time', $event)"
                    />
                </div>
            </div>
        </span>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import FormInput from '@commonComponents/FormInput.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import ValidationError from '@commonComponents/ValidationError.vue';

const props = defineProps({
    promotionForm: {
        type: Object,
        required: true,
    },
    timeFrames: {
        type: Array,
        required: true,
    },
    staticDetails: {
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
        { id: 7, name: 'Sunday', check: false },
    ],
    monthlySelections: [],
});

const emits = defineEmits([
    'add:new-week-day',
    'add:new-month-date',
    'remove:week-day',
    'remove:month-date',
    'clear:columns',
    'update:column-details',
]);

const selectTimeFrameType = (timeframe) => {
    if (timeframe.id === props.staticDetails.timeframe_manually_anytime) {
        clearAll();
    }

    if (timeframe.id === props.staticDetails.timeframe_date_wise) {
        clearWeeklyMonthlyHourly();
    }

    emits('update:column-details', {
        column_name: 'timeframe_type_id',
        value: timeframe.id
    });
};

const updateColumnDetails = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const selectWeekDay = (event, index, weekDay) => {
    clearDateWiseMonthlyHourly();
    state.weeklySelections[index].check = event;
    if (state.weeklySelections[index].check) {
        emits('add:new-week-day', weekDay);
        return;
    }
    for (const key in props.promotionForm.week_days) {
        if (props.promotionForm.week_days[key] === weekDay) {
            emits('remove:week-day', key);
        }
    }
};

const isWeekDaySelected = (weekDay) => {
    for (const key in props.promotionForm.week_days) {
        if (props.promotionForm.week_days[key] === weekDay) {
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

    for (const key in props.promotionForm.month_dates) {
        if (props.promotionForm.month_dates[key] === monthDate) {
            emits('remove:month-date', key);
        }
    }
};

const isMonthDateSelected = (monthDate) => {
    for (const key in props.promotionForm.month_dates) {
        if (props.promotionForm.month_dates[key] === monthDate) {
            return true;
        }
    }
    return false;
};

const clearAll = () => {
    emits('clear:columns', {
        start_date: null,
        end_date: null,
        week_days: [],
        month_dates: [],
        date: null,
        start_time: null,
        end_time: null,
    });
};

const clearWeeklyMonthlyHourly = () => {
    emits('clear:columns', {
        week_days: [],
        month_dates: [],
        date: null,
        start_time: null,
        end_time: null,
    });
};

const clearDateWiseWeeklyMonthly = () => {
    emits('clear:columns', {
        end_date: null,
        week_days: [],
        month_dates: [],
    });
};

const clearDateWiseWeeklyHourly = () => {
    emits('clear:columns', {
        start_date: null,
        end_date: null,
        week_days: [],
        date: null,
        start_time: null,
        end_time: null,
    });
};

const clearDateWiseMonthlyHourly = () => {
    emits('clear:columns', {
        start_date: null,
        end_date: null,
        month_dates: [],
        date: null,
        start_time: null,
        end_time: null,
    });
};

const daysInMonth = 31;

for (let index = 1; index <= daysInMonth; index++) {
    state.monthlySelections.push({ id: index, name: index.toString(), check: false });
}
</script>
