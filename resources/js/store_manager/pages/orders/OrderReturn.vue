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
                :fetch-url="route('store_manager.order_returns.fetch_order_returns')"
                :columns="state.mainColumns"
                :refresh-table-data="state.refreshTableData"
                :additional-query-params="state.parameters"
                search-title="Search by Receipt Number"
                :is-modal-table="true"
            >
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

                <template #action="record">
                    <div class="flex justify-center items-center p-3">
                        <Tippy
                            tag="button"
                            type="button"
                            class="border rounded p-1 mr-2"
                            content="Order Items"
                            @click="showOrderReturnItemDetailsModal(record.item.id)"
                        >
                            <List class="w-5 h-5" />
                        </Tippy>

                        <Tippy
                            tag="button"
                            type="button"
                            class="border rounded p-1 mr-2"
                            content="Order Return Receipts"
                            @click="printOrderReceipt(record.item.id)"
                        >
                            <Printer class="w-5 h-5 mr-2" />
                        </Tippy>
                    </div>
                </template>
            </JTable>
        </div>
    </div>

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
import { currencyFormat, currentDateTime, displayAmountWithCurrencySymbol, isPrintRecords } from '@commonServices/helper';
import OrderReturnItemDetails from '@storeManagerPages/orders/partials/OrderReturnItemDetails.vue';
import axios from 'axios';
import { List, Printer } from 'lucide-vue-next';
import { nextTick, reactive, computed } from 'vue';
import { route } from 'ziggy';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Object,
        required: true
    },
    locationId: {
        type: Number,
        default: 0,
    },
    exportPermission: {
        type: String,
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
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'unit_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'subtotal',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }
    ],

    mainColumns: [
        {
            key: 'receipt_number',
            label: 'Receipt Number',
            isDisplay: true,
        }, {
            key: 'original_order_receipt_number',
            isDisplay: true,
        }, {
            key: 'location',
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
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    parameters: {
        member_id: null,
        date_range: currentDateTime(),
        location_id: props.locationId,
    },

    orderReturn: {},
    orderReturnReceipt: {},
    order: {},
    orderId: null,
    orderReturnDetails: {},
    selectedMember: null,

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
    state.selectedMember = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
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

const closeModal = () => {
    state.orderReturn = {};
    state.displayOrderReturnDetailsModal = false;
};

const printOrderReceipt = (orderId) => {
    state.order = {};
    axios.get(route('store_manager.orders.print_order_receipt', orderId))
        .then((response) => {
            state.order = response.data.order_details;

            nextTick(() => {
                state.printReceiptData = Math.random();
            });
        });
};

const updateLocation = (selectLocation) => {
    state.parameters.location_id = selectLocation;
    refreshTable();
};

const printOrderReturnReport = (orderReturnId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.orderReturn = [];
        axios.get(route('store_manager.order_returns.fetch_order_return_for_receipt', orderReturnId))
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
    axios.get(route('store_manager.order_returns.fetch_order_return_items', orderReturnId))
        .then((response) => {
            state.orderReturn = response.data.order_return_details;
            state.displayOrderReturnDetailsModal = true;
        });
};

</script>
