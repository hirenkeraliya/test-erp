<template>
    <PageTitle :title="category ? 'Edit Category' : 'Add Category'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Categories
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="category">Edit Category</span>
                        <span v-else>Add Category</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveCategory();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="categoryForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="categoryForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3  mt-5">
                                <label class="form-label">
                                    Description
                                </label>

                                <ckeditor
                                    v-model="categoryForm.description"
                                    :editor="ClassicEditor"
                                    :config="state.editorConfig"
                                    tag-name="textarea"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="categoryForm.status"
                                    input-label="Status?"
                                    class="mt-3"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="categoryForm.is_available_in_ecommerce"
                                    input-label="Is Available In Ecommerce?"
                                    class="mt-3"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="categoryForm.is_display_on_menu"
                                    input-label="Is Display on Ecommerce Menu?"
                                    class="mt-3"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileCropUpload
                                    v-model:input-file="categoryForm.square_image"
                                    input-label="Upload Square Image (600px X 600px)"
                                    validation-field-name="square_image"
                                    :max-width="600"
                                    :max-height="600"
                                    @update:input-file="uploadSquareImage"
                                />

                                <div
                                    v-if="categoryForm.square_url"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <Tippy
                                        tag="div"
                                        content="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeSquareImage(category?.id)"
                                    >
                                        <X class="w-4 h-4" />
                                    </Tippy>

                                    <img
                                        :src="categoryForm.square_url"
                                        :alt="categoryForm.square_url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileUpload
                                    input-label="Upload Portrait Images (900px X 1200px)"
                                    validation-field-name="portrait_images"
                                    :is-multiple="true"
                                    class="mt-3"
                                    @change="uploadPortraitImage"
                                />
                                <div
                                    v-for="(uploadedImage, index) in categoryForm.portrait_urls"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <Tippy
                                        tag="div"
                                        content="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removePortraitImage(index, uploadedImage.id, category?.id)"
                                    >
                                        <X class="w-4 h-4" />
                                    </Tippy>
                                    <img
                                        :src="uploadedImage.url"
                                        :alt="uploadedImage.url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                                <div
                                    v-for="(imageUrl, index) in categoryForm.uploadPortraitImageUrls"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <div
                                        title="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeUploadPortraitImage(index)"
                                    >
                                        <X class="w-4 h-4" />
                                    </div>
                                    <img
                                        :src="imageUrl"
                                        :alt="imageUrl"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileUpload
                                    input-label="Upload Landscape Images (1920px X 1080px)"
                                    validation-field-name="landscape_images"
                                    :is-multiple="true"
                                    class="mt-3"
                                    @change="uploadLandscapeImage"
                                />
                                <div
                                    v-for="(uploadedImage, index) in categoryForm.landscape_urls"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <Tippy
                                        tag="div"
                                        content="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeLandscapeImage(index, uploadedImage.id, category?.id)"
                                    >
                                        <X class="w-4 h-4" />
                                    </Tippy>
                                    <img
                                        :src="uploadedImage.url"
                                        :alt="uploadedImage.url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                                <div
                                    v-for="(imageUrl, index) in categoryForm.uploadLandscapeImageUrls"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <div
                                        title="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeUploadLandscapeImage(index)"
                                    >
                                        <X class="w-4 h-4" />
                                    </div>
                                    <img
                                        :src="imageUrl"
                                        :alt="imageUrl"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.categories.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="category ? 'Update' : 'Submit'"
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
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import { onMounted, reactive, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import { component as ckeditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import JSwitch from '@commonComponents/JSwitch.vue';
import { X } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    category: {
        type: Object,
        default: null,
    },
    parentCategoryId: {
        type: Number,
        default: 0,
    }
});

const categoryForm = useForm({
    _method: props.category ? 'put' : 'post',
    name: null,
    code: null,
    description: '',
    status: true,
    is_available_in_ecommerce: false,
    is_display_on_menu: false,
    parent_category_id: props.parentCategoryId,
    square_image: null,
    square_url: null,
    portrait_images: [],
    uploadPortraitImageUrls: [],
    landscape_images: [],
    uploadLandscapeImageUrls: [],
    watchEnabled: true,
});

const state = reactive({
    editorConfig: {
        toolbar: {
            items: [
                'heading',
                '|', 'bold', 'italic', 'link',
                '|', 'bulletedList', 'numberedList',
                '|', 'outdent', 'indent',
                '|', 'blockQuote', 'undo', 'redo'
            ],
        },
    },
    portraitImages: [],
    landscapeImages: [],
});

const saveCategory = () => {
    categoryForm.watchEnabled = false;
    removeLocalStorage('category');

    if (props.category) {
        categoryForm.post(route('admin.categories.update', props.category.id));
        return;
    }

    categoryForm.post(route('admin.categories.store'));
};

onMounted(() => {
    if (props.category) {
        removeLocalStorage('category');
        Object.assign(categoryForm, props.category);
        categoryForm.description = categoryForm.description ?? '';
    } else {
        setLocalStorage('category', categoryForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.category) {
        saveLocalStorage('category', categoryForm);
    }
};

const clearFormData = () => {
    categoryForm.reset();
};

const uploadSquareImage = (selectedImage) => {
    categoryForm.square_url = URL.createObjectURL(selectedImage);
};

const uploadPortraitImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.portraitImages.push(selectedImage.target.files[index]);
        categoryForm.portrait_images = state.portraitImages;
        categoryForm.uploadPortraitImageUrls.push(URL.createObjectURL(selectedImage.target.files[index]));
    }
};

const uploadLandscapeImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.landscapeImages.push(selectedImage.target.files[index]);
        categoryForm.landscape_images = state.landscapeImages;
        categoryForm.uploadLandscapeImageUrls.push(URL.createObjectURL(selectedImage.target.files[index]));
    }
};

const removePortraitImage = (index, mediaId, categoryId) => {
    categoryForm.portrait_urls.splice(index, 1);
    if (categoryId) {
        axios.get(route('admin.categories.remove_portrait_image', [categoryId, mediaId]));
    }
};

const removeUploadPortraitImage = (index) => {
    categoryForm.uploadPortraitImageUrls.splice(index, 1);
    state.portraitImages.splice(index, 1);
    categoryForm.portrait_images = state.portraitImages;
};

const removeUploadLandscapeImage = (index) => {
    categoryForm.uploadLandscapeImageUrls.splice(index, 1);
    state.landscapeImages.splice(index, 1);
    categoryForm.landscape_images = state.landscapeImages;
};

const removeLandscapeImage = (index, mediaId, categoryId) => {
    categoryForm.landscape_urls.splice(index, 1);
    if (categoryId) {
        axios.get(route('admin.categories.remove_landscape_image', [categoryId, mediaId]));
    }
};

const removeSquareImage = (categoryId) => {
    categoryForm.square_image = null;
    categoryForm.square_url = null;

    if (categoryId) {
        axios.get(route('admin.categories.remove_category_square_image', categoryId));
    }
};

watch(categoryForm, () => {
    if (categoryForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
