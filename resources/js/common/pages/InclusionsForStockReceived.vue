<template>
    <FormCustomCheckbox
        check-box-label="Inclusions For Stock Received:"
        :records="sellThroughIncludeTypes"
        :selected-records="selectedSellThroughIncludeTypes"
        check-box-class="col-span-4 items-center"
        check-box-label-class="col-span-4 items-center mb-2"
        @update:check-values="updateAccumulatedSellThroughIncludeTypes"
    >
        <template #goods_receive_note_in="goodsReceiveNoteIn">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(goodsReceiveNoteIn.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.goods_receive_note_in_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateGoodsReceivedNoteInLocationType"
                    />

                    <TabPanel
                        v-if="state.goods_receive_note_in_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByGoodsReceiveNoteInLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByGoodsReceiveNoteInLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.goods_receive_note_in_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByGoodsReceiveNoteInLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByGoodsReceiveNoteInLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #goods_receive_note_out="goodsReceiveNoteId">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(goodsReceiveNoteId.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.goods_receive_note_out_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateGoodsReceivedNoteOutLocationType"
                    />

                    <TabPanel
                        v-if="state.goods_receive_note_out_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByGoodsReceiveNoteOutLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByGoodsReceiveNoteOutLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.goods_receive_note_out_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByGoodsReceiveNoteOutLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByGoodsReceiveNoteOutLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #stock_adjustment_in="stockAdjustmentIn">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(stockAdjustmentIn.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.stock_adjustment_in_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateStockAdjustmentInLocationType"
                    />

                    <TabPanel
                        v-if="state.stock_adjustment_in_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockAdjustmentInLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockAdjustmentInLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.stock_adjustment_in_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockAdjustmentInLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockAdjustmentInLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #stock_adjustment_out="stockAdjustmentOut">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(stockAdjustmentOut.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.stock_adjustment_out_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateStockAdjustmentOutLocationType"
                    />

                    <TabPanel
                        v-if="state.stock_adjustment_out_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockAdjustmentOutLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockAdjustmentOutLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.stock_adjustment_out_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockAdjustmentOutLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockAdjustmentOutLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #stock_transfer_in="stockTransferIn">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(stockTransferIn.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.stock_transfer_in_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateStockTransferInLocationType"
                    />

                    <TabPanel
                        v-if="state.stock_transfer_in_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockTransferInLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockTransferInLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.stock_transfer_in_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockTransferInLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockTransferInLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #stock_transfer_out="stockTransferOut">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(stockTransferOut.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.stock_transfer_out_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateStockTransferOutLocationType"
                    />

                    <TabPanel
                        v-if="state.stock_transfer_out_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockTransferOutLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockTransferOutLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.stock_transfer_out_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByStockTransferOutLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByStockTransferOutLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #delivery_order_in="deliveryOrderIn">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(deliveryOrderIn.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.delivery_order_in_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateDeliveryOrderInLocationType"
                    />

                    <TabPanel
                        v-if="state.delivery_order_in_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByDeliveryOrderInLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByDeliveryOrderInLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.delivery_order_in_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByDeliveryOrderInLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByDeliveryOrderInLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>

        <template #delivery_order_out="deliveryOrderOut">
            <div
                v-if="selectedSellThroughIncludeTypes.includes(deliveryOrderOut.item)"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-4"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.delivery_order_out_type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateDeliveryOrderOutLocationType"
                    />

                    <TabPanel
                        v-if="state.delivery_order_out_type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByDeliveryOrderOutLocationIds"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByDeliveryOrderOutLocationIds"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.delivery_order_out_type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.includesByDeliveryOrderOutLocationIds"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="includesByDeliveryOrderOutLocationIds"
                        />
                    </TabPanel>
                </div>
            </div>
        </template>
    </FormCustomCheckbox>
</template>

<script setup>
import FormCustomCheckbox from '@commonComponents/FormCustomCheckbox.vue';
import { reactive } from 'vue';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    sellThroughIncludeTypes: {
        type: Array,
        required: true,
    },
    selectedSellThroughIncludeTypes: {
        type: Object,
        required: true,
    },
    staticAccumulatedSellThroughIncludeTypes: {
        type: Object,
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
});

