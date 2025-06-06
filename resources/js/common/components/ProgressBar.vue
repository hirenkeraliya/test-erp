<template>
    <div class="flex items-center">
        <span>
            {{ percentage }}%
        </span>

        <div class="progress ml-2">
            <div
                class="progress-bar w-1/2"
                role="progressbar"
                aria-valuenow="0"
                aria-valuemin="0"
                aria-valuemax="100"
                :style="{ width: percentage + '%' }"
            />
        </div>
    </div>
</template>
<script setup>

defineProps({
    percentage: {
        type: Number,
        default: 0,
    },
});
</script>
