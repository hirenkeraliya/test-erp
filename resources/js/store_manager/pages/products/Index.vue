<template>
    <PageTitle title="Products" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Products
        </h2>
    </div>

    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <ProductListFilters
            :product-statuses="productStatuses"
            :product-types="productTypes"
            :product-batches="productBatches"
            :product-sync-types="productSyncTypes"
            :all-product-sync-type="allProductSyncType"
            :all-status="allStatus"
            :categories-url="route('store_manager.categories.get_filtered_categories')"
            :brands-url="route('store_manager.brands.get_filtered_brands')"
            :sizes-url="route('store_manager.sizes.get_filtered_sizes')"
            :colors-url="route('store_manager.colors.get_filtered_colors')"
            :departments-url="route('store_manager.departments.get_filtered_departments')"
            :article-numbers-url="route('store_manager.products.get_filtered_article_number')"
            :styles-url="route('store_manager.styles.get_filtered_styles')"
            :tags-url="route('store_manager.tags.get_filtered_tags')"
            :product-collections-url="route('store_manager.product_collections.get_filtered_product_collection')"
            :attributes="attributes"
            @refresh-table="refreshTable"
            @update-params="updateParams"
        />
    </div>

    <JTable
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :fetch-url="route('store_manager.products.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-column-customization="true"
        :allow-pdf-export="true"
        :export-pdf-records-callback="exportPDFRecords"
        local-storage-key="store-manager-products-columns"
        search-title="Search by name, compound product name, code, upc, or retail price"
        @get-filter-columns="getFilterColumns"
    >
        <template #images="record">
            <Album
                class="cursor-pointer"
                @click="showImageModel(record.item.images)"
            />
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

        <template #name="record">
            <div class="flex items-center justify-left">
                <span>
                    {{ record.item.name }}
                </span>
            </div>
        </template>

        <template #external_reference="record">
            <div class="flex items-center justify-left">
                <span v-if="record.item.product_channel_reference">
                    External Product Id: {{ record.item.product_channel_reference.external_product_id }}<br>
                </span>
                <span v-else>N/A</span>
            </div>
        </template>

        <template #external_variant="record">
            <div class="flex items-center justify-left">
                <span v-if="record.item.product_channel_reference">
                    External Variant Id: {{ record.item.product_channel_reference.external_variant_id }}
                </span>
                <span v-else>N/A</span>
            </div>
        </template>

        <template #brand="record">
            {{ record.item.brand ? record.item.brand.name : 'N/A' }}
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(attribute, index) in record.item.attributes"
                    :key="index"
                    class="flex"
                >
                    {{ index }} : {{ attribute }}
                </p>
            </span>
            <span v-else>
                {{ record.item.color ? record.item.color.name : 'N/A' }}
            </span>
        </template>

        <template
            v-if="!pageProps.product_variant"
            #color="record"
        >
            {{ record.item.color ? record.item.color.name : 'N/A' }}
        </template>

        <template
            v-if="!pageProps.product_variant"
            #size="record"
        >
            {{ record.item.size ? record.item.size.name : 'N/A' }}
        </template>

        <template
            v-if="!pageProps.product_variant"
            #style="record"
        >
            {{ record.item.style ? record.item.style.name : 'N/A' }}
        </template>

        <template #department="record">
            {{ record.item.department ? record.item.department.name : 'N/A' }}
        </template>

        <template #retail_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.retail_price) }}
        </template>

        <template #franchise_price_1="record">
            {{ displayAmountWithCurrencySymbol(record.item.franchise_price_1) }}
        </template>

        <template #franchise_price_2="record">
            {{ displayAmountWithCurrencySymbol(record.item.franchise_price_2) }}
        </template>

        <template #franchise_price_3="record">
            {{ displayAmountWithCurrencySymbol(record.item.franchise_price_3) }}
        </template>

        <template #wholesale_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.wholesale_price) }}
        </template>

        <template #company_or_tender_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.company_or_tender_price) }}
        </template>

        <template #branch_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.branch_price) }}
        </template>

        <template #minimum_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.minimum_price) }}
        </template>

        <template #original_capital_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.original_capital_price) }}
        </template>

        <template #capital_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.capital_price) }}
        </template>

        <template #staff_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.staff_price) }}
        </template>

        <template #purchase_cost="record">
            {{ displayAmountWithCurrencySymbol(record.item.purchase_cost) }}
        </template>

        <template #online_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.online_price) }}
        </template>

        <template #categories="record">
            <span v-if="record.item.categories?.length">
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
            <div
                class="flex justify-center items-center cursor-pointer"
            >
                <div
                    class="flex items-center mr-3"
                    @click="showProductDetailModal(data.item)"
                >
                    <View class="w-4 h-4 mr-2" />
                    View
                </div>

                <Tippy
                    v-if="havePermission(uploadImagePermission) && (data.item.status === activeProduct)"
                    content="Upload Product Image"
                    class="cursor-pointer ml-2"
                    @click="openModalForUploadImage(data.item)"
                >
                    <Upload class="w-4 h-4 mr-1" />
                </Tippy>
            </div>
        </template>

        <template #extra-header-data>
            <p
                v-if="exportRecordCount > 0"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <Tippy
                    content="Export History"
                    class="btn btn-outline-primary"
                    @click="showExportProductData()"
                >
                    <FileClock class="text-primary w-8 cursor-pointer" />
                </Tippy>
            </p>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
            <div
                v-if="state.isExportFileInProgress"
                class="bg-slate-300 rounded p-1 w-12 flex justify-center"
            >
                <LoaderSvg />
            </div>

            <div
                v-else
                class="text-center focus:ring-0 mr-3"
            >
                <Dropdown
                    v-slot="{ dismiss }"
                    class="dropdown"
                >
                    <DropdownToggle
                        tag="a"
                        class="btn btn-outline-primary"
                        href="javascript:;"
                    >
                        Export
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                @click="exportCsvRecords(dismiss)"
                            >
                                CSV
                            </DropdownItem>

                            <DropdownItem
                                @click="exportExcelRecords(dismiss)"
                            >
                                EXCEL
                            </DropdownItem>

                            <DropdownItem
                                @click="exportExcelRecordsForBulkProductUpdate(dismiss)"
                            >
                                EXCEL BULK PRODUCT UPDATES
                            </DropdownItem>

                            <DropdownItem
                                @click="exportLoyaltyPointCsvRecords(dismiss)"
                            >
                                LOYALTY POINT CSV
                            </DropdownItem>

                            <DropdownItem
                                @click="exportLoyaltyPointExcelRecords(dismiss)"
                            >
                                LOYALTY POINT EXCEL
                            </DropdownItem>

                            <DropdownItem
                                @click="exportBoxProductCsvRecords(dismiss)"
                            >
                                BOX PRODUCT CSV
                            </DropdownItem>

                            <DropdownItem
                                @click="exportBoxProductExcelRecords(dismiss)"
                            >
                                BOX PRODUCT EXCEL
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>
    </JTable>

    <Modal
        size="modal-lg"
        :show="state.openModalForUploadImage"
        @hidden="hideUploadProductImageModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Upload Product Image
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideUploadProductImageModal()"
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
    <ProductViewDetails
        :modal-show="state.displayProductDetailsModal"
        :product-details="state.selectedProduct"
        @close-modal="state.displayProductDetailsModal = false"
    />

    <ImageVideoViewer
        v-if="state.displayImageVideoViewerModal"
        :modal-show="state.displayImageVideoViewerModal"
        :product-image-details="state.productImageDetails"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive, computed, onMounted } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import { ChevronRight, View, Album, Upload, X, FileClock } from 'lucide-vue-next';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { displayAmountWithCurrencySymbol, exportRecords, printReport, havePermission } from '@commonServices/helper';
