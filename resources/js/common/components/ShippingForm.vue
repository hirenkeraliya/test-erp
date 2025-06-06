<template>
    <PageTitle title="Ship Stock Transfer" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Transfers
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Package Type details</span>
                    </h2>
                </div>

                <div class="grid grid-cols-12 gap-6">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <div class="intro-y box">
                            <div class="p-5">
                                <div class="overflow-unset overflow-x-auto">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="whitespace-nowrap w-4/12">
                                                    Product Name
                                                </th>
                                                <th 
                                                    v-if="! pageProps.product_variant"
                                                    class="whitespace-nowrap"
                                                >
                                                    Color
                                                </th>
                                                <th 
                                                    v-if="! pageProps.product_variant"
                                                    class="whitespace-nowrap"
                                                >
                                                    Size
                                                </th>
                                                <th 
                                                    v-if="pageProps.product_variant"
                                                    class="whitespace-nowrap"
                                                >
                                                    Attributes
                                                </th>
                                                <th class="whitespace-nowrap">
                                                    Transfer Stock
                                                </th>
                                                <th class="whitespace-nowrap">
                                                    Package Type
                                                </th>
                                                <th class="whitespace-nowrap">
                                                    Package Type Quantity
                                                </th>
                                                <th class="whitespace-nowrap">
                                                    Total Quantity per Package Type
                                                </th>
                                                <th class="whitespace-nowrap">
                                                    Remarks
                                                </th>
                                                <th class="whitespace-nowrap">
                                                    Batch
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr
                                                v-for="(item, itemIndex) in state.stockTransferItems"
                                                :key="'stock-transfer-item-' + itemIndex"
                                            >
                                                <td class="whitespace-nowrap w-4/12">
                                                    {{ item.product.name }} <br>
                                                </td>

                                                <td 
                                                    v-if="! pageProps.product_variant"
                                                    class="mt-10 whitespace-nowrap"
                                                >
                                                    {{ item.color }}
                                                </td>

                                                <td 
                                                    v-if="! pageProps.product_variant"
                                                    class="whitespace-nowrap"
                                                >
                                                    {{ item.size }}
                                                </td>

                                                <td 
                                                    v-if="pageProps.product_variant"
                                                    class="whitespace-nowrap"
                                                >
                                                    <p
                                                        v-for="(product_variant, index) in item.product_variant_values"
                                                        :key="index"
                                                        class="pl-4"
                                                    >
                                                        {{ product_variant.attribute.name }} : {{ product_variant.value }}
                                                    </p>
                                                </td>

                                                <td class="mt-10 whitespace-nowrap">
                                                    {{ item.transfer_stock }}
                                                    {{ item.derivative ? item.derivative.name : null }}
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    <FormSelectBox
                                                        :records="packageTypes"
                                                        :display-label="false"
                                                        class="mt-[0]"
                                                        input-label="Type"
                                                        :validation-field-name="'stock_transfer_items.' + itemIndex + '.package_type_id'"
                                                        @update:selected-record="updatePackageTypeId($event, itemIndex)"
                                                    />
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    <input
                                                        type="number"
                                                        min="0.01"
                                                        class="form-control"
                                                        :value="item.package_quantity"
                                                        @input="updatePackageQuantity($event, itemIndex)"
                                                    >
                                                    <ValidationError
                                                        :validation-field-name="'stock_transfer_items.' + itemIndex + '.package_quantity'"
                                                    />
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    <input
                                                        type="text"
                                                        min="0.01"
                                                        class="form-control"
                                                        :value="item.package_total_quantity"
                                                        @blur="updatePackageTotalQuantity($event, itemIndex)"
                                                    >
                                                    <ValidationError
                                                        :validation-field-name="'stock_transfer_items.' + itemIndex + '.package_total_quantity'"
                                                    />
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    {{ item.remarks }}
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    {{ item.product.has_batch ? '' : 'No' }}

                                                    <PrimaryButton
                                                        v-if="item.product.has_batch"
                                                        type="button"
                                                        class="w-full mt-1"
                                                        text="Specify Batch Details*"
                                                        @click="openProductBatchDetailsModal(itemIndex)"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-5">
                                    <Link :href="route(cancelUrl)">
                                        <SecondaryButton
                                            type="button"
                                            text="Cancel"
                                            class="w-24 mr-1"
                                        />
                                    </Link>

                                    <PrimaryButton
                                        type="button"
                                        text="Approve"
                                        class="w-24"
                                        @click="markAsApproved()"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <BatchDetailsModal
        v-if="state.batchDetailsModalIndex !== null"
        :batch-details="state.stockTransferItems[state.batchDetailsModalIndex].batch_details"
        :modal-show="state.displayProductBatchDetailsModal"
        message="The total of all the quantities you specify with the batch numbers must match the product quantity for the stock transfer."
        @close-modal="closeBatchDetailsModal()"
        @update:batch-details="updateBatchDetails"
    />
