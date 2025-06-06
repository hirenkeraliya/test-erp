<template>
    <PageTitle title="Orders" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            {{ reportHeadingName }}
        </h2>

        <slot />
    </div>

    <div
        v-if="state.displayOrdersFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5 h-44">
            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedMember"
                    :search-records="searchMembers"
                    placeholder="Member Name to search..."
                    input-label="Member"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateMember"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.type_id"
                    :records="orderTypes"
                    input-label="Order Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateOrderType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.location_id"
                    :records="locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocation"
                />
            </div>

            <div>
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-3 mt-5">
        <div class="col-span-12 bg-white p-3">
            <JTable
                :fetch-url="route(fetchUrl)"
                :columns="state.mainColumns"
                :refresh-table-data="state.refreshTableData"
                :additional-query-params="state.parameters"
                search-title="Search by Receipt Number or Bill Reference Number"
                :is-modal-table="true"
            >
                <template #extra-header-data="record">
                    <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                        <JBadge
                            v-if="record.data.total_orders"
                            :label="'Orders: ' + currencyFormat(record.data.total_orders)"
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="record.data.total_units_sold"
                            :label="'Units Sold: ' + currencyFormat(record.data.total_units_sold)"
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="record.data.total_orders_amount"
                            :label="'Orders: ' + displayAmountWithCurrencySymbol(record.data.total_orders_amount)"
                        />
                    </div>

                    <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                        <OutlinePrimaryButton
                            text="Filters"
                            class="text-sm shadow-md"
                            @click="state.displayOrdersFilter = !state.displayOrdersFilter"
                        />
                    </p>
                </template>

                <template
                    #total_amount_paid="record"
                >
                    <Tippy
                        tag="label"
                        class="flex justify-end items-center"
                        :content="getRemainingLayawayOrCreditAmount(record.item)"
                    >
                        {{ record.item.total_amount_paid }}

                        <Info
                            v-if="record.item.credit_pending_amount"
                            class="ml-1 text-primary"
                            :size="15"
                        />

                        <Info
                            v-if="record.item.layaway_pending_amount"
                            class="ml-1 text-primary"
                            :size="15"
                        />
                    </Tippy>
                </template>

                <template #type="record">
                    <JBadge
                        v-if="record.item.order_types.cancelOrder !== record.item.type_id"
                        :label="record.item.type"
                        class="mb-1 lg:mb-1 xl:mb-0"
                    />

                    <JBadge
                        v-if="record.item.order_types.cancelOrder === record.item.type_id"
                        :label="record.item.type"
                        type="danger"
                    />
                </template>

                <template #location="record">
                    {{ record.item.location }}
                </template>

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
                                        v-if="record.item.is_order_returned === false && record.item.order_types.cancelOrder !== record.item.type_id"
                                        @click="showCancelOrderModal(record.item.id, dismiss)"
                                    >
                                        <PackageX class="w-5 h-5 mr-2" /> Cancel Order
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="record.item.is_order_returned === false && record.item.order_types.cancelOrder !== record.item.type_id && record.item.order_types.pendingLayawayOrder === record.item.type_id"
                                        @click="showLayawayOrderModal(record.item, dismiss)"
                                    >
                                        <PackageCheck class="w-5 h-5 mr-2" />
                                        Complete Layaway Order
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="record.item.is_order_returned === false && record.item.order_types.cancelOrder !== record.item.type_id && record.item.order_types.pendingCreditOrder === record.item.type_id"
                                        @click="showCreditOrderModal(record.item, dismiss)"
                                    >
                                        <PackageCheck class="w-5 h-5 mr-2" />
                                        Complete Credit Order
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="record.item.is_order_returned === false && record.item.order_types.cancelOrder !== record.item.type_id"
                                        class="disabled"
                                        @click="showOrderReturnDetailsModal(record.item.id, dismiss)"
                                    >
                                        <PackageMinus class="w-5 h-5 mr-2" />
                                        Order Return
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="orderTypesStaticUse.pendingLayawayOrder === record.item.type_id || orderTypesStaticUse.completeLayawayOrder === record.item.type_id"
                                        @click="printLayawayOrderReceipt(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Layaway Order Receipt
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="orderTypesStaticUse.pendingCreditOrder === record.item.type_id || orderTypesStaticUse.completeCreditOrder === record.item.type_id"
                                        @click="printCreditOrderReceipt(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Credit Order Receipt
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="orderTypesStaticUse.pendingCreditOrder !== record.item.type_id || orderTypesStaticUse.completeCreditOrder !== record.item.type_id || orderTypesStaticUse.pendingLayawayOrder !== record.item.type_id || orderTypesStaticUse.completeLayawayOrder !== record.item.type_id"
                                        @click="printOrderReceipt(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Order Receipt
                                    </DropdownItem>

                                    <DropdownItem
                                        @click="printOrderTaxInvoice(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Order Tax Invoice
                                    </DropdownItem>

                                    <DropdownItem
                                        @click="printPurchaseOrder(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Purchase Order
                                    </DropdownItem>
                                </DropdownContent>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </template>
            </JTable>
        </div>
    </div>

    <div>
        <Modal
            size="modal-xl"
            :show="state.manageSaleExchange"
            @hidden="hideSaleExchangeModal"
        >
            <ModalHeader>
                <h2 class="font-medium text-base mr-auto pr-8">
                    Exchange Items Details
                </h2>

                <a
                    class="absolute right-0 top-0 mt-2 mr-3"
                    href="javascript:;"
                    @click="hideSaleExchangeModal"
                >
                    <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
                </a>
            </ModalHeader>

            <ModalBody class="p-5 sm:p-10">
                <div>
                    <JSimpleTable
                        :columns="state.columns"
                        :records="state.salesReturnData ?? []"
                        table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                        row-classes="border-b-2 border-slate-300"
                        :allow-search="true"
                    >
                        <template #total_amount="data">
                            {{ displayAmountWithCurrencySymbol(data.item.total_amount) }}
                        </template>

                        <template #return_quantity="data">
                            <FormInput
                                v-model:input-value="data.item.return_quantity"
                                type="number"
                                placeholder="Enter Return Quantity"
                                label-class="mt-0"
                            />
                        </template>

                        <template #reason="data">
                            <FormSelectBox
                                v-model:selected-record="data.item.reason"
                                :records="saleReturnReasons"
                                placeholder="Select Reason"
                                record-key-name="reason"
                                label-class="form-label w-full flex sm:flex-row"
                            />
                        </template>
                    </JSimpleTable>
                </div>
                <div class="mt-5">
                    <PrimaryButton
                        type="button"
                        text="Proceed"
                        class="w-24"
                        @click="hideSaleExchangeModal"
                    />
                </div>
            </ModalBody>
        </Modal>
    </div>

    <OrderDetails
        :modal-show="state.displayOrderDetailsModal"
        :order="state.order"
        :columns-for-payment-details="state.columnsForPaymentDetails"
        :columns-for-order-item-details="state.columnsForOrderItemDetails"
        @close-modal="closeModal"
    />

    <OrderReturnDetails
        v-if="Object.keys(state.orderReturnDetails).length"
        :modal-show="state.displayOrderReturnDetailsModal"
        :order-return-details="state.orderReturnDetails"
        :order-return-reasons="orderReturnReasons"
        @close-modal="closeModal"
    />

    <CancelOrder
        v-if="state.showCancelOrderModal && state.orderId !== null"
        :modal-show="state.showCancelOrderModal"
        :order-id="state.orderId"
        :cancel-order-reasons="cancelOrderReasons"
        @cancel-order-close-modal="cancelOrderCloseModal"
    />

    <LayawayOrder
        v-if="state.showLayawayOrderModal && Object.keys(state.order).length !== 0"
        :modal-show="state.showLayawayOrderModal"
        :order="state.order"
        :payment-types="paymentTypes"
        @layaway-order-close-modal="layawayOrderCloseModal"
    />

    <CreditOrder
        v-if="state.showCreditOrderModal && Object.keys(state.order).length !== 0"
        :modal-show="state.showCreditOrderModal"
        :order="state.order"
        :payment-types="paymentTypes"
        @credit-order-close-modal="creditOrderCloseModal"
    />

    <Receipt
        v-if="state.orderReceipt && Object.keys(state.order).length"
        :order="state.order"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import Receipt from '@commonComponents/Receipt.vue';
