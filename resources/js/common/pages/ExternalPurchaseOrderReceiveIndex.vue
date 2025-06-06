<template>
    <PageTitle title="External Purchase Order Partial Receive" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            External Purchase Order Partial Receive
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <Link :href="route('admin.external_purchase_orders.index', externalPurchaseOrder.purchase_plan_id)">
                <SecondaryButton
                    type="button"
                    text="External Purchase Orders"
                    class="shadow-md mx-2"
                />
            </Link>
            <PrimaryButton
                text="Add Partial Receive"
                class="shadow-md mr-1"
                :disabled="hasPartialReceiveItems"
                @click="redirectToAddPartialReceive()"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route(fetchUrl,{external_purchase_order_id: externalPurchaseOrder.id})"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
    >
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
                content="External Purchase Order Partial Receive Items"
                class="cursor-pointer"
                @click="openExternalPurchaseOrderPartialReceiveItemsModal(record.item.id)"
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
                                v-if="record.item.status_id !== statuses.cancelled && record.item.status_id !== statuses.completed"
                                @click="edit(record.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Edit
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id !== statuses.completed && record.item.status_id !== statuses.cancelled"
                                @click="markAsCompleted(record.item.id, statuses.completed, dismiss)"
                            >
                                <CheckCircle2 class="w-4 h-4 mr-2" /> Completed
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id === statuses.completed && !record.item.is_grn"
                                @click="addGoodsReceivedNotes(record.item.id, dismiss)"
                            >
                                <CheckCircle2 class="w-4 h-4 mr-2" /> Add Goods Received Notes
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id === statuses.pending"
                                class="text-danger"
                                @click="markAsCanceled(record.item.id, statuses.cancelled, dismiss)"
                            >
                                <X class="w-4 h-4 mr-2" /> Cancel
                            </DropdownItem>
                            <DropdownItem @click="printExternalPurchaseOrderReceive(record.item.id, dismiss)">
                                <Printer class="w-4 h-4 mr-1" />Print
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>
    </JTable>

    <SelectedProducts
        :modal-show="state.displayExternalPurchaseOrderReceiveItemsModal"
        :columns="state.externalPurchaseOrderReceiveItemsFields"
        :records="state.externalPurchaseOrderReceiveItems"
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
import { MoreHorizontal, CheckCircle2, List, Printer, CheckSquare, X } from 'lucide-vue-next';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { confirmDialogBoxWithCenterText, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import { exportRecords, printReport } from '@commonServices/helper';

const props = defineProps({
    fetchUrl: {
        type: String,
        required: true,
    },
    createPartialReceiveUrl: {
        type: String,
        required: true,
    },
    externalPurchaseOrder: {
        type: Object,
        required: true,
    },
    approvePartialReceiveUrl: {
        type: String,
        required: true,
    },
    addGrnUrl: {
        type: String,
        required: true,
    },
    cancelPartialReceiveUrl: {
        type: String,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    hasPartialReceiveItems: {
        type: Boolean,
        default: false,
    },
    fetchExternalPurchaseOrderReceiveItemsUrl: {
        type: String,
        required: true,
    },
    exportUrl: {
        type: String,
        required: true,
    },
    printExternalPurchaseOrderReceiveUrl: {
        type: String,
        required: true,
    },
    editExternalPartialReceiveUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    externalPurchaseOrderReceiveItems: [],
    displayExternalPurchaseOrderReceiveItemsModal: false,
    externalPurchaseOrderReceiveId: null,
    totals: [],
    columns: [
        {
            key: 'received_date',
        }, {
            key: 'notes',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            sortable: true
        }, {
            key: 'items',
        },{
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    externalPurchaseOrderReceiveItemsFields: [
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
            key: 'notes',
            headerClass: 'text-left',
        }
    ],
});

const getStatusColor = (status) => {
    if (status === props.statuses.partial) {
        return 'btn btn-rounded btn-warning-soft';
    }

    if (status === props.statuses.completed) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.statuses.pending) {
        return 'btn btn-rounded btn-warning-soft';
    }

    if (status === props.statuses.cancelled) {
        return 'btn btn-rounded btn-danger-soft';
    }
};

const redirectToAddPartialReceive = () => {
    router.get(route(props.createPartialReceiveUrl, props.externalPurchaseOrder.id));
};

const openExternalPurchaseOrderPartialReceiveItemsModal = (externalPurchaseOrderReceiveId) => {
    state.externalPurchaseOrderReceiveItems = [];
    axios.get(route(props.fetchExternalPurchaseOrderReceiveItemsUrl, externalPurchaseOrderReceiveId))
        .then((response) => {
            state.externalPurchaseOrderReceiveItems = response.data.external_purchase_order_receive_items;
            state.totals = response.data.totals;
            state.displayExternalPurchaseOrderReceiveItemsModal = true;
        }).catch((error) => {
            showErrorNotification(error.response.data.message);
        });
    state.externalPurchaseOrderReceiveId = externalPurchaseOrderReceiveId;
};

const markAsCompleted = (partialReceiveId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to complete the Partial Receive?', () => {
        router.post(route(props.approvePartialReceiveUrl, partialReceiveId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const addGoodsReceivedNotes = (partialReceiveId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to add Goods Received Notes?', () => {
        router.post(route(props.addGrnUrl, partialReceiveId), {
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsCanceled = (partialReceiveId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to cancel the Partial Receive?', () => {
        router.post(route(props.cancelPartialReceiveUrl, partialReceiveId), {
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

const closeModal = () => {
    state.displayExternalPurchaseOrderReceiveItemsModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        props.exportUrl + state.externalPurchaseOrderReceiveId + '/',
        'external-purchase-order-partial-receive-items.csv',
        params
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        props.exportUrl + state.externalPurchaseOrderReceiveId + '/',
        'external-purchase-order-partial-receive-items.xlsx',
        params
    );
};

const printExternalPurchaseOrderReceive = (externalPurchaseOrderReceiveId, dismiss) => {
    printReport(route(props.printExternalPurchaseOrderReceiveUrl, externalPurchaseOrderReceiveId), props.exportPermission);
    dismiss();
};

const edit = (externalPartialReceiveId, dismiss) => {
    router.get(route(props.editExternalPartialReceiveUrl, externalPartialReceiveId));
    dismiss();
};
</script>
