
<template>
    <PageTitle title="Sell Through Report" />

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex justify-between items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        Sell Through Report
                    </h2>

                    <h2 class="font-medium text-base">
                        <div class="flex">
                            <Tippy
                                content="Refresh Data"
                                class="btn btn-outline-primary"
                            >
                                <button
                                    :disabled="state.disableRefreshButton"
                                    class="transition-opacity duration-200 ease-in-out"
                                    :class="{'opacity-50 cursor-not-allowed': state.disableRefreshButton}"
                                    @click="syncData"
                                >
                                    <RefreshCw class="text-primary w-5" />
                                </button>
                            </Tippy>

                            <p class="ml-2 text-xs">
                                <span class="text-sm font-medium">Last Update:</span><br>{{ aggregateProcessTracker.date }}
                            </p>
                        </div>
                    </h2>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4 border-b pb-5">
                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <div class="flex">
                                <div class="w-full">
                                    <FormSelectBox
                                        :selected-record="state.parameters.main_report_type"
                                        :records="sellThroughMainReportTypes"
                                        :required="true"
                                        input-label="Main Types"
                                        label-class="block font-medium text-base text-primary-p3 mb-2"
                                        @update:selected-record="updateMainReportId"
                                    />
                                </div>

                                <div class="px-3 mt-2 sm:mt-2 md:mt-8">
                                    <OutlinePrimaryButton
                                        type="button"
                                        text="Customized"
                                        class="w-24 mt-3"
                                        @click="displayCustomizedFilter"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-4 xl:col-span-4"
                        >
                            <div>
                                <div class="w-full">
                                    <JMultiSelect
                                        :selected-records="state.locations"
                                        :records="stores"
                                        :required="true"
                                        input-label="Please Select Location"
                                        label-class="block font-medium text-base text-primary-p3 mb-2"
                                        @update:selected-records="updateStores"
                                    />
                                </div>

                                <div>
                                    <OutlinePrimaryButton
                                        type="button"
                                        text="Select all"
                                        class="w-24 mt-3 mr-3"
                                        @click="selectAllLocations"
                                    />

                                    <OutlineDangerButton
                                        v-if="state.locations.length > 0"
                                        type="button"
                                        text="Clear All"
                                        class="w-24 mt-3"
                                        @click="clearAllLocations"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="(state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation && state.parameters.location_ids.length > 0) || state.parameters.main_report_type === staticSellThroughMainReportTypes.byCompany"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-2 xl:col-span-2"
                        >
                            <FormSelectBox
                                :selected-record="state.parameters.report_type"
                                :records="sellThroughTypes"
                                :required="true"
                                input-label="Report Types"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:selected-record="updateReportId"
                            />
                        </div>
                        <div
                            v-if="(state.parameters.report_type === staticSellThroughTypes.byAttributes || state.parameters.report_type === staticSellThroughTypes.summary) && pageProps.product_variant"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-2 xl:col-span-2"
                        >
                            <FormSelectBox
                                :selected-record="state.parameters.attribute_type"
                                :records="attributes"
                                :required="true"
                                input-label="Attribute Types"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:selected-record="updateAttributeId"
                            />
                        </div>

                        <div
                            v-if="(state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation && state.parameters.location_ids.length > 0) || state.parameters.main_report_type === staticSellThroughMainReportTypes.byCompany"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3 mt-3"
                        >
                            <div class="form-check">
                                <label
                                    v-for="(dateType, index) in sellThroughDateTypes"
                                    :key="'print-size-' + index"
                                    class="form-check-label mr-3 ml-0"
                                >
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        :checked="isDateTypeSelected(dateType.id)"
                                        @input="updateDateType(dateType.id)"
                                    >
                                    {{ dateType.name }}
                                </label>
                            </div>

                            <JDatePicker
                                v-if="state.parameters.select_date_type === staticSellThroughDateTypes.accumulated"
                                v-model:input-value="state.parameters.date"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:input-value="updateDate()"
                            />

                            <JDatePicker
                                v-if="state.parameters.select_date_type === staticSellThroughDateTypes.customized"
                                :range-picker="true"
                                :input-value="state.parameters.date_range"
                                :max-date="new Date()"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:input-value="updateDate($event)"
                            />
                        </div>
                    </div>

                    <div
                        v-if="state.displayProductFilter"
                        class="grid grid-cols-12 gap-0 sm:gap-6 mb-4 pb-5 border-b"
                    >
                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormSelectBox
                                :selected-record="state.parameters.filter_by"
                                :records="sellThroughFilterTypes"
                                input-label="Filter By"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:selected-record="updateFilterBy"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                input-label="Products"
                                :selected-record="state.selectedProduct"
                                :search-records="searchProducts"
                                placeholder="Product Name/UPC to search..."
                                @update:selected-record="selectProduct"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormSelectBox
                                :selected-record="state.parameters.product_collection_id"
                                :records="productCollections"
                                placeholder="Please select Product Collection"
                                input-label="Product Collection"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:selected-record="updateProductCollectionId"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                input-label="Categories"
                                :selected-record="state.selectedCategory"
                                :search-records="searchCategory"
                                placeholder="Please type the name of the category to search."
                                @update:selected-record="selectCategory"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                input-label="Brands"
                                :selected-record="state.selectedBrand"
                                :search-records="searchBrand"
                                placeholder="Please type the name of the brand to search."
                                @update:selected-record="selectBrand"
                            />
                        </div>

                        <template
                            v-if="state.parameters.report_type && pageProps.product_variant"
                        >
                            <AttributesFilters 
                                :attributes="attributes"
                                :custom-class="'input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3'"
                                @update-params="updateParams($event, params)"
                            />
                        </template>

                        <div
                            v-if="state.parameters.report_type && !pageProps.product_variant"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                input-label="Sizes"
                                :selected-record="state.selectedSize"
                                :search-records="searchSize"
                                placeholder="Please type the name of the size to search."
                                @update:selected-record="selectSize"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type && !pageProps.product_variant"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                input-label="Colors"
                                :selected-record="state.selectedColors"
                                :search-records="searchColor"
                                :multi-select="true"
                                placeholder="Please type the name of the color to search."
                                @update:selected-record="selectColors"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                :selected-record="state.selectedDepartments"
                                :search-records="searchDepartment"
                                :multi-select="true"
                                input-label="Department"
                                placeholder="Please type the name of the department to search."
                                @update:selected-record="selectDepartments"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                :selected-record="state.selectedArticleNumber"
                                :search-records="searchArticleNumber"
                                :multi-select="true"
                                track-by="article_number"
                                label="article_number"
                                input-label="Article Number"
                                placeholder="Please type the article number of the product to search."
                                @update:selected-record="selectArticleNumbers"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type && !pageProps.product_variant"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                :selected-record="state.selectedStyles"
                                :search-records="searchStyle"
                                :multi-select="true"
                                input-label="Styles"
                                placeholder="Please type the name of the style to search."
                                @update:selected-record="selectStyles"
                            />
                        </div>

                        <div
                            v-if="state.parameters.report_type"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <div>
                                <FormAjaxSelect
                                    :selected-record="state.selectedTags"
                                    :search-records="searchTag"
                                    :multi-select="true"
                                    input-label="Tags"
                                    placeholder="Please type the name of the tag to search."
                                    @update:selected-record="selectTags"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="state.displayCustomizedFilter"
                        id="inclusions-for-stock-received"
                        class="input-form col-span-12"
                    >
                        <div>
                            <InclusionsForStockReceived
                                :stores="stores"
                                :warehouses="warehouses"
                                :static-location-types="staticLocationTypes"
                                :location-types="locationTypes"
                                :sell-through-include-types="sellThroughIncludeTypes"
                                :selected-sell-through-include-types="state.parameters.include_by"
                                :static-accumulated-sell-through-include-types="staticSellThroughIncludeTypes"
                                @update-accumulated-sell-through-include-types="updateAccumulatedSaleThroughIncludeTypes"
                                @includes-by-goods-receive-note-in-location-ids="includesByGoodsReceiveNoteInLocationIds"
                                @clear-data-for-goods-received-note-in="clearDataForGoodsReceivedNoteIn"
                                @includes-by-goods-receive-note-out-location-ids="includesByGoodsReceiveNoteOutLocationIds"
                                @clear-data-for-goods-received-note-out="clearDataForGoodsReceivedNoteOut"
                                @includes-by-stock-adjustment-in-location-ids="includesByStockAdjustmentInLocationIds"
                                @clear-data-for-stock-adjustment-in="clearDataForStockAdjustmentIn"
                                @includes-by-stock-adjustment-out-location-ids="includesByStockAdjustmentOutLocationIds"
                                @clear-data-for-stock-adjustment-out="clearDataForStockAdjustmentOut"
                                @includes-by-stock-transfer-in-location-ids="includesByStockTransferInLocationIds"
                                @clear-data-for-stock-transfer-in="clearDataForStockTransferIn"
                                @includes-by-stock-transfer-out-location-ids="includesByStockTransferOutLocationIds"
                                @clear-data-for-stock-transfer-out="clearDataForStockTransferOut"
                                @includes-by-delivery-order-in-location-ids="includesByDeliveryOrderInLocationIds"
                                @clear-data-for-delivery-order-in="clearDataForDeliveryOrderIn"
                                @includes-by-delivery-order-out-location-ids="includesByDeliveryOrderOutLocationIds"
                                @clear-data-for-delivery-order-out="clearDataForDeliveryOrderOut"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 mt-4">
                        <div
                            v-if="state.parameters.report_type && (state.parameters.report_type !== staticSellThroughTypes.byAttributes || state.parameters.report_type !== staticSellThroughTypes.summary || state.parameters.attribute_type)"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <div class="flex">
                                <OutlineDangerButton
                                    type="button"
                                    text="Clear Filter"
                                    class="shadow-md flex flex-col sm:flex-row mt-2 sm:mt-0 md:mt-0 mr-2 w-32"
                                    @click="clearParameters()"
                                />

                                <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                                    <OutlinePrimaryButton
                                        text="Filters"
                                        class="text-sm shadow-md mb-2 sm:mb-0"
                                        @click="updateDisplayProductFilter"
                                    />
                                </p>

                                <p
                                    v-if="!state.showReport && staticSellThroughTypes.summary !== state.parameters.report_type"
                                    class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
                                >
                                    <PrimaryButton
                                        text="View Report"
                                        class="text-sm shadow-md mb-2 sm:mb-0"
                                        @click="showViewReport"
                                    />
                                </p>

                                <BySummary
                                    v-if="staticSellThroughTypes.summary === state.parameters.report_type"
                                    :parameters="state.parameters"
                                    :refresh-table-data="state.refreshTableData"
                                    :export-permission="exportPermission"
                                    class="mt-2 sm:mt-0"
                                    :display-product-filter="state.displayProductFilter"
                                    :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
                                    @update:display-product-filter="updateDisplayProductFilter"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <BySize
        v-if="staticSellThroughTypes.sizes === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :export-permission="exportPermission"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByColor
        v-if="staticSellThroughTypes.colors === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByStyle
        v-if="staticSellThroughTypes.styles === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByArticleNumber
        v-if="staticSellThroughTypes.byMasterProduct === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByUpc
        v-if="staticSellThroughTypes.byUpc === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByLocation
        v-if="staticSellThroughTypes.locations === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByDepartment
        v-if="staticSellThroughTypes.departments === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByBrand
        v-if="staticSellThroughTypes.brands === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByCategory
        v-if="staticSellThroughTypes.categories === state.parameters.report_type && state.abortControllers[state.parameters.report_type] && state.showReport"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :export-permission="exportPermission"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />

    <ByAttributes
        v-if="(staticSellThroughTypes.byAttributes === state.parameters.report_type && state.parameters.attribute_type) && state.abortControllers[state.parameters.report_type] && state.showReport"
        :export-permission="exportPermission"
        :parameters="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :token-controller="state.abortControllers[state.parameters.report_type]"
        class="z-0 relative"
        :display-product-filter="state.displayProductFilter"
        :is-location-compulsory-selection="state.parameters.main_report_type === staticSellThroughMainReportTypes.byLocation"
        @update:display-product-filter="updateDisplayProductFilter"
    />
