<template>
    <PageTitle title="External Purchase Orders" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            External Purchase Orders
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <Link :href="route('admin.purchase_plans.index')">
                <SecondaryButton
                    type="button"
                    text="Purchase Plans"
                    class="shadow-md mx-2"
                />
            </Link>
            <PrimaryButton
                text="Add New External Purchase Order"
                class="shadow-md mr-1"
                :disabled="hasPurchaseOrderItems"
                @click="redirectToAddExternalPurchaseOrder()"
            />
        </div>
    </div>

    <div
        v-if="state.displayExternalPurchaseOrderFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.select_status"
                    :records="externalPurchaseOrderStatuses"
                    placeholder="Please select status"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStatus"
                />
            </div>

            <div>
                <JDatePicker
                    :max-date="new Date()"
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Date Range"
                    @update:input-value="updateDateRange($event)"
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
        :fetch-url="route(fetchUrl,{purchase_plan_id: purchasePlan.id})"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by order number"
    >
        <template #total_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.total_amount) }}
        </template>
        <template #status="record">
            <div class="inline-flex items-center">
                <span
                    :class="getStatusColor(record.item.status_id)"
                >
                    {{ record.item.status }}
                </span>
            </div>
        </template>
        <template #items="record">
            <Tippy
                content="External Purchase Order Items"
                class="cursor-pointer"
                @click="openExternalPurchaseOrderItemsModal(record.item.id)"
            >
                <List />
            </Tippy>
        </template>
        <template #action="record">
            <div
                class="flex justify-center items-center"
            >
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
                                v-if="record.item.status_id !== statuses.approved && record.item.status_id !== statuses.cancelled && record.item.status_id !== statuses.completed && record.item.status_id !== statuses.partial"
                                @click="edit(record.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Edit
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id === statuses.pending"
                                @click="markAsApprove(record.item.id, statuses.approved, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Approved
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id === statuses.approved || record.item.status_id === statuses.partial || record.item.status_id === statuses.completed"
                                @click="externalPurchaseOrderReceive(record.item.id, dismiss)"
                            >
                                <CheckCircle2 class="w-4 h-4 mr-2" /> Receive
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id !== statuses.cancelled && record.item.status_id !== statuses.completed && record.item.status_id !== statuses.partial"
                                class="text-danger"
                                @click="markAsCancel(record.item.id, statuses.cancelled, dismiss)"
                            >
                                <X class="w-4 h-4 mr-2" /> Cancel
                            </DropdownItem>
                            <DropdownItem @click="printExternalPurchaseOrder(record.item.id, dismiss)">
                                <Printer class="w-4 h-4 mr-1" />Print
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
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
                    class="mr-2"
                    @click="clearAll()"
                />
            </div>
            <p class="text-lg font-bold mr-2 mb-2 sm:mb-0 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayExternalPurchaseOrderFilter = !state.displayExternalPurchaseOrderFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        :modal-show="state.displayExternalPurchaseOrderItemsModal"
        :columns="state.externalPurchaseOrderItemsFields"
        :records="state.externalPurchaseOrderItems"
        :totals="state.totals"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        @close-modal="closeModal"
    >
        <template #quantity="data">
            {{ data.item.quantity }}
            <br>
            {{ data.item.derivative }}
        </template>

        <template #received_quantity="data">
            {{ data.item.received_quantity }}
            <br>
            {{ data.item.derivative }}
        </template>
    </SelectedProducts>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';
