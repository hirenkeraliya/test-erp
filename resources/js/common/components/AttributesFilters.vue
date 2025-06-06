<template>
    <div
        v-for="(attribute) in state.selectedTemplateAttributes"
        :key="attribute.id"
        :class="customClass"
    >
        <JMultiSelect
            v-model:selected-records="attribute.selected_value"
            :records="fetchOptions(attribute)"
            :input-label="attribute.name"
            :placeholder="attribute.name"
            :track-by="''"
            :label="''"
            label-class="block font-medium text-base text-primary-p3 mb-2"
            @update:selected-records="updateAttribute"
        />
    </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const emits = defineEmits([
    'update-params',
]);

const props = defineProps({
    attributes: {
        type: Object,
        required: true,
    },
    customClass: {
        type: String,
        default: '',
    },
});

const state = reactive({
    selectedTemplateAttributes: [],
});

const fetchOptions = (attribute) => {
    return attribute.options?.filter(option => (option && option.trim() !== ''));
};

const updateAttribute = () => {
    emits('update-params', state.selectedTemplateAttributes.flatMap(item => item.selected_value));
};

onMounted(() => {
    if (props.attributes) {
        state.selectedTemplateAttributes = props.attributes;
    }
});
</script>
