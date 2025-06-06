<template>
    <Modal
        size="modal-xs"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Selected Shipped Types
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <FormSelectBox
                class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60 mb-2"
                :selected-record="state.shippedType"
                :records="shippedTypes"
                :placeholder="'Please select shipped type'"
                @update:selected-record="getShippedTransit"
            />

            <span v-if="state.shippedType === shippedTransit">
                <JTabs
                    :records="state.locationTypes"
                    :selected-record="state.locationType"
                    :required="true"
                    @update:selected-record="updateLocationType"
                >
                    <TabPanel
                        v-if="state.locationType === 'Store'"
                        class="active"
                    >
                        <FormSelectBox
                            v-model:selected-record="state.locationId"
                            :records="filteredStores"
                            placeholder="Please select store"
                            input-label="Stores"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.locationType === 'Warehouse'"
                        class="active"
                    >
                        <FormSelectBox
                            v-model:selected-record="state.locationId"
                            :records="filteredWarehouses"
                            placeholder="Please select warehouse"
                            input-label="Warehouses"
                        />
                    </TabPanel>
                </JTabs>
            </span>

            <div class="mt-5">
                <PrimaryButton
                    type="submit"
                    text="Submit"
                    class="w-24"
                    @click="updateShippedTransit()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { route } from 'ziggy';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import { showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    stockTransferId: {
        type: Number,
        required: true,
    },
    routeUrl: {
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
    filteredStores: {
        type: Array,
        required: true,
    },
    filteredWarehouses: {
        type: Array,
        required: true,
    },
    statusId: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    locationTypes: [
        { id: 'Store', name: 'Store' },
        { id: 'Warehouse', name: 'Warehouse' },
    ],
    locationType: 'Store',
    locationId: null,
    shippedType: null,
});

const emits = defineEmits([
    'close-modal',
]);

const closeModal = () => {
    emits('close-modal');
};

const getShippedTransit = (id) => {
    if (props.shippedTransit !== id) {
        state.locationId = null;
        state.locationType = null;
    }

    state.shippedType = id;
};

const updateLocationType = (locationType) => {
    state.locationType = locationType;
    state.locationId = null;
};

const updateShippedTransit = () => {
    const data = {
        shipped_type: state.shippedType,
        status_id: props.statusId,
    };

    if (props.shippedTransit === state.shippedType) {
        data.location_id = state.locationId;
    }

    const httpStatusOk = 200;

    axios.post(route(props.routeUrl, props.stockTransferId), data)
        .then((response) => {
            if (response.status === httpStatusOk) {
                closeModal();
            }
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

</script>