import { currencyFormat, currentDateTime, displayAmountWithCurrencySymbol, printReport } from '@commonServices/helper';
import { confirmDialogBox } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import CancelOrder from '@storeManagerPages/orders/partials/CancelOrder.vue';
import CreditOrder from '@storeManagerPages/orders/partials/CreditOrder.vue';
import LayawayOrder from '@storeManagerPages/orders/partials/LayawayOrder.vue';
import OrderDetails from '@commonComponents/OrderDetails.vue';
import OrderReturnDetails from '@storeManagerPages/orders/partials/OrderReturnDetails.vue';
import axios from 'axios';
import { Info, List, MoreHorizontal, PackageCheck, PackageMinus, PackageX, Printer, X } from 'lucide-vue-next';
import { computed, nextTick, reactive } from 'vue';
import { route } from 'ziggy';
import { usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    orderTypes: {
        type: Object,
        required: true
    },
    orderTypesStaticUse: {
        type: Object,
        required: true
    },
    orderReturnReasons: {
        type: Object,
        required: true
    },
    cancelOrderReasons: {
        type: Object,
        required: true
    },
    paymentTypes: {
        type: Object,
        required: true
    },
    exportPermission: {
        type: String,
        required: true,
    },
    locations: {
        type: Object,
        required: true
    },
    locationId: {
        type: Number,
        default: 0,
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    reportHeadingName: {
        type: String,
        required: true
    },
});