import ProductViewDetails from '@commonComponents/ProductViewDetails.vue';
import ImageVideoViewer from '@commonComponents/ImageVideoViewer.vue';
import ProductListFilters from '@commonComponents/ProductListFilters.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { router, usePage  } from '@inertiajs/vue3';
import { showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import LoaderSvg from '@svg/LoaderSvg.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    productStatuses: {
        type: Array,
        required: true,
    },

    productBatches: {
        type: Array,
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

    productTypes: {
        type: Array,
        required: true,
    },

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

    exportType: {
        type: Number,
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

    exportRecordCount: {
        type: Number,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => {},
    },
});
const state = reactive({
    columns: [
        {
            key: 'images',
            label: 'Media',
            isDisplay: true,
        }, {
            key: 'thumbnail_url',
            label: 'Thumbnail',
            isDisplay: true,
        }, {
            key: 'name',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'external_reference',
            isDisplay: false,
        }, {
            key: 'external_variant',
            isDisplay: false,
        }, {
            key: 'code',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'brand',
            isDisplay: true,
        }, {
            key: 'attributes',
            isDisplay: true,
        }, {
            key: 'color',
            isDisplay: true,
        }, {
            key: 'size',
            isDisplay: true,
        }, {
            key: 'style',
            isDisplay: true,
        }, {
            key: 'department',
            isDisplay: true,
        }, {
            key: 'categories',
            isDisplay: true,
        }, {
            key: 'upc',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'article_number',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'retail_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'original_created_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'created_by',
            isDisplay: true,
        }, {
            key: 'approved_by',
            isDisplay: true,
        }, {
            key: 'last_editor_by',
            isDisplay: true,
        },  {
            key: 'created_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'updated_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'is_temporarily_unavailable',
            isDisplay: false,
        }, {
            key: 'ean',
            isDisplay: false,
        }, {
            key: 'custom_sku',
            isDisplay: false,
        },{
            key: 'manufacturer_sku',
            isDisplay: false,
        },{
            key: 'type_id',
            isDisplay: false,
        },{
            key: 'franchise_price_1',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'franchise_price_2',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'franchise_price_3',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'wholesale_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'company_or_tender_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'branch_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'minimum_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'original_capital_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'capital_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'staff_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'purchase_cost',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'online_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'subcategory_name',
            isDisplay: false,
        },{
            key: 'subsubcategory_name',
            isDisplay: false,
        },{
            key: 'has_batch',
            isDisplay: false,
        },{
            key: 'is_non_inventory',
            isDisplay: false,
        },{
            key: 'is_non_selling_item',
            isDisplay: false,
        },{
            key: 'is_available_in_pos',
            isDisplay: false,
        },{
            key: 'is_available_in_ecommerce',
            isDisplay: false,
        },{
            key: 'is_sold_as_single_item',
            isDisplay: false,
        },{
            key: 'sell_item_via_derivative',
            isDisplay: false,
        },{
            key: 'tags',
            isDisplay: false,
        },{
            key: 'vendor',
            isDisplay: false,
        },{
            key: 'sale_channels',
            isDisplay: false,
        },{
            key: 'unit_of_measure',
            isDisplay: false,
        }, {
            key: 'description',
            isDisplay: false,
        }, {
            key: 'season',
            isDisplay: false,
        }, {
            key: 'sub_department',
            isDisplay: false,
        }, {
            key: 'action',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    dynamicColumns: [],
    displayProductDetailsModal: false,
    selectedProduct: [],
    openModalForUploadImage: false,
    uploadImage: null,
    image_url: null,
    productId: null,
    isExportFileInProgress: false,
    parameters: {
        status: props.allStatus,
        batch: props.allBatch,
        date_range: null,
        product_type_id: null,
        category_ids: null,
        brand_ids: null,
        color_ids: null,
        size_ids: null,
        department_ids: null,
        article_numbers: null,
        tag_ids: null,
        style_ids: null,
        export_columns: null,
    },
    displayProductFilter: false,
    refreshTableData: Math.random(),
    selectedCategories: null,
    selectedBrands: null,
    selectedSizes: null,
    selectedColors: null,
    selectedDepartments: null,
    selectedTags: null,
    selectedArticleNumber: null,
    selectedStyles: null,
    displayImageVideoViewerModal: false,
    productImageDetails: null
});

const getFilteredColumns = () => {
    const columns = state.columns || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size', 'style'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'attributes');
};

onMounted(() => {
    state.dynamicColumns = getFilteredColumns();
});

const showImageModel = (productImages) => {
    state.productImageDetails = productImages;
    state.displayImageVideoViewerModal = true;
};

const openModalForUploadImage = (product) => {
    state.openModalForUploadImage = true;
    state.productId = product.id;
    state.image_url = product.image_url;
};

const uploadImage = (selectedImage) => {
    state.image_url = URL.createObjectURL(selectedImage);
};

const saveUploadImageProduct = () => {
    router.post(route('store_manager.products.upload_image'), {
        product_id: state.productId,
        image: state.uploadImage,
    }, {
        onSuccess: () => {
            state.openModalForUploadImage = false;
            router.get(route('store_manager.products.index'));
        },
        onError: (error) => {
            showErrorNotification(error.image);
        }
    });
};

const hideUploadProductImageModal = () => {
    state.openModalForUploadImage = false;
};

const updateParams = (params) => {
    state.parameters = params;
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const showProductDetailModal = (product) => {
    state.displayProductDetailsModal = true;
    state.selectedProduct = product;
};

const exportCsvRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-products/',
                    'products.csv',
                    state.parameters,
                    props.exportPermission,
                    state.parameters.export_columns
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportExcelRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-products/',
                    'products.xlsx',
                    state.parameters,
                    props.exportPermission,
                    state.parameters.export_columns
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('store_manager.products.print_products', params), props.exportPermission);
};

const exportExcelRecordsForBulkProductUpdate = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_product_export_limit_for_import_bulk_update', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-products-for-import-bulk-update/',
                    'products.xlsx',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const closeModal = () => {
    state.displayImageVideoViewerModal = false;
};

const showExportProductData = () => {
    router.get(route('store_manager.export_records.index', { export_type: props.exportType }));
};

const exportLoyaltyPointCsvRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_product_loyalty_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-loyalty-point-products/',
                    'loyalty-point-products.csv',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportLoyaltyPointExcelRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_product_loyalty_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-loyalty-point-products/',
                    'loyalty-point-products.xlsx',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportBoxProductCsvRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_box_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-box-products/',
                    'box-products.csv',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportBoxProductExcelRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('store_manager.products.check_box_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-box-products/',
                    'box-products.xlsx',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const getFilterColumns = (columns) => {
    state.parameters.export_columns = columns;
};

</script>
