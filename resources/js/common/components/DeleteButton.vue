<template>
    <button
        :type="type"
        :disabled="disabled"
    >
        <Trash2 :color="disabled ? '#808080' : '#000000' " />
    </button>
</template>

<script setup>
import { Trash2 } from 'lucide-vue-next';

defineProps({
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>
