<template>
    <PageTitle title="Stock Transfers" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Transfers
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <PrimaryButton
                text="Request Order"
                class="shadow-md mr-1"
                @click="redirectToAddStockTransfer(stockTransferTypes.request_order)"
            />

            <PrimaryButton
                text="Transfer Order"
                class="shadow-md"
                @click="redirectToAddStockTransfer(stockTransferTypes.transfer_order)"
            />
        </div>
    </div>

    <div
        v-if="state.displayStockTransferFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.typeId"
                    input-label="Location Selection"
                    return-selected-record="id"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocationType"
                />
            </div>

            <div>
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
                    :selected-record="state.parameters.transfer_type"
                    :records="transferTypes"
                    placeholder="Please select transfer type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Transfer Type"
                    @update:selected-record="updateTransferType"
                />
            </div>
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
                <FormInput
                    :input-value="state.parameters.stock_transfer_number"
                    input-label="Stock Transfer Number"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    placeholder="Please type the stock transfer number."
                    @update:input-value="selectStockTransferNumber"
                />
            </div>

            <div>
                <JDatePicker
                    :max-date="new Date()"
                    :range-picker="true"
                    :input-value="state.parameters.stock_transfer_date"
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
        :fetch-url="route(stockTransferFetchUrl,{ stock_transfer_number: stockTransferNumber, stock_transfer_id: stockTransferId, dashboard_transfer_type: dashboardFilterData.transfer_type })"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        search-title="Search by from location, to location, or status"
    >
        <template #status="record">
            <div class="inline-flex items-center">
                <span :class="getStatusColor(record.item.status)">{{ record.item.status }}</span>
                <Tippy
                    v-if="Object.keys(record.item.status_times).length"
                    :content="getStatusTimes(record.item.status_times)"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="18"
                    />
                </Tippy><br>

                <Tippy
                    v-if="record.item.transit_location_name"
                    :content="'Transit via: ' + record.item.transit_location_name"
                >
                    <GitMerge
                        class="text-purple-800 ml-2"
                        :size="18"
                    />
                </Tippy>
            </div>
        </template>

        <template #to="record">
            {{ record.item.to }}

            <span v-if="Object.keys(record.item.traveling_average_lead_days).length > 0">
                <Tippy
                    :content="record.item.traveling_average_lead_days.message"
                >
                    <div class="relative w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                        <div
                            class="h-4 rounded-full"
                            :style="{
                                width: record.item.traveling_average_lead_days.progress_percentage + '%',
                                background: getStockTransferShipmentProgress(record.item.traveling_average_lead_days.progress_percentage)
                            }"
                        >
                            <Truck
                                class="absolute w-5 h-5"
                                :style="{ left: record.item.traveling_average_lead_days.progress_percentage - 10 + '%' }"
                            />
                        </div>
                    </div>
                </Tippy>
            </span>
        </template>

        <template #transfer_numbers="record">
            <span v-html="objectArrayToString(record.item.order_numbers, '<br />')" />
        </template>

        <template #items="record">
            <Tippy
                content="Stock Transfer Items"
                class="cursor-pointer"
                @click="openStockTransferItemsModal(record.item)"
            >
                <List />
            </Tippy>
        </template>

        <template #actions="data">
            <div
                class="flex justify-center items-center"
            >
                <Dropdown
                    v-if="hasActionMenu(data.item)"
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
                                v-if="canTakeActionInDraft(data.item)"
                                @click="edit(data.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Edit
                            </DropdownItem>

                            <DropdownItem
                                v-if="canTakeActionInOpenRequestOrder(data.item)"
                                @click="editRequestOrderByDestination(data.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Edit Request Order
                            </DropdownItem>

                            <DropdownItem
                                v-if="canTakeActionInDraft(data.item)"
                                @click="updateStatus(data.item.id, statuses.open, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-2" /> Open
                            </DropdownItem>

                            <DropdownItem
                                v-if="canTakeActionInOpenRequestOrder(data.item)"
                                @click="markAsApproved(data.item, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Approve
                            </DropdownItem>

                            <DropdownItem
                                v-if="canCancelTransfer(data.item)"
                                class="text-danger"
                                @click="cancelStatus(data.item.id, statuses.cancelled)"
                            >
                                <X class="w-4 h-4 mr-2" /> Cancel
                            </DropdownItem>

                            <DropdownItem
                                v-if="canTakeActionInOpenMode(data.item)"
                                class="text-danger"
                                @click="rejectStatus(data.item.id, statuses.rejected)"
                            >
                                <X class="w-4 h-4 mr-1" />
                                Reject
                            </DropdownItem>

                            <DropdownItem
                                v-if="canMarkAsShip(data.item)"
                                @click="markAsShipped(data.item.id, dismiss, data.item.source_id, data.item.destination_id)"
                            >
                                <Truck class="w-4 h-4 mr-1" />
                                Mark as Shipped
                            </DropdownItem>

                            <DropdownItem
                                v-if="canMarkAsTransitIn(data.item)"
                                @click="updateStatus(data.item.id, statuses.transit_in, dismiss)"
                            >
                                <Truck class="w-4 h-4 mr-1" />
                                Mark as Transit IN
                            </DropdownItem>

                            <DropdownItem
                                v-if="canRevertBackToDirect(data.item)"
                                @click="revertBackToDirect(data.item.id, dismiss)"
                            >
                                <Tippy
                                    content="In case of by mistakenly selected the Transit and revert back to the shipped as Direct."
                                    tag="div"
                                    class="dropdown-item -ml-2"
                                >
                                    <CheckSquare class="w-4 h-4 mr-2" />Mark as Direct
                                </Tippy>
                            </DropdownItem>

                            <DropdownItem
                                v-if="canMarkAsTransitOut(data.item)"
                                @click="updateStatus(data.item.id, statuses.transit_out, dismiss)"
                            >
                                <Truck class="w-4 h-4 mr-1" />
                                Mark as Transit OUT
                            </DropdownItem>

                            <DropdownItem
                                v-if="isShipped(data.item)"
                                @click="updateStatus(data.item.id, statuses.received, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Mark as Received
                            </DropdownItem>

                            <DropdownItem
                                v-if="isReceived(data.item)"
                                @click="deliveryNotes(data.item.id, dismiss)"
                            >
                                <FileText class="w-4 h-4 mr-1" />
                                Add Delivery Notes
                            </DropdownItem>

                            <DropdownItem
                                v-if="canCloseTransfer(data.item)"
                                @click="closeDiscrepancy(data.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Close
                            </DropdownItem>

                            <DropdownItem
                                v-if="getTransferOrderPrintType(data.item) !== ''"
                                @click="printStockTransfer(data.item.id, getTransferOrderPrintType(data.item))"
                            >
                                <Printer class="w-4 h-4 mr-1" />
                                Print Transfer Order
                            </DropdownItem>

                            <DropdownItem
                                v-if="getRequestOrderPrintType(data.item) !== ''"
                                @click="printStockTransfer(data.item.id, getRequestOrderPrintType(data.item))"
                            >
                                <Printer class="w-4 h-4 mr-1" />
                                Print Request Order
                            </DropdownItem>

                            <DropdownItem
                                v-if="canPrintTransferOut(data.item)"
                                @click="printStockTransfer(data.item.id, 'OUT')"
                            >
                                <Printer class="w-4 h-4 mr-1" />
                                Print Transfer Out
                            </DropdownItem>

                            <DropdownItem
                                v-if="canPrintTransferIn(data.item)"
                                @click="printStockTransfer(data.item.id, 'IN')"
                            >
                                <Printer class="w-4 h-4 mr-1" />
                                Print Transfer In
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
                            v-if="getBadgeDisplay(record.data.transferOrderStatusCounts, staticStockTransferType.transferOrder)"
                            class="block items-center xl:flex"
                            :class="getBadgeDisplay(record.data.requestOrderStatusCounts, staticStockTransferType.requestOrder) ? 'mb-2' : ''"
                        >
                            <label class="mr-2 font-semibold">Transfer Order : </label>
                            <div>
                                <div class="block items-center xl:flex mr-2">
                                    <JBadge
                                        v-for="(statusCount, index) in record.data.transferOrderStatusCounts"
                                        :key="index"
                                        class="mb-1 xl:mb-2 2xl:mb-0 cursor-pointer"
                                        :label="`${index} : ${statusCount.count}`"
                                        @click="statusChanges(statusCount.id, staticStockTransferType.transferOrder)"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="getBadgeDisplay(record.data.requestOrderStatusCounts, staticStockTransferType.requestOrder)"
                            class="block items-center xl:flex"
                        >
                            <label class="mx-2 font-semibold">Request Order : </label>
                            <div>
                                <JBadge
                                    v-for="(statusCount, index) in record.data.requestOrderStatusCounts"
                                    :key="index"
                                    class="mb-1 xl:mb-2 2xl:mb-0 cursor-pointer"
                                    :label="`${index} : ${statusCount.count}`"
                                    @click="statusChanges(statusCount.id, staticStockTransferType.requestOrder)"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <div
                            v-if="getBadgeDisplay(record.data.transferInStatusCounts, staticStockTransferType.transferIn)"
                            class="block items-center xl:flex"
                            :class="getBadgeDisplay(record.data.transferOutStatusCounts, staticStockTransferType.transferOut) ? 'mb-2' : ''"
                        >
                            <label class="mx-2 font-semibold">Transfer In : </label>
                            <div>
                                <JBadge
                                    v-for="(statusCount, index) in record.data.transferInStatusCounts"
                                    :key="index"
                                    class="mb-1 xl:mb-2 2xl:mb-0 cursor-pointer"
                                    :label="`${index} : ${statusCount.count}`"
                                    @click="statusChanges(statusCount.id, staticStockTransferType.transferIn)"
                                />
                            </div>
                        </div>

                        <div
                            v-if="getBadgeDisplay(record.data.transferOutStatusCounts, staticStockTransferType.transferOut)"
                            class="block items-center xl:flex"
                        >
                            <label class="mx-2 font-semibold">Transfer Out : </label>
                            <div>
                                <JBadge
                                    v-for="(statusCount, index) in record.data.transferOutStatusCounts"
                                    :key="index"
                                    class="mb-1 xl:mb-2 2xl:mb-0 cursor-pointer"
                                    :label="`${index} : ${statusCount.count}`"
                                    @click="statusChanges(statusCount.id, staticStockTransferType.transferOut)"
                                />
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
                </div>
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
                    @click="state.displayStockTransferFilter = !state.displayStockTransferFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :modal-show="state.displayStockTransferItemsModal"
        :records="state.stockTransferItems"
        :totals="state.stockTransferItemsTotals"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        title="Stock Transfer Items"
        @close-modal="closeModal"
    >
        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>

        <template #quantity="data">
            {{ data.item.quantity }}
            <br>
            {{ data.item.derivative }}
        </template>

        <template #received_quantity="data">
            <span v-if="data.item.received_quantity">
                {{ data.item.received_quantity }}
                <br>
                {{ data.item.derivative }}
            </span>
        </template>

        <template #discrepancy_proof="record">
            <div
                v-if="record.item.discrepancy_proof"
                class="col-span-5 md:col-span-2 relative image-fit cursor-pointer w-20"
            >
                <div v-if="record.item.mime_type === mimeTypes.videoMp4 || record.item.mime_type === mimeTypes.videoMpeg || record.item.mime_type === mimeTypes.videoQuickTime">
                    <Tippy
                        content="Preview"
                        class="cursor-pointer flex justify-center"
                        @click="openVideoPlayModal(record.item.discrepancy_proof)"
                    >
                        <PlayCircle class="text-indigo-900" />
                    </Tippy>
                </div>

                <div v-else>
                    <img
                        :src="record.item.discrepancy_proof"
                        :alt="record.item.discrepancy_proof"
                        width="80"
                    >
                </div>

                <Tippy
                    tag="a"
                    content="Download the image"
                    class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-primary right-0 top-0 -mr-2 -mt-2"
                    :href="record.item.discrepancy_proof"
                    download
                >
                    <Download class="w-4 h-4" />
                </Tippy>
            </div>

            <div v-else>
                N/A
            </div>
        </template>

        <template #remarks="record">
            <span
                class="flex"
            >
                <Tippy
                    v-if="record.item.remarks.length"
                    :content="record.item.remarks"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="18"
                    />
                </Tippy>
            </span>
        </template>

        <template #product_name="record">
            <span
                v-if="record.item.is_extra_item"
                class="flex"
            >
                <Tippy
                    content="This is an additionally received item which wasn't ordered initially."
                    class="flex"
                >
                    {{ record.item.product_name }}
                    <Info class="ml-3 text-red-400" />
                </Tippy>
            </span>
        </template>
    </SelectedProducts>

    <StockTransferReceivedDate
        v-if="state.displayReceivedDateModal"
        :modal-show="state.displayReceivedDateModal"
        :stock-transfer-id="state.stockTransferId"
        :route-url="stockTransferUpdateReceivedDateStatusUrl"
        @close-modal="closeReceivedDateModal"
    />

    <StockTransferCancelRemarks
        v-if="state.displayCancelRemarksModal"
        :modal-show="state.displayCancelRemarksModal"
        :stock-transfer-id="state.stockTransferId"
        :status-id="state.statusId"
        :header-message="statuses.cancelled === state.statusId ? 'Cancel Remarks' : 'Reject Remarks'"
        :route-url="stockTransferUpdateStatusUrl"
        @close-modal="closeCancelRemarksModal"
    />

    <StockTransferShipped
        v-if="state.displayTransitModal"
        :modal-show="state.displayTransitModal"
        :stock-transfer-id="state.stockTransferId"
        :shipped-types="shippedTypes"
        :shipped-transit="shippedTransit"
        :filtered-stores="state.shippedFilteredStores"
        :filtered-warehouses="state.shippedFilteredWarehouses"
        :status-id="state.statusId"
        :route-url="stockTransferShippedOrTransitUrl"
        @close-modal="closeTransitModal"
    />

    <VideoPlay
        v-if="state.displayVideoPlayModal"
        :modal-show="state.displayVideoPlayModal"
        :video-url="state.videoUrl"
        @close-modal="closeVideoOrImageModal"
    />
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JTable from '@commonComponents/JTable.vue';
import JTabs from '@commonComponents/JTabs.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import StockTransferCancelRemarks from '@commonComponents/StockTransferCancelRemarks.vue';
import StockTransferReceivedDate from '@commonComponents/StockTransferReceivedDate.vue';
import StockTransferShipped from '@commonComponents/StockTransferShipped.vue';
import VideoPlay from '@commonComponents/VideoPlay.vue';
import { exportRecords, objectArrayToString, printReport, getStockTransferShipmentProgress } from '@commonServices/helper';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { TabPanel } from '@commonVendor/tab';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, CheckSquare, Download, FileText, GitMerge, Info, List, MoreHorizontal, PlayCircle, Printer, Truck, X } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    staticTitleStatuses: {
        type: Object,
        required: true,
    },
    transferTypes: {
        type: Object,
        required: true,
    },
    stockTransferTypes: {
        type: Object,
        required: true,
    },
    status: {
        type: Array,
        required: true,
    },
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    stockTransferNumber: {
        type: String,
        default: '',
    },
    stockTransferId: {
        type: String,
        default: '',
    },
    staticStockTransferType: {
        type: Object,
        required: true,
    },
    parametersLocationType: {
        type: Number,
        required: true,
    },
    parametersLocationId: {
        type: [String, Number],
        required: true,
    },
    parametersTransferType: {
        type: [String, Number],
        default: null,
    },
    parametersSelectStatus: {
        type: [String, Number],
        default: null,
    },
    fetchStockTransferItemsUrl: {
        type: String,
        required: true,
    },
    printStockTransferUrl: {
        type: String,
        required: true,
    },
    stockTransferCreateUrl: {
        type: String,
        required: true,
    },
    stockTransferEditUrl: {
        type: String,
        required: true,
    },
    stockTransferEditRequestOrderUrl: {
        type: String,
        required: true,
    },
    stockTransferDeliveryNoteUrl: {
        type: String,
        required: true,
    },
    stockTransferShippingDetailsUrl: {
        type: String,
        required: true,
    },
    stockTransferUpdateStatusUrl: {
        type: String,
        required: true,
    },
    stockTransferDiscrepancyUrl: {
        type: String,
        required: true,
    },
    stockTransferUpdateReceivedDateStatusUrl: {
        type: String,
        required: true,
    },
    stockTransferFetchUrl: {
        type: String,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    redirectUrl: {
        type: String,
        required: true,
    },
    stockTransferShippedOrTransitUrl: {
        type: String,
        required: true,
    },
    shippedTypes: {
        type: Object,
        required: true,
    },
    shippedTransit: {
        type: Number,
        required: true,
    },
    shippedDirect: {
        type: Number,
        required: true,
    },
    mimeTypes: {
        type: Object,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const state = reactive({
    displayStockTransferItemsModal: false,
    stockTransferId: null,
    statusId: null,
    stockTransferItems: [],
    stockTransferItemsTotals: [],
    locationTypes: props.locationTypes,
    displayVideoPlayModal: false,
    videoUrl: null,

    columns: [
        {
            key: 'created_at',
            label: 'Date',
            sortable: true,
        }, {
            key: 'transfer_numbers',
            label: 'Transfer Number',
        }, {
            key: 'transfer_type_details',
            label: 'Transfer Type',
        }, {
            key: 'from',
        }, {
            key: 'to',
        }, {
            key: 'status',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'items',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'reference_number',
            sortable: true
        }, {
            key: 'actions',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    stockTransferItemsFields: [
        {
            key: 'id',
            counter: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'product_name',
            label: 'Name',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'product_upc',
            label: 'UPC',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'product_color',
            label: 'Color',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'product_size',
            label: 'Size',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'product_variant_values',
            label: 'Attributes',
        }, {
            key: 'quantity',
            label: 'Requested Quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'received_quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'discrepancy_proof',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'remarks',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }
    ],

    refreshTableData: Math.random(),
    locationId: props.parametersLocationId,
    displayStockTransferFilter: false,
    displayReceivedDateModal: false,
    displayCancelRemarksModal: false,
    displayTransitModal: false,
    shippedFilteredStores: null,
    shippedFilteredWarehouses: null,
    typeId: props.parametersLocationType,
    parameters: {
        stock_transfer_date: null,
        location_id: props.parametersLocationId,
        transfer_type: !props.dashboardFilterData.is_from_stock_overview ? props.parametersTransferType : null,
        select_status: props.parametersSelectStatus,
        stock_transfer_number: props.stockTransferNumber,
        stock_transfer_id: props.stockTransferId,
    },
    dynamicColumns: [],
});
const openStockTransferItemsModal = (data) => {
    state.stockTransferItems = [];
    axios.get(route(props.fetchStockTransferItemsUrl, data.id))
        .then((response) => {
            state.stockTransferItems = response.data.stock_transfer_items;
            state.displayStockTransferItemsModal = true;
        });
    state.stockTransferItemsTotals = data.totals;
    state.stockTransferId = data.id;
};

const closeModal = () => {
    state.displayStockTransferItemsModal = false;
};

const openVideoPlayModal = (data) => {
    state.displayVideoPlayModal = true;
    state.videoUrl = data;
};

const closeVideoOrImageModal = () => {
    state.displayVideoPlayModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-stock-transfer-items/' + state.stockTransferId + '/',
        'stock-transfer-items.csv',
        params,
        props.exportPermission
    );
};

const printStockTransfer = (stockTransferId, transferType) => {
    printReport(route(props.printStockTransferUrl, [stockTransferId, transferType]), props.exportPermission);
};

const redirectToAddStockTransfer = (transferType) => {
    router.get(route(props.stockTransferCreateUrl, transferType));
};

const edit = (stockTransferId, dismiss) => {
    router.get(route(props.stockTransferEditUrl, stockTransferId));
    dismiss();
};

const editRequestOrderByDestination = (stockTransferId, dismiss) => {
    router.get(route(props.stockTransferEditRequestOrderUrl, stockTransferId));
    dismiss();
};

const deliveryNotes = (stockTransferId, dismiss) => {
    router.get(route(props.stockTransferDeliveryNoteUrl, stockTransferId));
    dismiss();
};

const updateStatus = (stockTransferId, statusId, dismiss) => {
    dismiss();
    let message = 'You cannot edit the stock transfer once it is opened. Are you sure you want to open the stock transfer?';

    if (statusId === props.statuses.received) {
        message = 'Are you sure you want to mark the stock transfer as received?';
        confirmDialogBox(message, () => {
            state.stockTransferId = stockTransferId;
            state.displayReceivedDateModal = true;
            dismiss();
        });
        return;
    } else if (statusId === props.statuses.shipped) {
        message = 'Are you sure you want to mark the stock transfer as shipped?';
    } else if (statusId === props.statuses.approved) {
        message = 'Are you sure you want to mark the stock transfer as approved?';
    } else if (statusId === props.statuses.transit_in) {
        message = 'Are you sure you want to mark the stock transfer as transit IN?';
    } else if (statusId === props.statuses.transit_out) {
        message = 'Are you sure you want to mark the stock transfer as transit OUT?';
    }

    const timeoutDuration = 1000;

    confirmDialogBox(message, () => {
        router.post(route(props.stockTransferUpdateStatusUrl, stockTransferId), {
            status_id: statusId
        }, {
            onSuccess: () => setTimeout(() => {
                dismiss();
                refreshTable();
            }, timeoutDuration)
        });
    });
};

const closeReceivedDateModal = () => {
    state.displayReceivedDateModal = false;
    refreshTable();
};

const closeCancelRemarksModal = () => {
    state.displayCancelRemarksModal = false;
    state.statusId = null;
    refreshTable();
};

const statusChanges = (status, transferType) => {
    state.parameters.select_status = status;
    state.parameters.transfer_type = transferType;
    refreshTable();
};

const cancelStatus = (stockTransferId, statusId) => {
    confirmDialogBox('Are you sure you want to cancel the Stock Transfer?', () => {
        state.stockTransferId = stockTransferId;
        state.statusId = statusId;
        state.displayCancelRemarksModal = true;
    });
};

const rejectStatus = (stockTransferId, statusId) => {
    confirmDialogBox('Are you sure you want to reject the Stock Transfer?', () => {
        state.stockTransferId = stockTransferId;
        state.statusId = statusId;
        state.displayCancelRemarksModal = true;
    });
};

const refreshTable = () => {
    state.totalActionMenuItems = 0;
    state.refreshTableData = Math.random();
};
const updateLocationType = (locationType) => {
    state.typeId = locationType;
    state.parameters.location_id = null;
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = parseInt(locationId);
    refreshTable();
};
const updateSelectedStatus = (status) => {
    state.parameters.select_status = status;
    refreshTable();
};

const clearAll = () => {
    state.parameters.location_id = props.parametersLocationId;
    state.parameters.stock_transfer_date = null;
    state.typeId = props.parametersLocationType;
    state.parameters.select_status = null;
    state.parameters.transfer_type = null;
    state.parameters.stock_transfer_number = null;
    refreshTable();
};
const updateTransferDate = (selectedDate) => {
    state.parameters.stock_transfer_date = selectedDate;
    refreshTable();
};

const updateTransferType = (transferType) => {
    state.parameters.transfer_type = parseInt(transferType);
    refreshTable();
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-stock-transfer-items/' + state.stockTransferId + '/',
        'stock-transfer-items.xlsx',
        params,
        props.exportPermission
    );
};

const markAsApproved = (stockTransferId, dismiss) => {
    router.get(route(props.stockTransferShippingDetailsUrl, stockTransferId));
    dismiss();
};

const closeDiscrepancy = (stockTransferId, dismiss) => {
    router.get(route(props.stockTransferDiscrepancyUrl, stockTransferId));
    dismiss();
};

const getStatusTimes = (times) => {
    let timing = '';
    for (const key in times) {
        if (times[key] === 'divider') {
            timing += '-------------------------' + '<br />';
            continue;
        }

        timing += key + ' : ' + times[key] + '<br />';
    }

    return timing;
};

const exportListPageCsvRecords = (params) => {
    params.dashboard_transfer_type = props.dashboardFilterData.transfer_type;

    return exportRecords(
        'export-stock-transfers/',
        'stock-transfers.csv',
        params,
        props.exportPermission
    );
};

const exportListPageExcelRecords = (params) => {
    params.dashboard_transfer_type = props.dashboardFilterData.transfer_type;

    return exportRecords(
        'export-stock-transfers/',
        'stock-transfers.xlsx',
        params,
        props.exportPermission
    );
};

const getStatusColor = (status) => {
    if (status === props.staticTitleStatuses.closed || status === props.staticTitleStatuses.approved) {
        return 'bg-green-200 text-green-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticTitleStatuses.cancelled || status === props.staticTitleStatuses.discrepancy || status === props.staticTitleStatuses.rejected) {
        return 'bg-red-200 text-red-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticTitleStatuses.open || status === props.staticTitleStatuses.draft || status === props.staticTitleStatuses.shipped) {
        return 'bg-yellow-200 text-yellow-800 text-center text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticTitleStatuses.transit || status === props.staticTitleStatuses.transit_in || status === props.staticTitleStatuses.transit_out) {
        return 'bg-orange-200 text-orange-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticTitleStatuses.received) {
        return 'bg-pink-200 text-pink-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
};

const getFilterTabStatus = () => {
    return state.displayStockTransferFilter === false && (state.parameters.transfer_type !== null || state.parameters.select_status !== null);
};

const getBadgeDisplay = (statusCounts, transferType) => {
    if (state.parameters.transfer_type !== null) {
        return state.parameters.transfer_type === transferType;
    }

    return statusCounts ? Object.keys(statusCounts).length > 0 : false;
};

const markAsShipped = (stockTransferId, dismiss, sourceLocationId, destinationLocationId) => {
    state.stockTransferId = stockTransferId;
    state.displayTransitModal = true;
    state.statusId = props.statuses.shipped;

    state.shippedFilteredStores = props.stores.filter((store) => {
        if (store.id === sourceLocationId) {
            return false;
        }

        if (store.id === destinationLocationId) {
            return false;
        }

        return true;
    });

    state.shippedFilteredWarehouses = props.warehouses.filter((warehouse) => {
        if (warehouse.id === sourceLocationId) {
            return false;
        }

        if (warehouse.id === destinationLocationId) {
            return false;
        }

        return true;
    });

    dismiss();
};

const closeTransitModal = () => {
    state.displayTransitModal = false;
    refreshTable();
};

const canTakeActionInDraft = (stockTransfer) => {
    if (
        (
            stockTransfer.status_id === props.statuses.draft ||
            stockTransfer.status_id === props.statuses.system_generated
        ) &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    if (
        (
            stockTransfer.status_id === props.statuses.draft ||
            stockTransfer.status_id === props.statuses.system_generated
        ) &&
        stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canTakeActionInOpenRequestOrder = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.open &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canTakeActionInOpenMode = (stockTransfer) => {
    if (canTakeActionInOpenRequestOrder(stockTransfer)) {
        return true;
    }

    if (
        stockTransfer.status_id === props.statuses.open &&
        stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canCancelTransfer = (stockTransfer) => {
    if (canTakeActionInDraft(stockTransfer)) {
        return true;
    }

    if (
        stockTransfer.status_id === props.statuses.open &&
        stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    if (
        stockTransfer.status_id === props.statuses.open &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    if (
        stockTransfer.status_id === props.statuses.shipped &&
        stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    if (
        stockTransfer.status_id === props.statuses.shipped &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canMarkAsShip = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.open &&
        stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    if (
        stockTransfer.status_id === props.statuses.approved &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canMarkAsTransitIn = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.transit &&
        stockTransfer.transit_location_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canRevertBackToDirect = (stockTransfer) => {
    if (stockTransfer.status_id === props.statuses.transit &&
        stockTransfer.transit_location_id !== null
    ) {
        return true;
    }

    return false;
};

const canMarkAsTransitOut = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.transit_in &&
        stockTransfer.transit_location_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const isShipped = (stockTransfer) => {
    if (
        (stockTransfer.status_id === props.statuses.shipped ||
            stockTransfer.status_id === props.statuses.transit_out
        ) &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const isReceived = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.received &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const canCloseTransfer = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.discrepancy &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    return false;
};

const getTransferOrderPrintType = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.draft ||
        stockTransfer.status_id === props.statuses.system_generated ||
        stockTransfer.status_id === props.statuses.open
    ) {
        if (
            stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
            stockTransfer.source_id === props.parametersLocationId
        ) {
            return 'OUT';
        }
    }

    if (
        stockTransfer.status_id === props.statuses.open ||
        stockTransfer.status_id === props.statuses.approved
    ) {
        if (
            stockTransfer.transfer_type === props.stockTransferTypes.transfer_order &&
            stockTransfer.destination_id === props.parametersLocationId
        ) {
            return 'IN';
        }
    }

    if (
        stockTransfer.status_id === props.statuses.rejected ||
        stockTransfer.status_id === props.statuses.cancelled
    ) {
        if (
            stockTransfer.source_id === props.parametersLocationId
        ) {
            return 'OUT';
        }

        if (
            stockTransfer.destination_id === props.parametersLocationId
        ) {
            return 'IN';
        }
    }

    return '';
};

const getRequestOrderPrintType = (stockTransfer) => {
    if (
        (
            stockTransfer.status_id === props.statuses.draft ||
            stockTransfer.status_id === props.statuses.system_generated ||
            stockTransfer.status_id === props.statuses.open
        ) &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order
    ) {
        if (
            stockTransfer.destination_id === props.parametersLocationId
        ) {
            return 'IN';
        }

        if (
            stockTransfer.status_id === props.statuses.open &&
            stockTransfer.source_id === props.parametersLocationId
        ) {
            return 'OUT';
        }
    }

    return '';
};

const canPrintTransferOut = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.approved &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.source_id === props.parametersLocationId
    ) {
        return true;
    }

    if (stockTransfer.status_id === props.statuses.transit_out || stockTransfer.status_id === props.statuses.transit) {
        return true;
    }

    if (
        stockTransfer.source_id === props.parametersLocationId
    ) {
        if (stockTransfer.status_id === props.statuses.shipped) {
            return true;
        }

        if (stockTransfer.status_id === props.statuses.received) {
            return true;
        }

        if (stockTransfer.status_id === props.statuses.discrepancy) {
            return true;
        }

        if (stockTransfer.status_id === props.statuses.closed) {
            return true;
        }
    }

    return false;
};

const canPrintTransferIn = (stockTransfer) => {
    if (
        stockTransfer.status_id === props.statuses.approved &&
        stockTransfer.transfer_type === props.stockTransferTypes.request_order &&
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        return true;
    }

    if (stockTransfer.status_id === props.statuses.transit_in) {
        return true;
    }

    if (
        stockTransfer.destination_id === props.parametersLocationId
    ) {
        if (stockTransfer.status_id === props.statuses.shipped) {
            return true;
        }

        if (stockTransfer.status_id === props.statuses.received) {
            return true;
        }

        if (stockTransfer.status_id === props.statuses.discrepancy) {
            return true;
        }

        if (stockTransfer.status_id === props.statuses.closed) {
            return true;
        }
    }

    return false;
};

const hasActionMenu = (stockTransfer) => {
    return canTakeActionInDraft(stockTransfer) ||
        canTakeActionInOpenRequestOrder(stockTransfer) ||
        canCancelTransfer(stockTransfer) ||
        canTakeActionInOpenMode(stockTransfer) ||
        canMarkAsShip(stockTransfer) ||
        isShipped(stockTransfer) ||
        isReceived(stockTransfer) ||
        canCloseTransfer(stockTransfer) ||
        getTransferOrderPrintType(stockTransfer) !== '' ||
        getRequestOrderPrintType(stockTransfer) !== '' ||
        canPrintTransferOut(stockTransfer) ||
        canPrintTransferIn(stockTransfer) ||
        canMarkAsTransitIn(stockTransfer) ||
        canRevertBackToDirect(stockTransfer) ||
        canMarkAsTransitOut(stockTransfer);
};

const revertBackToDirect = (stockTransferId, dismiss) => {
    dismiss();

    const message = 'Are you sure you want to shipped as direct?';
    const data = {
        shipped_type: props.shippedDirect,
    };

    confirmDialogBox(message, () => {
        const httpStatusOk = 200;
        axios.post(route(props.stockTransferShippedOrTransitUrl, stockTransferId), data)
            .then((response) => {
                if (response.status === httpStatusOk) {
                    refreshTable();
                }
            }).catch((error) => {
                if (error.response.data.message) {
                    showErrorNotification(error.response.data.message);
                }
            });
    });
};

const selectStockTransferNumber = (stockTransferNumber) => {
    state.parameters.stock_transfer_number = stockTransferNumber;
    refreshTable();
};

const refreshPage = () => {
    router.get(props.redirectUrl);
};

const getFilteredColumns = () => {
    const columns = state.stockTransferItemsFields || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['product_color', 'product_size'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'product_variant_values');
};

onMounted(() => {
    if (props.stockTransferNumber) {
        state.isClear = true;
        state.displayStockTransferFilter = true;
        refreshTable();
    }
    state.dynamicColumns = getFilteredColumns();
});
</script>
