<template>
    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

    <h2 class="font-medium text-base ml-5 mt-2">
        Attach Templates to Product
    </h2>

    <div class="p-5">
        <JMultiSelect
            v-model:selected-records="state.selectedTemplateIds"
            :records="templates"
            input-label="Template"
            @option-added="templateAdded"
            @update:selected-records="updateAttachedTemplates"
            @option-removed="templateRemoved"
        />
    </div>

    <div
        v-if="state.selectedTemplateAttributes.length !== 0"
        id="basic-accordion"
        class="p-3 sm:p-5"
    >
        <div class="preview">
            <div
                id="faq-accordion-1"
                class="accordion"
            >
                <div
                    v-for="(template, templateIndex) in state.selectedTemplateAttributes"
                    :key="templateIndex"
                    class="accordion-item"
                >
                    <div
                        :id="'faq-accordion-content-' + templateIndex"
                        class="accordion-header bg-slate-100 px-4 py-2"
                        @click="toggleAccordion(templateIndex)"
                    >
                        <div class="flex items-center">
                            <div class="w-9/12 sm:w-11/12">
                                <button
                                    class="accordion-button"
                                    style="font-size: 0.9rem;"
                                    type="button"
                                    :aria-expanded="isAccordionOpen(templateIndex) ? 'true' : 'false'"
                                    :aria-controls="'faq-accordion-collapse-' + templateIndex"
                                >
                                    {{ template['name'] }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        :id="'faq-accordion-collapse-' + templateIndex"
                        class="accordion-collapse collapse"
                        :class="{ 'show': isAccordionOpen(templateIndex) }"
                    >
                        <div
                            v-for="(attribute, attributeIndex) in template['attributes']"
                            :key="attribute.id"
                        >
                            <div class="px-5">
                                <JMultiSelect
                                    v-if="attribute.field_type === props.fieldTypes.multiselect"
                                    v-model:selected-records="attribute.selected_value"
                                    :records="fetchOptions(attribute)"
                                    :input-label="attribute.name"
                                    :placeholder="attribute.name"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    :track-by="''"
                                    :label="''"
                                    :required="attribute.is_required"
                                    @update:selected-records="updateCustomField"
                                />

                                <FormSelectBox
                                    v-if="attribute.field_type === props.fieldTypes.select"
                                    v-model:selected-record="attribute.selected_value"
                                    :records="fetchOptions(attribute)"
                                    :input-label="attribute.name"
                                    :placeholder="attribute.name"
                                    record-value-key-name="name"
                                    :required="attribute.is_required"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    @update:selected-record="updateCustomField"
                                />

                                <FormInput
                                    v-if="attribute.field_type === props.fieldTypes.decimal"
                                    v-model:input-value="attribute.selected_value"
                                    :input-label="attribute.name"
                                    :max="attribute.to"
                                    :min="attribute.from"
                                    type="number"
                                    :required="attribute.is_required"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    @update:input-value="updateCustomField"
                                />

                                <JSwitch
                                    v-if="attribute.field_type === props.fieldTypes.toggle"
                                    v-model:is-checked="attribute.selected_value"
                                    :input-label="attribute.name"
                                    :required="attribute.is_required"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    @update:is-checked="updateCustomField"
                                />

                                <FormInput
                                    v-if="attribute.field_type === props.fieldTypes.text"
                                    v-model:input-value="attribute.selected_value"
                                    :input-label="attribute.name"
                                    :required="attribute.is_required"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    @update:input-value="updateCustomField"
                                />

                                <JDateTimePicker
                                    v-if="attribute.field_type === props.fieldTypes.datetime"
                                    v-model:input-value="attribute.selected_value"
                                    :input-label="attribute.name"
                                    :max-date="attribute.to ?? null"
                                    :min-date="attribute.from ?? new Date()"
                                    :required="attribute.is_required"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    @update:input-value="updateCustomField"
                                />

                                <JDatePicker
                                    v-if="attribute.field_type === props.fieldTypes.date"
                                    v-model:input-value="attribute.selected_value"
                                    :input-label="attribute.name"
                                    :max-date="attribute.to ?? null"
                                    :min-date="attribute.from ?? new Date()"
                                    :required="attribute.is_required"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    @update:input-value="updateCustomField"
                                />

                                <FormInput
                                    v-if="attribute.field_type === props.fieldTypes.number"
                                    v-model:input-value="attribute.selected_value"
                                    :input-label="attribute.name"
                                    :validation-field-name="'custom_field_values.' + templateIndex + '.attributes.' + attributeIndex + '.selected_value'"
                                    type="number"
                                    :required="attribute.is_required"
                                    @update:input-value="updateCustomField"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';

const props = defineProps({
    templates: {
        type: Array,
        required: true
    },
    fieldTypes: {
        type: Object,
        required: true
    },
    customFieldValues: {
        type: Array,
        default: null,
    },
});

const state = reactive({
    selectedTemplateIds: [],
    selectedTemplateAttributes: [],
});

const toggleAccordion = (templateIndex) => {
    if (state.accordionOpenIndex === templateIndex) {
        state.accordionOpenIndex = null;
    } else {
        state.accordionOpenIndex = templateIndex;
    }
};

const isAccordionOpen = (templateIndex) => {
    return state.accordionOpenIndex === templateIndex;
};

const updateCustomField = () => {
    emits('update:custom-field-values', prepareCustomFieldForm());
};

const prepareCustomFieldForm = () => {
    return state.selectedTemplateAttributes;
};

const emits = defineEmits([
    'update:custom-field-values',
]);

const fetchOptions = (attribute) => {
    if (attribute.field_type === props.fieldTypes.select) {
        return attribute.options.filter(option => (option && option.trim() !== ''))?.map(function (option) {
            return {
                name: option
            };
        });
    }
    return attribute.options?.filter(option => (option && option.trim() !== ''));
};

const templateAdded = async (addedTemplate) => {
    const fetchedTemplate = (await axios.post(route('admin.custom_field_values.fetch'), { templateId: addedTemplate.id }));
    state.selectedTemplateAttributes.push(fetchedTemplate.data.template);
};

const templateRemoved = async (removedTemplate) => {
    const removedTemplateIndex = state.selectedTemplateAttributes.findIndex(template => template.id === removedTemplate.id);
    state.selectedTemplateAttributes.splice(removedTemplateIndex, 1);
};

const updateAttachedTemplates = () => {
    emits('update:custom-field-values', prepareCustomFieldForm());
};

onMounted(() => {
    if (props.customFieldValues) {
        state.selectedTemplateIds = props.customFieldValues;
        state.selectedTemplateAttributes = props.customFieldValues;
    }
});
</script>