</template>

<script setup>
import ByArticleNumber from '@adminPages/reports/sell_through_aggregate_reports/partial/ByArticleNumber.vue';
import ByBrand from '@adminPages/reports/sell_through_aggregate_reports/partial/ByBrand.vue';
import ByCategory from '@adminPages/reports/sell_through_aggregate_reports/partial/ByCategory.vue';
import ByColor from '@adminPages/reports/sell_through_aggregate_reports/partial/ByColor.vue';
import ByDepartment from '@adminPages/reports/sell_through_aggregate_reports/partial/ByDepartment.vue';
import BySize from '@adminPages/reports/sell_through_aggregate_reports/partial/BySize.vue';
import ByLocation from '@adminPages/reports/sell_through_aggregate_reports/partial/ByLocation.vue';
import ByStyle from '@adminPages/reports/sell_through_aggregate_reports/partial/ByStyle.vue';
import BySummary from '@adminPages/reports/sell_through_aggregate_reports/partial/BySummary.vue';
import ByUpc from '@adminPages/reports/sell_through_aggregate_reports/partial/ByUpc.vue';
import ByAttributes from '@adminPages/reports/sell_through_aggregate_reports/partial/ByAttributes.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import InclusionsForStockReceived from '@commonPages/InclusionsForStockReceived.vue';
import { currentDate, sellThroughReportFilterValidationCheck } from '@commonServices/helper';
import axios from 'axios';
import { onMounted, reactive, computed } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { RefreshCw } from 'lucide-vue-next';
import { showSuccessNotification } from '@commonServices/notifier';
import { usePage } from '@inertiajs/vue3';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stores: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: Object,
        required: true,
    },
    sellThroughTypes: {
        type: Array,
        required: true,
    },
    sellThroughFilterTypes: {
        type: Array,
        required: true,
    },
    staticSellThroughFilterTypes: {
        type: Object,
        required: true,
    },
    staticSellThroughTypes: {
        type: Object,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    sellThroughIncludeTypes: {
        type: Array,
        required: true,
    },
    staticSellThroughIncludeTypes: {
        type: Object,
        required: true,
    },
    sellThroughMainReportTypes: {
        type: Array,
        required: true,
    },
    staticSellThroughMainReportTypes: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Array,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
    sellThroughDateTypes: {
        type: Array,
        required: true,
    },
    staticSellThroughDateTypes: {
        type: Object,
        required: true,
    },
    aggregateProcessTracker: {
        type: Object,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    parameters: {
        main_report_type: props.dashboardFilterData.main_report_type,
        filter_by: props.staticSellThroughFilterTypes.all,
        report_type: props.dashboardFilterData.report_type,
        attribute_type: props.dashboardFilterData.attribute_type,
        select_date_type: props.dashboardFilterData.select_date_type,
        date: currentDate(),
        date_range: [],
        product_id: null,
        product_collection_id: null,
        category_id: null,
        brand_id: null,
        size_id: null,
        color_ids: null,
        department_ids: null,
        article_numbers: props.dashboardFilterData.article_number !== null ? [props.dashboardFilterData.article_number] : [],
        tag_ids: null,
        style_ids: null,
        sort_by: props.dashboardFilterData.sort_by,
        sort_direction: props.dashboardFilterData.sort_direction,
        location_ids: [props.dashboardFilterData.location_id],
        include_by: props.dashboardFilterData.default_include_type,
        includes_by_goods_receive_note_in_location_ids: [],
        includes_by_goods_receive_note_out_location_ids: [],
        includes_by_stock_adjustment_in_location_ids: [],
        includes_by_stock_adjustment_out_location_ids: [],
        includes_by_stock_transfer_in_location_ids: [],
        includes_by_stock_transfer_out_location_ids: [],
        includes_by_delivery_order_in_location_ids: [],
        includes_by_delivery_order_out_location_ids: [],
        attributes: null,
    },
    selectedProduct: null,
    selectedCategory: null,
    selectedBrand: null,
    selectedSize: null,
    selectedColors: null,
    selectedDepartments: null,
    selectedArticleNumber: props.dashboardFilterData.selectedArticleNumbers,
    selectedTags: null,
    locations: [],
    selectedStyles: null,
    sellThroughIncludeTypes: [],
    type_id: props.staticLocationTypes.store,

    includesByGoodsReceiveNoteInLocationIds: [],
    includesByGoodsReceiveNoteOutLocationIds: [],
    includesByStockAdjustmentInLocationIds: [],
    includesByStockAdjustmentOutLocationIds: [],
    includesByStockTransferInLocationIds: [],
    includesByStockTransferOutLocationIds: [],
    includesByDeliveryOrderInLocationIds: [],
    includesByDeliveryOrderOutLocationIds: [],

    displayProductFilter: false,
    displayCustomizedFilter: false,
    disableRefreshButton: false,

    refreshTableData: Math.random(),
    abortControllers: {},
    showReport: false,
});

