<template>
    <JMultiSelect
        v-if="attribute.field_type === props.fieldTypes.multiselect"
        :selected-records="attributeSelectedValue"
        :records="fetchOptions(attribute)"
        :input-label="attribute.name"
        :placeholder="attribute.name"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        :track-by="''"
        :label="''"
        :required="attribute.is_required"
        @update:selected-records="updateCustomFieldAttribute"
    />

    <FormSelectBox
        v-if="attribute.field_type === props.fieldTypes.select"
        :selected-record="attributeSelectedValue"
        :records="fetchOptions(attribute)"
        :input-label="attribute.name"
        :placeholder="attribute.name"
        record-value-key-name="name"
        :required="true"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        @update:selected-record="updateCustomFieldAttribute"
    />

    <FormInput
        v-if="attribute.field_type === props.fieldTypes.decimal"
        :input-value="attributeSelectedValue"
        :input-label="attribute.name"
        :max="attribute.to"
        :min="attribute.from"
        type="number"
        :required="attribute.is_required"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        @update:input-value="updateCustomFieldAttribute"
    />

    <JSwitch
        v-if="attribute.field_type === props.fieldTypes.toggle"
        :is-checked="attributeSelectedValue"
        :input-label="attribute.name"
        :required="attribute.is_required"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        @update:is-checked="updateCustomFieldAttribute"
    />

    <FormInput
        v-if="attribute.field_type === props.fieldTypes.text"
        :input-value="attributeSelectedValue"
        :input-label="attribute.name"
        :required="attribute.is_required"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        @update:input-value="updateCustomFieldAttribute"
    />

    <JDateTimePicker
        v-if="attribute.field_type === props.fieldTypes.datetime"
        :input-value="attributeSelectedValue"
        :input-label="attribute.name"
        :max-date="attribute.to ?? null"
        :min-date="attribute.from ?? new Date()"
        :required="attribute.is_required"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        @update:input-value="updateCustomFieldAttribute"
    />

    <JDatePicker
        v-if="attribute.field_type === props.fieldTypes.date"
        :input-value="attributeSelectedValue"
        :input-label="attribute.name"
        :max-date="attribute.to ?? null"
        :min-date="attribute.from ?? new Date()"
        :required="attribute.is_required"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        @update:input-value="updateCustomFieldAttribute"
    />

    <FormInput
        v-if="attribute.field_type === props.fieldTypes.number"
        :input-value="attributeSelectedValue"
        :input-label="attribute.name"
        :validation-field-name="'variants.' + variantIndex + '.product_variant_values.' + attributeIndex + '.selected_value'"
        type="number"
        :required="attribute.is_required"
        @update:input-value="updateCustomFieldAttribute"
    />
</template>

<script setup>
import "@left4code/tw-starter/dist/js/modal";
import FormInput from "@commonComponents/FormInput.vue";
import JSwitch from "@commonComponents/JSwitch.vue";
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';

const props = defineProps({
    attribute: {
        type: [Object, Array],
        required: true,
    },
    variantIndex: {
        type: Number,
        default: 0,
    },
    attributeSelectedValue: {
        type: [Object, Array, String, Number, Boolean, Date, null],
        required: true,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
    attributeIndex: {
        type: Number,
        required: true,
    },
});

const fetchOptions = (attribute) => {
    if (attribute.field_type === props.fieldTypes.select) {
        return Array.isArray(attribute.options) ?
            attribute.options.filter(option => (option && option.trim() !== ''))?.map(function (option) {
                return {
                    name: option
                };
            })
            : [];

    }
    return attribute.options?.filter(option => (option && option.trim() !== ''));
};

const emits = defineEmits([
    'update:custom-attribute-values',
]);

const updateCustomFieldAttribute = (value) => {
    emits('update:custom-attribute-values', value);
};

</script>