const state = reactive({
    columns: [
        {
            key: 'receipt_number',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'bill_reference_number',
            label: '# Reference',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'store_manager',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'location',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'total_amount',
            label: 'Amount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'total_quantity',
            label: 'Quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'return_quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'reason',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
    ],

    columnsForPaymentDetails: [
        {
            key: 'id',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'payment_type',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'amount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }
    ],

    columnsForOrderItemDetails: [
        {
            key: 'upc',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'product',
            headerClass: 'text-left',
            bodyClass: 'text-left',
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
            key: 'quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'unit_price',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'subtotal',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'total_discount_amount',
            label: 'Discount',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }
    ],

    mainColumns: [
        { key: 'receipt_number', label: 'Receipt' },
        { key: 'happened_at', label: 'Date' },
        { key: 'store_manager' },
        { key: 'location' },
        { key: 'member' },
        { key: 'type' },
        { key: 'channel' },
        { key: 'gross_sales', bodyClass: 'text-right', headerClass: 'text-right' },
        { key: 'total_tax_amount', label: 'Tax', bodyClass: 'text-right', headerClass: 'text-right' },
        { key: 'total_discount_amount', label: 'Discount', bodyClass: 'text-right', headerClass: 'text-right' },
        { key: 'net_total', label: 'Net', bodyClass: 'text-right', headerClass: 'text-right' },
        { key: 'total_amount_paid', label: 'Paid', bodyClass: 'text-right', headerClass: 'text-right' },
        { key: 'units_sold', bodyClass: 'text-right', headerClass: 'text-right' },
        { key: 'notes' },
        { key: 'bill_reference_number', label: '# Reference' },
        { key: 'action', headerClass: 'text-center', bodyClass: 'text-center' }
    ],

    parameters: {
        member_id: null,
        date_range: currentDateTime(),
        type_id: null,
        location_id: props.locationId,
        channel_type_id: null,
    },

    selected_product_id: null,
    order: {},
    orderId: null,
    orderReturnDetails: {},
    selected_complimentary_item_reason: null,
    discount_amount: null,
    selectedMember: null,

    refreshTableData: Math.random(),
    printReceiptData: Math.random(),

    memberModalShow: false,
    manageSaleReturn: false,
    manageSaleExchange: false,
    displayOrdersFilter: false,
    displayOrderDetailsModal: false,
    displayOrderReturnDetailsModal: false,
    showCancelOrderModal: false,
    showLayawayOrderModal: false,
    showCreditOrderModal: false,
    orderReceipt: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.member_id = null;
    state.parameters.type_id = null;
    state.parameters.location_id = null;
    state.parameters.channel_type_id = null;
    state.selectedMember = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
    refreshTable();
};

const updateOrderType = (selectOrderType) => {
    state.parameters.type_id = selectOrderType;
    refreshTable();
};

const updateLocation = (selectLocation) => {
    state.parameters.location_id = selectLocation;
    refreshTable();
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };

    axios.get(route('store_manager.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const hideSaleExchangeModal = () => {
    state.manageSaleExchange = false;
};

const showOrderDetailsModal = (orderId) => {
    state.order = [];
    state.orderReceipt = false;
    const filterData = {
        order_id: orderId,
    };
    axios.get(route('store_manager.orders.fetch_order_items', filterData))
        .then((response) => {
            state.order = response.data.order_details;
        });

    state.displayOrderDetailsModal = true;
};

const showOrderReturnDetailsModal = (orderId, dismiss) => {
    confirmDialogBox('Are You Sure To Return The Order.', () => {
        state.orderReturnDetails = [];
        axios.get(route('store_manager.orders.fetch_order_return_details', orderId))
            .then((response) => {
                state.orderReturnDetails = response.data.order_return_details;
            });

        state.displayOrderReturnDetailsModal = true;
    });

    dismiss();
};

const closeModal = () => {
    state.order = {};
    state.displayOrderDetailsModal = false;
    state.displayOrderReturnDetailsModal = false;
};

const cancelOrderCloseModal = () => {
    refreshTable();
    state.showCancelOrderModal = false;
    state.orderId = null;
};

const layawayOrderCloseModal = () => {
    refreshTable();
    state.showLayawayOrderModal = false;
    state.orderId = null;
};

const creditOrderCloseModal = () => {
    refreshTable();
    state.showCreditOrderModal = false;
    state.orderId = null;
};

const showCancelOrderModal = (orderId, dismiss) => {
    confirmDialogBox('Are You Sure To Cancel The Order.', () => {
        state.showCancelOrderModal = true;
        state.orderId = orderId;
    });

    dismiss();
};

const showLayawayOrderModal = (order, dismiss) => {
    state.showLayawayOrderModal = true;
    state.order = order;
    dismiss();
};

const showCreditOrderModal = (order, dismiss) => {
    state.showCreditOrderModal = true;
    state.order = order;
    dismiss();
};

const printLayawayOrderReceipt = (orderId) => {
    printReport(route('store_manager.orders.print_layaway_order_report', orderId));
};

const printCreditOrderReceipt = (orderId) => {
    printReport(route('store_manager.orders.print_credit_order_report', orderId), props.exportPermission);
};

const printOrderTaxInvoice = (orderId) => {
    printReport(route('store_manager.orders.print_order_tax_invoice', orderId), props.exportPermission);
};

const printPurchaseOrder = (orderId) => {
    printReport(route('store_manager.orders.print_purchase_order', orderId), props.exportPermission);
};

const printOrderReceipt = (orderId) => {
    state.order = {};
    state.orderReceipt = true;
    axios.get(route('store_manager.orders.print_order_receipt', orderId))
        .then((response) => {
            state.order = response.data.order_details;

            nextTick(() => {
                state.printReceiptData = Math.random();
            });
        });
};

const getRemainingLayawayOrCreditAmount = (items) => {
    const amount = items.type_id === props.orderTypesStaticUse.pendingLayawayOrder
        ? displayAmountWithCurrencySymbol(items.layaway_pending_amount)
        : displayAmountWithCurrencySymbol(items.credit_pending_amount);

    return 'Due: ' + amount;
};
</script>