</template>

<script setup>
import BatchDetailsModal from '@commonComponents/BatchDetailsModal.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
import { usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stockTransferItems: {
        type: Object,
        required: true,
    },

    packageTypes: {
        type: Object,
        required: true,
    },

    stockTransferId: {
        type: Number,
        required: true,
    },

    cancelUrl: {
        type: String,
        required: true,
    },

    submitShippingUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    stockTransferItems: [],
    displayProductBatchDetailsModal: false,
    batchDetailsModalIndex: null
});

const openProductBatchDetailsModal = (itemIndex) => {
    state.batchDetailsModalIndex = itemIndex;

    if (!state.stockTransferItems[state.batchDetailsModalIndex].batch_details.length) {
        state.stockTransferItems[state.batchDetailsModalIndex].batch_details = [
            {
                batch_number: null,
                quantity: null,
            }
        ];
    }

    state.displayProductBatchDetailsModal = true;
};

const updatePackageQuantity = (element, itemIndex) => {
    const inputValue = element.target.value ? element.target.value : 0;

    state.stockTransferItems[itemIndex].package_quantity = parseInt(inputValue);
};

const updatePackageTotalQuantity = (element, itemIndex) => {
    const inputValue = element.target.value;

    if (parseFloat(inputValue) !== parseFloat(state.stockTransferItems[itemIndex].transfer_stock)) {
        showErrorNotification('The transferred stock and the total quantity of packages do not match.');
        return;
    }

    state.stockTransferItems[itemIndex].package_total_quantity = parseFloat(inputValue);
};

const updatePackageTypeId = (packageTypeId, itemIndex) => {
    state.stockTransferItems[itemIndex].package_type_id = packageTypeId;
    state.stockTransferItems[itemIndex].package_total_quantity = state.stockTransferItems[itemIndex].transfer_stock;
};

const updateBatchDetails = (batchDetails) => {
    state.stockTransferItems[state.batchDetailsModalIndex].batch_details = batchDetails;
};

const closeBatchDetailsModal = () => {
    state.displayProductBatchDetailsModal = false;
    state.batchDetailsModalIndex = null;
};

const markAsApproved = () => {
    confirmDialogBox('Are you sure you want to mark as approved?', () => {
        router.post(route(props.submitShippingUrl, props.stockTransferId), {
            stock_transfer_items: getPreparedStockTransferItems(),
        });
    });
};

const getPreparedStockTransferItems = () => {
    return state.stockTransferItems.map((stockTransferItem) => {
        return {
            id: stockTransferItem.id,
            package_type_id: stockTransferItem.package_type_id,
            package_quantity: stockTransferItem.package_quantity,
            package_total_quantity: stockTransferItem.package_total_quantity,
            batch_details: stockTransferItem.batch_details,
        };
    });
};

onMounted(() => {
    if (props.stockTransferItems) {
        Object.assign(state.stockTransferItems, props.stockTransferItems.data);
    }
});
</script>