import { MoreHorizontal, CheckSquare, X, List, Printer, CheckCircle2, Check } from 'lucide-vue-next';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import JBadge from '@commonComponents/JBadge.vue';
import { displayAmountWithCurrencySymbol, exportRecords, printReport } from '@commonServices/helper';
import { confirmDialogBoxWithCenterText, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

const props = defineProps({
    fetchUrl: {
        type: String,
        required: true,
    },
    purchasePlan: {
        type: Object,
        required: true,
    },
    createExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    editExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    cancelExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    fetchExternalPurchaseOrderItemsUrl: {
        type: String,
        required: true,
    },
    printExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    externalPurchaseOrderReceiveUrl: {
        type: String,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    hasPurchaseOrderItems: {
        type: Boolean,
        default: false,
        required: true,
    },
    exportUrl: {
        type: String,
        required: true,
    },
    approveExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    externalPurchaseOrderStatuses: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    displayExternalPurchaseOrderFilter: false,
    isClear: false,
    totals: [],
    columns: [
        {
            key: 'order_number',
            sortable: true,
        }, {
            key: 'date',
        }, {
            key: 'notes',
        }, {
            key: 'total_amount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'items',
            headerClass: 'text-left',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    externalPurchaseOrderItemsFields: [
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
        {
            key: 'quantity',
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
    displayExternalPurchaseOrderItemsModal: false,
    externalPurchaseOrderItems: [],
    externalPurchaseOrderId: null,
    parameters: {
        select_status: null,
        date_range: null,
    },
});

const redirectToAddExternalPurchaseOrder = () => {
    router.get(route(props.createExternalPurchaseOrderUrl, props.purchasePlan.id));
};

const edit = (externalPurchaseOrderId, dismiss) => {
    router.get(route(props.editExternalPurchaseOrderUrl, externalPurchaseOrderId));
    dismiss();
};

const markAsCancel = (externalPurchaseOrderId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure to cancel this request?', () => {
        router.post(route(props.cancelExternalPurchaseOrderUrl, externalPurchaseOrderId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const getStatusColor = (status) => {
    if (status === props.statuses.partial) {
        return 'btn btn-rounded btn-primary-soft';
    }

    if (status === props.statuses.completed) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.statuses.cancelled) {
        return 'btn btn-rounded btn-danger-soft';
    }

    if (status === props.statuses.pending) {
        return 'btn btn-rounded btn-warning-soft';
    }

    if (status === props.statuses.approved) {
        return 'btn btn-rounded btn-success-soft';
    }
};

const openExternalPurchaseOrderItemsModal = (externalPurchaseOrderId) => {
    state.externalPurchaseOrderItems = [];
    axios.get(route(props.fetchExternalPurchaseOrderItemsUrl, externalPurchaseOrderId))
        .then((response) => {
            state.externalPurchaseOrderItems = response.data.external_purchase_order_items;
            state.totals = response.data.totals;
            state.displayExternalPurchaseOrderItemsModal = true;
        }).catch((error) => {
            showErrorNotification(error.response.data.message);
        });
    state.externalPurchaseOrderId = externalPurchaseOrderId;
};

const closeModal = () => {
    state.displayExternalPurchaseOrderItemsModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        props.exportUrl + state.externalPurchaseOrderId + '/',
        'external-purchase-order-items.csv',
        params
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        props.exportUrl + state.externalPurchaseOrderId + '/',
        'external-purchase-order-items.xlsx',
        params
    );
};

const printExternalPurchaseOrder = (externalPurchaseOrderId, dismiss) => {
    printReport(route(props.printExternalPurchaseOrderUrl, externalPurchaseOrderId), props.exportPermission);
    dismiss();
};

const externalPurchaseOrderReceive = (externalPurchaseOrderPlaneId, dismiss) => {
    router.get(route(props.externalPurchaseOrderReceiveUrl, externalPurchaseOrderPlaneId));
    dismiss();
};

const markAsApprove = (externalPurchaseOrderId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to approve the external purchase order?', () => {
        router.post(route(props.approveExternalPurchaseOrderUrl, externalPurchaseOrderId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const updateDateRange = (selectedDate) => {
    state.parameters.date_range = selectedDate;
    refreshTable();
};

const clearAll = () => {
    state.parameters.date_range = null;
    state.parameters.select_status = null;
    refreshTable();
};

const updateStatus = (statusId) => {
    state.parameters.select_status = parseInt(statusId);
    refreshTable();
};

const getBadgeDisplay = (statusCounts) => {
    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const statusChanges = (status) => {
    state.parameters.select_status = status;
    refreshTable();
};

const getFilterTabStatus = () => {
    return state.displayExternalPurchaseOrderFilter === false && (state.parameters.select_status !== null);
};
</script>
