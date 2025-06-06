<template>
    <PageTitle title="Add Goods Received Note" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Goods Received Notes
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        Add Goods Received Note
                    </h2>
                </div>
                <div class="p-5">
                    <div class="text-lg font-bold text-orange-600 mb-3">
                        Instructions
                    </div>
                    <p> 1. GRN Reference will be generated automatically.</p>
                    <p> 2. For products with UOM, derivative name value is mandatory .</p>
                    <p>
                        3. Please be mindful of UOM derivative ratio for derivative products as the quantity will be added  accordingly.<br>
                        4. The product must be of the serial type, not a batch type, and it should not have a UOM (Unit of Measurement) when entering the serial number. <br> The serial number should consist of alphanumeric characters, and the quantity must be 1.
                        <b class="mt-2 inline-block">Example:</b> <br> Lets assume UOM is <b>Meter</b> & derivatives is <b>Centimeter</b> with ratio set at 100.
                        If Quantity received is marked as 5000cm system will record as 50m (5000/100). <br>
                    </p>
                </div>

                <form @submit.prevent="saveGoodsReceivedNote();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="mt-3">
                                    <JTabs
                                        :records="locationTypes"
                                        :selected-record="goodsReceivedNoteForm.type_id"
                                        :required="true"
                                        return-selected-record="id"
                                        input-label="Location"
                                        @update:selected-record="updateLocationType"
                                    />
                                </div>
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <TabPanel
                                    v-if="goodsReceivedNoteForm.type_id === staticLocationTypes.store"
                                    class="active"
                                >
                                    <FormSelectBox
                                        v-model:selected-record="goodsReceivedNoteForm.location_id"
                                        :records="stores"
                                        :required="true"
                                        validation-field-name="location_id"
                                        placeholder="Please select store"
                                        input-label="Stores"
                                    />
                                </TabPanel>

                                <TabPanel
                                    v-if="goodsReceivedNoteForm.type_id === staticLocationTypes.warehouse"
                                    class="active"
                                >
                                    <FormSelectBox
                                        v-model:selected-record="goodsReceivedNoteForm.location_id"
                                        :records="warehouses"
                                        :required="true"
                                        validation-field-name="location_id"
                                        placeholder="Please select warehouse"
                                        input-label="Warehouses"
                                    />
                                </TabPanel>
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="goodsReceivedNoteForm.purchase_order_reference"
                                    input-name="purchase_order_reference"
                                    input-label="Purchase Order Reference"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="goodsReceivedNoteForm.delivery_order_reference"
                                    input-name="delivery_order_reference"
                                    input-label="Delivery Order Reference"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="goodsReceivedNoteForm.notes"
                                    input-name="notes"
                                    input-label="Notes"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="goodsReceivedNoteForm.vendor_id"
                                    :records="vendors"
                                    input-label="Vendor"
                                    validation-field-name="vendor_id"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                <FileUploadAndDisplayRecords
                                    :selected-products="state.selectedProducts"
                                    :unmatched-products="state.unmatchedProducts"
                                    product-upc-url="admin.products.get_matching_upc_inventory_products"
                                    get-record-name="quantity"
                                    input-label="Upload File"
                                    validation-field-name="uploaded_file"
                                    file-path="/files/goods-received-note-products-sample-file.xlsx"
                                    @display-selected-products-modal="openSelectedProductsModal"
                                    @update:column-details="updateColumnDetails"
                                    @display-unmatched-products-modal="openUnmatchedProductsModal"
                                    @get-upload-file="getUploadFile"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.goods_received_notes.index')">
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
import FormTextarea from '@commonComponents/FormTextarea.vue';
import { route } from 'ziggy';
import { reactive } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';

const props = defineProps({
    vendors: {
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
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const goodsReceivedNoteForm = useForm({
    purchase_order_reference: null,
    delivery_order_reference: null,
    notes: null,
    uploaded_file: null,
    vendor_id: null,
    type_id: props.staticLocationTypes.store,
    location_id: null,
});

const state = reactive({
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

const updateLocationType = (typeId) => {
    goodsReceivedNoteForm.type_id = typeId;
    goodsReceivedNoteForm.location_id = null;
};

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const getUploadFile = (fileName) => {
    goodsReceivedNoteForm.uploaded_file = fileName;
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

const saveGoodsReceivedNote = () => {
    goodsReceivedNoteForm.post(route('admin.goods_received_notes.store'));
};

</script>
