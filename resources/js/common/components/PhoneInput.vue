<template>
    <div class="mt-3">
        <Tippy
            tag="label"
            :for="validationFieldName ?? inputName"
            :class="labelClass"
            :content="title"
        >
            {{ inputLabel }}
            <span
                v-if="required"
                class="text-danger"
            >*</span>
            <Info
                v-if="title"
                class="ml-2 inline-block text-cyan-400"
                :size="15"
            />
        </Tippy>

        <VueTelInput
            v-if="state.isDisplay"
            :auto-default-country="false"
            :dropdown-options="{
                showSearchBox: true,
                showFlags: true,
                showDialCodeInList: true,
                showDialCodeInSelection: true,
            }"
            :input-options="state.inputBoxOptions"
            :dynamic-placeholder="true"
            :default-country="isdCode ? parseInt(isdCode) : null"
            :model-value="inputValue"
            @update:model-value="updateInput"
            @country-changed="updateCountry"
        />

        <ValidationError :validation-field-name="validationFieldName ?? inputName" />
    </div>
</template>

<script setup>
import { reactive, watch, nextTick } from 'vue';
import { VueTelInput } from 'vue-tel-input';
import ValidationError from '@commonComponents/ValidationError.vue';
import { Info } from 'lucide-vue-next';

const props = defineProps({
    inputValue: {
        type: [String, Number],
        default: null,
    },
    isdCode: {
        type: [String, Number],
        default: null,
    },
    placeholder: {
        type: String,
        default: null,
    },
    inputName: {
        type: String,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    required: {
        type: [Boolean, Number],
        default: false,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
    title: {
        type: String,
        default: null,
    },
});

const state = reactive({
    isDisplay: true,
    inputBoxOptions: {
        name: props.inputName,
        placeholder: props.placeholder
            ? props.placeholder
            : 'Enter ' + props.inputLabel,
        styleClasses:
            'dark:disabled:bg-darkmode-800/50 [&amp;[readonly]]:bg-slate-100 [&amp;[readonly]]:cursor-not-allowed [&amp;[readonly]]:dark:bg-darkmode-800/50 [&amp;[readonly]]:dark:border-transparent focus:ring-primary focus:border-primary dark:bg-darkmode-800 group-[.input-group]:[&amp;:not(:first-child)]:border-l-transparent w-full rounded-md border-slate-200 text-sm shadow-sm transition duration-200 ease-in-out placeholder:text-slate-400/90 focus:border-opacity-40 focus:ring-4 focus:ring-opacity-20 disabled:cursor-not-allowed disabled:bg-slate-100 group-[.input-group]:z-10 group-[.form-inline]:flex-1 group-[.input-group]:rounded-none group-[.input-group]:first:rounded-l group-[.input-group]:last:rounded-r dark:border-transparent dark:placeholder:text-slate-500/80 dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:disabled:border-transparent',
    },
});

const emits = defineEmits([
    'update:input-value',
    'update:isd-code',
]);

const updateCountry = (countryObject) => {
    emits(
        'update:isd-code',
        countryObject.dialCode,
    );
};

const updateInput = (value) => {
    emits('update:input-value', value);
};

watch(
    () => [props.isdCode],
    () => {
        state.isDisplay = false;
        nextTick(() => {
            state.isDisplay = true;
        });
    }
);
</script>