const updateReportId = (reportType) => {
    state.showReport = false;
    state.parameters.report_type = reportType === null ? null : parseInt(reportType);
    state.parameters.attribute_type = null;
    refreshTable();
};

const updateAttributeId = (attributeType) => {
    state.showReport = false;
    state.parameters.attribute_type = attributeType === null ? null : parseInt(attributeType);
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const refreshTable = () => {
    abortTheRequest();
    state.refreshTableData = Math.random();
    if (!sellThroughReportFilterValidationCheck(state.parameters, state.isLocationCompulsorySelection)) {
        state.showReport = false;
    }
};

const clearParameters = () => {
    state.parameters.filter_by = props.staticSellThroughFilterTypes.all;
    state.parameters.report_type = null;
    state.date = currentDate();
    state.parameters.product_id = null;
    state.parameters.category_id = null;
    state.parameters.brand_id = null;
    state.parameters.size_id = null;
    state.parameters.color_ids = null;
    state.parameters.department_ids = null;
    state.parameters.tag_ids = null;
    state.parameters.article_numbers = null;
    state.parameters.style_ids = null;
    state.selectedProduct = null;
    state.selectedCategory = null;
    state.selectedBrand = null;
    state.selectedSize = null;
    state.selectedColors = null;
    state.selectedDepartments = null;
    state.selectedArticleNumber = null;
    state.selectedStyles = null;
    state.selectedTags = null;
    state.locations = null;
    state.parameters.product_collection_id = null;
    state.sellThroughIncludeTypes = [
        props.staticSellThroughIncludeTypes.goodsReceiveNoteIn,
        props.staticSellThroughIncludeTypes.goodsReceiveNoteOut,
        props.staticSellThroughIncludeTypes.stockAdjustmentIn,
        props.staticSellThroughIncludeTypes.stockAdjustmentOut
    ];
    state.parameters.attributes = null;
    refreshTable();
};

const searchProducts = (searchText, componentState) => {
    axios.get(route('admin.get_filtered_inventory_products'), {
        params: {
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const selectProduct = (selectedProduct) => {
    state.selectedProduct = selectedProduct;
    state.parameters.product_id = selectedProduct !== null ? selectedProduct.id : null;
    refreshTable();
};

const searchCategory = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('admin.categories.get_filtered_categories'), filterData).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const selectCategory = (selectedCategory) => {
    state.selectedCategory = selectedCategory;
    state.parameters.category_id = selectedCategory !== null ? selectedCategory.id : null;
    refreshTable();
};

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('admin.brands.get_filtered_brands'), filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const selectBrand = (selectedBrand) => {
    state.selectedBrand = selectedBrand;
    state.parameters.brand_id = null;
    if (selectedBrand !== null) {
        state.parameters.brand_id = selectedBrand.id;
    }
    refreshTable();
};

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.sizes.get_filtered_sizes'), filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const selectSize = (selectedSizes) => {
    state.selectedSize = selectedSizes;
    state.parameters.size_id = null;
    if (selectedSizes !== null) {
        state.parameters.size_id = selectedSizes.id;
    }
    refreshTable();
};

const searchColor = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.colors.get_filtered_colors'), filterData).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};

const selectColors = (selectedColors) => {
    state.selectedColors = selectedColors;

    if (selectedColors !== null) {
        state.parameters.color_ids = selectedColors.map(function (color) {
            return color.id;
        });
    } else {
        state.parameters.color_ids = null;
    }

    refreshTable();
};

const updateParams = (params) => {
    state.parameters.attributes = params;
    refreshTable();
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.departments.get_filtered_departments'), filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    } else {
        state.parameters.department_ids = null;
    }
    refreshTable();
};

const minSearchLength = 3;

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    if (selectedNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    } else {
        state.parameters.article_numbers = [];
    }
    refreshTable();
};

const searchStyle = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.styles.get_filtered_styles'), filterData).then((response) => {
            componentState.records = response.data.styles;
            componentState.isLoading = false;
        });
    }
};

