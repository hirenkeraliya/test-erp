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
                    :selected-record="state.parameters.channel_id"
                    :records="orderChannels"
                    input-label="Order Channel"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateOrderChannel"
                />
            </div>

            <div v-if="showInvoice">
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
        <div class="col-span-12 bg-white p-3 !z-[1]">
            <JTable
                :fetch-url="route(fetchUrl)"
                :columns="state.mainColumns"
                :refresh-table-data="state.refreshTableData"
                :additional-query-params="state.parameters"
                search-title="Search by Receipt Number or Bill Reference Number"
                :is-modal-table="true"
                :allow-csv-export="true"
                :allow-excel-export="true"
                :allow-pdf-export="true"
                :allow-column-customization="true"
                :export-csv-records-callback="exportCsvRecords"
                :export-excel-records-callback="exportExcelRecords"
                :export-pdf-records-callback="exportPDFRecords"
                :search-value="receiptNumber"
            >
                <template #receipt_number="record">
                    <div class="flex flex-col justify-start items-start space-y-2 rounded-lg shadow-sm">
                        <div class="flex justify-between items-center">
                            <span>{{ record.item.receipt_number }}</span>

                            <Tippy
                                v-if="
                                    record.item.digital_invoice_submitted &&
                                        showInvoice
                                "
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
                    </div>
                </template>

                <template #external_order_id="record">
                    <div class="flex flex-col justify-start items-start space-y-2 rounded-lg shadow-sm">
                        <div class="flex justify-between items-center">
                            <span>{{ record.item.order_channel_reference?.external_order_id }}</span>
                        </div>
                    </div>
                </template>

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
                            text="Picking List"
                            class="text-sm shadow-md mb-2 sm:mb-0"
                            @click="addPickingList()"
                        />
                    </p>

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
                    {{ record.item.total_amount_paid }}
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
                <template #select="record">
                    <div
                        v-if="
                            record.item.status_id ===
                                orderStatusStaticUse.accepted
                        "
                    >
                        <FormCheckbox
                            :check-value="
                                state.selectedRecords.includes(record.item.id)
                            "
                            @change="updateCheckbox($event, record.item.id)"
                        />
                    </div>
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
                            v-if="
                                record.item.status_id ===
                                    orderStatusStaticUse.placed ||
                                    (checkEInvoicePermission(
                                        eInvoiceGeneratePermission
                                    ) &&
                                        showInvoice)
                            "
                            class="dropdown"
                        >
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
                                            record.item.status_id ===
                                                orderStatusStaticUse.placed
                                        "
                                        class="flex items-center mr-3"
                                        @click="accepted(record.item.id)"
                                    >
                                        <Check class="w-4 h-4 mr-1" />
                                        Accepted
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="
                                            record.item.status_id ===
                                                orderStatusStaticUse.placed
                                        "
                                        class="flex items-center mr-3"
                                        @click="cancel(record.item.id)"
                                    >
                                        <X class="w-4 h-4 mr-1" />
                                        Cancel
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="
                                            checkEInvoicePermission(
                                                eInvoiceGeneratePermission
                                            ) &&
                                                showInvoice &&
                                                allowEInvoice
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
                                                .digital_invoice_submitted &&
                                                showInvoice
                                        "
                                        @click="
                                            printMarketPlaceOrderDigitalInvoice(
                                                record.item.id
                                            )
                                        "
                                    >
                                        <Printer class="w-5 h-5 mr-2" /> Print
                                        E-Invoice
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="record.item.status_id === orderStatusStaticUse.accepted"
                                        class="flex items-center mr-3"
                                        @click="readyForPickup(record.item.id, dismiss)"
                                    >
                                        <Check class="w-4 h-4 mr-1" />
                                        Ready For Pickup
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="record.item.status_id === orderStatusStaticUse.readyForPickup"
                                        @click="printNinjaVanWayBill(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" />
                                        Print Ninja Van Way Bill
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="
                                            record.item.status_id === orderStatusStaticUse.placed || record.item.status_id === orderStatusStaticUse.accepted
                                        "
                                        @click="changeOrderAddress(record.item.id, orderAddressStaticTypes.shippingAddress)"
                                    >
                                        <Truck class="w-5 h-5 mr-2" />
                                        Change Shipping Address
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="
                                            record.item.status_id === orderStatusStaticUse.placed || record.item.status_id === orderStatusStaticUse.accepted
                                        "
                                        @click="changeOrderAddress(record.item.id, orderAddressStaticTypes.billingAddress)"
                                    >
                                        <Truck class="w-5 h-5 mr-2" />
                                        Change Billing Address
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
        v-if="state.displayEInvoiceFormModal && showInvoice"
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

    <OrderAddressForm
        :modal-show="state.displayOrderAddressModel"
        :order-address="state.orderAddress"
        :order-id="state.orderId"
        @close-modal="closeModal"
    />
