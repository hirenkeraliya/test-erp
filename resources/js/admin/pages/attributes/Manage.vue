<template>
    <PageTitle :title="attribute ? 'Edit Attribute' : 'Add Attribute'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Attributes of Template:
            <span class="text-primary">{{ templateName }}</span>
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="attribute">Edit Attribute</span>
                        <span v-else>Add Attribute</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveAttribute()">
                    <div class="p-5">
                        <div
                            v-if="attributes.length > 0"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-2">
                                <JSwitch
                                    v-model:is-checked="attributeOldForm.existing_attribute"
                                    input-label="Would you like to add an existing attribute?"
                                />
                            </div>
                        </div>

                        <div
                            v-if="attributeOldForm.existing_attribute"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-2"
                            >
                                <FormSelectBox
                                    v-model:selected-record="attributeOldForm.attribute_id"
                                    :records="attributes"
                                    input-label="Attribute"
                                    validation-field-name="attribute_id"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div
                            v-if="!attributeOldForm.existing_attribute"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <FormInput
                                    v-model:input-value="attributeForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <FormInput
                                    v-model:input-value="attributeForm.description"
                                    input-name="description"
                                    input-label="Description"
                                />
                            </div>
                        </div>

                        <div
                            v-if="!attributeOldForm.existing_attribute"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-2"
                            >
                                <FormSelectBox
                                    v-model:selected-record="attributeForm.field_type"
                                    :records="fieldTypes"
                                    input-label="Field Type"
                                    validation-field-name="field_type"
                                    :required="true"
                                    :disabled="isDisabled()"
                                    @update:selected-record="initializeFieldTypes()"
                                />
                            </div>
                            <div
                                v-if="
                                    attributeForm.field_type === fieldTypeCases.decimal ||
                                        attributeForm.field_type === fieldTypeCases.number
                                "
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <div
                                    class="grid grid-cols-6 gap-0 sm:gap-3"
                                >
                                    <div
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <FormInput
                                            v-model:input-value="attributeForm.from"
                                            type="number"
                                            input-name="from"
                                            input-label="From"
                                            :required="true"
                                        />
                                    </div>

                                    <div
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <FormInput
                                            v-model:input-value="attributeForm.to"
                                            type="number"
                                            input-name="to"
                                            input-label="To"
                                            :required="true"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="
                                    attributeForm.field_type === fieldTypeCases.date
                                "
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <div
                                    class="grid grid-cols-6 gap-0 sm:gap-3"
                                >
                                    <div
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JDatePicker
                                            v-model:input-value="attributeForm.from"
                                            input-label="From Date"
                                            input-name="from"
                                            :required="true"
                                            validation-field-name="from"
                                        />
                                    </div>

                                    <div
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JDatePicker
                                            v-model:input-value="attributeForm.to"
                                            input-label="To Date"
                                            input-name="to"
                                            :required="true"
                                            validation-field-name="to"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="
                                    attributeForm.field_type === fieldTypeCases.datetime
                                "
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <div
                                    class="grid grid-cols-6 gap-0 sm:gap-3"
                                >
                                    <div
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JDateTimePicker
                                            v-model:input-value="attributeForm.from"
                                            input-label="From Date &amp; Time"
                                            input-name="from"
                                            :required="true"
                                            validation-field-name="from"
                                        />
                                    </div>

                                    <div
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JDateTimePicker
                                            v-model:input-value="attributeForm.to"
                                            input-label="To Date &amp; Time"
                                            input-name="to"
                                            :required="true"
                                            validation-field-name="to"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="isFieldTypeListOrSelect()"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4 grid"
                            >
                                <div
                                    v-for="(optionLoop, optionIndex) in attributeForm.options"
                                    :key="optionIndex"
                                >
                                    <div class="block sm:flex items-center">
                                        <FormInput
                                            v-model:input-value="attributeForm.options[optionIndex]"
                                            :input-name="'options.' + optionIndex"
                                            input-label="Option"
                                            class="w-full"
                                        />
                                        <PrimaryButton
                                            v-if="optionIndex === attributeForm.options.length - 1"
                                            text="+"
                                            class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                            type="button"
                                            @click="addOption"
                                        />
                                        <PrimaryButton
                                            v-if="optionIndex < attributeForm.options.length - 1"
                                            text="-"
                                            class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                            type="button"
                                            @click="removeOption(optionIndex)"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.toggle"
                                >
                                    <JSwitch
                                        :is-checked="attributeForm.default_value"
                                        class="flex"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        @update:is-checked="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.decimal || attributeForm.field_type === fieldTypeCases.number"
                                >
                                    <FormInput
                                        v-model:input-value="attributeForm.default_value"
                                        type="number"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.text"
                                >
                                    <FormInput
                                        v-model:input-value="attributeForm.default_value"
                                        input-name="default_value"
                                        input-label="Default Value"
                                        validation-field-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.date"
                                >
                                    <JDatePicker
                                        v-model:input-value="attributeForm.default_value"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        validation-field-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.datetime"
                                >
                                    <JDateTimePicker
                                        v-model:input-value="attributeForm.default_value"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        validation-field-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.select"
                                >
                                    <FormSelectBox
                                        v-model:selected-record="attributeForm.default_value"
                                        :records="fetchOptions()"
                                        validation-field-name="default_value"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        placeholder="Select Default Value"
                                        record-value-key-name="name"
                                        @update:selected-record="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="attributeForm.field_type === fieldTypeCases.multiselect"
                                >
                                    <JMultiSelect
                                        :selected-records="attributeForm.default_value"
                                        :records="fetchOptions()"
                                        input-label="Default Value"
                                        placeholder="Select Default Value"
                                        validation-field-name="default_value"
                                        :track-by="''"
                                        :label="''"
                                        @update:selected-records="updateDefaultValue($event)"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12 flex justify-end">
                                    <JSwitch
                                        v-model:is-checked="attributeForm.is_required"
                                        input-label="Is Required?"
                                        input-name="is_required"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.attributes.index', templateId)">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="attribute ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import {
    removeLocalStorage,
    setLocalStorage,
    saveLocalStorage,
} from '@commonServices/helper';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    attribute: {
        type: Object,
        default: null,
    },
    templateId: {
        type: Number,
        required: true,
    },
    templateName: {
        type: String,
        required: true,
    },
    fieldTypes: {
        type: Array,
        required: true,
    },
    fieldTypeCases: {
        type: Object,
        required: true,
    },
    attributes: {
        type: Array,
        default: () => [],
    },
    isVariant: {
        type: Boolean,
        required: true,
    },
});

