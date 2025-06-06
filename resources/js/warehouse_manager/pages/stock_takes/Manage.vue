<template>
    <PageTitle title="Stock Take Products" />

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        The changes of this page are auto-saved i.e. As soon as you change a value in any of the fields, the new values are
        saved automatically.
    </InfoAlert>

    <div class="intro-y xl:flex items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Take Products
        </h2>

        <div class="w-full sm:w-auto lg:flex mt-4 xl:mt-0">
            <OutlinePrimaryButton
                text="Bulk Update Submitted Stock"
                class="shadow-md text-sm mx-2 mb-2 lg:mb-0"
                @click="state.showModal = true; state.showBulkSubmittedStockContentInModal = true"
            />

            <JBadge
                :label="'Pending stock products submission count: ' + state.pendingStockSubmissionCount"
                class=" mb-2 lg:mb-0"
            />

            <JBadge
                :label="'Grand Submitted Stock: ' + state.grandTotalSubmittedStock"
                class=" mb-2 lg:mb-0"
            />

            <Link :href="route('warehouse_manager.stock_takes.index')">
                <SecondaryButton
                    type="button"
                    text="Save as Draft"
                    class="w-30 mr-1 mb-2 lg:mb-0"
                />
            </Link>

            <PrimaryButton
                text="Submit"
                class="shadow-md mb-2 lg:mb-0"
                :disabled="state.uploadStocks"
                @click="state.showModal = true; state.showSubmitStockTakeContentInModal = true"
            />

            <OutlinePrimaryButton
                text="Advance Selection"
                class="shadow-md text-sm mx-1 mb-2 lg:mb-0"
                @click="displayAdvanceMatrixProductSearchModalButton"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route('warehouse_manager.stock_takes.fetch_stock_take_products', props.stockTakeId)"
        :columns="state.columns"
    >
        <template #items="data">
            <JSimpleTable
                :columns="state.innerColumns"
                :records="Object.values(data.item.items)"
                :allow-search="true"
            >
                <template #submitted_stock="innerRow">
                    <FormInputNumber
                        v-model:input-value="innerRow.item.submitted_stock"
                        :is-maximum-increment-required="false"
                        @update:input-value="updateSubmittedStock($event, innerRow.item.stock_take_product_id, innerRow.item.id)"
                    />
                </template>
            </JSimpleTable>
        </template>

        <template #total_submitted_stock="data">
            <div class="-mt-24 flex justify-center">
                <Tippy content="Submitted Stock">
                    <JBadge
                        v-if="data.item.total_submitted_stock"
                        :label="data.item.total_submitted_stock"
                    />
                </Tippy>

                <Tippy content="Submitted Stock">
                    <JBadge
                        v-if="data.item.total_submitted_stock === 0"
                        :label="data.item.total_submitted_stock"
                        type="danger"
                    />
                </Tippy>
            </div>
        </template>
    </JTable>

    <Modal
        :size="state.showSubmitStockTakeContentInModal ? 'modal-md' : 'modal-xl'"
        :show="state.showModal"
        @hidden="closeMainModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Bulk Update Submitted Stock
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="closeMainModal"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <span v-if="state.showSubmitStockTakeContentInModal">
                <InfoAlert
                    color="primary"
                    class="mb-3"
                >
                    Are you sure you want to submit the stock take? This action is irreversible.
                </InfoAlert>

                <form @submit.prevent="submitStockTake();">
                    <JDatePicker
                        v-model:input-value="state.stockTakeForm.compare_stock_date"
                        input-label="Compare Record Date"
                        :max-date="new Date()"
                        :required="true"
                    />

                    <div class="mt-5">
                        <PrimaryButton
                            type="submit"
                            text="Submit"
                            class="w-24"
                        />
                    </div>
                </form>
            </span>

            <span v-if="state.showBulkSubmittedStockContentInModal">
                <InfoAlert
                    color="primary"
                    class="mb-5"
                >
                    Export the file by clicking on the export button and update the submitted stock. After the updates,
                    import that file to perform the bulk updates.
                </InfoAlert>
                <div class="w-full px-3 text-right">
                    <ExportDropDown
                        class="mr-3"
                        :allow-csv-export="true"
                        :allow-excel-export="true"
                        @update:export-csv-file="exportCsvRecord(props.stockTakeId, filterState)"
                        @update:export-excel-file="exportExcelRecord(props.stockTakeId, filterState)"
                    />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5 mt-2">
                    <div>
                        <FormAjaxSelect
                            :selected-record="state.selectedBrands"
                            :search-records="searchBrand"
                            :multi-select="true"
                            input-label="Brands"
                            placeholder="Please type the name of the brand to search."
                            @update:selected-record="selectBrands"
                        />
                    </div>

                    <div>
                        <FormAjaxSelect
                            :selected-record="state.selectedDepartments"
                            :search-records="searchDepartment"
                            :multi-select="true"
                            input-label="Department"
                            placeholder="Please type the name of the department to search."
                            @update:selected-record="selectDepartments"
                        />
                    </div>

                    <div>
                        <FormAjaxSelect
                            :selected-record="state.selectedSizes"
                            :search-records="searchSize"
                            :multi-select="true"
                            input-label="Sizes"
                            placeholder="Please type the name of the size to search."
                            @update:selected-record="selectSizes"
                        />
                    </div>

                    <div>
                        <FormAjaxSelect
                            :selected-record="state.selectedColors"
                            :search-records="searchColor"
                            :multi-select="true"
                            input-label="Colors"
                            placeholder="Please type the name of the color to search."
                            @update:selected-record="selectColors"
                        />
                    </div>

                    <div>
                        <JFileUpload
                            v-model:input-file="stockTakeForm.stock_take_bulk_submitted_stocks"
                            class="mt-9"
                            accept=".xlsx, .xls, .ods"
                            validation-field-name="bulk_update_stock_take_stock"
                            input-label="Bulk Update Submitted Stock"
                            :required="state.selectedProducts.length ? false : true"
                            @update:input-file="importRecords($event)"
                        />
                    </div>
                </div>

                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24 mt-3"
                    @click="submitBulkUpdates"
                />
            </span>
        </ModalBody>
    </Modal>

    <AdvanceProductSelectionModalForStockTake
        v-if="state.displayAdvanceProductSelectionModal"
        :modal-show="state.displayAdvanceProductSelectionModal"
        product-article-search-url="warehouse_manager.products.search_products_by_article_number"
        @update:filter-advance-product-selection="advanceFilterProductSelection"
        @close-modal="closeAdvanceProductSelectionModal()"
    />