</template>

<script setup>
import FormAjaxSelect from "@commonComponents/FormAjaxSelect.vue";
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import JBadge from "@commonComponents/JBadge.vue";
import JDateTimePicker from "@commonComponents/JDateTimePicker.vue";
import JTable from "@commonComponents/JTable.vue";
import OutlinePrimaryButton from "@commonComponents/OutlinePrimaryButton.vue";
import {
    currencyFormat,
    currentDateTime,
    displayAmountWithCurrencySymbol,
    checkEInvoicePermission,
    printReport,
    exportRecords,
} from "@commonServices/helper";
import OrderDetails from "@commonComponents/OrderDetails.vue";
import OrderAddressForm from "@commonComponents/OrderAddressForm.vue";
import axios from "axios";
import {
    List,
    MoreHorizontal,
    Notebook,
    ReceiptText,
    Check,
    Printer,
    Truck,
    X,
} from "lucide-vue-next";
import { reactive, computed } from "vue";
import { route } from "ziggy";
import FormCheckbox from "@commonComponents/FormCheckbox.vue";
import {
    showErrorNotification,
    showSuccessNotification,
    confirmDialogBoxWithCenterText,
} from "@commonServices/notifier";
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from "@commonVendor/dropdown";
import EInvoiceFormModal from "@commonComponents/EInvoiceFormModal.vue";
import { router, usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    orderTypes: {
        type: Object,
        required: true,
    },
    orderChannels: {
        type: Object,
        required: true,
    },
    orderTypesStaticUse: {
        type: Object,
        required: true,
    },
    orderAddressStaticTypes: {
        type: Object,
        required: true
    },
    orderStatusStaticUse: {
        type: Object,
        required: true,
    },
    paymentTypes: {
        type: Object,
        required: true,
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    filterMembersUrl: {
        type: String,
        required: true,
    },
    fetchOrderItemsUrl: {
        type: String,
        required: true,
    },
    storePickingListsUrl: {
        type: String,
        required: true,
    },
    acceptedStatusUrl: {
        type: String,
        required: true,
    },
    cancelledStatusUrl: {
        type: String,
        required: true,
    },
    readyForPickupStatusUrl: {
        type: String,
        default: "",
    },
    printNinjaVanWayBillUrl: {
        type: String,
        default: "",
    },
    printPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    reportHeadingName: {
        type: String,
        required: true,
    },
    eInvoiceGeneratePermission: {
        type: String,
        default: "",
    },
    moduleType: {
        type: String,
        default: "",
    },
    showInvoice: {
        type: Boolean,
        default: false,
    },
    allowEInvoice: {
        type: Boolean,
        default: false,
    },
    printDigitalInvoiceUrl: {
        type: String,
        required: true,
    },
    printPdfUrl: {
        type: String,
        required: true,
    },
    receiptNumber: {
        type: String,
        default: null,
    },
    dateRange: {
        type: Array,
        default: null,
    },
    fetchOrderAddressUrl: {
        type: String,
        required: true,
    }
});