const attributeForm = useForm({
    name: null,
    description: null,
    field_type: props.isVariant ? props.fieldTypeCases.select : null,
    from: null,
    to: null,
    options: [''],
    default_value: null,
    is_required: true,
    watchEnabled: true,
});

const attributeOldForm = useForm({
    attribute_id: null,
    existing_attribute: false,
});

const saveAttribute = () => {
    attributeForm.watchEnabled = false;

    cleanFormBeforeSubmitting();

    removeLocalStorage('attribute');

    if (props.attribute) {
        attributeForm.put(route('admin.attributes.update', [props.templateId, props.attribute.data.id]));
        return;
    }

    if (attributeOldForm.existing_attribute) {
        attributeOldForm.post(route('admin.attributes.store_old', props.templateId));
        return;
    }

    attributeForm.post(route('admin.attributes.store', props.templateId));
};

onMounted(() => {
    if (props.attribute) {
        removeLocalStorage('attribute');
        Object.assign(attributeForm, props.attribute.data);
    } else {
        setLocalStorage('attribute', attributeForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.attribute) {
        saveLocalStorage('attribute', attributeForm);
    }
};

const clearFormData = () => {
    attributeForm.reset();
    attributeOldForm.reset();
};

const addOption = () => {
    attributeForm.options.push('');
};

const removeOption = (index) => {
    removeOptionFromDefaultValue(attributeForm.options[index]);
    attributeForm.options.splice(index, 1);
};

const removeOptionFromDefaultValue = (value) => {
    const { multiselect, select } = props.fieldTypeCases;

    if (!attributeForm.default_value) return;

    if (attributeForm.field_type === multiselect) {
        attributeForm.default_value = attributeForm.default_value.filter(x => x !== value);
    }

    if (attributeForm.field_type === select && attributeForm.default_value === value) {
        attributeForm.default_value = null;
    }
};

const cleanFormBeforeSubmitting = () => {
    if (!isFieldTypeListOrSelect()) {
        attributeForm.options = null;
    }

    if (attributeForm.field_type === props.fieldTypeCases.text || attributeForm.field_type === props.fieldTypeCases.toggle) {
        attributeForm.from = null;
        attributeForm.to = null;
    }
};

const initializeFieldTypes = () => {
    attributeForm.default_value = null;
    if (isFieldTypeListOrSelect()) {
        if (!areOptionsSet()) {
            attributeForm.options = [''];
        }
    } else {
        attributeForm.options = null;
    }
    if (attributeForm.field_type === props.fieldTypeCases.toggle) {
        attributeForm.default_value = false;
    }

    attributeForm.from = null;
    attributeForm.to = null;
};

const isFieldTypeListOrSelect = () => (attributeForm.field_type === props.fieldTypeCases.multiselect) || (attributeForm.field_type === props.fieldTypeCases.select);

const areOptionsSet = () => (attributeForm.options && attributeForm.options.length > 0
);

const updateDefaultValue = (value) => {
    attributeForm.default_value = value;
};

const isDisabled = () => {
    if (props.attribute) {
        return true;
    }

    if (props.isVariant) {
        return true;
    }

    return false;
};

const fetchOptions = () => {
    if (attributeForm.field_type === props.fieldTypeCases.select) {
        return Array.isArray(attributeForm.options) ?
            attributeForm.options.filter(option => (option && option.trim() !== ''))?.map(function (option) {
                return {
                    name: option
                };
            })
            : [];
    }
    return attributeForm.options?.filter(x => (x && x.trim() !== ''));
};

watch(
    attributeForm,
    () => {
        if (attributeForm.watchEnabled) {
            checkSaveLocalStorage();
        }
    },
    { deep: true }
);

</script>
