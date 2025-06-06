<template>
    <PageTitle title="Purchase" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Purchase Plans
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <PrimaryButton
                text="Add a New Purchase plan"
                class="shadow-md mr-1"
                @click="redirectToAddPurchasePlan()"
            />
        </div>
    </div>

    <div
        v-if="state.displayPurchasePlanFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div
                v-if="isAdmin"
                class="mt-3"
            >
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.typeId"
                    return-selected-record="id"
                    input-label="Location Selection"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocationType"
                />
            </div>

            <div v-if="isAdmin">
                <TabPanel
                    v-if="state.typeId === staticLocationTypes.store"
                    class="active"
                >
                    <FormSelectBox
                        :selected-record="state.parameters.location_id"
                        :records="stores"
                        placeholder="Please select store"
                        input-label="Store"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateLocationId"
                    />
                </TabPanel>

                <TabPanel
                    v-if="state.typeId === staticLocationTypes.warehouse"
                    class="active"
                >
                    <FormSelectBox
                        :selected-record="state.parameters.location_id"
                        :records="warehouses"
                        placeholder="Please select warehouse"
                        input-label="Warehouse"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateLocationId"
                    />
                </TabPanel>
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.vendor_id"
                    :records="vendors"
                    placeholder="Please select vendor"
                    input-label="Vendor"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateVendorId"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.select_status"
                    :records="planStatuses"
                    placeholder="Please select status"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStatus"
                />
            </div>

            <div>
                <FormInput
                    :input-value="state.parameters.plan_number"
                    input-label="Plan Number"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    placeholder="Please type the plan number."
                    @update:input-value="selectPlanNumber($event)"
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
        :fetch-url="route(props.fetchPurchasePlanUrl)"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecordsForList"
        :export-excel-records-callback="exportExcelRecordsForList"
        search-title="Search by purchase number"
    >
        <template #to="record">
            {{ record.item.to }}
        </template>

        <template #from="record">
            {{ record.item.from }}
        </template>

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
                content="Purchase Plan Items"
                class="cursor-pointer"
                @click="openPurchasePlanItemsModal(record.item.id)"
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
                                v-if="record.item.status_id !== statuses.approved && record.item.status_id !== statuses.cancelled && record.item.status_id !== statuses.completed"
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
                                v-if="record.item.status_id === statuses.approved || record.item.status_id === statuses.completed"
                                @click="externalPurchaseOrder(record.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" /> External Purchase Order
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.status_id !== statuses.approved && record.item.status_id !== statuses.completed && record.item.status_id !== statuses.cancelled"
                                class="text-danger"
                                @click="markAsCancel(record.item.id, statuses.cancelled, dismiss)"
                            >
                                <X class="w-4 h-4 mr-2" /> Cancel
                            </DropdownItem>
                            <DropdownItem
                                @click="printPurchasePlan(record.item.id, dismiss)"
                            >
                                <Printer class="w-4 h-4 mr-1" />Print
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #extra-header-data="record">
            <div class="mx-0 mb-2 sm:mb-0 md:mx-2">
                <div>
                    <div class="table w-full mt-5 rounded border-separate border-spacing-y-2 border-spacing-x-1">
                        <div
                            v-if="getBadgeDisplay(record.data.purchasePlanStatusCounts)"
                            class="table-row"
                        >
                            <label class="table-cell font-semibold">Purchase Plan: </label>

                            <JBadge
                                v-for="(statusCount, index) in record.data.purchasePlanStatusCounts"
                                :key="index"
                                class="table-cell cursor-pointer mr-1"
                                :label="`${index} : ${statusCount.count}`"
                                @click="statusChanges(statusCount.id)"
                            />
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
            <p
                v-if="state.isClear"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Clear"
                    class="text-sm shadow-md"
                    @click="refreshPage"
                />
            </p>
            <p class="text-lg font-bold mr-2 mb-2 sm:mb-0 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayPurchasePlanFilter = !state.displayPurchasePlanFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        :modal-show="state.displayPurchasePlanItemsModal"
        :columns="state.purchasePlanItemsFields"
        :records="state.purchasePlanItems"
        :totals="state.totals"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        title="Transfer Items"
        @close-modal="closeModal"
    >
        <template #quantity="data">
            {{ data.item.quantity }}
            <br>
            {{ data.item.derivative }}
        </template>

        <template #transferred_quantity="data">
            {{ data.item.transferred_quantity }}
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
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import { MoreHorizontal, List, CheckSquare, X, Check, Printer } from 'lucide-vue-next';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import axios from 'axios';
import { exportRecords, displayAmountWithCurrencySymbol, printReport } from '@commonServices/helper';
import { TabPanel } from '@commonVendor/tab';
import JTabs from '@commonComponents/JTabs.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';
import JBadge from '@commonComponents/JBadge.vue';

