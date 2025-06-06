<template>
    <div
        v-if="errorMessage"
        class="text-danger mt-2"
    >
        {{ errorMessage }}
    </div>
</template>
<script setup>
import { computed } from 'vue';
import { validation } from '@commonServices/displayErrors';

const props = defineProps({
    validationFieldName: {
        type: String,
        default: null,
    }
});

const errorMessage = computed(() => {
    for (const key in validation.errors) {
        const keyName = key.split('.');
        if (key === props.validationFieldName) {
            return validation.errors[props.validationFieldName];
        }

        if (props.validationFieldName && keyName[0] === props.validationFieldName.replaceAll('-', '_')) {
            return validation.errors[key];
        }
    }

    return '';
});
</script>
