<template>
    <PageTitle title="Delivery Order" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Delivery Order
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <Link :href="route(indexUrl)">
                <SecondaryButton
                    text="Purchase Orders"
                    class="shadow-md mx-2"
                />
            </Link>

            <PrimaryButton
                v-if="purchaseOrder.order_type === staticDetails.sales_order"
                text="Create DO"
                class="shadow-md mr-1"
                :disabled="hasPurchaseOrderItems"
                @click="redirectToAddDeliveryOrder(purchaseOrder.id)"
            />
        </div>
    </div>

    <div
        v-if="state.displayPurchaseOrderFulfillmentFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.select_status"
                    :records="status"
                    placeholder="Please select status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
                />
            </div>

            <div>
                <JDatePicker
                    :max-date="new Date()"
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Date Range"
                    @update:input-value="updateTransferDate($event)"
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
        :fetch-url="route(fetchUrl,{purchase_order_id: purchaseOrder.id})"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by delivery order number"
    >
        <template #order_number="record">
            <span
                v-for="(order_number, index) in record.item.order_numbers"
                :key="index"
            >
                {{ order_number }}<br>
            </span>
        </template>

        <template #status="record">
            <div class="inline-flex items-center">
                <span :class="getStatusColor(record.item.status_id)">{{ record.item.status }}</span>
                <Tippy
                    v-if="record.item.status_times"
                    :content="record.item.status_times"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="18"
                    />
                </Tippy><br>
            </div>
        </template>

        <template #items="record">
            <Tippy
                content="Purchase Order Fulfillment Items"
                class="cursor-pointer"
                @click="openPurchaseOrderFulfillmentItemsModal(record.item.id)"
            >
                <List />
            </Tippy>
        </template>
        <template #action="record">
            <div class="flex justify-center items-center">
                <DeliveryOrderDropdown
                    :record="record"
                    :fulfillment-statuses="fulFillmentStatuses"
                    :mark-as-shipped-url="shippedUrl"
                    :mark-as-received-url="markAsReceivedUrl"
                    :mark-as-cancel-url="markAsCancelUrl"
                    :mark-as-open-url="markAsOpenUrl"
                    :delivery-note-url="deliveryNoteUrl"
                    :mark-as-closed-url="discrepancyClosedDeliveryOrderUrl"
                    :edit-url="editUrl"
                    :print-purchase-order-fulfillment-url="printUrl"
                    @update:refresh-table-data="refreshTable"
                />
            </div>
        </template>
        <template #extra-header-data="record">
            <div class="mx-0 mb-2 sm:mb-0 md:mx-2">
                <div class="flex justify-between items-center content-center">
                    <div>
                        <div
                            v-if="getBadgeDisplay(record.data.statusCounts)"
                            class="block items-center xl:flex"
                        >
                            <label class="mr-2 font-semibold">Status: </label>

                            <div>
                                <div class="block items-center xl:flex mr-2">
                                    <JBadge
                                        v-for="(statusCount, index) in record.data.statusCounts"
                                        :key="index"
                                        class="mb-1 xl:mb-2 2xl:mb-0 cursor-pointer"
                                        :label="`${index} : ${statusCount.count}`"
                                        @click="statusChanges(statusCount.id)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="getFilterTabStatus()"
            >
                <OutlinePrimaryButton
                    type="button"
                    text="Clear"
                    @click="clearAll()"
                />
            </div>
            <p class="text-lg font-bold mr-2 mb-2 sm:mb-0 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayPurchaseOrderFulfillmentFilter = !state.displayPurchaseOrderFulfillmentFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        :modal-show="state.displayPurchaseOrderFulfillmentItemsModal"
        :columns="state.purchaseOrderFulfillmentItemsFields"
        :records="state.purchaseOrderFulfillmentItems"
        :totals="state.totals"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        @close-modal="closeModal"
    >
        <template
            v-if="pageProps.product_variant"
            #product_variant_values="data"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in data.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>
    </SelectedProducts>
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { exportRecords } from '@commonServices/helper';
import { showErrorNotification } from '@commonServices/notifier';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Info, List } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';
import DeliveryOrderDropdown from '@commonComponents/DeliveryOrderDropdown.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    fulFillmentStatuses: {
        type: Object,
        required: true,
    },
    purchaseOrder: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    status: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    indexUrl: {
        type: String,
        required: true,
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    shippedUrl: {
        type: String,
        required: true,
    },
    markAsReceivedUrl: {
        type: String,
        required: true,
    },
    markAsCancelUrl: {
        type: String,
        required: true,
    },
    markAsOpenUrl: {
        type: String,
        required: true,
    },
    deliveryNoteUrl: {
        type: String,
        required: true,
    },
    discrepancyClosedDeliveryOrderUrl: {
        type: String,
        required: true,
    },
    editUrl: {
        type: String,
        required: true,
    },
    printUrl: {
        type: String,
        required: true,
    },
    shippingDetailsUrl: {
        type: String,
        required: true,
    },
    fetchPurchaseOrderFulfillmentItemsUrl: {
        type: String,
        required: true,
    },
    exportUrl: {
        type: String,
        required: true,
    },
    hasPurchaseOrderItems: {
        type: Boolean,
        default: false,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    displayPurchaseOrderFulfillmentItemsModal: false,
    purchaseOrderFulfillmentItems: [],
    totals: [],
    purchaseOrderFulfillmentId: null,
    displayPurchaseOrderFulfillmentFilter: false,
    columns: [
        {
            key: 'happened_at',
            label: 'Date',
            sortable: true,
        },
        {
            key: 'order_number',
        }, {
            key: 'items',
            headerClass: 'text-left',
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'action',
        }
    ],
    purchaseOrderFulfillmentItemsFields: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'product_name',
            label: 'Name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'product_upc',
            label: 'UPC',
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
                    key: 'product_color',
                    label: 'Color',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
                {
                    key: 'product_size',
                    label: 'Size',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]),
        {
            key: 'transfer_quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'received_quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'remarks',
            headerClass: 'text-left',
        }
    ],
    parameters: {
        select_status: null,
        date_range: null,
    },
});

