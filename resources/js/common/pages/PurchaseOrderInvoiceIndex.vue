<template>
    <PageTitle title="Purchase Order Invoice" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Purchase Invoices
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <PrimaryButton
                text="Create Invoice"
                class="shadow-md mr-1"
                @click="createInvoice()"
            />
        </div>
    </div>
    <div
        v-if="state.displayPurchaseOrderInvoiceFilter"
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
        :fetch-url="route(fetchUrl)"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by invoice number"
        :additional-query-params="state.parameters"
    >
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
                                v-if="record.item.status_id === statuses.draft"
                                @click="refreshPurchaseCost(record.item.purchase_order_id, dismiss)"
                            >
                                <RefreshCw class="w-4 h-4 mr-2" />Price Refresh
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id === statuses.draft"
                                @click="edit(record.item.id, record.item.purchase_order_id ,dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Edit
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === statuses.draft"
                                @click="markAsSent(record.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-2" /> Send
                            </DropdownItem>

                            <DropdownItem
                                v-if="!record.item.created_by_company_id && record.item.status_id === statuses.sent"
                                @click="markAsReceived(record.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-2" /> Mark as Received
                            </DropdownItem>

                            <DropdownItem
                                v-if="!record.item.created_by_company_id && record.item.status_id === statuses.received"
                                @click="markAsPaid(record.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-2" /> Pay
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === statuses.draft"
                                class="text-danger"
                                @click="markAsCancel(record.item.id, dismiss)"
                            >
                                <X class="w-4 h-4 mr-2" />Cancel
                            </DropdownItem>

                            <DropdownItem
                                @click="printInvoice(record.item.id, dismiss)"
                            >
                                <Printer class="w-4 h-4 mr-1" />Print Invoice
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
                    @click="clearAll()"
                />
            </div>
            <p class="text-lg font-bold mr-2 mb-2 sm:mb-0 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayPurchaseOrderInvoiceFilter = !state.displayPurchaseOrderInvoiceFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { RefreshCw, X, MoreHorizontal, Check, CheckSquare, Printer, Info } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import { confirmDialogBoxWithCenterText, showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import { printReport } from '@commonServices/helper';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JBadge from '@commonComponents/JBadge.vue';
import axios from 'axios';

const props = defineProps({
    statuses: {
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
    fetchUrl: {
        type: String,
        required: true,
    },
    createUrl: {
        type: String,
        required: true,
    },
    cancelUrl: {
        type: String,
        required: true,
    },
    sentUrl: {
        type: String,
        required: true,
    },
    paidUrl: {
        type: String,
        required: true,
    },
    markAsReceivedUrl: {
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
    refreshPurchaseCostUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    purchaseOrderId: null,
    displayPurchaseOrderInvoiceFilter: false,
    columns: [
        {
            key: 'created_at',
            label: 'Date',
            sortable: true,
        }, {
            key: 'invoice_number',
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
    parameters: {
        select_status: null,
        date_range: null,
    },

});

const createInvoice = () => {
    router.get(route(props.createUrl));
};

const getStatusColor = (status) => {
    if (status === props.statuses.sent) {
        return 'btn btn-rounded btn-primary-soft';
    }
    if (status === props.statuses.paid) {
        return 'btn btn-rounded btn-success-soft';
    }
    if (status === props.statuses.cancelled) {
        return 'btn btn-rounded btn-danger-soft';
    }
    if (status === props.statuses.draft) {
        return 'btn btn-rounded btn-warning-soft';
    }
    if (status === props.statuses.received) {
        return 'btn btn-rounded bg-cyan-200 hover:bg-cyan-100 text-cyan-900';
    }
};

const markAsCancel = (purchaseOrderInvoiceId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to cancel the Purchase Order Invoice?', () => {
        router.post(route(props.cancelUrl, purchaseOrderInvoiceId), {},
            {
                onSuccess: () => refreshTable()
            });
    });
    dismiss();
};

const markAsSent = (purchaseOrderInvoiceId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want Send the purchase Order Invoice?', () => {
        router.post(route(props.sentUrl, purchaseOrderInvoiceId), {}, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsPaid = (purchaseOrderInvoiceId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to pay the purchase Order Invoice?', () => {
        router.post(route(props.paidUrl, purchaseOrderInvoiceId), {}, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsReceived = (purchaseOrderInvoiceId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to mark the purchase order invoice as received?', () => {
        router.post(route(props.markAsReceivedUrl, purchaseOrderInvoiceId), {}, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const edit = (purchaseOrderInvoiceId, purchaseOrderId, dismiss) => {
    router.get(route(props.editUrl, [purchaseOrderInvoiceId, purchaseOrderId]));
    dismiss();
};

const printInvoice = (purchaseOrderInvoiceId, dismiss) => {
    printReport(route(props.printUrl, purchaseOrderInvoiceId), props.exportPermission);
    dismiss();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const getBadgeDisplay = (statusCounts) => {
    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const statusChanges = (status, orderType) => {
    state.parameters.select_status = status;
    state.parameters.order_type = orderType;
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
    return state.displayPurchaseOrderInvoiceFilter === false && (state.parameters.select_status !== null);
};

const refreshPurchaseCost = (purchaseOrderId, dismiss) => {
    dismiss();
    const httpStatusOk = 200;
    axios.get(route(props.refreshPurchaseCostUrl, [purchaseOrderId]))
        .then((response) => {
            if (response.status === httpStatusOk) {
                showSuccessNotification('Price refreshed successfully.');
            }
        }).catch((error) => {
            showErrorNotification(error.message);
        });
};

</script>
