<template>
    <PageTitle title="Online Sales Charges" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Order Picking List
        </h2>
    </div>
    <JTable
        :fetch-url="route('admin.order_picking_lists.fetch')"
        :columns="state.columns"
        :additional-query-params="state.parameters"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by number"
    >
        <template #action="record">
            <div class="flex justify-center items-center p-3">
                <Tippy
                    tag="button"
                    type="button"
                    class="border rounded p-1 mr-2"
                    content="Order Items"
                    @click="showOrderDetailsModal(record.item.id)"
                >
                    <List class="w-5 h-5" />
                </Tippy>

                <Dropdown
                    v-slot="{ dismiss }"
                    class="dropdown"
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
                                v-if="record.item.status_id === orderPickingStatusStaticUse.completed"
                                @click="printNinjaVanWayBill(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" />
                                Print Ninja Van Way Bill
                            </DropdownItem>
                            <DropdownItem
                                @click="printOrderPackaging(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" />
                                Print Packaging
                            </DropdownItem>
                            <DropdownItem
                                @click="printOrderPackingList(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" />
                                Print Packing List
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === orderPickingStatusStaticUse.draft"
                                class="flex items-center mr-3"
                                @click="inProgress(record.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                In Progress
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === orderPickingStatusStaticUse.draft"
                                class="flex items-center mr-3"
                                @click="cancel(record.item.id, dismiss)"
                            >
                                <X class="w-4 h-4 mr-1" />
                                Cancel
                            </DropdownItem>

                            <DropdownItem
                                v-if="record.item.status_id === orderPickingStatusStaticUse.inProgress"
                                class="flex items-center mr-3"
                                @click="completed(record.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Ready For Pickup
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>
    </JTable>

    <OrderPickingListDetails
        v-if="state.displayOrderPickingListDetailsModal"
        :modal-show="state.displayOrderPickingListDetailsModal"
        :orders="state.orders"
        :order-items="state.orderItems"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { Check, List, MoreHorizontal, Printer, X } from 'lucide-vue-next';
import axios from 'axios';
import OrderPickingListDetails from '@adminPages/order_picking_lists/partials/OrderPickingListDetails.vue';
import { printReport } from '@commonServices/helper';
import { router } from '@inertiajs/vue3';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';

defineProps({
    orderPickingStatusStaticUse: {
        type: Object,
        required: true
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
        }, {
            key: 'number',
            sortable: true,
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    orders: [],
    orderItems: [],
    displayOrderPickingListDetailsModal: false,
    refreshTableData: Math.random(),
});

const showOrderDetailsModal = (orderPickingId) => {
    state.orders = [];

    axios.get(route('admin.order_picking_lists.fetch_order_picking_list_items', orderPickingId))
        .then((response) => {
            state.orders = response.data.order_details;
            state.orderItems = response.data.item_details;
        });

    state.displayOrderPickingListDetailsModal = true;
};

const printOrderPackaging = (orderPickingListId) => {
    printReport(route('admin.order_picking_lists.print_order_packaging', orderPickingListId));
};

const printNinjaVanWayBill = (orderPickingListId) => {
    printReport(route('admin.order_picking_lists.print_ninja_van_way_bills', orderPickingListId));
};

const printOrderPackingList = (orderPickingListId) => {
    printReport(route('admin.order_picking_lists.print_order_packing_list', orderPickingListId));
};

const closeModal = () => {
    state.displayOrderPickingListDetailsModal = false;
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const inProgress = (orderPickingListId, dismiss) => {
    dismiss();
    confirmDialogBoxWithCenterText('Are you sure? You want to mark this picking list as in-progress.', () => {
        router.post(route('admin.order_picking_lists.inprogress', orderPickingListId), {}, {
            onSuccess: () => refreshTable()
        });
    });
};

const cancel = (orderPickingListId, dismiss) => {
    dismiss();
    confirmDialogBoxWithCenterText('Are you sure? You want to mark this picking list as cancelled.', () => {
        router.post(route('admin.order_picking_lists.cancel', orderPickingListId), {}, {
            onSuccess: () => refreshTable()
        });
    });
};

const completed = (orderPickingListId, dismiss) => {
    dismiss();
    confirmDialogBoxWithCenterText('Are you sure? You want to mark this picking list as completed.', () => {
        router.post(route('admin.order_picking_lists.completed', orderPickingListId), {}, {
            onSuccess: () => refreshTable()
        });
    });
};

</script>
