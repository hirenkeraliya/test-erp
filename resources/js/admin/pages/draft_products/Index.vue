<template>
    <PageTitle title="Products Pending Approval" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Products Pending Approval
        </h2>
    </div>

    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <ProductListFilters
            :product-statuses="productStatuses"
            :product-batches="productBatches"
            :product-types="productTypes"
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
            :tags-url="route('admin.tags.get_filtered_tags')"
            :is-active-products="false"
            :attributes="attributes"
            @refresh-table="refreshTable"
            @update-params="updateParams($event, params)"
            @clear-all="clearAll"
        >
            <FormAjaxSelect
                :selected-record="state.selectedEmployee"
                :search-records="searchEmployees"
                placeholder="Employee Name to search..."
                input-label="Employee"
                label-class="block font-medium text-base text-primary-p3 mb-2"
                @update:selected-record="updateEmployee"
            />
        </ProductListFilters>
    </div>

    <JTable
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :fetch-url="route('admin.draft_products.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-column-customization="true"
        row-key-background-color="bg-red-200"
        row-key-for-background-color="match_count"
        local-storage-key="admin-draft-products-columns"
        search-title="Search by name, compound product name, code, upc, or retail price"
        @get-search-text="getSearchText"
        @get-total-records="getTotalRecords"
    >
        <template #action="record">
            <div class="flex justify-center items-center cursor-pointer">
                <Dropdown
                    v-slot="{ dismiss }"
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
                                @click="edit(record.item.id, dismiss)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />
                                Edit
                            </DropdownItem>

                            <DropdownItem
                                class="flex items-center mr-3"
                                @click="showProductDetailModal(record.item.id, dismiss)"
                            >
                                <View class="w-4 h-4 mr-1" />
                                View
                            </DropdownItem>

                            <DropdownItem
                                class="flex items-center mr-3"
                                @click="deleteProduct(record.item.id, dismiss)"
                            >
                                <Archive class="w-4 h-4 mr-1" />
                                Delete
                            </DropdownItem>

                            <DropdownItem
                                v-if="showApproveButton(record.item)"
                                class="flex items-center mr-3"
                                @click="singleProductApprove(record.item.id, dismiss)"
                            >
                                <Check class="w-4 h-4 mr-1" />
                                Approve
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>

                <Tippy
                    v-if="record.item.match_count > 0"
                    tag="button"
                    type="button"
                    content="Similar Products"
                    @click="showMatchActiveProducts(record.item.id)"
                >
                    <List
                        class="mr-3"
                    />
                </Tippy>

                <FormCheckbox
                    v-if="showApproveButton(record.item)"
                    :check-value="state.selectedRecords.includes(record.item.id)"
                    @change="updateCheckbox($event, record.item.id)"
                />
            </div>
        </template>

        <template #images="record">
            <Album
                class="cursor-pointer"
                @click="showImageModel(record.item.images)"
            />
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
            {{ record.item.brand.name }}
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(attribute, index) in record.item.attributes"
                    :key="index"
                    class="inline-block flex"
                >
                    {{ index }} : {{ attribute }}
                </p>
            </span>
            <span v-else>
                N/A
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

        <template #categories="record">
            <span v-if="record.item.categories.length">
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

        <template #extra-header-data>
            <div>
                <JBadge
                    v-if="state.selectedRecords.length !== 0"
                    :label="'Selected Products: ' + parseInt(state.selectedRecords.length)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
            </div>
            <div
                v-if="state.selectedRecords.length !== 0"
                class="flex"
            >
                <p class="text-md font-bold mr-1 sm:mr-2 float-left sm:float-none">
                    <PrimaryButton
                        text="Approve"
                        class="w-24"
                        @click="approved()"
                    />
                </p>
            </div>
            <div
                v-if="state.selectedRecords.length !== 0"
                class="flex"
            >
                <p class="text-md font-bold mr-1 sm:mr-2 float-left sm:float-none">
                    <PrimaryButton
                        text="Delete"
                        class="w-24"
                        @click="deleted()"
                    />
                </p>
            </div>
            <div
                v-if="state.selectedRecords.length !== 0"
                class="flex"
            >
                <p class="text-md font-bold mr-1 sm:mr-2 float-left sm:float-none">
                    <OutlinePrimaryButton
                        text="Clear All"
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
                    @click="getProductIds()"
                />
            </div>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
        </template>
    </JTable>

    <MatchActiveProductsModel
        :modal-show="state.displayMatchProductsModal"
        :match-products="state.matchProducts"
        @close-modal="closeModal"
    />

    <DraftProductViewDetails
        :modal-show="state.displayProductDetailsModal"
        :product-details="state.selectedProduct"
        :user="user"
        :creator-can-approve-draft-product="creatorCanApproveDraftProduct"
        @close-modal="state.displayProductDetailsModal = false"
        @approve-product="approvedProduct($event)"
    />

    <ImageVideoViewer
        v-if="state.displayImageVideoViewerModal"
        :modal-show="state.displayImageVideoViewerModal"
        :product-image-details="state.productImageDetails"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive, computed, onMounted } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, ChevronRight, Album, View, MoreHorizontal, Check, List, Archive } from 'lucide-vue-next';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import ImageVideoViewer from '@commonComponents/ImageVideoViewer.vue';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
