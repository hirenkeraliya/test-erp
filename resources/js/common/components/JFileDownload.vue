<template>
    <label class="block font-medium text-base text-primary-p3 mb-2">
        <slot>
            {{ inputLabel }}
        </slot>
    </label>

    <div class="relative flex">
        <button
            type="button"
            class="btn btn-outline-warning"
            @click="downloadFile"
        >
            <div class="flex justify-center items-center">
                <Download class="w-4 h-4 mr-1" />
                Download
            </div>
        </button>
    </div>
</template>

<script setup>
import { Download } from 'lucide-vue-next';

const props = defineProps({
    filePath: {
        type: String,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
});

const downloadFile = () => {
    window.open(props.filePath, '_blank');
};
</script>
