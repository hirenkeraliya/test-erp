<template>
    <div
        v-if="state.imagePreview"
        class="object-fill mx-auto my-auto"
    >
        <img
            ref="image"
            class="block max-w-full"
            :src="state.imagePreview"
        >
    </div>

    <div class="content-end mt-3">
        <span v-if="!state.imagePreview">
            <label :for="validationFieldName">
                {{ inputLabel }}
                <span
                    v-if="required"
                    class="text-danger"
                >*</span>
            </label>
            <input
                class="form-control shadow-transparent"
                type="file"
                accept="image/*"
                :required="required"
                @change="updateFile"
            >
        </span>

        <div class="mt-3">
            <button
                v-if="state.imagePreview"
                class="btn btn-blue w-32 mx-3 ml-auto"
                type="button"
                @click="uploadCroppedImage"
            >
                Upload Image
            </button>
            <button
                v-if="state.selectedFile && state.imagePreview"
                class="btn btn-gray w-32 mx-1 mt-3"
                type="button"
                @click="clearFile"
            >
                Cancel
            </button>
        </div>
        <ValidationError :validation-field-name="validationFieldName" />
    </div>
</template>

<script setup>
import { ref, onUnmounted, reactive, nextTick } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import ValidationError from '@commonComponents/ValidationError.vue';

const props = defineProps({
    inputLabel: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    inputFile: {
        type: File,
        default: null,
    },
    maxWidth: {
        type: Number,
        default: 300,
    },
    maxHeight: {
        type: Number,
        default: 300,
    },
});

const emits = defineEmits(['update:input-file']);

const image = ref(null);

const state = reactive({
    selectedFile: null,
    imagePreview: props.inputFile || null,
    fileReader: new FileReader(),
    cropper: null,
    cropperConfig: {
        aspectRatio: 1,
        dragMode: 'move',
        background: false,
        cropBoxMovable: false,
        cropBoxResizable: false,
        zoomable: false,
        guides: false,
        autoCropArea: 1,
        zoomOnWheel: false,
        model: false,
        autoCrop: false,
        width: props.maxWidth,
        height: props.maxHeight,
        movable: false,
    },
});

const getCroppedCanvas = () => {
    return state.cropper.getCroppedCanvas({
        height: props.maxHeight,
        width: props.maxWidth,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
};

const uploadCroppedImage = () => {
    const canvas = getCroppedCanvas();

    canvas.toBlob((blob) => {
        const fileName = state.selectedFile?.name;
        const fileType = state.selectedFile?.type;
        const file = new File([blob], fileName, {
            type: fileType,
        });

        emits('update:input-file', file);
    });

    if (state.cropper) {
        state.imagePreview = null;
        state.cropper.destroy();
        state.cropper = null;
    }
};

const getImageDimensions = (file) => {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => resolve({ width: img.width, height: img.height });
        img.src = URL.createObjectURL(file);
    });
};

const updateFile = (inputFile) => {
    state.selectedFile = inputFile.target.files[0];
    state.fileReader.readAsDataURL(state.selectedFile);
    const timeoutDelay = 200;

    setTimeout(async () => {
        state.imagePreview = state.fileReader.result;

        if (state.cropper) {
            state.cropper.replace(state.imagePreview);
        }

        if (state.selectedFile) {
            const { width, height } = await getImageDimensions(state.selectedFile);

            if (width >= props.maxWidth && height >= props.maxHeight) {
                state.cropperConfig.background = true;
                state.cropperConfig.cropBoxMovable = true;
                state.cropperConfig.cropBoxResizable = true;
                state.cropperConfig.zoomable = true;
                state.cropperConfig.guides = true;
                state.cropperConfig.autoCrop = true;
                state.cropperConfig.zoomOnWheel = true;
                state.cropperConfig.movable = true;
                state.cropperConfig.model = true;
                state.cropperConfig.aspectRatio = getAspectRatio();

                nextTick(() => {
                    state.cropper = new Cropper(image.value, {
                        ...state.cropperConfig,
                    });
                });
                return;
            }

            state.imagePreview = null;

            emits('update:input-file', state.selectedFile);
        }
    }, timeoutDelay);
};

const greatestCommonDivisor = (numberOne, numberTwo) => {
    if (!numberTwo) {
        return numberOne;
    }
    return greatestCommonDivisor(numberTwo, numberOne % numberTwo);
};

const getAspectRatio = () => {
    const divisor = greatestCommonDivisor(props.maxWidth, props.maxHeight);
    const aspectWidth = props.maxWidth / divisor;
    const aspectHeight = props.maxHeight / divisor;

    return aspectWidth / aspectHeight;
};

const clearFile = () => {
    state.selectedFile = null;
    state.imagePreview = null;
    if (state.cropper) {
        state.cropper.destroy();
        state.cropper = null;
    }
};

onUnmounted(() => {
    if (state.cropper) {
        state.cropper.destroy();
    }
});
</script>
