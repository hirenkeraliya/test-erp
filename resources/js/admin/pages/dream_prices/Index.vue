<template>
    <PageTitle title="Price Markdown" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Price Markdown
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.dream_prices.create')">
                <PrimaryButton
                    text="Add New Price Markdown"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayDreamPriceFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="statuses"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Status"
                    @update:selected-record="updateFilterStatus"
                />
            </div>
        </div>
        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.dream_prices.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by name"
    >
        <template #usage="data">
            <div class="flex justify-end">
                <span>{{ data.item.total_used_counts }}</span>
                <Tippy
                    :content="displayAmountWithCurrencySymbol(data.item.total_discount_amount)"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="18"
                    />
                </Tippy>
            </div>
        </template>
        <template #dream_price_products_count="record">
            <div>
                <div v-if="record.item.upload_status !== importRecordStatus.completed && record.item.upload_status !== 'N/A'">
                    <span class="flex flex-col gap-2 text-center">
                        <span>
                            Check Upload Status
                            <Link
                                :href="route('admin.import_records.index', record.item.import_record_id)"
                                class="text-blue-600 underline"
                            >
                                Here
                            </Link>
                        </span>

                        <div class="flex items-center">
                            <span>
                                {{ completionPercentage(record.item.total_records, record.item.total_records_imported, record.item.total_records_failed) }}%
                            </span>

                            <div class="progress ml-2">
                                <div
                                    class="progress-bar w-1/2"
                                    role="progressbar"
                                    aria-valuenow="0"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    :style="{ width: completionPercentage(record.item.total_records, record.item.total_records_imported, record.item.total_records_failed) + '%' }"
                                />
                            </div>
                        </div>
                    </span>
                </div>
                <Tippy
                    v-else
                    content="Uploaded Products List"
                >
                    <JBadge
                        :label="record.item.dream_price_products_count"
                        type="primary"
                        @click="showProductsModal(record.item.id)"
                    />
                </Tippy>
            </div>
        </template>

        <template #uploaded_file="record">
            <div class="flex justify-end">
                <Tippy
                    v-if="record.item.upload_file_url"
                    tag="a"
                    :content="`Records Uploaded for Dream Price Products:` + record.item.total_records"
                    :href="record.item.upload_file_url"
                    download
                >
                    <Download />
                </Tippy>

                <span v-else>
                    N/A
                </span>
            </div>
        </template>

        <template #status="data">
            <JSwitch
                input-class="ml-0 mt-0"
                :is-checked="data.item.status"
                class="mt-[0px]"
                @update:is-checked="updateStatus(data.item.id, $event)"
            />
        </template>
        <template #action="data">
            <div
                v-if="state.parameters.status === allStatuses.active"
                class="flex justify-center items-center"
            >
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.dream_prices.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <div
                    class="flex justify-center items-center"
                >
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.dream_prices.upload_form', data.item.id)"
                    >
                        <Upload class="w-4 h-4 mr-1" />
                        Upload Products
                    </Link>
                </div>
            </div>
        </template>
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayDreamPriceFilter = !state.displayDreamPriceFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        :modal-show="state.displayProductsModal"
        :columns="state.columnsForDreamPriceProducts"
        :records="state.products"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        @close-modal="closeModal"
    >
        <template #product_name="record">
            {{ record.item.name }}
        </template>

        <template #upc="record">
            {{ record.item.upc }}
        </template>

        <template 
            v-if="! pageProps.product_variant"
            #color="record"
        >
            {{ record.item.color }}
        </template>

        <template 
            v-if="! pageProps.product_variant"
            #size="record"
        >
            {{ record.item.size }}
        </template>

        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>

        <template #price="record">
            {{ displayAmountWithCurrencySymbol(record.item.retail_price) }}
        </template>

        <template #promo_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.price) }}
        </template>
    </SelectedProducts>
</template>

