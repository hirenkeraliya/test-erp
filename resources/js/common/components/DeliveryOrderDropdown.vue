<template>
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

        <DropdownMenu class="w-60">
            <DropdownContent>
                <DropdownItem
                    v-if="record.item.status_id === fulfillmentStatuses.draft && record.item.created_by_company_id"
                    @click="edit(record.item.id, dismiss)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />Edit
                </DropdownItem>

                <DropdownItem
                    v-if="record.item.status_id === fulfillmentStatuses.draft && record.item.created_by_company_id"
                    @click="markAsOpened(record.item.id, dismiss)"
                >
                    <Truck class="w-4 h-4 mr-2" /> Open
                </DropdownItem>

                <DropdownItem
                    v-if="record.item.status_id === fulfillmentStatuses.open && record.item.created_by_company_id"
                    @click="markAsShipped(record.item.id, fulfillmentStatuses.shipped)"
                >
                    <Truck class="w-4 h-4 mr-2" /> Ship
                </DropdownItem>

                <DropdownItem
                    v-if="!record.item.created_by_company_id && (record.item.status_id === fulfillmentStatuses.shipped || record.item.status_id === fulfillmentStatuses.open)"
                    class="text-danger"
                    @click="markAsCanceled(record.item.id, dismiss)"
                >
                    <X class="w-4 h-4 mr-2" /> Cancel
                </DropdownItem>

                <DropdownItem
                    v-if="!record.item.created_by_company_id && record.item.status_id === fulfillmentStatuses.shipped"
                    @click="markAsReceived(record.item.id, dismiss)"
                >
                    <CheckCircle2 class="w-4 h-4 mr-2" /> Received
                </DropdownItem>

                <DropdownItem
                    v-if="!record.item.created_by_company_id && record.item.status_id === fulfillmentStatuses.received"
                    @click="deliveryNote(record.item.id, dismiss)"
                >
                    <CheckCircle2 class="w-4 h-4 mr-2" /> Delivery Note
                </DropdownItem>

                <DropdownItem
                    v-if="record.item.created_by_company_id && record.item.status_id === fulfillmentStatuses.discrepancy"
                    @click="markAsClosed(record.item.id, dismiss)"
                >
                    <Check class="w-4 h-4 mr-2" /> Closed
                </DropdownItem>

                <DropdownItem @click="printPurchaseOrderFulfillment(record.item.id, dismiss)">
                    <Printer class="w-4 h-4 mr-1" />Print
                </DropdownItem>
            </DropdownContent>
        </DropdownMenu>
    </Dropdown>
</template>

<script setup>
import { printReport } from '@commonServices/helper';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import { Check, CheckCircle2, CheckSquare, MoreHorizontal, Printer, Truck, X } from 'lucide-vue-next';
import { route } from 'ziggy';

const props = defineProps({
    record: {
        type: Object,
        required: true,
    },
    fulfillmentStatuses: {
        type: Object,
        required: true,
    },
    markAsShippedUrl: {
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
    markAsClosedUrl: {
        type: String,
        required: true,
    },
    editUrl: {
        type: String,
        required: true,
    },
    printPurchaseOrderFulfillmentUrl: {
        type: String,
        required: true,
    }
});

const delayTime = 1000;

const markAsShipped = (purchaseOrderFulfillmentId, statusId) => {
    const message = 'Are you sure to process this shipment?';
    confirmDialogBoxWithCenterText(message, () => {
        router.put(route(props.markAsShippedUrl, purchaseOrderFulfillmentId), {
            status_id: statusId,
        }, {
            onSuccess: () => setTimeout(() => {
                refreshTable();
            }, delayTime)
        });
    });
};

const markAsReceived = (purchaseOrderFulfillmentId, dismiss) => {
    const message = 'Are you sure you want to received the details?';
    confirmDialogBoxWithCenterText(message, () => {
        router.put(route(props.markAsReceivedUrl, purchaseOrderFulfillmentId), {}, {
            onSuccess: () => setTimeout(() => {
                refreshTable();
            }, delayTime)
        });
    });
    dismiss();
};

const markAsCanceled = (purchaseOrderFulfillmentId, dismiss) => {
    const message = 'Are you sure you want to cancel the DO?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.markAsCancelUrl, purchaseOrderFulfillmentId), {}, {
            onSuccess: () => setTimeout(() => {
                refreshTable();
            }, delayTime)
        });
    });
    dismiss();
};

const markAsOpened = (purchaseOrderFulfillmentId, dismiss) => {
    const message = 'Are you sure you want to open the DO?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.markAsOpenUrl, purchaseOrderFulfillmentId), {}, {
            onSuccess: () => setTimeout(() => {
                refreshTable();
            }, delayTime)
        });
    });
    dismiss();
};

const deliveryNote = (purchaseOrderFulfillmentId, dismiss) => {
    router.get(route(props.deliveryNoteUrl, purchaseOrderFulfillmentId));
    dismiss();
};

const markAsClosed = (purchaseOrderFulfillmentId, dismiss) => {
    router.get(route(props.markAsClosedUrl, purchaseOrderFulfillmentId));
    dismiss();
};

const edit = (purchaseOrderFulfillmentId, dismiss) => {
    router.get(route(props.editUrl, purchaseOrderFulfillmentId));
    dismiss();
};

const printPurchaseOrderFulfillment = (purchaseOrderFulfillmentId, dismiss) => {
    printReport(route(props.printPurchaseOrderFulfillmentUrl, purchaseOrderFulfillmentId), props.exportPermission);
    dismiss();
};

const emits = defineEmits('update:refresh-table-data');

const refreshTable = () => {
    emits('update:refresh-table-data');
};
</script>

<style>
/* Center the entire dialog box */
.alertify .ajs-modal {
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Center the content of the dialog box */
.alertify .ajs-content {
  text-align: center;
}

/* Center the header of the dialog box */
.alertify .ajs-header {
  text-align: center;
}

/* Center the footer of the dialog box */
.alertify .ajs-footer {
  text-align: center;
}
</style>