</template>

<script setup>
import FormInputNumber from '@commonComponents/FormInputNumber.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import JTable from '@commonComponents/JTable.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import axios from 'axios';
import { X } from 'lucide-vue-next';
import { router, useForm } from '@inertiajs/vue3';
import JBadge from '@commonComponents/JBadge.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import ExportDropDown from '@commonComponents/ExportDropDown.vue';
import { exportRecords } from '@commonServices/helper';
import XLSX from 'xlsx';
import onScan from 'onscan.js/onscan.js';
import AdvanceProductSelectionModalForStockTake from '@commonComponents/AdvanceProductSelectionModalForStockTake.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('store_manager.brands.get_filtered_brands'), filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('store_manager.departments.get_filtered_departments'), filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('store_manager.sizes.get_filtered_sizes'), filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const searchColor = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('store_manager.colors.get_filtered_colors'), filterData).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};

const selectBrands = (selectedBrands) => {
    state.selectedBrands = selectedBrands;
    if (selectedBrands !== null) {
        filterState.brand_ids = selectedBrands.map(function (brand) {
            return brand.id;
        });
    }
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        filterState.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
};

const selectSizes = (selectedSizes) => {
    state.selectedSizes = selectedSizes;
    if (selectedSizes !== null) {
        filterState.size_ids = selectedSizes.map(function (size) {
            return size.id;
        });
    }
};

const selectColors = (selectedColors) => {
    state.selectedColors = selectedColors;
    if (selectedColors !== null) {
        filterState.color_ids = selectedColors.map(function (color) {
            return color.id;
        });
    }
};

const filterState = reactive({
    department_ids: null,
    brand_ids: null,
    color_ids: null,
    size_ids: null,
});

const state = reactive({
    columns: [
        {
            key: 'product',
            rowSpanClass: '-mt-24 block',
        }, {
            key: 'article_number',
            rowSpanClass: '-mt-24 block',
        }, {
            key: 'items',
        }, {
            key: 'total_submitted_stock',
            label: 'Submitted Stock',
            bodyClass: 'text-center',
            rowSpanClass: '-mt-24',
        }
    ],
    innerColumns: [
        {
            key: 'id',
            bodyClass: 'text-center'
        }, {
            key: 'UPC',
        }, {
            key: 'color',
        }, {
            key: 'size',
        }, {
            key: 'unit_of_measure',
        }, {
            key: 'submitted_stock',
            bodyClass: 'text-center'
        }
    ],
    stockTakeForm: {
        compare_stock_date: null,
    },
    records: [],
    pendingStockSubmissionCount: 0,
    grandTotalSubmittedStock: 0,
    showModal: false,
    uploadStocks: false,
    showBulkSubmittedStockContentInModal: false,
    showSubmitStockTakeContentInModal: false,
    selectedProducts: [],
    importBulkUpdates: [],
    refreshTableData: Math.random(),
    selectedBrands: null,
    selectedDepartments: null,
    selectedSizes: null,
    selectedColors: null,
});

const props = defineProps({
    stockTakeId: {
        type: Number,
        required: true,
    },
});

const closeMainModal = () => {
    state.showModal = false;
    state.showSubmitStockTakeContentInModal = false;
    state.showBulkSubmittedStockContentInModal = false;
};

const exportCsvRecord = (stockTakeId, params) => {
    return exportRecords(
        '/warehouse-manager/download-stock-take-products/' + stockTakeId + '/',
        'stock-take-products-' + stockTakeId + '.csv',
        params
    );
};