const selectStyles = (selectedStyles) => {
    state.selectedStyles = selectedStyles;
    if (selectedStyles !== null) {
        state.parameters.style_ids = selectedStyles.map(function (selectedStyle) {
            return selectedStyle.id;
        });
    } else {
        state.parameters.style_ids = null;
    }
    refreshTable();
};

const searchTag = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.tags.get_filtered_tags'), filterData).then((response) => {
        componentState.records = response.data.tags;
        componentState.isLoading = false;
    });
};

const selectTags = (selectedTags) => {
    state.selectedTags = selectedTags;
    if (selectedTags !== null) {
        state.parameters.tag_ids = selectedTags.map(function (tag) {
            return tag.id;
        });
    } else {
        state.parameters.style_ids = null;
    }
    refreshTable();
};

const updateAccumulatedSaleThroughIncludeTypes = (sellThroughIncludeTypes) => {
    state.parameters.include_by = sellThroughIncludeTypes;
    refreshTable();
};

const updateStores = (stores) => {
    state.locations = stores;
    state.parameters.location_ids = stores.map(function (store) {
        return store.id;
    });

    refreshTable();
};

const updateFilterBy = (selectedFilterBy) => {
    state.parameters.filter_by = selectedFilterBy === null ? null : parseInt(selectedFilterBy);
    refreshTable();
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);

