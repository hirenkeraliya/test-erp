<template>
    <div class="flex flex-col items-center">
        <span class="text-xs font-medium text-gray-500">{{ label }}</span>
        <div
            class="w-8 h-8 rounded-full flex items-center justify-center"
            :class="bgColorClass"
        >
            <TrendingUp
                v-if="value > 0"
                class="w-4 h-4"
                :class="iconColorClass"
            />
            <TrendingDown
                v-else-if="value < 0"
                class="w-4 h-4"
                :class="iconColorClass"
            />
            <Minus
                v-else
                class="w-4 h-4"
                :class="iconColorClass"
            />
        </div>
        <span
            class="text-xs font-semibold mt-1"
            :class="textColorClass"
        >
            {{ formattedPercentage }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';

const props = defineProps({
    value: {
        type: Number,
        required: true,
    },
    percentage: {
        type: Number,
        required: true,
    },
    label: {
        type: String,
        required: true
    }
});

const bgColorClass = computed(() => {
    return props.value > 0 ? 'bg-green-100' : props.value < 0 ? 'bg-red-100' : 'bg-gray-100';
});

const iconColorClass = computed(() => {
    return props.value > 0 ? 'text-green-600' : props.value < 0 ? 'text-red-600' : 'text-gray-600';
});

const textColorClass = computed(() => {
    return props.value > 0 ? 'text-green-600' : props.value < 0 ? 'text-red-600' : 'text-gray-600';
});

const formattedPercentage = computed(() => {
    const absPercentage = Math.abs(props.percentage).toFixed(1);
    return props.percentage > 0 ? `+${absPercentage}%` : props.percentage < 0 ? `-${absPercentage}%` : '0%';
});
</script>
