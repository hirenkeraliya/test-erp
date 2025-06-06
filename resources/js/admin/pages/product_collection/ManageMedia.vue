<template>
    <PageTitle title="Product Collections" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Manage Images
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.product_collections.index')">
                <SecondaryButton
                    text="Back To Product Collection List"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>
    <div>
        <h3 class="text-md font-medium mr-auto">
            Collection Name : {{ productCollection.name }}
        </h3>
    </div>
    <form @submit.prevent="saveImages();">
        <div class="grid grid-cols-12 gap-0 mt-4 sm:gap-6 p-4 bg-slate-200 rounded-md">
            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                <JFileCropUpload
                    v-model:input-file="imageUploadForm.square_image"
                    input-label="Upload Square Image (600px X 600px)"
                    validation-field-name="square_image"
                    :max-width="600"
                    :max-height="600"
                    @update:input-file="uploadSquareImage"
                />

                <div
                    v-if="imageUploadForm.square_url"
                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                >
                    <img
                        :src="imageUploadForm.square_url"
                        :alt="imageUploadForm.square_url"
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
                    v-for="(uploadedImage, index) in imageUploadForm.portrait_urls"
                    :key="index"
                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                >
                    <Tippy
                        tag="div"
                        content="Remove this image?"
                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                        @click="removePortraitImage(index, uploadedImage.id, productCollection?.id)"
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
                    v-for="(imageUrl, index) in imageUploadForm.uploadPortraitImageUrls"
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
                    v-for="(uploadedImage, index) in imageUploadForm.landscape_urls"
                    :key="index"
                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                >
                    <Tippy
                        tag="div"
                        content="Remove this image?"
                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                        @click="removeLandscapeImage(index, uploadedImage.id, productCollection?.id)"
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
                    v-for="(imageUrl, index) in imageUploadForm.uploadLandscapeImageUrls"
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
            <Link :href="route('admin.product_collections.index')">
                <SecondaryButton
                    type="button"
                    text="Cancel"
                    class="w-24 mr-1"
                />
            </Link>

            <PrimaryButton
                type="submit"
                text="Upload"
                class="w-24"
            />
        </div>
    </form>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { route } from 'ziggy';
import { X } from 'lucide-vue-next';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';

const props = defineProps({
    productCollection: {
        type: Object,
        required: true,
    },
});

const imageUploadForm = useForm({
    square_image: null,
    square_url: null,
    portrait_images: [],
    uploadPortraitImageUrls: [],
    landscape_images: [],
    uploadLandscapeImageUrls: [],
});

const saveImages = () => {
    imageUploadForm.post(route('admin.product_collections.upload_images', props.productCollection.id));
};

const state = reactive({
    portraitImages: [],
    landscapeImages: [],
});

const uploadSquareImage = (selectedImage) => {
    imageUploadForm.square_url = URL.createObjectURL(selectedImage);
};

const uploadPortraitImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.portraitImages.push(selectedImage.target.files[index]);
        imageUploadForm.portrait_images = state.portraitImages;
        imageUploadForm.uploadPortraitImageUrls.push(URL.createObjectURL(selectedImage.target.files[index]));
    }
};

const removePortraitImage = (index, mediaId, productCollectionId) => {
    imageUploadForm.portrait_urls.splice(index, 1);

    if (productCollectionId) {
        axios.get(route('admin.product_collections.remove_portrait_image', [productCollectionId, mediaId]));
    }
};

const removeUploadPortraitImage = (index) => {
    imageUploadForm.uploadPortraitImageUrls.splice(index, 1);
    state.portraitImages.splice(index, 1);
    imageUploadForm.portrait_images = state.portraitImages;
};

const uploadLandscapeImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.landscapeImages.push(selectedImage.target.files[index]);
        imageUploadForm.landscape_images = state.landscapeImages;
        imageUploadForm.uploadLandscapeImageUrls.push(URL.createObjectURL(selectedImage.target.files[index]));
    }
};

const removeLandscapeImage = (index, mediaId, productCollectionId) => {
    imageUploadForm.landscape_urls.splice(index, 1);

    if (productCollectionId) {
        axios.get(route('admin.product_collections.remove_landscape_image', [productCollectionId, mediaId]));
    }
};

const removeUploadLandscapeImage = (index) => {
    imageUploadForm.uploadLandscapeImageUrls.splice(index, 1);
    state.landscapeImages.splice(index, 1);
    imageUploadForm.landscape_images = state.landscapeImages;
};

onMounted(() => {
    if (props.productCollection) {
        Object.assign(imageUploadForm, props.productCollection);
    }
});
</script>
