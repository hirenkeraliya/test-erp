<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-10">
            <div v-if="productImageDetails">
                <div class="flex flex-col">
                    <div class="flex justify-between mt-4">
                        <div class="col-span-12 mt-4">
                            <h1 class="mb-2 font-medium text-md">
                                Thumbnail
                            </h1>
                            <div v-if="productImageDetails.thumbnail_url">
                                <img
                                    :src="productImageDetails.thumbnail_url"
                                    width="100"
                                    class="mt-2"
                                >
                            </div>
                            <div v-else>
                                N/A
                            </div>
                        </div>
                    </div>
                    <div class="col-span-12 mt-4">
                        <h1 class="mb-2 font-medium text-md">
                            Images
                        </h1>
                        <div v-if="productImageDetails.image_urls.length > 0">
                            <div class="flex gap-5">
                                <div
                                    v-for="(imageUrl, index) in productImageDetails.image_urls"
                                    :key="index"
                                >
                                    <img
                                        :src="imageUrl.url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <span class="font-medium text-md">
                                No Images Found.
                            </span>
                        </div>
                    </div>
                    <div class="col-span-12 mt-4">
                        <h1 class="mb-2 font-medium text-md">
                            Videos
                        </h1>
                        <div v-if="productImageDetails.video_urls.length > 0">
                            <div
                                v-for="(videoUrl, index) in productImageDetails.video_urls"
                                :key="index"
                            >
                                <video
                                    controls
                                    class="w-full"
                                >
                                    <source :src="videoUrl.url">
                                </video>
                            </div>
                        </div>

                        <div v-else>
                            <span class="font-medium text-md">
                                No Videos Found.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Cancel"
                    class="w-24 mr-1"
                    @click="closeModal"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Image-Video Viewer'
    },
    productImageDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal');
};
</script>