const props = defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
    stores: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    vendors: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    fetchPurchasePlanUrl: {
        type: String,
        required: true,
    },
    createPurchasePlanUrl: {
        type: String,
        required: true,
    },
    editPurchasePlanUrl: {
        type: String,
        required: true,
    },
    printPurchasePlanUrl: {
        type: String,
        required: true,
    },
    indexPurchasePlanUrl: {
        type: String,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
    fetchPurchasePlanItemsUrl: {
        type: String,
        required: true,
    },
    planStatuses: {
        type: Object,
        required: true,
    },
    cancelPurchasePlanUrl: {
        type: String,
        required: true,
    },
    approvePurchasePlanUrl: {
        type: String,
        required: true,
    },
    externalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    displayPurchasePlanItemsModal: false,
    purchasePlanItems: [],
    totals: [],
    purchasePlanId: null,
    displayPurchasePlanFilter: false,
    externalWarehouses: [],
    externalStores: [],
    isClear: false,
    columns: [
        {
            key: 'created_at',
            label: 'Date',
            sortable: true,
        }, {
            key: 'from',
            label: 'Vendor',
        }, {
            key: 'to',
        }, {
            key: 'plan_number',
            sortable: true
        },{
            key: 'reference_number',
            sortable: true
        }, {
            key: 'total_amount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'remarks',
        }, {
            key: 'items',
            headerClass: 'text-center',
            bodyClass: 'text-center',
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
    purchasePlanItemsFields: [
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
        }, {
            key: 'product_color',
            label: 'Color',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'product_size',
            label: 'Size',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'quantity',
            label: 'Quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'transferred_quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'cost_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'total_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'remarks',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],
    externalTypeId: props.staticLocationTypes.store,
    typeId: props.isAdmin ? props.staticLocationTypes.store : null,
    parameters: {

        select_status: null,
        date_range: null,
        location_id: '',
        vendor_id: null,
        status: null,
        plan_number: null,
    },
});

const redirectToAddPurchasePlan = () => {
    router.get(route(props.createPurchasePlanUrl));
};

const openPurchasePlanItemsModal = (purchasePlanId) => {
    state.purchasePlanItems = [];
    state.totals = [];
    axios.get(route(props.fetchPurchasePlanItemsUrl, purchasePlanId))
        .then((response) => {
            state.purchasePlanItems = response.data.purchase_plan_items;
            state.totals = response.data.totals;
            state.displayPurchasePlanItemsModal = true;
        });
    state.purchasePlanId = purchasePlanId;
};

const getBadgeDisplay = (statusCounts) => {
    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const statusChanges = (status) => {
    state.parameters.select_status = status;
    refreshTable();
};

const getStatusColor = (status) => {
    if (status === props.statuses.approved) {
        return 'btn btn-rounded btn-success-soft';
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
};

const edit = (purchasePlanId, dismiss) => {
    router.get(route(props.editPurchasePlanUrl, purchasePlanId));
    dismiss();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const closeModal = () => {
    state.displayPurchasePlanItemsModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-purchase-plan-items/' + state.purchasePlanId + '/',
        'purchase-plan-items.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-purchase-plan-items/' + state.purchasePlanId + '/',
        'purchase-plan-items.xlsx',
        params,
        props.exportPermission
    );
};

const clearAll = () => {
    state.parameters.date_range = null;
    state.parameters.location_id = null;
    state.parameters.vendor_id = null;
    state.parameters.plan_number = null;
    state.parameters.select_status = null;

    refreshTable();
};

const selectPlanNumber = (planNumber) => {
    state.parameters.plan_number = planNumber;
    refreshTable();
};


const updateTransferDate = (selectedDate) => {
    state.parameters.date_range = selectedDate;
    refreshTable();
};

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.parameters.location_id = null;
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = parseInt(locationId);
    refreshTable();
};

const updateVendorId = (vendorId) => {
    state.parameters.vendor_id = parseInt(vendorId);
    refreshTable();
};

const updateStatus = (statusId) => {
    state.parameters.select_status = parseInt(statusId);
    refreshTable();
};

const getFilterTabStatus = () => {
    return state.displayPurchasePlanFilter === false && (state.parameters.select_status !== null);
};

const refreshPage = () => {
    router.get(route(props.indexPurchaseOrderUrl));
};

const exportCsvRecordsForList = (params) => {
    return exportRecords(
        'export-purchase-plans/',
        'purchase-plan.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecordsForList = (params) => {
    return exportRecords(
        'export-purchase-plans/',
        'purchase-plan.xlsx',
        params,
        props.exportPermission
    );
};

const markAsCancel = (purchasePlanId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure to cancel this request?', () => {
        router.post(route(props.cancelPurchasePlanUrl, purchasePlanId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const markAsApprove = (purchasePlanId, statusId, dismiss) => {
    confirmDialogBoxWithCenterText('Are you sure you want to approve the purchase Plan?', () => {
        router.post(route(props.approvePurchasePlanUrl, purchasePlanId), {
            status_id: statusId
        }, {
            onSuccess: () => refreshTable()
        });
    });
    dismiss();
};

const printPurchasePlan = (purchasePlanId, dismiss) => {
    printReport(route(props.printPurchasePlanUrl, purchasePlanId), props.exportPermission);
    dismiss();
};

const externalPurchaseOrder = (purchasePlaneId, dismiss) => {
    router.get(route(props.externalPurchaseOrderUrl, purchasePlaneId));
    dismiss();
};

</script>
