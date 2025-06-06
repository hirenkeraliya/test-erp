<template>
    <PageTitle title="Purchase" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Purchase Orders
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <Link :href="route(props.deliveryOrdersUrl)">
                <PrimaryButton
                    text="Delivery Orders"
                    class="shadow-md mr-1"
                />
            </Link>

            <PrimaryButton
                text="Purchase Request"
                class="shadow-md mr-1"
                @click="redirectToAddPurchaseRequest()"
            />

            <PrimaryButton
                text="Transfer Request"
                class="shadow-md mr-1"
                @click="redirectToAddTransferRequest()"
            />
        </div>
    </div>

    <div
        v-if="state.displayPurchaseOrderFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.external_company_id"
                    :records="externalCompanies"
                    placeholder="Please select external company"
                    input-label="External Company"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateExternalCompanyId"
                />
            </div>

            <div
                v-if="state.parameters.external_company_id"
                class="mt-3"
            >
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.externalTypeId"
                    return-selected-record="id"
                    input-label="External Location"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateExternalLocationType"
                />
            </div>

            <div v-if="state.parameters.external_company_id">
                <TabPanel
                    v-if="state.externalTypeId === staticLocationTypes.store"
                    class="active"
                >
                    <FormSelectBox
                        :selected-record="state.parameters.external_location_id"
                        :records="state.externalStores"
                        placeholder="Please select store"
                        input-label="External Stores"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateExternalLocationId"
                    />
                </TabPanel>

                <TabPanel
                    v-if="state.externalTypeId === staticLocationTypes.warehouse"
                    class="active"
                >
                    <FormSelectBox
                        :selected-record="state.parameters.external_location_id"
                        :records="state.externalWarehouses"
                        placeholder="Please select warehouse"
                        input-label="External Warehouses"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateExternalLocationId"
                    />
                </TabPanel>
            </div>

            <div
                v-if="isAdmin"
                class="mt-3"
            >
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.typeId"
                    return-selected-record="id"
                    input-label="Location Selection"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocationType"
                />
            </div>

            <div v-if="isAdmin">
                <TabPanel
                    v-if="state.typeId === staticLocationTypes.store"
                    class="active"
                >
                    <FormSelectBox
                        :selected-record="state.parameters.location_id"
                        :records="stores"
                        placeholder="Please select store"
                        input-label="Store"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateLocationId"
                    />
                </TabPanel>

                <TabPanel
                    v-if="state.typeId === staticLocationTypes.warehouse"
                    class="active"
                >
                    <FormSelectBox
                        :selected-record="state.parameters.location_id"
                        :records="warehouses"
                        placeholder="Please select warehouse"
                        input-label="Warehouse"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateLocationId"
                    />
                </TabPanel>
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.order_type"
                    :records="orderType"
                    placeholder="Please select order type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Order Type"
                    @update:selected-record="updateOrderType"
                />
            </div>

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
                <FormInput
                    :input-value="state.parameters.order_number"
                    input-label="Order Number"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    placeholder="Please type the order number."
                    @update:input-value="selectOrderNumber"
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
        :fetch-url="route(props.fetchPurchaseOrderUrl, {order_number: orderNumber})"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecordsForList"
        :export-excel-records-callback="exportExcelRecordsForList"
        search-title="Search by order number or reference number"
    >
        <template #order_number="record">
            <span
                v-for="(order_number, index) in record.item.order_numbers"
                :key="index"
            >
                {{ order_number }}<br>
            </span>
        </template>

        <template #to="record">
            <Tippy
                :content="'Company: ' + record.item.to_company"
            >
                {{ record.item.to }}
                <Info
                    class="text-cyan-400 ml-2 inline-block"
                    :size="18"
                />
            </Tippy>
        </template>

        <template #from="record">
            <Tippy
                :content="'Company: ' + record.item.from_company"
            >
                {{ record.item.from }}
                <Info
                    class="text-cyan-400 ml-2 inline-block"
                    :size="18"
                />
            </Tippy><br>
        </template>

        <template #status="record">
            <div class="inline-flex items-center">
                <span
                    v-if="record.item.DOStatus"
                    class="w-40"
                    :class="getStatusColor(record.item.status_id)"
                >
                    {{ record.item.status }} <br> {{ record.item.DOStatus }}
                </span>
                <span
                    v-else
                    :class="getStatusColor(record.item.status_id)"
                >
                    {{ record.item.status }}
                </span>
                <Tippy
                    v-if="record.item.status_times"
                    :content="getStatusTimesAndFulfillmentStatusesSummary(record.item)"
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
                content="Purchase Order Items"
                class="cursor-pointer"
                @click="openPurchaseOrderItemsModal(record.item.id)"
            >
                <List />
            </Tippy>
        </template>

        <template #action="record">
            <div class="flex justify-center items-center">
                <Dropdown
                    v-slot="{ dismiss }"
                    class="dropdown absolute"
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
                                v-if="
                                    (record.item.status_id === statuses.draft) ||
                                        (record.item.status_id === statuses.opened && !record.item.created_by_company_id)
                                "
                                @click="edit(record.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Edit
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === statuses.draft"
                                @click="markAsOpen(record.item.id, statuses.opened)"
                            >
                                <Check class="w-4 h-4 mr-2" /> Open
                            </DropdownItem>

                            <DropdownItem
                                v-if="canMarkOrderAsCanceled(record.item.status_id, record.item.order_type_id)"
                                class="text-danger"
                                @click="markAsCancel(record.item.id, statuses.cancelled, dismiss)"
                            >
                                <X class="w-4 h-4 mr-2" /> Cancel
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === statuses.opened && !record.item.created_by_company_id"
                                @click="markAsApproved(record.item.id, statuses.approved,dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Approved
                            </DropdownItem>

                            <DropdownItem
                                v-if="canMarkOrderAsRejected(record.item.status_id, record.item.order_type_id, record.item.created_by_company_id)"
                                class="text-danger"
                                @click="markAsReject(record.item.id, statuses.rejected, dismiss)"
                            >
                                <X class="w-4 h-4 mr-1" />
                                Rejected
                            </DropdownItem>

                            <DropdownItem
                                v-if="
                                    (
                                        record.item.order_type_id === orderTypes.purchase_order
                                        || record.item.order_type_id === orderTypes.sales_order
                                    ) && (
                                        record.item.status_id === statuses.approved
                                        || record.item.status_id === statuses.closed
                                        || record.item.status_id === statuses.partial_fulfillment
                                        || record.item.status_id === statuses.fulfillment_completed
                                    )
                                "
                                @click="deliveryOrder(record.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Delivery Order
                            </DropdownItem>

                            <DropdownItem
                                @click="printPurchaseOrder(record.item.id, dismiss)"
                            >
                                <Printer class="w-4 h-4 mr-1" />Print
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #extra-header-data="record">
            <div class="mx-0 mb-2 sm:mb-0 md:mx-2">
                <div>
                    <div class="table w-full mt-5 rounded border-separate border-spacing-y-2 border-spacing-x-1">
                        <div
                            v-if="getBadgeDisplay(record.data.transferRequestStatusCounts, orderTypes.transfer_request)"
                            class="table-row"
                        >
                            <label class="font-semibold table-cell">Transfer Request: </label>
                            <JBadge
                                v-for="(statusCount, index) in record.data.transferRequestStatusCounts"
                                :key="index"
                                class="table-cell cursor-pointer mr-1"
                                :label="`${index} : ${statusCount.count}`"
                                @click="statusChanges(statusCount.id, orderTypes.transfer_request)"
                            />
                        </div>

                        <div
                            v-if="getBadgeDisplay(record.data.purchaseRequestStatusCounts, orderTypes.purchase_request)"
                            class="table-row"
                        >
                            <label class="table-cell font-semibold">Purchase Request: </label>
                            <JBadge
                                v-for="(statusCount, index) in record.data.purchaseRequestStatusCounts"
                                :key="index"
                                class="table-cell cursor-pointer mr-1"
                                :label="`${index} : ${statusCount.count}`"
                                @click="statusChanges(statusCount.id, orderTypes.purchase_request)"
                            />
                        </div>
                        <div
                            v-if="getBadgeDisplay(record.data.purchaseOrderStatusCounts, orderTypes.purchase_order)"
                            class="table-row"
                        >
                            <label class="table-cell font-semibold">Purchase Order: </label>

                            <JBadge
                                v-for="(statusCount, index) in record.data.purchaseOrderStatusCounts"
                                :key="index"
                                class="table-cell cursor-pointer mr-1"
                                :label="`${index} : ${statusCount.count}`"
                                @click="statusChanges(statusCount.id, orderTypes.purchase_order)"
                            />
                        </div>

                        <div
                            v-if="getBadgeDisplay(record.data.salesOrderStatusCounts, orderTypes.sales_order)"
                            class="table-row"
                        >
                            <label class="table-cell font-semibold">Sales Order: </label>

                            <JBadge
                                v-for="(statusCount, index) in record.data.salesOrderStatusCounts"
                                :key="index"
                                class="table-cell cursor-pointer mr-1"
                                :label="`${index} : ${statusCount.count}`"
                                @click="statusChanges(statusCount.id, orderTypes.sales_order)"
                            />
                        </div>

                        <div
                            v-if="getDeliveryOrdersBadgeDisplay(record.data.deliveryOrdersStatusCounts, fulFillmentStatuses)"
                            class="table-row"
                        >
                            <label class="table-cell font-semibold">Delivery Orders: </label>

                            <JBadge
                                v-for="(statusCount, index) in record.data.deliveryOrdersStatusCounts"
                                :key="index"
                                class="table-cell cursor-pointer mr-1"
                                :label="`${index} : ${statusCount.count}`"
                                @click="redirectToDeliveryOrders(statusCount.id)"
                            />
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
            <p
                v-if="state.isClear"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Clear"
                    class="text-sm shadow-md"
                    @click="refreshPage"
                />
            </p>
            <p class="text-lg font-bold mr-2 mb-2 sm:mb-0 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayPurchaseOrderFilter = !state.displayPurchaseOrderFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :modal-show="state.displayPurchaseOrderItemsModal"
        :records="state.purchaseOrderItems"
        :totals="state.totals"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        title="Transfer Items"
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

        <template #quantity="data">
            {{ data.item.quantity }}
            <br>
            {{ data.item.derivative }}
        </template>

        <template #rejected_quantity="data">
            {{ data.item.rejected_quantity }}
            <br>
            {{ data.item.derivative }}
        </template>

        <template #transferred_quantity="data">
            {{ data.item.transferred_quantity }}
            <br>
            {{ data.item.derivative }}
        </template>
    </SelectedProducts>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { usePage, router } from '@inertiajs/vue3';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import { X, MoreHorizontal, Check, List, CheckSquare, Printer, Info } from 'lucide-vue-next';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import axios from 'axios';