const includesByGoodsReceiveNoteInLocationIds = (locationIds) => {
    state.parameters.includes_by_goods_receive_note_in_location_ids = locationIds;

    refreshTable();
};

const includesByGoodsReceiveNoteOutLocationIds = (locationIds) => {
    state.parameters.includes_by_goods_receive_note_out_location_ids = locationIds;

    refreshTable();
};

const includesByStockAdjustmentInLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_adjustment_in_location_ids = locationIds;

    refreshTable();
};

const includesByDeliveryOrderInLocationIds = (locationIds) => {
    state.parameters.includes_by_delivery_order_in_location_ids = locationIds;

    refreshTable();
};

const includesByStockTransferInLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_transfer_in_location_ids = locationIds;

    refreshTable();
};

const includesByStockAdjustmentOutLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_adjustment_out_location_ids = locationIds;

    refreshTable();
};

const includesByDeliveryOrderOutLocationIds = (locationIds) => {
    state.parameters.includes_by_delivery_order_out_location_ids = locationIds;

    refreshTable();
};

const includesByStockTransferOutLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_transfer_out_location_ids = locationIds;

    refreshTable();
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

onMounted(() => {
    if (props.dashboardFilterData.location_id !== null) {
        state.locations = [props.stores.find(store => store.id === props.dashboardFilterData.location_id)];
        state.parameters.location_ids = [props.dashboardFilterData.location_id];

        refreshTable();
    }

    state.abortControllers = Object.values(props.staticSellThroughTypes).reduce((acc, value) => {
        acc[value] = new AbortController();
        return acc;
    }, {});

    if (props.staticSellThroughMainReportTypes.byLocation === props.dashboardFilterData.main_report_type && props.dashboardFilterData.store_id && props.dashboardFilterData.report_type &&
        props.dashboardFilterData.default_include_type) {

        showViewReport();
    }

    if (
        props.dashboardFilterData.main_report_type === props.staticSellThroughMainReportTypes.byCompany &&
        props.dashboardFilterData.report_type &&
        props.dashboardFilterData.default_include_type
    ) {
        showViewReport();
    }

    state.disableRefreshButton = props.aggregateProcessTracker.status;
});

