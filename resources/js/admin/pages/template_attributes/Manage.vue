<template>
    <PageTitle :title="attribute ? 'Edit Attribute' : 'Add Attribute'" />

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
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <FormInput
                                    v-model:input-value="templateAttributeForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                            >
                                <FormInput
                                    v-model:input-value="templateAttributeForm.description"
                                    input-name="description"
                                    input-label="Description"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-2"
                            >
                                <FormSelectBox
                                    v-model:selected-record="templateAttributeForm.field_type"
                                    :records="fieldTypes"
                                    input-label="Field Type"
                                    validation-field-name="field_type"
                                    :required="true"
                                    :disabled="attribute ? true : false"
                                    @update:selected-record="initializeFieldTypes()"
                                />
                            </div>
                            <div
                                v-if="
                                    templateAttributeForm.field_type === fieldTypeCases.decimal ||
                                        templateAttributeForm.field_type === fieldTypeCases.number
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
                                            v-model:input-value="templateAttributeForm.from"
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
                                            v-model:input-value="templateAttributeForm.to"
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
                                    templateAttributeForm.field_type === fieldTypeCases.date
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
                                            v-model:input-value="templateAttributeForm.from"
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
                                            v-model:input-value="templateAttributeForm.to"
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
                                    templateAttributeForm.field_type === fieldTypeCases.datetime
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
                                            v-model:input-value="templateAttributeForm.from"
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
                                            v-model:input-value="templateAttributeForm.to"
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
                                    v-for="(optionLoop, optionIndex) in templateAttributeForm.options"
                                    :key="optionIndex"
                                >
                                    <div class="block sm:flex items-center">
                                        <FormInput
                                            v-model:input-value="templateAttributeForm.options[optionIndex]"
                                            :input-name="'options.' + optionIndex"
                                            input-label="Option"
                                            class="w-full"
                                        />
                                        <PrimaryButton
                                            v-if="optionIndex === templateAttributeForm.options.length - 1"
                                            text="+"
                                            class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                            type="button"
                                            @click="addOption"
                                        />
                                        <PrimaryButton
                                            v-if="optionIndex < templateAttributeForm.options.length - 1"
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
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.toggle"
                                >
                                    <JSwitch
                                        :is-checked="templateAttributeForm.default_value"
                                        class="flex"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        @update:is-checked="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.decimal || templateAttributeForm.field_type === fieldTypeCases.number"
                                >
                                    <FormInput
                                        v-model:input-value="templateAttributeForm.default_value"
                                        type="number"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.text"
                                >
                                    <FormInput
                                        v-model:input-value="templateAttributeForm.default_value"
                                        input-name="default_value"
                                        input-label="Default Value"
                                        validation-field-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.date"
                                >
                                    <JDatePicker
                                        v-model:input-value="templateAttributeForm.default_value"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        validation-field-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.datetime"
                                >
                                    <JDateTimePicker
                                        v-model:input-value="templateAttributeForm.default_value"
                                        input-label="Default Value"
                                        input-name="default_value"
                                        validation-field-name="default_value"
                                        @update:input-value="updateDefaultValue($event)"
                                    />
                                </div>
                                <div
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.select"
                                >
                                    <FormSelectBox
                                        v-model:selected-record="templateAttributeForm.default_value"
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
                                    v-if="templateAttributeForm.field_type === fieldTypeCases.multiselect"
                                >
                                    <JMultiSelect
                                        :selected-records="templateAttributeForm.default_value"
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
                                        v-model:is-checked="templateAttributeForm.is_required"
                                        input-label="Is Required?"
                                        input-name="is_required"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.template_attributes.index')">
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
    fieldTypes: {
        type: Array,
        required: true,
    },
    fieldTypeCases: {
        type: Object,
        required: true,
    },
});

const templateAttributeForm = useForm({
    name: null,
    description: null,
    field_type: null,
    from: null,
    to: null,
    options: [''],
    default_value: null,
    is_required: true,
    watchEnabled: true,
});

const saveAttribute = () => {
    templateAttributeForm.watchEnabled = false;

    cleanFormBeforeSubmitting();

    removeLocalStorage('attribute');

    if (props.attribute) {
        templateAttributeForm.put(route('admin.template_attributes.update', [props.attribute.data.id]));
        return;
    }

    templateAttributeForm.post(route('admin.template_attributes.store'));
};

onMounted(() => {
    if (props.attribute) {
        removeLocalStorage('template_attribute');
        Object.assign(templateAttributeForm, props.attribute.data);
    } else {
        setLocalStorage('template_attribute', templateAttributeForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.attribute) {
        saveLocalStorage('template_attribute', templateAttributeForm);
    }
};

const clearFormData = () => {
    templateAttributeForm.reset();
};

const addOption = () => {
    templateAttributeForm.options.push('');
};

const removeOption = (index) => {
    removeOptionFromDefaultValue(templateAttributeForm.options[index]);
    templateAttributeForm.options.splice(index, 1);
};

const removeOptionFromDefaultValue = (value) => {
    const { multiselect, select } = props.fieldTypeCases;

    if (!templateAttributeForm.default_value) return;

    if (templateAttributeForm.field_type === multiselect) {
        templateAttributeForm.default_value = templateAttributeForm.default_value.filter(x => x !== value);
    }

    if (templateAttributeForm.field_type === select && templateAttributeForm.default_value === value) {
        templateAttributeForm.default_value = null;
    }
};

const cleanFormBeforeSubmitting = () => {
    if (!isFieldTypeListOrSelect()) {
        templateAttributeForm.options = null;
    }

    if (templateAttributeForm.field_type === props.fieldTypeCases.text || templateAttributeForm.field_type === props.fieldTypeCases.toggle) {
        templateAttributeForm.from = null;
        templateAttributeForm.to = null;
    }
};

const initializeFieldTypes = () => {
    templateAttributeForm.default_value = null;
    if (isFieldTypeListOrSelect()) {
        if (!areOptionsSet()) {
            templateAttributeForm.options = [''];
        }
    } else {
        templateAttributeForm.options = null;
    }
    if (templateAttributeForm.field_type === props.fieldTypeCases.toggle) {
        templateAttributeForm.default_value = false;
    }

    templateAttributeForm.from = null;
    templateAttributeForm.to = null;
};

const isFieldTypeListOrSelect = () => (templateAttributeForm.field_type === props.fieldTypeCases.multiselect) || (templateAttributeForm.field_type === props.fieldTypeCases.select);

const areOptionsSet = () => (templateAttributeForm.options && templateAttributeForm.options.length > 0
);

const updateDefaultValue = (value) => {
    templateAttributeForm.default_value = value;
};

const fetchOptions = () => {
    if (templateAttributeForm.field_type === props.fieldTypeCases.select) {
        return templateAttributeForm.options.filter(x => (x && x.trim() !== ''))?.map(function (x) {
            return {
                name: x
            };
        });
    }
    return templateAttributeForm.options?.filter(x => (x && x.trim() !== ''));
};

watch(
    templateAttributeForm,
    () => {
        if (templateAttributeForm.watchEnabled) {
            checkSaveLocalStorage();
        }
    },
    { deep: true }
);

</script>