import { useForm, router, usePage } from '@inertiajs/vue3';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import ProductListFilters from '@commonComponents/ProductListFilters.vue';
import DraftProductViewDetails from '@commonComponents/DraftProductViewDetails.vue';
import axios from 'axios';
import JBadge from '@commonComponents/JBadge.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import MatchActiveProductsModel from '@commonComponents/MatchActiveProductsModel.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { getDraftProductHelpText } from '@commonStores/documentation';

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

    user: {
        type: Object,
        required: true,
    },

    attributes: {
        type: Object,
        default: () => {},
    },

    creatorCanApproveDraftProduct: {
        type: Boolean,
        required: true,
    },
});
const state = reactive({
    columns: [
        {
            key: 'images',
            label: 'Media',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        }, {
            key: 'thumbnail_url',
            label: 'Thumbnail',
            isDisplay: true,
        }, {
            key: 'name',
            sortable: true,
            isDisplay: true,
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
            key: 'article_number',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'retail_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'created_by',
            isDisplay: true,
        }, {
            key: 'created_at',
            isDisplay: true,
        }, {
            key: 'updated_at',
            isDisplay: true,
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        },
    ],
    dynamicColumns: [],
    parameters: {
        employee_id: null,
    },
    displayMatchProductsModal: false,
    matchProducts: [],
    displayProductFilter: false,
    newProductDetails: null,
    displayInventoryUpdateFilterModal: false,
    refreshTableData: Math.random(),
    image: '',
    uploadImage: null,
    productId: null,
    image_url: null,
    displayImageVideoViewerModal: false,
    productImageDetails: null,
    selectedRecords: [],
    displayProductDetailsModal: false,
    selectedProduct: [],
    selectedEmployee: null,
    displaySelectAllButton: true,
    totalRecords: 0,
});

const draftProductForm = useForm({
    selectedRecords: [],
});

const showImageModel = (productImages) => {
    state.productImageDetails = productImages;
    state.displayImageVideoViewerModal = true;
};

const refreshTable = () => {
    state.selectedRecords = [];
    state.refreshTableData = Math.random();
};

const updateParams = (params) => {
    state.parameters = params;
    state.displaySelectAllButton = true;
};

const closeModal = () => {
    state.displayImageVideoViewerModal = false;
    state.displayMatchProductsModal = false;
    state.matchProducts = [];
};

const showProductDetailModal = (productId, dismiss) => {
    axios.get(route('admin.draft_products.get_draft_product_details', productId)).then((response) => {
        state.displayProductDetailsModal = true;
        state.selectedProduct = response.data.product;
        dismiss();
    }).catch((error) => {
        dismiss();
        showErrorNotification(error.response.data.message);
    });
};

