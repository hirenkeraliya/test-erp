<template>
    <Tippy
        v-if="salePercentage < 0"
        tag="div"
        class="py-[3px] flex rounded-full text-white text-xs pl-2 pr-1 items-center font-medium"
        :class="percentageIndicator ? 'bg-danger' : 'bg-slate-500'"
        :content="Math.abs(salePercentage) + '% Lower than ' + content"
    >
        {{ displayAmountWithPercentageSymbol(Math.abs(salePercentage)) }}

        <ArrowDown
            v-if="percentageIndicator"
            width="16"
            height="16"
        />
    </Tippy>

    <Tippy
        v-else
        id="div"
        class="py-[3px] flex rounded-full text-white text-xs pl-2 pr-1 items-center font-medium"
        :class="percentageIndicator ? 'bg-success' : 'bg-slate-500'"
        :content="salePercentage + '% Higher than ' + content"
    >
        {{ displayAmountWithPercentageSymbol(salePercentage) }}

        <ArrowUp
            v-if="percentageIndicator"
            width="16"
            height="16"
        />
    </Tippy>
</template>

<script setup>
import { displayAmountWithPercentageSymbol } from '@commonServices/helper';
import { ArrowDown, ArrowUp } from 'lucide-vue-next';

defineProps({
    content: {
        type: String,
        default: '',
    },
    salePercentage: {
        type: Number,
        default: 0,
    },
    percentageIndicator: {
        type: Boolean,
        default: true
    },
});
</script>