import { exportRecords, printReport } from '@commonServices/helper';
import JBadge from '@commonComponents/JBadge.vue';
import { TabPanel } from '@commonVendor/tab';
import JTabs from '@commonComponents/JTabs.vue';
import FormInput from '@commonComponents/FormInput.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
    stores: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Object,
        required: true,
    },
    orderTypes: {
        type: Object,
        required: true,
    },
    orderType: {
        type: Object,
        required: true,
    },
    status: {
        type: Object,
        required: true,
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    orderNumber: {
        type: String,
        default: '',
    },
    exportPermission: {
        type: String,
        required: true,
    },
    fulFillmentStatuses: {
        type: Object,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    deliveryOrdersUrl: {
        type: String,
        required: true,
    },
    fetchPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    createPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    fetchPurchaseOrderItemsUrl: {
        type: String,
        required: true,
    },
    cancelPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    approvePurchaseOrderUrl: {
        type: String,
        required: true,
    },
    rejectPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    openPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    editPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    deliveryOrderUrl: {
        type: String,
        required: true,
    },
    printPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    getExternalLocationsUrl: {
        type: String,
        required: true,
    },
    indexPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const state = reactive({
    refreshTableData: Math.random(),
    displayPurchaseOrderItemsModal: false,
    purchaseOrderItems: [],
    totals: [],
    purchaseOrderId: null,
    displayPurchaseOrderFilter: false,
    externalWarehouses: [],
    externalStores: [],
    isClear: false,
    columns: [
        {
            key: 'created_at',
            label: 'Date',
            sortable: true,
        }, {
            key: 'order_type',
            label: 'Transfer Type',
        }, {
            key: 'order_number',
        }, {
            key: 'from',
        }, {
            key: 'to',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'items',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'reference_number',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    purchaseOrderItemsFields: [
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
        }, {
            key: 'product_color',
            label: 'Color',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'product_size',
            label: 'Size',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'product_variant_values',
            label: 'Attributes',
        },{
            key: 'quantity',
            label: 'Quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'rejected_quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'transferred_quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'price_per_unit',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'remarks',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],
    externalTypeId: props.staticLocationTypes.store,
    typeId: props.isAdmin ? props.staticLocationTypes.store : null,
    parameters: {
        order_type: props.dashboardFilterData.order_type,
        select_status: props.dashboardFilterData.select_status,
        date_range: null,
        external_location_id: null,
        external_company_id: null,
        order_number: props.orderNumber,
        location_id: props.isAdmin ? props.dashboardFilterData.location_id : '',
    },
    dynamicColumns: [],
});

const redirectToAddTransferRequest = () => {
    router.get(route(props.createPurchaseOrderUrl, props.orderTypes.transfer_request));
};

const redirectToDeliveryOrders = (selectStatusId) => {
    router.get(route(props.deliveryOrdersUrl, { select_status: selectStatusId }));
};

const redirectToAddPurchaseRequest = () => {
    router.get(route(props.createPurchaseOrderUrl, props.orderTypes.purchase_request));
};

const openPurchaseOrderItemsModal = (purchaseOrderId) => {
    state.purchaseOrderItems = [];
    state.totals = [];
    axios.get(route(props.fetchPurchaseOrderItemsUrl, purchaseOrderId))
        .then((response) => {
            state.purchaseOrderItems = response.data.purchase_order_items;
            state.totals = response.data.totals;
            state.displayPurchaseOrderItemsModal = true;
        });
    state.purchaseOrderId = purchaseOrderId;
};

const getStatusColor = (status) => {
    if (status === props.statuses.approved) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.statuses.closed) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.statuses.cancelled || status === props.statuses.rejected) {
        return 'btn btn-rounded btn-danger-soft';
    }

    if (status === props.statuses.opened) {
        return 'btn btn-rounded bg-cyan-200 hover:bg-cyan-100 text-cyan-900';
    }

    if (status === props.statuses.partial_fulfillment) {
        return 'btn btn-rounded btn-primary-soft';
    }

    if (status === props.statuses.fulfillment_completed) {
        return 'btn btn-rounded btn-primary-soft';
    }

    if (status === props.statuses.draft) {
        return 'btn btn-rounded btn-warning-soft';
    }
};

const markAsCancel = (purchaseOrderId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure to cancel this request?', () => {
        router.post(route(props.cancelPurchaseOrderUrl, purchaseOrderId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsApproved = (purchaseOrderId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to approve the purchase Order?', () => {
        router.post(route(props.approvePurchaseOrderUrl, purchaseOrderId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsReject = (purchaseOrderId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to reject the purchase Order?', () => {
        router.post(route(props.rejectPurchaseOrderUrl, purchaseOrderId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsOpen = (purchaseOrderId, statusId) => {
    const message = 'Are you sure to open this request?';
    const delayMs = 1000;

    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.openPurchaseOrderUrl, purchaseOrderId), {
            status_id: statusId
        }, {
            onSuccess: () => setTimeout(() => {
                refreshTable();
            }, delayMs)
        });
    });
};

const edit = (purchaseOrderId, dismiss) => {
    router.get(route(props.editPurchaseOrderUrl, purchaseOrderId));
    dismiss();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const closeModal = () => {
    state.displayPurchaseOrderItemsModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-purchase-order-items/' + state.purchaseOrderId + '/',
        'purchase-order-items.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-purchase-order-items/' + state.purchaseOrderId + '/',
        'purchase-order-items.xlsx',
        params,
        props.exportPermission
    );
};

const deliveryOrder = (purchaseOrderId, dismiss) => {
    router.get(route(props.deliveryOrderUrl, purchaseOrderId));
    dismiss();
};

const printPurchaseOrder = (purchaseOrderId, dismiss) => {
    printReport(route(props.printPurchaseOrderUrl, purchaseOrderId), props.exportPermission);
    dismiss();
};

const getBadgeDisplay = (statusCounts, orderType) => {
    if (state.parameters.order_type !== null) {
        return state.parameters.order_type === orderType;
    }

    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const getDeliveryOrdersBadgeDisplay = (statusCounts, orderType) => {
    if (state.parameters.order_type !== null) {
        return state.parameters.order_type === orderType;
    }

    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const statusChanges = (status, orderType) => {
    state.parameters.select_status = status;
    state.parameters.order_type = orderType;
    refreshTable();
};

const clearAll = () => {
    state.parameters.select_status = null;
    state.parameters.order_type = null;
    state.parameters.date_range = null;
    state.externalTypeId = null;
    state.parameters.external_location_id = null;
    state.parameters.external_company_id = null;
    state.parameters.order_number = null;
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

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.parameters.location_id = null;
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = parseInt(locationId);
    state.parameters.transfer_type = '';
    refreshTable();
};

const updateOrderType = (orderType) => {
    state.parameters.order_type = parseInt(orderType);
    refreshTable();
};

const getFilterTabStatus = () => {
    return state.displayPurchaseOrderFilter === false && (state.parameters.order_type !== null || state.parameters.select_status !== null);
};

const updateExternalLocationType = (typeId) => {
    state.externalTypeId = typeId;
    state.parameters.external_location_id = null;
    refreshTable();
};

const updateExternalLocationId = (locationId) => {
    state.parameters.external_location_id = locationId;
    refreshTable();
};

const updateExternalCompanyId = (externalCompanyId) => {
    state.parameters.external_company_id = externalCompanyId;

    axios.get(route(props.getExternalLocationsUrl, externalCompanyId))
        .then((response) => {
            state.externalStores = response.data.externalStores;
            state.externalWarehouses = response.data.externalWarehouses;
        });
};

const selectOrderNumber = (orderNumber) => {
    state.parameters.order_number = orderNumber;
    refreshTable();
};

const refreshPage = () => {
    router.get(route(props.indexPurchaseOrderUrl));
};

const getStatusTimesAndFulfillmentStatusesSummary = (item) => {
    let timing = item.status_times;
    if (item.fulfillmentStatusesSummary !== '') {
        timing += '<br>' + '-------------------------' + '<br>';
        timing += item.fulfillmentStatusesSummary;
    }

    return timing;
};

const exportCsvRecordsForList = (params) => {
    return exportRecords(
        'export-purchase-orders/',
        'purchase-order.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecordsForList = (params) => {
    return exportRecords(
        'export-purchase-orders/',
        'purchase-order.xlsx',
        params,
        props.exportPermission
    );
};

onMounted(() => {
    if (props.orderNumber) {
        state.isClear = true;
        state.displayPurchaseOrderFilter = true;
        refreshTable();
    }

    state.dynamicColumns = getFilteredColumns();
});

const getFilteredColumns = () => {
    const columns = state.purchaseOrderItemsFields || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['product_color', 'product_size'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'product_variant_values');
};

const canMarkOrderAsCanceled = (statusId, orderTypeId) => {
    const { isAdmin, statuses, orderTypes } = props;

    if (isAdmin) {
        const isDraftStatus = statusId === statuses.draft;
        const isApprovedSalesOrder = orderTypeId === orderTypes.sales_order && statusId === statuses.approved;
        return isDraftStatus || isApprovedSalesOrder;
    }

    return statusId === statuses.draft;
};

const canMarkOrderAsRejected = (statusId, orderTypeId, createdByCompanyId) => {
    const { isAdmin, statuses, orderTypes } = props;

    if (isAdmin) {
        const isOpenedStatus = statusId === statuses.opened;
        const isPurchaseOrderApproved = orderTypeId === orderTypes.purchase_order && statusId === statuses.approved;
        return (isOpenedStatus && !createdByCompanyId) || isPurchaseOrderApproved;
    }

    return statusId === statuses.opened && !createdByCompanyId;
};
</script>