const exportExcelRecord = (stockTakeId, params) => {
    return exportRecords(
        '/warehouse-manager/download-stock-take-products/' + stockTakeId + '/',
        'stock-take-products-' + stockTakeId + '.xlsx',
        params
    );
};

const updateSubmittedStock = (submittedStock, stockTakeProductId, productId) => {
    const inputValue = submittedStock < 0 ? 0 : submittedStock;

    axios.post(route('warehouse_manager.stock_takes.update_submitted_stock', props.stockTakeId), {
        stock_take_product_id: stockTakeProductId,
        product_id: productId,
        submitted_stock: parseFloat(inputValue),
    });

    getPendingStockProductsSubmissionCount();
    getGrandTotalSubmittedStock();
};

const submitStockTake = () => {
    if (!state.stockTakeForm.compare_stock_date) {
        showErrorNotification('Comparing stock data is required to submit the stock take.');
        return;
    }

    router.post(route('warehouse_manager.stock_takes.submit', props.stockTakeId), {
        compare_stock_date: state.stockTakeForm.compare_stock_date
    });
    state.showModal = false;
};

const importRecords = (files) => {
    const reader = new FileReader();

    if (files.name.endsWith('.csv')) {
        showErrorNotification('.csv File Upload Is Not Allowed.');
        stockTakeForm.stock_take_bulk_submitted_stocks = null;
        return;
    }

    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const records = JSON.parse(JSON.stringify(XLSX.utils.sheet_to_json(worksheet,
            {
                blankRows: false,
                defval: null,
                raw: false
            }
        )).replace(/"\s+|\s+"/g, '"'));

        state.importBulkUpdates = [];

        for (const key in records) {
            if (!records[key].upc) {
                showErrorNotification('UPC is required.');
                if (props.validationFieldName) {
                    document.getElementById('bulk_update_stock_take_stock').value = '';
                }
                return;
            }

            state.importBulkUpdates.push({ upc: records[key].upc, submitted_stock: records[key].submitted_stock });
        }

        showSuccessNotification('The bulk updates file was uploaded successfully.');

        document.getElementById('bulk_update_stock_take_stock').value = '';
    };

    reader.readAsArrayBuffer(files);
};

const stockTakeForm = useForm({
    stock_take_bulk_submitted_stocks: null,
});

const submitBulkUpdates = () => {
    if (stockTakeForm.stock_take_bulk_submitted_stocks === null) {
        return showErrorNotification('Please Upload A File.');
    }

    state.uploadStocks = true;
    state.showModal = false;

    state.pendingStockSubmissionCount = 0; // here assumed that all products stock uploaded.

    stockTakeForm.post(route('warehouse_manager.stock_takes.bulk_update_stocks', props.stockTakeId), {
        onSuccess: (page) => {
            if (page.props.flash.success === null && page.props.flash.error !== null) {
                showSuccessNotification(page.props.flash.error);
                return;
            }

            showSuccessNotification('File uploaded successfully. The import process will occur in the background. We will notify you via email once the import is complete.');
            router.get(route('warehouse_manager.stock_takes.index'));
        }
    });
};

const getPendingStockProductsSubmissionCount = () => {
    axios.get(route('warehouse_manager.stock_takes.get_pending_stock_product_submission_count', props.stockTakeId)).then((response) => {
        state.pendingStockSubmissionCount = response.data.pending_stock_products_submission_count;
    });
};

onMounted(() => {
    getGrandTotalSubmittedStock();

    getPendingStockProductsSubmissionCount();

    onScanProductCheck();
});

const displayAdvanceMatrixProductSearchModalButton = () => {
    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
    state.displayAdvanceProductSelectionModal = true;
};

const onScanProductCheck = () => {
    onScan.attachTo(document);
};

const closeAdvanceProductSelectionModal = () => {
    state.displayAdvanceProductSelectionModal = false;
    onScanProductCheck();
};

const advanceFilterProductSelection = (selectedProducts) => {
    const productDetails = selectedProducts.reduce((withoutEmptyStockProducts, selectedProduct) => {
        if (selectedProduct.stock !== null) {
            withoutEmptyStockProducts.push({
                product_id: selectedProduct.id,
                submitted_stock: selectedProduct.stock
            });
        }

        return withoutEmptyStockProducts;
    }, []);

    if (productDetails.length === 0) {
        return;
    }

    const httpStatusOk = 200;

    axios.post(route('warehouse_manager.stock_takes.update_submitted_stock_by_stock_id', props.stockTakeId), {
        products: productDetails,
    }).then((response) => {
        if (response.status === httpStatusOk) {
            getPendingStockProductsSubmissionCount();
        }
    }).catch((error) => {
        if (error.response.data.message) {
            showErrorNotification(error.response.data.message);
        }
    });
};

const getGrandTotalSubmittedStock = () => {
    axios.get(route('warehouse_manager.stock_takes.grand_total_submitted_stock', props.stockTakeId))
        .then((response) => {
            state.grandTotalSubmittedStock = response.data.grandTotal;
        });
};
</script>
