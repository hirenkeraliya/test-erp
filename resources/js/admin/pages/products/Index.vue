<template>
    <PageTitle title="Products" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Products
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <OutlinePrimaryButton
                text="Upload Images"
                class="shadow-md text-sm mx-1"
                @click="displayUploadImagesByArticleNumber"
            />
        </div>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.products.create')">
                <PrimaryButton
                    text="Add New Product"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <ProductListFilters
            :product-statuses="productStatuses"
            :product-types="productTypes"
            :product-batches="productBatches"
            :product-sync-types="productSyncTypes"
            :all-product-sync-type="allProductSyncType"
            :all-status="allStatus"
            :categories-url="route('admin.categories.get_filtered_categories')"
            :brands-url="route('admin.brands.get_filtered_brands')"
            :sizes-url="route('admin.sizes.get_filtered_sizes')"
            :colors-url="route('admin.colors.get_filtered_colors')"
            :departments-url="route('admin.departments.get_filtered_departments')"
            :article-numbers-url="route('admin.products.get_filtered_article_number')"
            :styles-url="route('admin.styles.get_filtered_styles')"
            :product-collections-url="route('admin.product_collections.get_filtered_product_collection')"
            :tags-url="route('admin.tags.get_filtered_tags')"
            :attributes="attributes"
            @refresh-table="refreshTable"
            @update-params="updateParams"
        />
    </div>

    <JTable
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :fetch-url="route('admin.products.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-column-customization="true"
        :allow-pdf-export="true"
        :export-pdf-records-callback="exportPDFRecords"
        local-storage-key="admin-products-columns"
        search-title="Search by name, compound product name, code, upc, or retail price"
        @get-filter-columns="getFilterColumns"
        @get-total-records="getTotalRecords"
    >
        <template #selection="record">
            <FormCheckbox
                :check-value="state.selectedRecords.includes(record.item.id)"
                @change="updateCheckbox($event, record.item.id)"
            />
        </template>

        <template #images="record">
            <Album
                class="cursor-pointer"
                @click="showImageModel(record.item.images)"
            />
        </template>

        <template #name="record">
            <div class="flex items-center justify-left">
                <span>
                    {{ record.item.name }}
                </span>
            </div>
        </template>

        <template #external_reference="record">
            <div class="flex items-center justify-left">
                <span v-if="record.item.product_channel_reference">
                    External Product Id: {{ record.item.product_channel_reference.external_product_id }}<br>
                </span>
                <span v-else>N/A</span>
            </div>
        </template>

        <template #external_variant="record">
            <div class="flex items-center justify-left">
                <span v-if="record.item.product_channel_reference">
                    External Variant Id: {{ record.item.product_channel_reference.external_variant_id }}
                </span>
                <span v-else>N/A</span>
            </div>
        </template>

        <template #thumbnail_url="record">
            <img
                v-if="record.item.thumbnail_url"
                :src="record.item.thumbnail_url"
                :alt="record.item.name"
                width="100"
            >
            <span v-else>N/A</span>
        </template>

        <template #brand="record">
            {{ record.item.brand ? record.item.brand.name : 'N/A' }}
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(attribute, index) in record.item.attributes"
                    :key="index"
                    class="flex"
                >
                    {{ index }} : {{ attribute }}
                </p>
            </span>
            <span v-else>
                {{ record.item.color ? record.item.color.name : 'N/A' }}
            </span>
        </template>

        <template
            v-if="!pageProps.product_variant"
            #color="record"
        >
            {{ record.item.color ? record.item.color.name : 'N/A' }}
        </template>

        <template
            v-if="!pageProps.product_variant"
            #size="record"
        >
            {{ record.item.size ? record.item.size.name : 'N/A' }}
        </template>

        <template
            v-if="!pageProps.product_variant"
            #style="record"
        >
            {{ record.item.style ? record.item.style.name : 'N/A' }}
        </template>

        <template #department="record">
            {{ record.item.department ? record.item.department.name : 'N/A' }}
        </template>

        <template #retail_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.retail_price) }}
        </template>

        <template #franchise_price_1="record">
            {{ displayAmountWithCurrencySymbol(record.item.franchise_price_1) }}
        </template>

        <template #franchise_price_2="record">
            {{ displayAmountWithCurrencySymbol(record.item.franchise_price_2) }}
        </template>

        <template #franchise_price_3="record">
            {{ displayAmountWithCurrencySymbol(record.item.franchise_price_3) }}
        </template>

        <template #wholesale_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.wholesale_price) }}
        </template>

        <template #company_or_tender_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.company_or_tender_price) }}
        </template>

        <template #branch_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.branch_price) }}
        </template>

        <template #minimum_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.minimum_price) }}
        </template>

        <template #original_capital_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.original_capital_price) }}
        </template>

        <template #capital_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.capital_price) }}
        </template>

        <template #staff_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.staff_price) }}
        </template>

        <template #purchase_cost="record">
            {{ displayAmountWithCurrencySymbol(record.item.purchase_cost) }}
        </template>

        <template #online_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.online_price) }}
        </template>

        <template #categories="record">
            <span v-if="record.item.categories?.length">
                <span
                    v-for="(category, index) in record.item.categories"
                    :key="index"
                    class="inline-block"
                >
                    {{ category.name }}

                    <ChevronRight
                        v-if="index != record.item.categories.length - 1"
                        class="form-check w-4 h-4 text-slate-400 inline-block"
                    />
                </span>
            </span>
            <span v-else>
                N/A
            </span>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    v-if="(data.item.status === activeProduct)"
                    class="flex items-center mr-3"
                    :href="route('admin.products.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Dropdown
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
                                v-if="(data.item.status === activeProduct)"
                                class="flex items-center mr-3"
                                @click="openModalForMerge(data.item.id)"
                            >
                                <GitMerge class="w-4 h-4 mr-1" />
                                Merge
                            </DropdownItem>

                            <DropdownItem
                                v-if="(data.item.status === activeProduct)"
                                class="flex items-center mr-3"
                                @click="archive(data.item, $event)"
                            >
                                <Archive class="w-4 h-4 mr-1" />
                                Archive
                            </DropdownItem>

                            <DropdownItem
                                v-if="(data.item.status === archivedProduct)"
                                class="flex items-center mr-3"
                                @click="restore(data.item, $event)"
                            >
                                <Archive class="w-4 h-4 mr-1" />
                                Restore
                            </DropdownItem>

                            <DropdownItem
                                v-if="havePermission(uploadImagePermission) && (data.item.status === activeProduct)"
                                class="flex items-center mr-3"
                                @click="openModalForUploadImage(data.item)"
                            >
                                <Upload class="w-4 h-4 mr-1" />
                                Upload Product Image
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #extra-header-data="data">
            <p
                v-if="exportRecordCount > 0"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <Tippy
                    content="Export History"
                    class="btn btn-outline-primary"
                    @click="showExportProductData()"
                >
                    <FileClock class="text-primary w-8 cursor-pointer" />
                </Tippy>
            </p>
            <div
                v-if="state.selectedRecords.length"
                class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
            >
                <Dropdown
                    v-slot="{ dismiss }"
                    class="flex items-center"
                >
                    <DropdownToggle
                        tag="a"
                        href="javascript:;"
                    >
                        <PrimaryButton
                            text="Clear External Reference"
                            class="shadow-md"
                        />
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                v-for="(saleChannel, index) in saleChannels"
                                :key="index"
                                class="flex items-center mr-3"
                                @click="removeSaleChannelReferences(saleChannel.id, dismiss)"
                            >
                                <span v-if="saleChannel.updated_at">
                                    {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                                </span>
                                <span v-else>
                                    {{ saleChannel.name }}
                                </span>
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
            <div
                v-if="state.selectedRecords.length !== 0"
                class="flex"
            >
                <p class="text-md font-bold mr-1 sm:mr-2 float-left sm:float-none">
                    <OutlinePrimaryButton
                        text="Clear"
                        class="w-24"
                        @click="clearAll()"
                    />
                </p>
            </div>

            <div
                v-if="state.totalRecords > 0 && state.totalRecords !== state.selectedRecords.length && state.displaySelectAllButton"
                class="mr-2"
            >
                <OutlinePrimaryButton
                    text="Select All"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="getProductIds(data.data)"
                />
            </div>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
            <div
                v-if="state.isExportFileInProgress"
                class="bg-slate-300 rounded p-1 w-12 flex justify-center"
            >
                <LoaderSvg />
            </div>

            <div
                v-else
                class="text-center focus:ring-0 mr-3"
            >
                <Dropdown
                    v-slot="{ dismiss }"
                    class="dropdown"
                >
                    <DropdownToggle
                        tag="a"
                        href="javascript:;"
                        class="btn btn-outline-primary"
                    >
                        Export
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                @click="exportCsvRecords(dismiss)"
                            >
                                CSV
                            </DropdownItem>

                            <DropdownItem
                                @click="exportExcelRecords(dismiss)"
                            >
                                EXCEL
                            </DropdownItem>

                            <DropdownItem
                                @click="exportExcelRecordsForBulkProductUpdate(dismiss)"
                            >
                                EXCEL BULK PRODUCT UPDATES
                            </DropdownItem>

                            <DropdownItem
                                @click="exportLoyaltyPointCsvRecords(dismiss)"
                            >
                                LOYALTY POINT CSV
                            </DropdownItem>

                            <DropdownItem
                                @click="exportLoyaltyPointExcelRecords(dismiss)"
                            >
                                LOYALTY POINT EXCEL
                            </DropdownItem>

                            <DropdownItem
                                @click="exportBoxProductCsvRecords(dismiss)"
                            >
                                BOX PRODUCT CSV
                            </DropdownItem>

                            <DropdownItem
                                @click="exportBoxProductExcelRecords(dismiss)"
                            >
                                BOX PRODUCT EXCEL
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>
    </JTable>

    <Modal
        size="modal-lg"
        :show="state.openModalForMerge"
        @hidden="hideMergeModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Merge Product
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideMergeModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <InfoAlert
                color="primary"
                class="mb-3 mt-5"
            >
                1. The Selected Main Product Is Going To Merge With New Product. So The Selected Main Product Is Going To Be
                Deleted.
                <br>
                2. Same Product type only can be merge. Like Regular v/s Regular or Bundle v/s Bundle etc...
                <br>
                3. Either Article number must be same or non-article number means no article numbers for both products.
                <br>
            </InfoAlert>

            <div>
                <div class="mb-3">
                    <JProductFilter
                        :product-search-url="route('admin.get_filtered_inventory_products')"
                        get-product-url-name="admin.get_product"
                        :selected-product-id="state.newSelectedProductId"
                        @update:product-selected="productSelected($event)"
                        @update:display-product-filters="displayUpdateFilter()"
                    />
                </div>

                <div class="flex justify-between overflow-auto">
                    <div v-if="state.selectedProduct && state.newProductDetails">
                        <table class="table mt-3 text-center">
                            <thead>
                                <tr>
                                    <th>UPC</th>
                                    <th class="text-red-800 bg-red-50">
                                        {{ state.selectedProduct.upc }}
                                    </th>
                                    <th class="text-green-800 bg-green-50">
                                        {{ state.newProductDetails.upc }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(column, key) in getColumnForProductMergeDetails()"
                                    :key="key"
                                >
                                    <td>{{ getFormattedColumnName(column) }}</td>
                                    <td class="text-red-800 bg-red-50">
                                        <span v-if="column === 'categories'">
                                            <span
                                                v-if="state.selectedProduct.categories.length <= 0"
                                            >
                                                <p>N/A</p>
                                            </span>
                                            <span
                                                v-for="(category, index) in state.selectedProduct.categories"
                                                :key="index"
                                                class="inline-block"
                                            >
                                                {{ category.name }}

                                                <ChevronRight
                                                    v-if="index != state.selectedProduct.categories.length - 1"
                                                    class="form-check w-4 h-4 text-slate-400 inline-block"
                                                />
                                            </span>
                                        </span>

                                        <span v-else-if="column === 'attributes' && pageProps.product_variant">
                                            <span
                                                v-if="state.selectedProduct.attributes.length <= 0"
                                            >
                                                <p>N/A</p>
                                            </span>
                                            <p
                                                v-for="(attribute, index) in state.selectedProduct.attributes"
                                                :key="index"
                                                class="flex"
                                            >
                                                {{ index }} : {{ attribute }}
                                            </p>
                                        </span>

                                        <span v-else-if="column === 'tags'">
                                            <span
                                                v-if="state.selectedProduct.tags.length <= 0"
                                            >
                                                <p>N/A</p>
                                            </span>
                                            <span
                                                v-for="(tag, index) in state.selectedProduct.tags"
                                                :key="index"
                                                class="inline-block"
                                            >
                                                {{ tag.name }}

                                                <ChevronRight
                                                    v-if="index != state.selectedProduct.tags.length - 1"
                                                    class="form-check w-4 h-4 text-slate-400 inline-block"
                                                />
                                            </span>
                                        </span>

                                        <span v-else>
                                            {{ state.selectedProduct[column] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-green-800 bg-green-50">
                                        <span v-if="column === 'categories'">
                                            <span
                                                v-if="state.newProductDetails.categories.length <= 0"
                                            >
                                                <p>N/A</p>
                                            </span>
                                            <span
                                                v-for="(category, index) in state.newProductDetails.categories"
                                                :key="index"
                                                class="inline-block"
                                            >
                                                {{ category.name ?? 'N/A' }}

                                                <ChevronRight
                                                    v-if="index != state.newProductDetails.categories.length - 1"
                                                    class="form-check w-4 h-4 text-slate-400 inline-block"
                                                />
                                            </span>
                                        </span>
                                        <span v-else-if="column === 'attributes' && pageProps.product_variant">
                                            <span
                                                v-if="state.newProductDetails.attributes.length <= 0"
                                            >
                                                <p>N/A</p>
                                            </span>
                                            <p
                                                v-for="(attribute, index) in state.newProductDetails.attributes"
                                                :key="index"
                                                class="flex"
                                            >
                                                {{ index }} : {{ attribute }}
                                            </p>
                                        </span>

                                        <span v-else-if="column === 'tags'">
                                            <span
                                                v-if="state.newProductDetails.tags.length <= 0"
                                            >
                                                <p>N/A</p>
                                            </span>
                                            <span
                                                v-for="(tag, index) in state.newProductDetails.tags"
                                                :key="index"
                                                class="inline-block"
                                            >
                                                {{ tag.name }}

                                                <ChevronRight
                                                    v-if="index != state.newProductDetails.tags.length - 1"
                                                    class="form-check w-4 h-4 text-slate-400 inline-block"
                                                />
                                            </span>
                                        </span>

                                        <span v-else>
                                            {{ state.newProductDetails[column] ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5">
                    <PrimaryButton
                        type="submit"
                        text="Merge"
                        class="w-24"
                        @click="mergeAndDeleteProductId"
                    />
                </div>
            </div>
        </ModalBody>
    </Modal>

    <Modal
        size="modal-lg"
        :show="state.openModalForUploadImage"
        @hidden="hideUploadProductImageModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Upload Product Image
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideUploadProductImageModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <div>
                <form
                    @submit.prevent="saveUploadImageProduct();"
                >
                    <div>
                        <JFileCropUpload
                            v-model:input-file="state.uploadImage"
                            input-label="Thumbnail (343px X 260px)"
                            validation-field-name="photo"
                            :max-width="343"
                            :max-height="260"
                            @update:input-file="uploadImage"
                        />

                        <div
                            v-if="state.image_url"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <img
                                :src="state.image_url"
                                :alt="state.image_url"
                                width="100"
                                class="mt-2"
                            >
                        </div>

                        <PrimaryButton
                            type="submit"
                            text="Upload"
                            class="w-24 mt-5"
                        />
                    </div>
                </form>
            </div>
        </ModalBody>
    </Modal>

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('admin.get_filtered_inventory_products_list')"
        :filtered-category-url="route('admin.categories.get_filtered_categories')"
        :filtered-brand-url="route('admin.brands.get_filtered_brands')"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />

    <ImageVideoViewer
        v-if="state.displayImageVideoViewerModal"
        :modal-show="state.displayImageVideoViewerModal"
        :product-image-details="state.productImageDetails"
        @close-modal="closeModal"
    />

    <UploadImagesByArticleNumber
        v-if="state.openModalForUploadImagesByArticleNumber"
        :modal-show="state.openModalForUploadImagesByArticleNumber"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { reactive, computed, onMounted } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import { CheckSquare, ChevronRight, Archive, GitMerge, X, MoreHorizontal, Upload, Album, FileClock, RefreshCw } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { confirmDialogBox, showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import { router, usePage  } from '@inertiajs/vue3';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { displayAmountWithCurrencySymbol, exportRecords, printReport, havePermission} from '@commonServices/helper';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import ImageVideoViewer from '@commonComponents/ImageVideoViewer.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import ProductListFilters from '@commonComponents/ProductListFilters.vue';
import LoaderSvg from '@svg/LoaderSvg.vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import UploadImagesByArticleNumber from '@adminPages/products/partials/UploadImagesByArticleNumber.vue';

const pageProps = computed(() => usePage().props);
const props = defineProps({
    productStatuses: {
        type: Array,
        required: true,
    },

    productBatches: {
        type: Array,
        required: true,
    },

    productTypes: {
        type: Array,
        required: true,
    },

    productSyncTypes: {
        type: Array,
        required: true,
    },

    allProductSyncType: {
        type: Number,
        required: true,
    },

    allStatus: {
        type: String,
        required: true,
    },

    allBatch: {
        type: String,
        required: true,
    },

    exportPermission: {
        type: String,
        required: true,
    },

    uploadImagePermission: {
        type: String,
        required: true,
    },
    activeProduct: {
        type: Number,
        required: true,
    },
    archivedProduct: {
        type: Number,
        required: true,
    },
    exportType: {
        type: Number,
        required: true,
    },
    exportRecordCount: {
        type: Number,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => {},
    },
});
const state = reactive({
    columns: [
        {
            key: 'selection',
            label: 'Select',
            isDisplay: true,
        },

        {
            key: 'images',
            label: 'Media',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true
        }, {
            key: 'thumbnail_url',
            label: 'Thumbnail',
            isDisplay: true,
        }, {
            key: 'name',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'external_reference',
            isDisplay: false,
        }, {
            key: 'external_variant',
            isDisplay: false,
        }, {
            key: 'code',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'brand',
            isDisplay: true,
        }, {
            key: 'attributes',
            isDisplay: true,
        }, {
            key: 'color',
            isDisplay: true,
        }, {
            key: 'size',
            isDisplay: true,
        }, {
            key: 'style',
            isDisplay: true,
        }, {
            key: 'department',
            isDisplay: true,
        }, {
            key: 'categories',
            isDisplay: true,
        }, {
            key: 'upc',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'verification_qr_code',
            sortable: false,
            isDisplay: true,
        }, {
            key: 'article_number',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'retail_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'original_created_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'created_by',
            isDisplay: true,
        }, {
            key: 'approved_by',
            isDisplay: true,
        }, {
            key: 'last_editor_by',
            isDisplay: true,
        }, {
            key: 'created_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'updated_at',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'is_temporarily_unavailable',
            isDisplay: false,
        }, {
            key: 'ean',
            isDisplay: false,
        }, {
            key: 'custom_sku',
            isDisplay: false,
        },{
            key: 'manufacturer_sku',
            isDisplay: false,
        },{
            key: 'type_id',
            isDisplay: false,
        },{
            key: 'franchise_price_1',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'franchise_price_2',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'franchise_price_3',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'wholesale_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'company_or_tender_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'branch_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'minimum_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'original_capital_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'capital_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'staff_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'purchase_cost',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'online_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: false,
        },{
            key: 'subcategory_name',
            isDisplay: false,
        },{
            key: 'subsubcategory_name',
            isDisplay: false,
        },{
            key: 'has_batch',
            isDisplay: false,
        },{
            key: 'is_non_inventory',
            isDisplay: false,
        },{
            key: 'is_non_selling_item',
            isDisplay: false,
        },{
            key: 'is_available_in_pos',
            isDisplay: false,
        },{
            key: 'is_available_in_ecommerce',
            isDisplay: false,
        },{
            key: 'is_sold_as_single_item',
            isDisplay: false,
        },{
            key: 'sell_item_via_derivative',
            isDisplay: false,
        },{
            key: 'tags',
            isDisplay: false,
        },{
            key: 'vendor',
            isDisplay: false,
        },{
            key: 'sale_channels',
            isDisplay: false,
        },{
            key: 'unit_of_measure',
            isDisplay: false,
        }, {
            key: 'description',
            isDisplay: false,
        }, {
            key: 'season',
            isDisplay: false,
        }, {
            key: 'sub_department',
            isDisplay: false,
        },{
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        }
    ],
    dynamicColumns: [],
    parameters: {
        status: props.allStatus,
        batch: props.allBatch,
        date_range: null,
        product_type_id: null,
        product_sync_type_id: props.allProductSyncType,
        category_ids: null,
        brand_ids: null,
        department_ids: null,
        color_ids: null,
        size_ids: null,
        article_numbers: null,
        tag_ids: null,
        style_ids: null,
        export_columns: null,
        all_product_selected:false,
    },

    isExportFileInProgress: false,
    isSelectAllProductIdsInProgress: false,
    displayProductFilter: false,
    openModalForMerge: false,
    openModalForUploadImage: false,
    selectedProductId: null,
    newSelectedProductId: null,
    selectedProduct: null,
    newProductDetails: null,
    displayInventoryUpdateFilterModal: false,
    refreshTableData: Math.random(),
    selectedBrands: null,
    selectedCategories: null,
    selectedDepartments: null,
    selectedSizes: null,
    selectedColors: null,
    selectedTags: null,
    selectedArticleNumber: null,
    selectedStyles: null,
    image: '',
    uploadImage: null,
    productId: null,
    image_url: null,
    displayImageVideoViewerModal: false,
    openModalForUploadImagesByArticleNumber: false,
    productImageDetails: null,
    disableRefreshButton: props.hasPendingSyncTransaction,
    selectedRecords: [],
    displaySelectAllButton: true,
    totalRecords: 0,
});

const getFilteredColumns = () => {
    const columns = state.columns || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size', 'style'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'attributes');
};

const getTotalRecords = (totalRecords) => {
    state.totalRecords = totalRecords;
};

onMounted(() => {
    state.dynamicColumns = getFilteredColumns();
});

const showImageModel = (productImages) => {
    state.productImageDetails = productImages;
    state.displayImageVideoViewerModal = true;
};

const updateParams = (params) => {
    state.parameters = params;
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const uploadImage = (selectedImage) => {
    state.image_url = URL.createObjectURL(selectedImage);
};

const openModalForUploadImage = (product) => {
    state.openModalForUploadImage = true;
    state.productId = product.id;
    state.image_url = product.image_url;
};

const saveUploadImageProduct = () => {
    router.post(route('admin.products.upload_image'), {
        product_id: state.productId,
        image: state.uploadImage,
    }, {
        onSuccess: () => {
            state.openModalForUploadImage = false;
            router.get(route('admin.products.index'));
        },
        onError: (error) => {
            showErrorNotification(error.image);
        }
    });
};

const archive = (product) => {
    const message = 'Archived products are not displayed/considered in dropdowns, search, tables, etc. Are you sure you want to archive the product named ' + product.name + '?';

    confirmDialogBox(message, () => {
        router.post(route('admin.products.archive', product.id), {}, {
            onSuccess: () => router.get(route('admin.products.index'))
        });
    });
};

const restore = (product) => {
    const message = 'Are you sure you want to restore the product named ' + product.name + '?';

    confirmDialogBox(message, () => {
        router.put(route('admin.products.restore', product.id), {}, {
            onSuccess: () => router.get(route('admin.products.index'))
        });
    });
};

const exportCsvRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-products/',
                    'products.csv',
                    state.parameters,
                    props.exportPermission,
                    state.parameters.export_columns
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportExcelRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-products/',
                    'products.xlsx',
                    state.parameters,
                    props.exportPermission,
                    state.parameters.export_columns
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportExcelRecordsForBulkProductUpdate = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_product_export_limit_for_import_bulk_update', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-products-for-import-bulk-update/',
                    'products.xlsx',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportLoyaltyPointCsvRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_product_loyalty_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-loyalty-point-products/',
                    'loyalty-point-products.csv',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};


const exportLoyaltyPointExcelRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_product_loyalty_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-loyalty-point-products/',
                    'loyalty-point-products.xlsx',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportBoxProductCsvRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_box_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-box-products/',
                    'box-products.csv',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};


const exportBoxProductExcelRecords = () => {
    state.isExportFileInProgress = true;
    return axios.get(route('admin.products.check_box_product_export_limit', state.parameters))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                state.isExportFileInProgress = false;
                return exportRecords(
                    'export-box-products/',
                    'box-products.xlsx',
                    state.parameters,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
            state.isExportFileInProgress = false;
        });
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.products.print_products', params), props.exportPermission);
};

const displayUpdateFilter = () => {
    state.displayInventoryUpdateFilterModal = true;
};

const productSelected = (selectedProduct) => {
    state.newSelectedProductId = selectedProduct.id;
    axios.get(route('admin.products.product_details', selectedProduct.id)).then((response) => {
        state.newProductDetails = response.data.product;
    });
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;

    productSelected(selectedProduct);
};

const openModalForMerge = (selectedProductId) => {
    axios.get(route('admin.products.product_details', selectedProductId)).then((response) => {
        state.selectedProduct = response.data.product;
    });
    state.selectedProductId = selectedProductId;
    state.openModalForMerge = true;
    state.newProductDetails = null;
};

const mergeAndDeleteProductId = () => {
    axios.put(route('admin.products.merge_products', [
        state.selectedProduct,
        state.newSelectedProductId
    ]))
        .then((response) => {
            showSuccessNotification(response.data.message);
            state.openModalForMerge = false;
            state.selectedProduct = null;
            state.newSelectedProductId = null;
            refreshTable();
        })
        .catch((error) => {
            showErrorNotification(error.response.data.message);
        });
};

const hideMergeModal = () => {
    state.openModalForMerge = false;
    state.newSelectedProductId = null;
};

const hideUploadProductImageModal = () => {
    state.openModalForUploadImage = false;
};

const getFormattedColumnName = (columnName) => {
    return columnName.replace(/_/g, ' ')
        .toLowerCase()
        .replace(/(?:^|\s)\w/g, match => match.toUpperCase());
};

const getColumnForProductMergeDetails = () => {
    const copiedObject = { ...state.selectedProduct };

    if (pageProps.value.product_variant) {
        Object.keys(copiedObject).forEach(key => {
            if (['color', 'size', 'style'].includes(key)) {
                delete copiedObject[key];
            }
        });
    } else {
        delete copiedObject['attributes'];
    }

    const removeIdAndUpcColumns = ['id', 'upc'];

    removeIdAndUpcColumns.forEach(key => delete copiedObject[key]);

    return Object.keys(copiedObject);
};

const closeModal = () => {
    state.displayImageVideoViewerModal = false;
    state.openModalForUploadImagesByArticleNumber = false;
};

const showExportProductData = () => {
    router.get(route('admin.export_records.index', { export_type: props.exportType }));
};

const syncData = (saleChannelId, dismiss) => {
    axios.get(route('admin.products.sync_data', saleChannelId)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });
    dismiss();
};

const displayUploadImagesByArticleNumber = () => {
    state.openModalForUploadImagesByArticleNumber = true;
};

const getFilterColumns = (columns) => {
    state.parameters.export_columns = columns;
};

const updateCheckbox = (element, productId) => {
    if (!element.target.checked) {
        const index = state.selectedRecords.lastIndexOf(parseInt(productId));
        state.selectedRecords.splice(index, 1);
    }

    if (element.target.checked) {
        state.selectedRecords.push(parseInt(productId));
    }

    state.parameters.all_product_selected = false;
    state.displaySelectAllButton = true;
};

const getProductIds = (responseData) => {
    state.parameters.all_product_selected = true;
    state.selectedRecords = (responseData.data).map(item => item.id);
    state.displaySelectAllButton = false;
};

const removeSaleChannelReferences = (saleChannelId, dismiss) => {
    const message = 'Are you sure you want to delete all products linked to this product channel reference?';
    confirmDialogBox(message , () => {
        axios.post(route('admin.products.remove_sales_channel_references_data'), {
            sale_channel_id: saleChannelId,
            product_ids: state.selectedRecords,
            filter_data: state.parameters,
        }).then(() => {
            showSuccessNotification('External references has been cleared successfully.');
            window.location.reload();
        });
        dismiss();
    });
};

const clearAll = () => {
    state.selectedRecords = [];
    state.parameters.all_product_selected = false;
    state.displaySelectAllButton = true;
};
</script>
