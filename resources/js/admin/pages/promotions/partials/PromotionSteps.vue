<template>
    <div class="col-span-12 sm:col-span-12 md:col-span-4 lg:col-span-3 xl:col-span-3 2xl:col-span-2">
        <div
            v-for="(step, index) in steps"
            :key="step.key"
            class="intro-x flex items-center mb-3 sm:mb-5"
        >
            <button
                type="button"
                class="w-10 h-10 rounded-full btn"
                :class="[ step.key === currentStep ? 'btn-primary' : 'text-slate-500 bg-slate-50' ]"
                @click="updateStepKey(step.key)"
            >
                {{ index + 1 }}
            </button>

            <button
                type="button"
                class="text-base ml-3"
                :class="[ step.key === currentStep ? 'font-medium ' : 'text-slate-600' ]"
                @click="updateStepKey(step.key)"
            >
                {{ step.label }}
            </button>
        </div>
    </div>
</template>

<script setup>

defineProps({
    steps: {
        type: Object,
        required: true,
    },
    currentStep: {
        type: String,
        default: null,
    }
});

const emits = defineEmits([
    'update:step-key',
]);

const updateStepKey = (stepKey) => {
    emits('update:step-key', stepKey);
};
</script>