const selectAllLocations = () => {
    state.locations = props.stores;
    state.parameters.location_ids = props.stores.map((store) => {
        return store.id;
    });

    refreshTable();
};

const clearAllLocations = () => {
    state.locations = [];
    state.parameters.location_ids = [];

    refreshTable();
};

const clearDataForGoodsReceivedNoteIn = () => {
    state.parameters.includes_by_goods_receive_note_in_location_ids = [];
};

const clearDataForGoodsReceivedNoteOut = () => {
    state.parameters.includes_by_goods_receive_note_out_location_ids = [];
};

const clearDataForStockAdjustmentIn = () => {
    state.parameters.includes_by_stock_adjustment_in_location_ids = [];
};

const clearDataForStockAdjustmentOut = () => {
    state.parameters.includes_by_stock_adjustment_out_location_ids = [];
};

const clearDataForStockTransferIn = () => {
    state.parameters.includes_by_stock_transfer_in_location_ids = [];
};

const clearDataForStockTransferOut = () => {
    state.parameters.includes_by_stock_transfer_out_location_ids = [];
};

const clearDataForDeliveryOrderIn = () => {
    state.parameters.includes_by_delivery_order_in_location_ids = [];
};

const clearDataForDeliveryOrderOut = () => {
    state.parameters.includes_by_delivery_order_out_location_ids = [];
};

