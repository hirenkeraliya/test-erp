<template>
    <div
        class="intro-y rounded-xl h-28 mb-3"
    >
        <div
            v-if="isDataFetching"
        >
            <div class="cp">
                <div class="animated-background !h-[120px] !rounded-xl" />
            </div>
        </div>

        <div
            v-else
            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-28 intro-y"
        >
            <div class="mr-2.5">
                <p class="text-lg text-slate-700">
                    {{ title }}
                </p>

                <p
                    v-if="amount"
                    class="mt-1 text-lg font-semibold"
                >
                    {{ displayAmountWithCurrencySymbol(amount) }}
                </p>

                <p
                    v-if="quantity"
                    class="mt-1 text-lg font-semibold"
                >
                    {{ truncateDecimal(quantity) }}
                </p>
            </div>

            <div
                class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
            >
                <ShieldAlert class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ShieldAlert } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol, truncateDecimal } from '@commonServices/helper';

defineProps({
    title: {
        type: String,
        default: '',
    },
    amount: {
        type: [Number, String],
        default: 0,
    },
    quantity: {
        type: Number,
        default: 0,
    },
    isDataFetching: {
        type: Boolean,
        required: true,
    },
});
</script>
