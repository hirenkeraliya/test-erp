<template>
    <div>
        <div class="intro-y flex flex-col sm:flex-row items-center mt-8 mb-2">
            <h2 class="text-lg font-medium mr-auto">
                Promotions
            </h2>

            <div class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2">
                <Link :href="route('admin.promotions.index')">
                    <OutlinePrimaryButton
                        text="Back"
                        class="shadow-md text-sm mx-1"
                    />
                </Link>
            </div>

            <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
                <Link :href="route('admin.promotions.create')">
                    <PrimaryButton
                        text="Add New Promotion"
                        class="shadow-md"
                    />
                </Link>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
            <h2 class="font-medium text-base mr-auto">
                Calendar
            </h2>
        </div>
        <div class="col-span-12 xl:col-span-8 2xl:col-span-9">
            <div class="box p-5">
                <Calendar
                    :promotion-limited-dates="promotionLimitedDates"
                    :promotion-weekly="promotionWeekly"
                    :promotion-monthly="promotionMonthly"
                    :promotion-hour-of-the-day="promotionHourOfTheDay"
                />
            </div>
        </div>
    </div>
</template>

<script setup>

import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import Calendar from '@adminPages/promotions/Calendar.vue';

defineProps({
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

</script>