<script setup>
import { reactive, computed } from 'vue';
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JBadge from '@commonComponents/JBadge.vue';
import { CheckSquare, Download, Upload, Info, RefreshCw } from 'lucide-vue-next';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';
import axios from 'axios';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router, usePage } from '@inertiajs/vue3';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { showSuccessNotification } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    importRecordStatus: {
        type: Object,
        required: true,
    },
    dreamPriceModelMappingType: {
        type: String,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
    allStatuses: {
        type: Object,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        }, {
            key: 'start_date',
        }, {
            key: 'end_date',
        }, {
            key: 'dream_price_products_count',
            label: 'SKU Count',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'uploaded_file',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'usage',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    columnsForDreamPriceProducts: [
        {
            key: 'product_name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]
            : [
                {
                    key: 'color',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
                {
                    key: 'size',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]),
        {
            key: 'price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'promo_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',

        }
    ],

    displayProductsModal: false,
    dreamPriceId: null,
    products: [],
    refreshTableData: Math.random(),
    dreamPriceInCompleteStatusExists: true,
    displayDreamPriceFilter: false,
    parameters: {
        status: props.allStatuses.active,
    },
    disableRefreshButton: props.hasPendingSyncTransaction,

});

const closeModal = () => {
    state.displayProductsModal = false;
};

const showProductsModal = (dreamPriceId) => {
    state.products = [];
    axios.get(route('admin.dream_prices.get_dream_price_product', dreamPriceId))
        .then((response) => {
            state.products = response.data.dream_price_products;
        });

    state.displayProductsModal = true;
    state.dreamPriceId = dreamPriceId;
};

const updateStatus = (dreamPriceId, status) => {
    const refreshDelay = 1000;
    router.post(route('admin.dream_prices.update_status', [dreamPriceId, status ? 1 : 0]), {}, {
        onSuccess: () => setTimeout(() => {
            refreshTable();
        }, refreshDelay)
    });
};

const updateFilterStatus = (status) => {
    state.parameters.status = parseInt(status);
    refreshTable();
};

const clearAll = () => {
    state.parameters.status = props.allStatuses.active;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-dream-price-products/' + state.dreamPriceId + '/',
        'dream-price-products.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-dream-price-products/' + state.dreamPriceId + '/',
        'dream-price-products.xlsx',
        params,
        props.exportPermission
    );
};

const exportListPageCsvRecords = (params) => {
    return exportRecords(
        'export-dream-prices/',
        'dream-prices.csv',
        params,
        props.exportPermission
    );
};

const exportListPageExcelRecords = (params) => {
    return exportRecords(
        'export-dream-prices/',
        'dream-prices.xlsx',
        params,
        props.exportPermission
    );
};

const fetchDreamPriceImportRecordsPendingStatuses = () => {
    if (!state.dreamPriceInCompleteStatusExists) {
        return;
    }

    axios.get(route('admin.import_records.get_import_record_pending_statuses', props.dreamPriceModelMappingType))
        .then(response => {
            const pendingCounts = response.data.pending_counts || 0;

            if (pendingCounts > 0) {
                refreshTable();
            } else if (state.dreamPriceInCompleteStatusExists) {
                state.dreamPriceInCompleteStatusExists = false;
                refreshTable();
            }
        });
};

const fetchInterval = 10000;

setInterval(fetchDreamPriceImportRecordsPendingStatuses, fetchInterval);

const completionPercentage = (totals, totalImported, totalRecordsFailed) => {
    const percentageMultiplier = 100;
    const percentage = ((parseInt(totalImported) + parseInt(totalRecordsFailed)) / totals) * percentageMultiplier;

    if (isNaN(percentage)) {
        return 0;
    }

    return Math.round(percentage);
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const syncData = (id, dismiss) => {
    axios.get(route('admin.dream_prices.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
    });

    dismiss();
};

const helpStore = useHelpCenterStore();
const helpInformation = `
    <ul class='list-disc pl-5'>
        <li class='text-justify'>
            Please
            <a
                href="/images/discount_applicable_flow.png"
                class="underline"
                target="_blank"
            >
                click here
            </a>
            To observe how the discounts are distributed among various promotions and other functionalities of the POS.
        </li>

        <li>
            First dream price will be apply in case of the product has multiple dream prices.
        </li>
    </ul>
`;

helpStore.setHelpData(helpInformation);

</script>
