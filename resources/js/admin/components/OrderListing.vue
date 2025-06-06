<template>
    <PageTitle title="Orders" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            {{ reportHeadingName }}
        </h2>
    </div>

    <div
        v-if="state.displayOrdersFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5 h-44"
        >
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
                    :selected-record="state.parameters.store_manager_id"
                    :records="storeManagers"
                    input-label="Store Managers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStoreManager"
                />
            </div>

            <div v-if="state.parameters.store_manager_id">
                <FormSelectBox
                    :selected-record="state.parameters.location_id"
                    :records="state.locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocation"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.e_invoice_submitted"
                    :records="state.eInvoiceFilter"
                    input-label="E Invoice Submitted"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEInvoice"
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
                :fetch-url="route(fetchRouteName)"
                :columns="state.mainColumns"
                :refresh-table-data="state.refreshTableData"
                :additional-query-params="state.parameters"
                search-title="Search by Receipt Number or Bill Reference Number"
                :is-modal-table="true"
            >
                <template #extra-header-data="record">
                    <div
                        class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0"
                    >
                        <JBadge
                            v-if="record.data.total_orders"
                            :label="
                                'Orders: ' +
                                    currencyFormat(record.data.total_orders)
                            "
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="record.data.total_units_sold"
                            :label="
                                'Units Sold: ' +
                                    currencyFormat(record.data.total_units_sold)
                            "
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="record.data.total_orders_amount"
                            :label="
                                'Orders: ' +
                                    displayAmountWithCurrencySymbol(
                                        record.data.total_orders_amount
                                    )
                            "
                        />
                    </div>

                    <p
                        class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
                    >
                        <OutlinePrimaryButton
                            text="Filters"
                            class="text-sm shadow-md"
                            @click="
                                state.displayOrdersFilter =
                                    !state.displayOrdersFilter
                            "
                        />
                    </p>
                </template>

                <template #total_amount_paid="record">
                    <Tippy
                        tag="label"
                        class="flex justify-end items-center"
                        :content="
                            getRemainingLayawayOrCreditAmount(record.item)
                        "
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
                <template #receipt_number="record">
                    <div class="flex justify-end items-center">
                        {{ record.item.receipt_number }}
                        <Tippy
                            v-if="record.item.digital_invoice_submitted"
                            tag="label"
                            class="flex justify-end items-center"
                            content="E-invoice generated"
                        >
                            <ReceiptText
                                class="ml-1 text-info"
                                :size="15"
                            />
                        </Tippy>
                    </div>
                </template>

                <template #type="record">
                    <JBadge
                        v-if="
                            record.item.order_types.cancelOrder !==
                                record.item.type_id
                        "
                        :label="record.item.type"
                        class="mb-1 lg:mb-1 xl:mb-0"
                    />

                    <JBadge
                        v-if="
                            record.item.order_types.cancelOrder ===
                                record.item.type_id
                        "
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
                            @click="
                                showOrderDetailsModal(
                                    record.item.id,
                                    record.item.location_id,
                                    record.item.store_manager_id
                                )
                            "
                        >
                            <List class="w-5 h-5" />
                        </Tippy>

                        <Dropdown class="dropdown">
                            <DropdownToggle
                                tag="a"
                                class="w-5 h-5 block"
                                href="javascript:;"
                            >
                                <MoreHorizontal
                                    class="w-5 h-5 text-slate-500"
                                />
                            </DropdownToggle>

                            <DropdownMenu class="w-60">
                                <DropdownContent>
                                    <DropdownItem
                                        v-if="
                                            isLayawayOrder(record.item.type_id)
                                        "
                                        @click="
                                            printLayawayOrderReceipt(
                                                record.item.id
                                            )
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Layaway Order Receipt
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="
                                            isCreditOrder(record.item.type_id)
                                        "
                                        @click="
                                            printCreditOrderReceipt(
                                                record.item.id
                                            )
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Credit Order Receipt
                                    </DropdownItem>

                                    <DropdownItem
                                        v-if="
                                            isCompleteOrder(record.item.type_id)
                                        "
                                        @click="
                                            printOrderReceipt(record.item.id)
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Order Receipt
                                    </DropdownItem>

                                    <DropdownItem
                                        @click="
                                            printOrderTaxInvoice(record.item.id)
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Order Tax Invoice
                                    </DropdownItem>

                                    <DropdownItem
                                        @click="
                                            printPurchaseOrder(record.item.id)
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Purchase Order
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="
                                            checkEInvoicePermission(
                                                eInvoiceGeneratePermission
                                            ) && allowEInvoice
                                        "
                                        class="flex items-center mr-3"
                                        @click="
                                            showEInvoiceFormModal(record.item)
                                        "
                                    >
                                        <Notebook class="w-4 h-4 mr-1" />
                                        E-Invoice Generation
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="
                                            record.item
                                                .digital_invoice_submitted
                                        "
                                        @click="
                                            printB2bOrderDigitalInvoice(
                                                record.item.id
                                            )
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" /> Print
                                        E-Invoice
                                    </DropdownItem>
                                </DropdownContent>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </template>
            </JTable>
        </div>
    </div>

    <EInvoiceFormModal
        v-if="state.displayEInvoiceFormModal"
        :module-id="state.orderId"
        :module-type="moduleType"
        :receipt-number="state.receiptNumber"
        :sequence-number="state.sequenceNumber"
        :member-name="state.memberName"
        :location-name="state.locationName"
        :digital-invoice-submitted="state.digitalInvoiceSubmitted"
        :display-e-invoice-form-modal="state.displayEInvoiceFormModal"
        @update:hide-e-invoice-modal="hideEInvoiceFormModal"
        @refresh:table-refresh="refreshTable"
    />

    <OrderDetails
        :modal-show="state.displayOrderDetailsModal"
        :order="state.order"
        :columns-for-payment-details="state.columnsForPaymentDetails"
        :columns-for-order-item-details="state.columnsForOrderItemDetails"
        @close-modal="closeModal"
    />

    <Receipt
        v-if="state.orderReceipt && Object.keys(state.order).length"
        :order="state.order"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import FormAjaxSelect from "@commonComponents/FormAjaxSelect.vue";
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import JBadge from "@commonComponents/JBadge.vue";
import JDateTimePicker from "@commonComponents/JDateTimePicker.vue";
import JTable from "@commonComponents/JTable.vue";
import OutlinePrimaryButton from "@commonComponents/OutlinePrimaryButton.vue";
import Receipt from "@commonComponents/Receipt.vue";
import {
    currencyFormat,
    currentDateTime,
    displayAmountWithCurrencySymbol,
    printReport,
    checkEInvoicePermission,
} from "@commonServices/helper";
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from "@commonVendor/dropdown";
import OrderDetails from "@commonComponents/OrderDetails.vue";
import axios from "axios";
import {
    Info,
    List,
    MoreHorizontal,
    Printer,
    Notebook,
    ReceiptText,
} from "lucide-vue-next";
import { nextTick, reactive, computed } from "vue";
import { route } from "ziggy";
import EInvoiceFormModal from "@commonComponents/EInvoiceFormModal.vue";
import { usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    orderTypes: {
        type: Object,
        required: true,
    },
    orderTypesStaticUse: {
        type: Object,
        required: true,
    },
    paymentTypes: {
        type: Object,
        required: true,
    },
    storeManagers: {
        type: Object,
        required: true,
    },
    fetchRouteName: {
        type: String,
        required: true,
    },
    reportHeadingName: {
        type: String,
        required: true,
    },
    eInvoiceGeneratePermission: {
        type: String,
        required: true,
    },
    moduleType: {
        type: String,
        required: true,
    },
    allowEInvoice: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: "receipt_number",
        },
        {
            key: "bill_reference_number",
            label: "# Reference",
        },
        {
            key: "store_manager",
        },
        {
            key: "location",
        },
        {
            key: "total_amount",
            label: 'Amount',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_quantity",
            label: 'Quantity',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "return_quantity",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "reason",
            bodyClass: "text-left",
            headerClass: "text-left",
        },
    ],
    eInvoiceFilter: [
        {
            id: "1",
            name: "Yes",
        },
        {
            id: "0",
            name: "No",
        },
    ],

    columnsForPaymentDetails: [
        {
            key: "id",
            bodyClass: "text-left",
            headerClass: "text-left",
        },
        {
            key: "payment_type",
            bodyClass: "text-left",
            headerClass: "text-left",
        },
        {
            key: "amount",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
    ],

    columnsForOrderItemDetails: [
        {
            key: "upc",
            bodyClass: "text-left",
            headerClass: "text-left",
        },
        {
            key: "product",
            bodyClass: "text-left",
            headerClass: "text-left",
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
            key: "quantity",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "unit_price",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "subtotal",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_discount_amount",
            label: "Discount",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_tax_amount",
            label: "Tax",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_price_paid",
            label: 'Price Paid',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
    ],

    mainColumns: [
        { key: "digital_invoice_number", label: "Sequence#" },
        { key: "receipt_number", label: "Receipt" },
        { key: "happened_at", label: "Date" },
        { key: "store_manager" },
        { key: "location" },
        { key: "member" },
        { key: "type" },
        { key: "channel" },
        {
            key: "gross_sales",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_tax_amount",
            label: "Tax",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_discount_amount",
            label: "Discount",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "net_total",
            label: "Net",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "total_amount_paid",
            label: "Paid",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: "units_sold",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        { key: "notes" },
        { key: "bill_reference_number", label: "# Reference" },
        { key: "action", bodyClass: "text-center", headerClass: "text-center" },
    ],

    parameters: {
        member_id: null,
        date_range: currentDateTime(),
        type_id: null,
        location_id: null,
        store_manager_id: null,
        e_invoice_submitted: null,
    },
    displayEInvoiceFormModal: false,
    orderId: null,
    selected_product_id: null,
    order: {},
    locations: [],
    selected_complimentary_item_reason: null,
    discount_amount: null,
    selectedMember: null,
    receiptNumber: null,
    sequenceNumber: null,
    memberName: null,
    locationName: null,
    digitalInvoiceSubmitted: null,

    refreshTableData: Math.random(),
    printReceiptData: Math.random(),

    manageSaleReturn: false,
    manageSaleExchange: false,
    displayOrdersFilter: false,
    displayOrderDetailsModal: false,
    orderReceipt: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const isLayawayOrder = (typeId) => {
    return (
        props.orderTypesStaticUse.pendingLayawayOrder === typeId ||
            props.orderTypesStaticUse.completeLayawayOrder === typeId
    );
};

const isCreditOrder = (typeId) => {
    return (
        props.orderTypesStaticUse.pendingCreditOrder === typeId ||
            props.orderTypesStaticUse.completeCreditOrder === typeId
    );
};
const isCompleteOrder = (typeId) => {
    return (
        props.orderTypesStaticUse.pendingCreditOrder !== typeId ||
            props.orderTypesStaticUse.completeCreditOrder !== typeId ||
            props.orderTypesStaticUse.pendingLayawayOrder !== typeId ||
            props.orderTypesStaticUse.completeLayawayOrder !== typeId
    );
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.member_id = null;
    state.parameters.type_id = null;
    state.parameters.location_id = null;
    state.parameters.store_manager_id = null;
    state.parameters.channel_type_id = null;
    state.parameters.e_invoice_submitted = null;
    state.selectedMember = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
    refreshTable();
};

const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    state.parameters.member_id = null;
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

const updateStoreManager = (selectStoreManager) => {
    state.locations = [];
    state.parameters.location_id = null;
    state.parameters.store_manager_id = selectStoreManager;

    if (selectStoreManager !== null) {
        axios
            .get(
                route(
                    "admin.store_managers.get_stores_of_store_manager_id",
                    selectStoreManager
                )
            )
            .then((response) => {
                state.locations = response.data.locations;
            });
    }

    refreshTable();
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };

    axios
        .get(route("admin.members.get_filtered_members", filterData))
        .then((response) => {
            componentState.records = response.data.members;
            componentState.isLoading = false;
        });
};

const showOrderDetailsModal = (orderId, locationId, storeManagerId) => {
    state.order = [];
    state.orderReceipt = false;
    const filterData = {
        order_id: orderId,
        location_id: locationId,
        store_manager_id: storeManagerId,
    };
    axios
        .get(route("admin.orders.fetch_order_items", filterData))
        .then((response) => {
            state.order = response.data.order_details;
        });

    state.displayOrderDetailsModal = true;
};

const closeModal = () => {
    state.order = {};
    state.displayOrderDetailsModal = false;
};

const hideEInvoiceFormModal = () => {
    state.displayEInvoiceFormModal = false;
};

const showEInvoiceFormModal = (order) => {
    state.orderId = order.id;
    state.displayEInvoiceFormModal = true;
    state.sequenceNumber = order.digital_invoice_number;
    state.receiptNumber = order.receipt_number;
    state.memberName = order.member;
    state.locationName = order.location;
    state.digitalInvoiceSubmitted = order.digital_invoice_submitted;
};

const printLayawayOrderReceipt = (orderId) => {
    printReport(route("admin.orders.print_layaway_order_report", orderId));
};

const printCreditOrderReceipt = (orderId) => {
    printReport(route("admin.orders.print_credit_order_report", orderId));
};

const printOrderTaxInvoice = (orderId) => {
    printReport(route("admin.orders.print_order_tax_invoice", orderId));
};

const printPurchaseOrder = (orderId) => {
    printReport(route("admin.orders.print_purchase_order", orderId));
};

const printB2bOrderDigitalInvoice = (orderId) => {
    printReport(route("admin.orders.print_b2b_order_digital_invoice", orderId));
};

const printOrderReceipt = (orderId) => {
    state.order = {};
    state.orderReceipt = true;
    axios
        .get(route("admin.orders.print_order_receipt", orderId))
        .then((response) => {
            state.order = response.data.order_details;

            nextTick(() => {
                state.printReceiptData = Math.random();
            });
        });
};

const getRemainingLayawayOrCreditAmount = (items) => {
    const amount =
            items.type_id === props.orderTypesStaticUse.pendingLayawayOrder
                ? displayAmountWithCurrencySymbol(items.layaway_pending_amount)
                : displayAmountWithCurrencySymbol(items.credit_pending_amount);

    return "Due: " + amount;
};
</script>
