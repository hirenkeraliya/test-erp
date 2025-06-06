<template>
    <div class="block sm:flex flex-col mb-3 -mx-3 sm:flex-row">
        <div class="w-full px-3">
            <JFileUpload
                v-model:input-file="state.uploaded_file"
                accept=".xlsx, .xls, .ods"
                :input-label="inputLabel"
                :validation-field-name="validationFieldName"
                @update:input-file="importRecords"
            />
        </div>

        <div
            v-if="filePath"
            class="w-full px-3 mt-4 sm:mt-0"
        >
            <JFileDownload
                :file-path="filePath"
                input-label="Download Sample File"
            />
        </div>
    </div>

    <div class="block sm:flex w-full my-2">
        <div class="w-full pr-0 sm:pr-3" />
        <div class="w-full pl-0 text-left sm:pl-3">
            <button
                :disabled="! selectedProducts.length"
                class="px-8 text-sm font-bold rounded-r-lg btn py-18 text-black-40 bg-slate-300"
                type="button"
                @click="openSelectedProducts"
            >
                View All Imported Products ({{ selectedProducts.length }})
            </button>
        </div>
    </div>
</template>

<script setup>
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import { reactive } from 'vue';

defineProps({
    selectedProducts: {
        type: Array,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    filePath: {
        type: String,
        default: null,
    },
    unmatchedProducts: {
        type: Array,
        default: null,
    },
    getRecordName: {
        type: String,
        default: null,
    },
    dataPropertyNames: {
        type: Array,
        default: () => []
    },
    matchedProductsList: {
        type: Array,
        default: () => []
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    productUpcUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    uploaded_file: null,
});

const emits = defineEmits([
    'display-selected-products-modal',
    'get-upload-file',
]);

const openSelectedProducts = () => {
    emits('display-selected-products-modal');
};

const importRecords = (event) => {
    emits('get-upload-file', event);
};
</script>
