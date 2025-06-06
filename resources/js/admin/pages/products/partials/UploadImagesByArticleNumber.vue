<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Upload Product Image
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="closeModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <div>
                <InfoAlert
                    color="primary"
                    class="mb-3 mt-5"
                >
                    Uploading a new thumbnail image on the product page will replace the existing thumbnail.
                </InfoAlert>
                <form
                    @submit.prevent="saveUploadImageProductByArticleNumber();"
                >
                    <FormAjaxSelect
                        :selected-record="state.selectedArticleNumberForUploadImages"
                        :search-records="searchArticleNumber"
                        track-by="article_number"
                        label="article_number"
                        input-label="Article Number"
                        validation-field-name="article_number"
                        placeholder="Please type the article number of the product to search."
                        @update:selected-record="selectArticleNumbers"
                    />
                    <div v-if="state.productDetailsForUploadImages">
                        <table class="table mt-2 intro-x font-medium bg-secondary">
                            <tr>
                                <th>Name</th>
                                <th>Brand</th>
                                <th>Style</th>
                                <th>Type</th>
                                <th>Category</th>
                            </tr>
                            <tr>
                                <td>{{ state.productDetailsForUploadImages?.name }}</td>
                                <td>{{ state.productDetailsForUploadImages?.brand }}</td>
                                <td>{{ state.productDetailsForUploadImages?.style }}</td>
                                <td>{{ state.productDetailsForUploadImages?.type }}</td>
                                <td>
                                    <span v-if="state.productDetailsForUploadImages?.categories.length">
                                        <span
                                            v-for="(category, index) in state.productDetailsForUploadImages?.categories"
                                            :key="index"
                                            class="inline-block"
                                        >
                                            {{ category.name }}

                                            <ChevronRight
                                                v-if="index != state.productDetailsForUploadImages?.categories.length - 1"
                                                class="form-check w-4 h-4 text-slate-400 inline-block"
                                            />
                                        </span>
                                    </span>
                                    <span v-else>
                                        N/A
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="mt-5">
                        <JFileCropUpload
                            v-model:input-file="state.uploadThumbnail"
                            input-label="Thumbnail (343px X 260px)"
                            validation-field-name="photo"
                            :max-width="343"
                            :max-height="260"
                            @update:input-file="uploadThumbnail"
                        />

                        <div
                            v-if="state.thumbnail_url"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <img
                                :src="state.thumbnail_url"
                                :alt="state.thumbnail_url"
                                width="100"
                                class="mt-2"
                            >
                        </div>

                        <JFileUpload
                            input-label="Images (500px X 500px)"
                            validation-field-name="images"
                            :is-multiple="true"
                            class="mt-3"
                            @change="uploadImages"
                        />
                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6"
                        >
                            <JSwitch
                                v-model:is-checked="state.delete_old_images"
                                input-label="Would you like to delete the old images?"
                                class="mt-3"
                                @update:is-checked="deleteOldImages($event)"
                            />
                        </div>
                        <div
                            v-for="(imageUrl, index) in state.uploadImageUrls"
                            :key="index"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <div
                                title="Remove this image?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="removeUploadProductImage(index)"
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

                        <JFileUpload
                            input-label="Videos"
                            validation-field-name="videos"
                            :is-multiple="true"
                            class="mt-3"
                            @change="uploadVideo"
                        />

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6"
                        >
                            <JSwitch
                                v-model:is-checked="state.delete_old_videos"
                                input-label="Would you like to delete the old videos?"
                                class="mt-3"
                                @update:is-checked="deleteOldVideos($event)"
                            />
                        </div>
                        <div
                            v-for="(videoUrl, index) in state.uploadVideoUrls"
                            :key="index"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <div
                                title="Remove this video?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="removeUploadProductVideo(index)"
                            >
                                <X class="w-4 h-4" />
                            </div>
                            <span
                                title="Video Play"
                                class="cursor-pointer flex justify-center w-12 h-12"
                            >
                                <PlayCircle class="text-indigo-900 w-14 h-14" />
                            </span>
                        </div>

                        <PrimaryButton
                            type="submit"
                            text="Upload"
                            class="w-24 mt-5"
                        />
                    </div>
                </form>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { ChevronRight, PlayCircle, X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { route } from 'ziggy';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import axios from 'axios';
import { showErrorNotification } from '@commonServices/notifier';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JSwitch from '@commonComponents/JSwitch.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    }
});

const state = reactive({
    selectedArticleNumberForUploadImages: null,
    productDetailsForUploadImages: null,
    uploadThumbnail: null,
    uploadImages: [],
    uploadVideos: [],
    uploadImageUrls: [],
    uploadVideoUrls: [],
    thumbnail_url: null,
    delete_old_images: false,
    delete_old_videos: false,
});

const selectArticleNumbers = (selectedNumber) => {
    state.selectedArticleNumberForUploadImages = selectedNumber;
    if (selectedNumber !== null) {
        axios.get(route('admin.products.fetch_product_details_by_article_number', selectedNumber)).then((response) => {
            state.productDetailsForUploadImages = response.data.product;
        });
    }
};

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    const minSearchLength = 3;
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const saveUploadImageProductByArticleNumber = () => {

    if (!state.uploadThumbnail && state.uploadImages.length === 0 && state.uploadVideos.length === 0) {
        showErrorNotification('At least one of Thumbnail, Images, or Videos is required');
        return;
    }

    router.post(route('admin.products.upload_image_by_article_number'), {
        article_number: state.selectedArticleNumberForUploadImages ? String(state.selectedArticleNumberForUploadImages.article_number) : null,
        thumbnail: state.uploadThumbnail,
        images: state.uploadImages,
        videos: state.uploadVideos,
        delete_old_images: state.delete_old_images,
        delete_old_videos: state.delete_old_videos,
    }, {
        onSuccess: () => {
            closeModal();
            router.get(route('admin.products.index'));
        },
    });
};

const uploadThumbnail = (selectedImage) => {
    state.thumbnail_url = URL.createObjectURL(selectedImage);
};

const uploadImages = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.uploadImages.push(selectedImage.target.files[index]);
        state.uploadImageUrls.push(URL.createObjectURL(selectedImage.target.files[index]));
    }
};

const uploadVideo = (selectedVideo) => {
    for (let index = 0; index < selectedVideo.target.files.length; index++) {
        state.uploadVideos.push(selectedVideo.target.files[index]);
        state.uploadVideoUrls.push(URL.createObjectURL(selectedVideo.target.files[index]));
    }
};

const removeUploadProductImage = (index) => {
    state.uploadImageUrls.splice(index, 1);
    state.uploadImages.splice(index, 1);
};

const removeUploadProductVideo = (index) => {
    state.uploadVideoUrls.splice(index, 1);
    state.uploadVideos.splice(index, 1);
};

const deleteOldImages = (value) => {
    state.delete_old_images = value;
};

const deleteOldVideos = (value) => {
    state.delete_old_videos = value;
};

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    state.uploadImages = [];
    state.uploadVideos = [];
    state.selectedArticleNumberForUploadImages = null;
    state.productDetailsForUploadImages = null;
    state.uploadImageUrls = [];
    state.uploadVideoUrls = [];
    state.thumbnail_url = null;
    emits('close-modal', true);
};
</script>
