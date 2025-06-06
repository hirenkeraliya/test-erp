<template>
    <PageTitle title="Add Stock Adjustment" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Adjustments
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        Add Stock Adjustment
                    </h2>
                </div>
                <div class="p-5">
                    <div class="text-lg font-bold text-orange-600 mb-3">
                        Important Instructions
                    </div>
                    <p> 1) The stock of a product cannot be below 0 after the adjustment. </p>
                    <p> 2) Batch Number &amp; Batch Expiry date is required for the batch products.</p>
                    <p> 3) If batch details are provided:</p>
                    <ul class="px-6 h-auto pr-2.5 list-disc ">
                        <li class="py-1 items-center justify-between cursor-pointer">
                            When adding/removing inventory units, they will be assigned the specified batch
                        </li>
                        <li class="py-1 items-center justify-between cursor-pointer">
                            When removing new inventory units, the ones with the specified batch will be removed.
                        </li>
                    </ul>
                    <p> 4. Product is belongs to UOM derivative_name value is required.</p>
                    <p>
                        5. While adding derivative product quantities, make sure to verify UOM & derivatives ratio. Accordingly quantity will be added.<br>
                        <b class="mt-2 inline-block">Example:</b> <br> UOM is <b>Meter</b> & derivatives is <b>Centimeter</b> with 100 ratio set.
                        Quantity is 5000 mentioned. <br>
                        So final quantity will be store 5000/100 = 50 quantity.
                    </p>
                    <p>
                        FIFO (First In, First Out) method will be used to remove inventory units of non-batch products.
                    </p>
                </div>

                <form @submit.prevent="saveStockAdjustment();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="stockAdjustmentForm.approved_by_employee_id"
                                    :records="employees"
                                    input-label="Approved By Employee"
                                    :required="true"
                                    validation-field-name="approved_by_employee_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="stockAdjustmentForm.type_id"
                                    :records="stockAdjustmentTypes"
                                    input-label="Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                    @update:selected-record="updateStockAdjustmentType"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="stockAdjustmentForm.reason"
                                    input-name="reason"
                                    input-label="Reason"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="stockAdjustmentForm.adjustment_date"
                                    input-label="Date"
                                />
                            </div>
                            <div
                                v-if="stockAdjustmentForm.type_id !== null"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                            >
                                <FileUploadAndDisplayRecords
                                    :selected-products="state.selectedProducts"
                                    :unmatched-products="state.unmatchedProducts"
                                    product-upc-url="admin.products.get_matching_upc_inventory_products"
                                    get-record-name="quantity"
                                    input-label="Upload Stock Adjustment File"
                                    validation-field-name="uploaded_file"
                                    :file-path="state.filePath"
                                    @display-selected-products-modal="openSelectedProductsModal"
                                    @update:column-details="updateColumnDetails"
                                    @display-unmatched-products-modal="openUnmatchedProductsModal"
                                    @get-upload-file="getUploadFile"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.stock_adjustments.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                text="Submit"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <SelectedProducts
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        @close-modal="closeModal"
    />

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        @close-modal="closeModal"
    />
</template>
<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { route } from 'ziggy';
import { reactive } from 'vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';

const stockAdjustmentForm = useForm({
    reason: null,
    approved_by_employee_id: null,
    adjustment_date: null,
    type_id: null,
    uploaded_file: null,
});

const props = defineProps({
    stockAdjustmentTypes: {
        type: Object,
        required: true,
    },
    employees: {
        type: Object,
        required: true,
    },
    stockAdjustmentStaticDetails: {
        type: Object,
        required: true,
    }
});

const state = reactive({
    filePath: null,
    fields: [
        {
            key: 'name',
        },
        {
            key: 'quantity',
        },
    ],

    selectedProducts: [],
    unmatchedProducts: [],
    displayUnmatchedProductsModal: false,
    displaySelectedProductsModal: false,
});

const updateStockAdjustmentType = (typeId) => {
    stockAdjustmentForm.type_id = typeId;

    state.filePath = '/files/stock-adjustments-sample-file-stock-in.xlsx';
    if (typeId === props.stockAdjustmentStaticDetails.sto) {
        state.filePath = '/files/stock-adjustments-sample-file-stock-out.xlsx';
    }
};

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const getUploadFile = (fileName) => {
    stockAdjustmentForm.uploaded_file = fileName;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
};

const closeModal = () => {
    if (state.displaySelectedProductsModal) {
        state.displaySelectedProductsModal = false;
        return;
    }

    if (state.displayUnmatchedProductsModal) {
        state.displayUnmatchedProductsModal = false;
    }
};

const saveStockAdjustment = () => {
    stockAdjustmentForm.post(route('admin.stock_adjustments.store'));
};
</script>