const emits = defineEmits([
    'update-accumulated-sell-through-include-types',
    'includes-by-goods-receive-note-in-location-ids',
    'clear-data-for-goods-received-note-in',
    'includes-by-goods-receive-note-out-location-ids',
    'clear-data-for-goods-received-note-out',
    'includes-by-stock-adjustment-in-location-ids',
    'clear-data-for-stock-adjustment-in',
    'includes-by-stock-adjustment-out-location-ids',
    'clear-data-for-stock-adjustment-out',
    'includes-by-stock-transfer-in-location-ids',
    'clear-data-for-stock-transfer-in',
    'includes-by-stock-transfer-out-location-ids',
    'clear-data-for-stock-transfer-out',
    'includes-by-delivery-order-in-location-ids',
    'clear-data-for-delivery-order-in',
    'includes-by-delivery-order-out-location-ids',
    'clear-data-for-delivery-order-out',
]);

const state = reactive({
    includesByGoodsReceiveNoteInLocationIds: [],
    includesByGoodsReceiveNoteOutLocationIds: [],

    includesByStockAdjustmentInLocationIds: [],
    includesByStockAdjustmentOutLocationIds: [],

    includesByStockTransferInLocationIds: [],
    includesByStockTransferOutLocationIds: [],

    includesByDeliveryOrderInLocationIds: [],
    includesByDeliveryOrderOutLocationIds: [],

    goods_receive_note_in_type_id: props.staticLocationTypes.store,
    goods_receive_note_out_type_id: props.staticLocationTypes.store,
    stock_adjustment_in_type_id: props.staticLocationTypes.store,
    stock_adjustment_out_type_id: props.staticLocationTypes.store,
    stock_transfer_in_type_id: props.staticLocationTypes.store,
    stock_transfer_out_type_id: props.staticLocationTypes.store,
    delivery_order_in_type_id: props.staticLocationTypes.store,
    delivery_order_out_type_id: props.staticLocationTypes.store,
});

const updateGoodsReceivedNoteInLocationType = (locationType) => {
    state.goods_receive_note_in_type_id = locationType;

    emits('clear-data-for-goods-received-note-in');
    state.includesByGoodsReceiveNoteInLocationIds = [];
};

const updateGoodsReceivedNoteOutLocationType = (locationType) => {
    state.goods_receive_note_out_type_id = locationType;

    emits('clear-data-for-stock-adjustment-in');
    state.includesByGoodsReceiveNoteOutLocationIds = [];
};

const updateStockAdjustmentInLocationType = (locationType) => {
    state.stock_adjustment_in_type_id = locationType;

    emits('clear-data-for-stock-adjustment-in');
    state.includesByStockAdjustmentInLocationIds = [];
};

const updateDeliveryOrderInLocationType = (locationType) => {
    state.delivery_order_in_type_id = locationType;

    emits('clear-data-for-delivery-order-in');
    state.includesByDeliveryOrderInLocationIds = [];
};

const updateStockTransferInLocationType = (locationType) => {
    state.stock_transfer_in_type_id = locationType;

    emits('clear-data-for-stock-transfer-in');
    state.includesByStockTransferInLocationIds = [];
};

const updateStockAdjustmentOutLocationType = (locationType) => {
    state.stock_adjustment_out_type_id = locationType;

    emits('clear-data-for-stock-adjustment-out');
    state.includesByStockAdjustmentOutLocationIds = [];
};

const updateDeliveryOrderOutLocationType = (locationType) => {
    state.delivery_order_out_type_id = locationType;

    emits('clear-data-for-delivery-order-out');
    state.includesByDeliveryOrderOutLocationIds = [];
};

const updateStockTransferOutLocationType = (locationType) => {
    state.stock_transfer_out_type_id = locationType;

    emits('clear-data-for-stock-transfer-out');
    state.includesByStockTransferOutLocationIds = [];
};

const includesByDeliveryOrderInLocationIds = (locations) => {
    state.includesByDeliveryOrderInLocationIds = locations;

    if (locations.length <= 0) {
        return emits('includes-by-delivery-order-in-location-ids', []);
    }

    const locationIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-delivery-order-in-location-ids', locationIds);
};

