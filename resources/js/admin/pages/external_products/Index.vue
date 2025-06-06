<template>
    <PageTitle title="Supplier Catalog" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Supplier Catalog
        </h2>
    </div>

    <div
        v-if="state.displayExternalProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="externalProductStatuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
                />
            </div>
            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Date Range"
                    @update:input-value="updateDate($event)"
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
        v-model:columns="state.columns"
        :fetch-url="route('admin.external_products.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by name, upc"
    >
        <template #action="record">
            <div class="inline-flex items-center">
                <div v-if="record.item.status_id === props.staticExternalProductStatuses.pending">
                    <FormCheckbox
                        :check-value="state.parameters.excludeProductsWithNoPrice"
                        class="ml-2"
                        @change="updateCheckbox($event, record.item.id)"
                    />
                </div>
            </div>
        </template>
        <template #status="record">
            <div class="inline-flex items-center">
                <span :class="getStatusColor(record.item.status_id)">{{ record.item.status }}</span>
            </div>
        </template>
        <template #product_details="record">
            <div
                class="flex justify-center items-center ml-2 cursor-pointer"
                @click="showProductDetailModal(record.item.product_details)"
            >
                <div
                    class="flex items-center mr-3"
                >
                    <View class="w-4 h-4 mr-2" />
                    View
                </div>
            </div>
        </template>

        <template #extra-header-data>
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
                <p class="text-md font-bold mr-1 sm:mr-2 float-left sm:float-none">
                    <PrimaryButton
                        text="Reject"
                        class="w-24"
                        @click="rejected()"
                    />
                </p>
            </div>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayExternalProductFilter = !state.displayExternalProductFilter"
                />
            </p>
        </template>
    </JTable>

    <ProductViewDetails
        :modal-show="state.displayProductDetailsModal"
        :product-details="state.productDetails"
        @close-modal="state.displayProductDetailsModal = false"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { confirmDialogBox } from '@commonServices/notifier';
import { useForm } from '@inertiajs/vue3';
import ProductViewDetails from '@adminPages/external_products/partials/ProductViewDetails.vue';
import { View } from 'lucide-vue-next';

const props = defineProps({
    externalProductStatuses: {
        type: Array,
        required: true,
    },
    staticExternalProductStatuses: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'product_name',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'sender_company_name',
            isDisplay: true,
        }, {
            key: 'upc',
            isDisplay: true,
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        }, {
            key: 'created_at',
            isDisplay: true,
        }, {
            key: 'product_details',
            label: 'Details',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
            isDisplay: true,
        },
    ],
    parameters: {
        status: null,
        date_range: null,
    },
    selectedRecords: [],
    displayExternalProductFilter: false,
    displayProductDetailsModal: false,
    productDetails: {},
});

const externalProductForm = useForm({
    selectedRecords: [],
});

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const clearAll = () => {
    state.parameters.date_range = null;
    state.parameters.status = null;
    refreshTable();
};

const updateCheckbox = (element, productId) => {
    if (!element.target.checked) {
        const index = state.selectedRecords.lastIndexOf(parseInt(productId));
        state.selectedRecords.splice(index, 1);
    }

    if (element.target.checked) {
        state.selectedRecords.push(parseInt(productId));
    }
};

const getStatusColor = (status) => {
    if (status === props.staticExternalProductStatuses.approved || status === props.staticExternalProductStatuses.created) {
        return 'bg-green-200 text-green-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticExternalProductStatuses.rejected) {
        return 'bg-red-200 text-red-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticExternalProductStatuses.in_progress) {
        return 'bg-yellow-200 text-pink-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticExternalProductStatuses.pending) {
        return 'bg-yellow-200 text-yellow-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
    if (status === props.staticExternalProductStatuses.duplicate) {
        return 'bg-orange-200 text-orange-800 text-md font-medium me-2 px-3 py-2 rounded-full';
    }
};

const approved = () => {
    const message = 'Are you sure you want to approve selected products ?';
    externalProductForm.selectedRecords = state.selectedRecords;
    confirmDialogBox(message, () => {
        externalProductForm.post(route('admin.external_products.approved'), {
            onSuccess: () => externalProductForm.get(route('admin.external_products.index'))
        });
    });
};

const rejected = () => {
    const message = 'Are you sure you want to reject selected products ?';
    externalProductForm.selectedRecords = state.selectedRecords;
    confirmDialogBox(message, () => {
        externalProductForm.post(route('admin.external_products.rejected'), {
            onSuccess: () => externalProductForm.get(route('admin.external_products.index'))
        });
    });
};

const showProductDetailModal = (productDetails) => {
    state.productDetails = productDetails;
    state.displayProductDetailsModal = true;
};
</script>