const state = reactive({
    columns: [
        {
            key: "receipt_number",
            isDisplay: true,
        },
        {
            key: "bill_reference_number",
            label: "# Reference",
            isDisplay: true,
        },
        {
            key: "store_manager",
            isDisplay: true,
        },
        {
            key: "location",
            isDisplay: true,
        },
        {
            key: "total_amount",
            label: 'Amount',
            bodyClass: "text-right",
            headerClass: "text-right",
            isDisplay: true,
        },
        {
            key: "total_quantity",
            label: 'Quantity',
            bodyClass: "text-right",
            headerClass: "text-right",
            isDisplay: true,
        },
        {
            key: "return_quantity",
            bodyClass: "text-right",
            headerClass: "text-right",
            isDisplay: true,
        },
        {
            key: "reason",
            bodyClass: "text-left",
            headerClass: "text-left",
            isDisplay: true,
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
        { key: "digital_invoice_number", label: "Sequence#", isDisplay: true },
        { key: "select", isDisplay: true },
        { key: "bill_reference_number", label: "Reference", isDisplay: true },
        { key: "receipt_number", label: "Receipt", isDisplay: true },
        { key: "external_order_id", label: "External Order ID", isDisplay: true },
        { key: "created_at", label: "Date", isDisplay: true },
        { key: "happened_at", label: "Happened At", isDisplay: true },
        { key: "member", label: "Member", isDisplay: true },
        { key: "channel", isDisplay: true },
        { key: "payment_types", isDisplay: true },
        { key: "logistic", isDisplay: true },
        {
            key: "units_sold",
            label: "Total",
            bodyClass: "text-right",
            headerClass: "text-right",
            isDisplay: true,
        },
        { key: "status", isDisplay: true },
        { key: "action", bodyClass: "text-center", headerClass: "text-center", isDisplay: true },
    ],

    parameters: {
        member_id: null,
        date_range: props.dateRange ? props.dateRange : currentDateTime(),
        type_id: null,
        channel_id: null,
        location_id: null,
        store_manager_id: null,
        e_invoice_submitted: null,
    },
    displayEInvoiceFormModal: false,
    orderId: 0,
    selected_product_id: null,
    order: {},
    shippingAddress: {},
    orderAddress: {},
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
    displayOrderAddressModel: false,
    orderReceipt: false,
    selectedRecords: [],
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.member_id = null;
    state.parameters.type_id = null;
    state.parameters.location_id = null;
    state.parameters.store_manager_id = null;
    state.parameters.e_invoice_submitted = null;
    state.parameters.channel_type_id = null;
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

const updateOrderChannel = (selectOrderChannel) => {
    state.parameters.channel_id = selectOrderChannel;
    refreshTable();
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };

    axios.get(route(props.filterMembersUrl, filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const showOrderDetailsModal = (orderId) => {
    state.order = [];
    state.orderReceipt = false;
    const filterData = {
        order_id: orderId,
    };
    axios.get(route(props.fetchOrderItemsUrl, filterData)).then((response) => {
        state.order = response.data.order_details;
    });

    state.displayOrderDetailsModal = true;
};

const closeModal = () => {
    state.order = {};
    state.displayOrderDetailsModal = false;
    state.displayShippingAddressModel = false;
    state.displayOrderAddressModel = false;
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

const updateCheckbox = (element, orderId) => {
    if (!element.target.checked) {
        const index = state.selectedRecords.lastIndexOf(parseInt(orderId));
        state.selectedRecords.splice(index, 1);
    }

    if (element.target.checked) {
        state.selectedRecords.push(parseInt(orderId));
    }
};

const addPickingList = () => {
    if (!state.selectedRecords.length) {
        showErrorNotification("Please Select Picking List");
        return;
    }

    axios
        .post(route(props.storePickingListsUrl), {
            order_ids: state.selectedRecords,
        })
        .then(() => {
            state.selectedRecords = [];
            showSuccessNotification(
                "Selected orders added successfully in picking list ."
            );
            refreshTable();
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const accepted = (OrderId) => {
    confirmDialogBoxWithCenterText(
        "Are you sure to accepted this request?",
        () => {
            router.post(
                route(props.acceptedStatusUrl, OrderId),
                {},
                {
                    onSuccess: () => refreshTable(),
                }
            );
        }
    );
};

const cancel = (OrderId) => {
    confirmDialogBoxWithCenterText(
        "Are you sure to cancel this request?",
        () => {
            router.post(
                route(props.cancelledStatusUrl, OrderId),
                {},
                {
                    onSuccess: () => refreshTable(),
                }
            );
        }
    );
};

const readyForPickup = (orderId) => {
    confirmDialogBoxWithCenterText(
        "Are you sure to ready for pickup this request?",
        () => {
            router.post(
                route(props.readyForPickupStatusUrl, orderId),
                {},
                {
                    onSuccess: () => refreshTable(),
                }
            );
        }
    );
};

const printNinjaVanWayBill = (orderId) => {
    printReport(route(props.printNinjaVanWayBillUrl, orderId));
};

const changeOrderAddress = async (orderId, orderTypeValue) => {
    state.orderAddress = {};

    const filterData = {
        order_id: orderId,
        type: orderTypeValue,
    };

    await axios.get(route(props.fetchOrderAddressUrl, filterData)).then((response) => {
        state.orderId = orderId;

        state.orderAddress = response.data.order_address;
    });

    state.displayOrderAddressModel = true;
};

const printMarketPlaceOrderDigitalInvoice = (orderId) => {
    printReport(route(props.printDigitalInvoiceUrl, orderId));
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-marketplace-orders/',
        'marketplace-orders.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-marketplace-orders/',
        'marketplace-orders.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportPDFRecords = (params, columns) => {
    params['module_type'] = props.moduleType;
    params['export_columns'] = columns;
    printReport(route(props.printPdfUrl, params), props.exportPermission);
};
</script>