const includesByDeliveryOrderOutLocationIds = (locations) => {
    state.includesByDeliveryOrderOutLocationIds = locations;

    if (locations.length <= 0) {
        return emits('includes-by-delivery-order-out-location-ids', []);
    }

    const locationIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-delivery-order-out-location-ids', locationIds);
};

const includesByGoodsReceiveNoteInLocationIds = (locations) => {
    state.includesByGoodsReceiveNoteInLocationIds = locations;

    if (locations.length <= 0) {
        return emits('includes-by-goods-receive-note-in-location-ids', []);;
    }

    const locationIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-goods-receive-note-in-location-ids', locationIds);
};

const includesByGoodsReceiveNoteOutLocationIds = (locations) => {
    state.includesByGoodsReceiveNoteOutLocationIds = locations;

    if (locations.length <= 0) {
        return emits('includes-by-goods-receive-note-out-location-ids', []);;
    }

    const locationIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-goods-receive-note-out-location-ids', locationIds);
};

const includesByStockAdjustmentInLocationIds = (locations) => {
    state.includesByStockAdjustmentInLocationIds = locations;

    if (locations.length <= 0) {
        emits('includes-by-stock-adjustment-in-location-ids', []);
    }

    const locationIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-stock-adjustment-in-location-ids', locationIds);
};

const includesByStockTransferInLocationIds = (locations) => {
    state.includesByStockTransferInLocationIds = locations;

    if (locations.length <= 0) {
        emits('includes-by-stock-transfer-in-location-ids', []);
    }

    const locationsIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-stock-transfer-in-location-ids', locationsIds);
};

const includesByStockAdjustmentOutLocationIds = (locations) => {
    state.includesByStockAdjustmentOutLocationIds = locations;

    if (locations.length <= 0) {
        return emits('includes-by-stock-adjustment-out-location-ids', []);
    }

    const locationIds = locations.map(function (location) {
        return location.id;
    });


    emits('includes-by-stock-adjustment-out-location-ids', locationIds);
};

const includesByStockTransferOutLocationIds = (locations) => {
    state.includesByStockTransferOutLocationIds = locations;

    if (locations.length <= 0) {
        return emits('includes-by-stock-transfer-out-location-ids', []);
    }

    const locationsIds = locations.map(function (location) {
        return location.id;
    });

    emits('includes-by-stock-transfer-out-location-ids', locationsIds);
};

const updateAccumulatedSellThroughIncludeTypes = (accumulatedSellThroughIncludeTypes) => {
    state.accumulatedSellThroughIncludeTypes = accumulatedSellThroughIncludeTypes;

    const difference = state.accumulatedSellThroughIncludeTypes.filter(accumulatedSaleThroughIncludeType => !accumulatedSellThroughIncludeTypes.includes(accumulatedSaleThroughIncludeType));

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.goodsReceiveNoteIn)) {
        emits('clear-data-for-goods-received-note-in');
        state.includesByGoodsReceiveNoteInLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.goodsReceiveNoteOut)) {
        emits('clear-data-for-goods-received-note-out');
        state.includesByGoodsReceiveNoteOutLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.stockAdjustmentIn)) {
        emits('clear-data-for-stock-adjustment-in');
        state.includesByStockAdjustmentInLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.stockAdjustmentOut)) {
        emits('clear-data-for-stock-adjustment-out');
        state.includesByStockAdjustmentOutLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.stockTransferIn)) {
        emits('clear-data-for-stock-transfer-in');
        state.includesByStockTransferInLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.stockTransferOut)) {
        emits('clear-data-for-goods-received-note-out');
        state.includesByStockTransferOutLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.deliveryOrderIn)) {
        emits('clear-data-for-delivery-order-in');
        state.includesByStockAdjustmentInLocationIds = [];
    }

    if (difference.includes(props.staticAccumulatedSellThroughIncludeTypes.deliveryOrderOut)) {
        emits('clear-data-for-delivery-order-out');
        state.includesByStockAdjustmentOutLocationIds = [];
    }

    emits('update-accumulated-sell-through-include-types', accumulatedSellThroughIncludeTypes);
};
</script>