const redirectToAddDeliveryOrder = (purchaseOrderId) => {
    router.get(route(props.shippingDetailsUrl, purchaseOrderId));
};

const openPurchaseOrderFulfillmentItemsModal = (purchaseOrderFulfillmentId) => {
    state.purchaseOrderFulfillmentItems = [];
    state.totals = [];
    axios.get(route(props.fetchPurchaseOrderFulfillmentItemsUrl, purchaseOrderFulfillmentId))
        .then((response) => {
            state.purchaseOrderFulfillmentItems = response.data.purchase_order_fulfillment_items;
            state.totals = response.data.totals;
            state.displayPurchaseOrderFulfillmentItemsModal = true;
        }).catch((error) => {
            showErrorNotification(error.response.data.message);
        });
    state.purchaseOrderFulfillmentId = purchaseOrderFulfillmentId;
};

const getStatusColor = (status) => {
    if (status === props.fulFillmentStatuses.closed) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.fulFillmentStatuses.discrepancy) {
        return 'btn btn-rounded btn-danger-soft';
    }

    if (status === props.fulFillmentStatuses.cancelled) {
        return 'btn btn-rounded btn-danger-soft';
    }

    if (status === props.fulFillmentStatuses.shipped) {
        return 'btn btn-rounded btn-primary-soft';
    }

    if (status === props.fulFillmentStatuses.draft) {
        return 'btn btn-rounded btn-warning-soft';
    }

    if (status === props.fulFillmentStatuses.open) {
        return 'btn btn-rounded bg-cyan-200 hover:bg-cyan-100 text-cyan-900';
    }

    if (status === props.fulFillmentStatuses.received) {
        return 'btn btn-rounded bg-cyan-200 hover:bg-cyan-100 text-cyan-900';
    }
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const closeModal = () => {
    state.displayPurchaseOrderFulfillmentItemsModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        props.exportUrl + state.purchaseOrderFulfillmentId + '/',
        'purchase-order-fulfillment-items.csv',
        params
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        props.exportUrl + state.purchaseOrderFulfillmentId + '/',
        'purchase-order-fulfillment-items.xlsx',
        params
    );
};

const getBadgeDisplay = (statusCounts) => {
    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const statusChanges = (status) => {
    state.parameters.select_status = status;
    refreshTable();
};

const clearAll = () => {
    state.parameters.select_status = null;
    state.parameters.date_range = null;
    refreshTable();
};

const updateSelectedStatus = (status) => {
    state.parameters.select_status = status;
    refreshTable();
};

const updateTransferDate = (selectedDate) => {
    state.parameters.date_range = selectedDate;
    refreshTable();
};

const getFilterTabStatus = () => {
    return state.displayPurchaseOrderFulfillmentFilter === false && (state.parameters.select_status !== null);
};

</script>