const updateDisplayProductFilter = () => {
    state.displayProductFilter = !state.displayProductFilter;
};

const showViewReport = () => {
    if (sellThroughReportFilterValidationCheck(state.parameters, state.isLocationCompulsorySelection)) {
        state.showReport = !state.showReport;
        return;
    }

    showErrorNotification('Please Select Any one filter, date and report types');
    state.showReport = false;
};

const updateMainReportId = (mainReportType) => {
    state.showReport = false;

    state.parameters.main_report_type = mainReportType === null ? null : parseInt(mainReportType);
    if (mainReportType !== null && mainReportType === props.staticSellThroughMainReportTypes.byCompany) {
        state.parameters.include_by = [
            props.staticSellThroughIncludeTypes.goodsReceiveNoteIn,
            props.staticSellThroughIncludeTypes.goodsReceiveNoteOut,
            props.staticSellThroughIncludeTypes.stockAdjustmentIn,
            props.staticSellThroughIncludeTypes.stockAdjustmentOut,
        ];
    }

    if (mainReportType !== null && mainReportType === props.staticSellThroughMainReportTypes.byLocation) {
        state.parameters.include_by = [
            props.staticSellThroughIncludeTypes.goodsReceiveNoteIn,
            props.staticSellThroughIncludeTypes.goodsReceiveNoteOut,
            props.staticSellThroughIncludeTypes.stockAdjustmentIn,
            props.staticSellThroughIncludeTypes.stockAdjustmentOut,
            props.staticSellThroughIncludeTypes.stockTransferIn,
            props.staticSellThroughIncludeTypes.deliveryOrderIn,
        ];
    }

    state.parameters.location_ids = [];
    refreshTable();
};

const displayCustomizedFilter = () => {
    state.displayCustomizedFilter = ! state.displayCustomizedFilter;
};

const abortTheRequest = () => {
    if (state.abortControllers[state.parameters.report_type] === undefined) state.abortControllers[state.parameters.report_type] = new AbortController();

    state.abortControllers[state.parameters.report_type].abort();
    state.abortControllers[state.parameters.report_type] = new AbortController();
};

const isDateTypeSelected = (value) => {
    if (state.parameters.select_date_type === value) {
        return true;
    }

    return false;
};

const updateDateType = (selectedDateType) => {
    state.parameters.select_date_type = selectedDateType;

    if (selectedDateType === props.staticSellThroughDateTypes.accumulated) {
        state.parameters.date = currentDate();
        state.parameters.date_range = null;
    }

    if (selectedDateType === props.staticSellThroughDateTypes.customized) {
        state.parameters.date = null;
        state.parameters.date_range = [currentDate(), currentDate()];
    }
    refreshTable();
};

const syncData = () => {
    axios.get(route('admin.sell_through_aggregate_reports.get_latest_data_sync'))
        .then((response) => {
            showSuccessNotification(response.data.message);
            state.disableRefreshButton = true;
        });
};
</script>
