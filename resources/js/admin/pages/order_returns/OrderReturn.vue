<template>
    <PageTitle title="Order Return" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Order Returns
        </h2>
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
                :fetch-url="route('admin.order_returns.fetch_order_returns')"
                :columns="state.mainColumns"
                :refresh-table-data="state.refreshTableData"
                :additional-query-params="state.parameters"
                search-title="Search by Receipt Number"
                :is-modal-table="true"
            >
                <template #receipt_number="data">
                    <div class="flex justify-left items-center">
                        <span>
                            {{ data.item.receipt_number }}
                        </span>
                        <Tippy
                            v-if="data.item.digital_invoice_submitted"
                            :content="'E-Invoice generated'"
                        >
                            <ReceiptText
                                class="ml-2 cursor-pointer text-info"
                                :size="15"
                            />
                        </Tippy>
                    </div>
                </template>
                <template #extra-header-data="record">
                    <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                        <JBadge
                            v-if="record.data.total_order_returns"
                            :label="'Order Returns: ' + currencyFormat(record.data.total_order_returns)"
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="record.data.total_units_sold"
                            :label="'Units Sold: ' + currencyFormat(record.data.total_units_sold)"
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="record.data.total_order_returns_amount"
                            :label="'Order Returns: ' + displayAmountWithCurrencySymbol(record.data.total_order_returns_amount)"
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

                <template #info="record">
                    <div class="flex justify-center items-center cursor-pointer">
                        <Printer
                            class="mr-1"
                            @click="printOrderReturnReport(record.item.id)"
                        />

                        <List
                            @click="showOrderReturnDetailsModal(record.item.id)"
                        />
                    </div>
                </template>

                <template #action="record">
                    <div class="flex items-center justify-center cursor-pointer">
                        <Dropdown
                            v-if="checkEInvoicePermission(eInvoiceGeneratePermission) && allowEInvoice"
                            class="flex items-center mr-3"
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
                                        class="flex items-center mr-3"
                                        @click="showEInvoiceFormModal(record.item)"
                                    >
                                        <Notebook class="w-4 h-4 mr-1" />
                                        E-Invoice Generation
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="record.item.digital_invoice_submitted"
                                        @click="printOrderReturnDigitalInvoice(record.item.id)"
                                    >
                                        <Printer class="w-5 h-5 mr-2" /> Print Invoice
                                    </DropdownItem>
                                </DropdownContent>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </template>

                <template
                    #total_amount_paid="record"
                >
                    {{ record.item.total_amount_paid }}
                </template>

                <template #type="record">
                    <JBadge
                        :label="record.item.type"
                        class="mb-1 lg:mb-1 xl:mb-0"
                    />

                    <JBadge
                        v-if="record.item.order_types.cancelOrder === record.item.type_id"
                        :label="record.item.type"
                        type="danger"
                    />
                </template>
            </JTable>
        </div>
    </div>

    <EInvoiceFormModal
        v-if="state.displayEInvoiceFormModal"
        :module-id="state.orderReturnId"
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

    <OrderReturnItemDetails
        v-if="Object.keys(state.orderReturn).length"
        :modal-show="state.displayOrderReturnDetailsModal"
        :order-return="state.orderReturn"
        :columns-for-order-return-item-details="state.columnsForOrderReturnItemDetails"
        @close-modal="closeModal"
    />

    <Receipt
        v-if="Object.keys(state.orderReturnReceipt).length"
        :order-return="state.orderReturnReceipt"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import Receipt from '@commonComponents/Receipt.vue';
