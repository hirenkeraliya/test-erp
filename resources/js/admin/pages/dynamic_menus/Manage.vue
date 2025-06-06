<template>
    <PageTitle :title="dynamicMenu ? 'Edit Dynamic Menu' : 'Add Dynamic Menu'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Dynamic Menu
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ dynamicMenu ? 'Edit' : 'Add' }} Dynamic Menu
                    </h2>
                </div>
                <form @submit.prevent="saveDynamicMenu();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="dynamicMenuForm.title"
                                    input-name="title"
                                    input-label="Title"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="dynamicMenuForm.type"
                                    :records="menuTypes"
                                    input-label="Menu Type"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    validation-field-name="type"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="dynamicMenuForm.type === staticMenuTypes.brand"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="dynamicMenuForm.module_id"
                                    :records="state.brands"
                                    placeholder="Select Brand..."
                                    input-label="Brand"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="dynamicMenuForm.type === staticMenuTypes.product_collection"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="dynamicMenuForm.module_id"
                                    :records="state.productCollection"
                                    placeholder="Select Product Collection..."
                                    input-label="Product Collection"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    :validation-field-name="module_id"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="dynamicMenuForm.type === staticMenuTypes.category"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="dynamicMenuForm.module_id"
                                    :records="state.categories"
                                    placeholder="Select Categories..."
                                    input-label="Category"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="dynamicMenuForm.status"
                                    input-label="Status"
                                    class="mt-3"
                                    title="Disabling a parent dynamic menu will automatically disable all its child dynamic menu."
                                />
                            </div>
                            <template v-if="dynamicMenuForm.type === staticMenuTypes.static_page">
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6"
                                >
                                    <label class="form-label mb-3 font-medium text-base"> Content </label>
                                    <ckeditor
                                        v-model="dynamicMenuForm.content"
                                        :editor="ClassicEditor"
                                        :config="state.editorConfig"
                                        tag-name="textarea"
                                    />

                                    <ValidationError validation-field-name="content" />
                                </div>
                            </template>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.dynamic_menus.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="dynamicMenu ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { component as ckeditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import ValidationError from '@commonComponents/ValidationError.vue';
import JSwitch from '@commonComponents/JSwitch.vue';

const props = defineProps({
    dynamicMenu: {
        type: Object,
        default: null,
    },
    menuTypes: {
        type: Array,
        required: true,
    },
    staticMenuTypes: {
        type: Array,
        required: true,
    },
    parentId: {
        type: Number,
        default: 0,
    },
    brands: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    productCollection: {
        type: Array,
        default: () => [],
    },
});

const dynamicMenuForm = useForm({
    _method: props.dynamicMenu ? 'put' : 'post',
    title: null,
    parent_id: props.parentId,
    content: null,
    type: null,
    module_id: null,
    status: true,
});

const state = reactive({
    brands: props.brands,
    categories: props.categories,
    productCollection: props.productCollection,
    editorConfig: {
        toolbar: {
            items: [
                "heading",
                "|",
                "bold",
                "italic",
                "link",
                "|",
                "bulletedList",
                "numberedList",
                "|",
                "outdent",
                "indent",
                "|",
                "blockQuote",
                "undo",
                "redo",
                "|",
            ],
        },
    },
});

const saveDynamicMenu = () => {
    prepareFormData();

    if (props.dynamicMenu) {
        dynamicMenuForm.post(route('admin.dynamic_menus.update', props.dynamicMenu.id));
        return;
    }

    dynamicMenuForm.post(route('admin.dynamic_menus.store'));
};

const prepareFormData = () => {
    dynamicMenuForm.parent_id = dynamicMenuForm.parent_id === 0 ? null : dynamicMenuForm.parent_id;
};

onMounted(() => {
    if (props.dynamicMenu) {
        Object.assign(dynamicMenuForm, props.dynamicMenu);
    }
    dynamicMenuForm.content = dynamicMenuForm.content ?? '';
});
</script>
