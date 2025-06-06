<template>
    <PageTitle title="Master Products" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Master Products
        </h2>

        <div
            v-if="saleChannel > 0"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync Data"
                class="btn btn-outline-primary"
                @click="syncData()"
            >
                <RefreshCw class="text-primary w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.master_products.create')">
                <PrimaryButton
                    text="Add New Master Product"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayMasterProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <MasterProductListFilters
            :product-statuses="productStatuses"
            :product-types="productTypes"
            :product-batches="productBatches"
            :product-sync-types="productSyncTypes"
            :all-product-sync-type="allProductSyncType"
            :all-status="allStatus"
            :categories-url="route('admin.categories.get_filtered_categories')"
            :brands-url="route('admin.brands.get_filtered_brands')"
            :departments-url="route('admin.departments.get_filtered_departments')"
            :article-numbers-url="route('admin.master_products.get_filtered_master_product_article_number')"
            @refresh-table="refreshTable"
            @update-params="updateParams"
        />
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.master_products.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-items-columns"
        search-title="Search by name code"
    >
        <template #images="record">
            <Album
                class="cursor-pointer"
                @click="showImageModel(record.item.images)"
            />
        </template>

        <template #name="record">
            <div class="flex items-center justify-left">
                <span>
                    {{ record.item.name }}
                </span>
            </div>
        </template>

        <template #thumbnail_url="record">
            <img
                v-if="record.item.thumbnail_url"
                :src="record.item.thumbnail_url"
                :alt="record.item.name"
                width="100"
            >
            <span v-else>N/A</span>
        </template>

        <template #brand="record">
            {{ record.item.brand.name }}
        </template>


        <template #categories="record">
            <span v-if="record.item.categories.length">
                <span
                    v-for="(category, index) in record.item.categories"
                    :key="index"
                    class="inline-block"
                >
                    {{ category.name }}

                    <ChevronRight
                        v-if="index != record.item.categories.length - 1"
                        class="form-check w-4 h-4 text-slate-400 inline-block"
                    />
                </span>
            </span>
            <span v-else>
                N/A
            </span>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    v-if="(data.item.status === activeProduct)"
                    class="flex items-center mr-3"
                    :href="route('admin.master_products.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Dropdown
                    class="flex items-center mr-3"
                >
                    <DropdownToggle
                        tag="a"
                        class="w-5 h-5 block"
                        href="javascript:;"
                    >
                        <MoreHorizontal class="w-5 h-5 text-slate-500" />
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                v-if="havePermission(uploadImagePermission) && (data.item.status === activeProduct)"
                                class="flex items-center mr-3"
                                @click="openModalForUploadImage(data.item)"
                            >
                                <Upload class="w-4 h-4 mr-1" />
                                Upload Product Image
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayMasterProductFilter = !state.displayMasterProductFilter"
                />
            </p>
        </template>
    </JTable>

    <Modal
        size="modal-lg"
        :show="state.openModalForUploadImage"
        @hidden="hideUploadItemImageModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Upload Product Image
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideUploadItemImageModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <div>
                <form
                    @submit.prevent="saveUploadImageProduct();"
                >
                    <div>
                        <JFileCropUpload
                            v-model:input-file="state.uploadImage"
                            input-label="Thumbnail (343px X 260px)"
                            validation-field-name="photo"
                            :max-width="343"
                            :max-height="343"
                            @update:input-file="uploadImage"
                        />

                        <div
                            v-if="state.image_url"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <img
                                :src="state.image_url"
                                :alt="state.image_url"
                                width="100"
                                class="mt-2"
                            >
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

    <ImageVideoViewer
        v-if="state.displayImageVideoViewerModal"
        :modal-show="state.displayImageVideoViewerModal"
        :product-image-details="state.itemImageDetails"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import { CheckSquare, ChevronRight, X, MoreHorizontal, Upload, Album, RefreshCw } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import { exportRecords, havePermission } from '@commonServices/helper';
import ImageVideoViewer from '@commonComponents/ImageVideoViewer.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import MasterProductListFilters from '@commonComponents/MasterProductListFilters.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    uploadImagePermission: {
        type: String,
        required: true,
    },
    activeProduct: {
        type: Number,
        required: true,
    },
    saleChannel: {
        type: Number,
        required: true,
    },
    productStatuses: {
        type: Array,
        required: true,
    },
    productBatches: {
        type: Array,
        required: true,
    },
    productTypes: {
        type: Array,
        required: true,
    },
    productSyncTypes: {
        type: Array,
        required: true,
    },
    allProductSyncType: {
        type: Number,
        required: true,
    },
    allStatus: {
        type: String,
        required: true,
    },
    allBatch: {
        type: String,
        required: true,
    },
});
const state = reactive({
    columns: [
        {
            key: 'images',
            label: 'Media',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true
        }, {
            key: 'thumbnail_url',
            label: 'Thumbnail',
            isDisplay: true,
        }, {
            key: 'name',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'code',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'brand',
            isDisplay: true,
        },{
            key: 'categories',
            isDisplay: true,
        },  {
            key: 'article_number',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'original_created_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'created_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'updated_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        }
    ],
    parameters: {
        date_range: null,
        category_ids: null,
        article_numbers: null,
    },
    openModalForUploadImage: false,
    refreshTableData: Math.random(),
    image: '',
    uploadImage: null,
    masterProductId: null,
    image_url: null,
    displayImageVideoViewerModal: false,
    itemImageDetails: null,
    displayMasterProductFilter: false,
});

const showImageModel = (itemImages) => {
    state.itemImageDetails = itemImages;
    state.displayImageVideoViewerModal = true;
};

const uploadImage = (selectedImage) => {
    state.image_url = URL.createObjectURL(selectedImage);
};

const openModalForUploadImage = (masterProduct) => {
    state.openModalForUploadImage = true;
    state.masterProductId = masterProduct.id;
    state.image_url = masterProduct.image_url;
};

const saveUploadImageProduct = () => {
    router.post(route('admin.master_products.upload_image'), {
        master_product_id: state.masterProductId,
        image: state.uploadImage,
    }, {
        onSuccess: () => {
            state.openModalForUploadImage = false;
            router.get(route('admin.master_products.index'));
        },
        onError: (error) => {
            showErrorNotification(error.image);
        }
    });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-master-products/',
        'master-products.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-master-products/',
        'master-products.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const hideUploadItemImageModal = () => {
    state.openModalForUploadImage = false;
};

const closeModal = () => {
    state.displayImageVideoViewerModal = false;
};

const syncData = () => {
    axios.get(route('admin.master_products.sync_data'))
        .then(() => {
            showSuccessNotification('Successfully Synchronized');
        });
};

const updateParams = (params) => {
    state.parameters = params;
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};
</script>