import { currencyFormat, currentDateTime, displayAmountWithCurrencySymbol, isPrintRecords, checkEInvoicePermission, printReport } from '@commonServices/helper';
import OrderReturnItemDetails from '@adminPages/order_returns/partials/OrderReturnItemDetails.vue';
import axios from 'axios';
import { List, Printer, MoreHorizontal, Notebook, ReceiptText } from 'lucide-vue-next';
import EInvoiceFormModal from '@commonComponents/EInvoiceFormModal.vue';
import { nextTick, reactive, computed } from 'vue';
import { route } from 'ziggy';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    storeManagers: {
        type: Object,
        required: true
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
    columnsForOrderReturnItemDetails: [
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
            key: 'put_back_in_inventory',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'order_return_reason',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'unit_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'subtotal',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }
    ],

    mainColumns: [
        {
            key: 'digital_invoice_number',
            label: 'Sequence#',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'receipt_number',
            label: 'Receipt Number',
            isDisplay: true,
        }, {
            key: 'original_order_receipt_number',
            isDisplay: true,
        }, {
            key: 'location',
            label: 'Location',
            isDisplay: true,
        }, {
            key: 'store_manager',
            isDisplay: true,
        }, {
            key: 'created_at',
            label: 'Date & Time',
            isDisplay: true,
        }, {
            key: 'member',
            isDisplay: true,
        }, {
            key: 'total_price_paid',
            label: 'Return',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    parameters: {
        member_id: null,
        date_range: currentDateTime(),
        location_id: null,
        store_manager_id: null,
    },
    displayEInvoiceFormModal: false,
    orderReturnId: null,
    orderReturn: {},
    orderReturnReceipt: {},
    orderReturnDetails: {},
    selectedMember: null,
    receiptNumber: null,
    sequenceNumber: null,
    memberName: null,
    locationName: null,
    digitalInvoiceSubmitted: null,

    refreshTableData: Math.random(),
    printReceiptData: Math.random(),

    memberModalShow: false,
    displayOrderReturnDetailsModal: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.member_id = null;
    state.parameters.location_id = null;
    state.parameters.store_manager_id = null;
    state.selectedMember = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const hideEInvoiceFormModal = () => {
    state.displayEInvoiceFormModal = false;
};

const showEInvoiceFormModal = (orderReturn) => {
    state.orderReturnId = orderReturn.id;
    state.displayEInvoiceFormModal = true;
    state.sequenceNumber = orderReturn.digital_invoice_number;
    state.receiptNumber = orderReturn.receipt_number;
    state.memberName = orderReturn.member;
    state.locationName = orderReturn.location;
    state.digitalInvoiceSubmitted = orderReturn.digital_invoice_submitted;
};

const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    state.parameters.member_id = null;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
    refreshTable();
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };

    axios.get(route('admin.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const closeModal = () => {
    state.orderReturn = {};
    state.displayOrderReturnDetailsModal = false;
};

const updateLocation = (selectLocation) => {
    state.parameters.location_id = selectLocation;
    refreshTable();
};

const printOrderReturnReport = (orderReturnId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.orderReturn = [];
        axios.get(route('admin.order_returns.print_order_return_receipt', orderReturnId))
            .then((response) => {
                state.orderReturnReceipt = response.data.order_return_details;
                nextTick(() => {
                    state.printReceiptData = Math.random();
                });
            });
    }
};

const showOrderReturnDetailsModal = (orderReturnId) => {
    state.orderReturn = [];
    axios.get(route('admin.order_returns.fetch_order_return_items', orderReturnId))
        .then((response) => {
            state.orderReturn = response.data.order_return_details;
            state.displayOrderReturnDetailsModal = true;
        });
};

const printOrderReturnDigitalInvoice = (orderReturnId) => {
    printReport(route('admin.order_returns.print_order_return_digital_invoice', orderReturnId));
};

const updateStoreManager = (selectStoreManager) => {
    state.locations = [];
    state.parameters.location_id = null;
    state.parameters.store_manager_id = selectStoreManager;

    if (selectStoreManager !== null) {
        axios.get(route('admin.store_managers.get_stores_of_store_manager_id', selectStoreManager))
            .then((response) => {
                state.locations = response.data.locations;
            });
    }

    refreshTable();
};
</script>