const updateCheckbox = (element, productId) => {
    if (!element.target.checked) {
        const index = state.selectedRecords.lastIndexOf(parseInt(productId));
        state.selectedRecords.splice(index, 1);
    }

    if (element.target.checked) {
        state.selectedRecords.push(parseInt(productId));
    }
    state.displaySelectAllButton = true;
};

const getProductIds = () => {
    axios.get(route('admin.draft_products.get_draft_product_ids', state.parameters)).then((response) => {
        state.selectedRecords = [];
        state.selectedRecords = response.data;
    });
    state.displaySelectAllButton = false;
};

const approvedProduct = (productId) => {
    state.selectedRecords = [];
    state.selectedRecords.push(parseInt(productId));
    state.displayProductDetailsModal = false;
    approved();
};

const approved = () => {
    const message = 'Are you sure you want to approve selected products ?';
    draftProductForm.selectedRecords = state.selectedRecords;
    confirmDialogBox(message, () => {
        draftProductForm.post(route('admin.draft_products.approved'), {
            onSuccess: () => draftProductForm.get(route('admin.draft_products.index'))
        });
    });
    state.displaySelectAllButton = true;
};

const deleted = () => {
    const message = 'Are you sure you want to delete selected products ?';
    draftProductForm.selectedRecords = state.selectedRecords;
    confirmDialogBox(message, () => {
        draftProductForm.post(route('admin.draft_products.delete'), {
            onSuccess: () => draftProductForm.get(route('admin.draft_products.index'))
        });
    });
    state.displaySelectAllButton = true;
};

const clearAll = () => {
    state.selectedRecords = [];
    state.parameters.search_text = null;
    state.parameters.employee_id = null;
    state.selectedEmployee = null;
    state.displaySelectAllButton = true;
};

const getSearchText = (searchText) => {
    clearAll();
    state.parameters.search_text = searchText;
    state.displaySelectAllButton = true;
};

const searchEmployees = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.get(route('admin.employees.get_filtered_employees', filterData)).then((response) => {
        componentState.records = response.data.employees;
        componentState.isLoading = false;
    });
};

const updateEmployee = (selectEmployee) => {
    state.parameters.employee_id = null;
    if (selectEmployee !== null) {
        state.selectedEmployee = selectEmployee;
        state.parameters.employee_id = selectEmployee.id;
    }
    refreshTable();
    state.displaySelectAllButton = true;
};

const getTotalRecords = (totalRecords) => {
    state.totalRecords = totalRecords;
};

const showMatchActiveProducts = (draftProductId) => {
    axios.get(route('admin.draft_products.get_match_active_products', draftProductId)).then((response) => {
        state.matchProducts = [];
        state.matchProducts = response.data.data;
    });
    state.displayMatchProductsModal = true;
};

const edit = (draftProductId, dismiss) => {
    router.get(route('admin.draft_products.edit', draftProductId));
    dismiss();
};

const singleProductApprove = (draftProductId, dismiss) => {
    state.selectedRecords = [];
    state.selectedRecords.push(parseInt(draftProductId));
    dismiss();
    approved();
};

const deleteProduct = (draftProductId, dismiss) => {
    state.selectedRecords = [];
    state.selectedRecords.push(parseInt(draftProductId));
    dismiss();
    deleted();
};

const showApproveButton = (data) => {
    if (props.creatorCanApproveDraftProduct) {
        return props.creatorCanApproveDraftProduct;
    }

    return props.user.id !== data.created_by_id && props.user.type === data.created_by_type;
};

const getFilteredColumns = () => {
    const columns = state.columns || [];

    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size', 'style'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'attributes');
};

onMounted(() => {
    state.dynamicColumns = getFilteredColumns();
});
const helpStore = useHelpCenterStore();
helpStore.setHelpData(getDraftProductHelpText());
</script>
