<template>
    <Tippy
        :content="title"
    >
        <div
            class="form-check form-switch mt-3"
        >
            <input
                class="form-check-input ml-4 rounded-full bg-contain bg-gray-300 focus:outline-none cursor-pointer shadow-sm"
                :class="inputClass"
                type="checkbox"
                :checked="isChecked"
                :disabled="disabled"
                @change="updateModel"
            >

            <label
                class="cursor-pointer pl-2"
                @click="changeEvent()"
            >
                {{ inputLabel }}
                <span
                    v-if="required"
                    class="text-danger"
                >*</span>
            </label>

            <Info
                v-if="title"
                class="text-cyan-400 ml-2"
                :size="15"
            />

            <ValidationError :validation-field-name="validationFieldName" />
        </div>
    </Tippy>
</template>

<script setup>
import ValidationError from '@commonComponents/ValidationError.vue';
import { Info } from 'lucide-vue-next';

const props = defineProps({
    inputLabel: {
        type: String,
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    isChecked: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    inputClass: {
        type: String,
        default: null,
    },
});

const changeEvent = () => {
    emits('update:is-checked', !props.isChecked);
};

const emits = defineEmits(['update:is-checked']);

const updateModel = (element) => {
    emits('update:is-checked', element.target.checked);
};
</script>
