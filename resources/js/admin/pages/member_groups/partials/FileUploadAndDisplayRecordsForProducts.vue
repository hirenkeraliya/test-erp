<template>
    <div class="block sm:flex flex-col mb-3 -mx-3 sm:flex-row">
        <div class="w-full px-3">
            <JFileUpload
                v-model:input-file="state.uploaded_file"
                accept=".xlsx, .xls, .ods"
                input-label="Upload products"
                validation-field-name="product_file"
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
                View All Imported Products
            </button>
        </div>
    </div>

    <Modal
        size="modal-xl"
        :show="state.display_selected_products_modal"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Products
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="sm:p-10 sm:pt-1">
            <div
                v-if="allowToClearSelectedProducts"
                class="row text-right mb-2"
            >
                <OutlineDangerButton
                    v-if="allowToClearSelectedProducts"
                    type="button"
                    text="Clear Products"
                    class="btn-sm w-30 h-10 mt-3 mr-2"
                    @click="clearSelectedProducts"
                />
            </div>

            <JSimpleTable
                :allow-search="true"
                :columns="state.fields"
                :records="selectedProducts"
                :totals="selectedProducts"
                :allow-pagination-and-sorting="allowPaginationAndSorting"
                first-div-class="pb-2 sm:pb-5 mt-0 intro-y"
            >
                <template
                    v-for="column in state.fields"
                    :key="column.key"
                    #[column.key]="record"
                >
                    <slot
                        :name="column.key"
                        :item="record.item"
                    />
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { reactive } from 'vue';

defineProps({
    selectedProducts: {
        type: Array,
        default: () => {},
    },
    filePath: {
        type: String,
        default: "",
    },
    allowToClearSelectedProducts: {
        type: Boolean,
        default: false,
    },
    allowPaginationAndSorting: {
        type: Boolean,
        default: true,
    },
});

const state = reactive({
    uploaded_file: null,
    display_selected_products_modal: false,
    fields: [
        {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
    ],
});

const emits = defineEmits([
    'get-products-upload-file',
    'clear-selected-products',
]);

const closeModal = () => {
    if (state.display_selected_products_modal) {
        state.display_selected_products_modal = false;
        return;
    }
};

const openSelectedProducts = () => {
    state.display_selected_products_modal = true;
};

const importRecords = (event) => {
    emits('get-products-upload-file', event);
};

const clearSelectedProducts = () => {
    closeModal();
    emits('clear-selected-products');
};
</script>