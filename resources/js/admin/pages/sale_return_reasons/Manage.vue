<template>
    <PageTitle :title="saleReturnReason ? 'Edit Sale Return Reason' : 'Add Sale Return Reason'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Return Codes
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="saleReturnReason">Edit Sale Return Reason</span>
                        <span v-else>Add Sale Return Reason</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveSaleReturnReason();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleReturnReasonForm.reason"
                                    input-name="reason"
                                    input-label="Reason"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    :is-checked="saleReturnReasonForm.put_back_in_inventory"
                                    input-label="Put back in inventory"
                                    validation-field-name="put_back_in_inventory"
                                    :required="true"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                    @update:is-checked="updatePutBackInInventory($event)"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="state.types"
                                    :records="types"
                                    input-label="Types"
                                    :required="true"
                                    validation-field-name="type_ids"
                                />
                            </div>
                            <div
                                v-show="! saleReturnReasonForm.put_back_in_inventory"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-3"
                            >
                                <JTabs
                                    :records="locationTypes"
                                    :selected-record="saleReturnReasonForm.type_id"
                                    :required="true"
                                    return-selected-record="id"
                                    input-label="Location"
                                    @update:selected-record="updateLocationType"
                                >
                                    <TabPanel
                                        v-if="saleReturnReasonForm.type_id === staticLocationTypes.store"
                                        class="active"
                                    >
                                        <FormSelectBox
                                            :selected-record="saleReturnReasonForm.location_id"
                                            :records="stores"
                                            validation-field-name="location_id"
                                            placeholder="Please select store"
                                            @update:selected-record="updateLocationId"
                                        />
                                    </TabPanel>

                                    <TabPanel
                                        v-if="saleReturnReasonForm.type_id === staticLocationTypes.warehouse"
                                        class="active"
                                    >
                                        <FormSelectBox
                                            :selected-record="saleReturnReasonForm.location_id"
                                            :records="warehouses"
                                            validation-field-name="location_id"
                                            placeholder="Please select warehouse"
                                            @update:selected-record="updateLocationId"
                                        />
                                    </TabPanel>
                                </JTabs>
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.sale_return_reasons.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="saleReturnReason ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
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
    locationTypes: {
        type: Object,
        required: true,
    },
    saleReturnReason: {
        type: Object,
        default: null,
    },
    types: {
        type: Object,
        required: true,
    },
    staticTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
});

const saleReturnReasonForm = useForm({
    reason: null,
    type_id: props.staticLocationTypes.store,
    location_id: null,
    put_back_in_inventory: true,
    type_ids: [],
});

const state = reactive({
    types: [],
});

const updateLocationType = (typeId) => {
    saleReturnReasonForm.type_id = typeId;
    saleReturnReasonForm.location_id = null;
};

const updateLocationId = (locationId) => {
    saleReturnReasonForm.location_id = parseInt(locationId);
};

const saveSaleReturnReason = () => {
    prepareSaleReturnReasonFormDetails();

    if (props.saleReturnReason) {
        saleReturnReasonForm.put(route('admin.sale_return_reasons.update', props.saleReturnReason.id));
        return;
    }
    saleReturnReasonForm.post(route('admin.sale_return_reasons.store'));
};

const updatePutBackInInventory = (event) => {
    saleReturnReasonForm.put_back_in_inventory = event;

    if (saleReturnReasonForm.put_back_in_inventory) {
        saleReturnReasonForm.type_id = props.staticLocationTypes.store;
        saleReturnReasonForm.location_id = null;
        return;
    }

    updateLocationType(props.staticLocationTypes.store);
};

const prepareSaleReturnReasonFormDetails = () => {
    saleReturnReasonForm.type_ids = state.types.map((type) => {
        return type.id;
    });
};

onMounted(() => {
    if (props.saleReturnReason) {
        Object.assign(saleReturnReasonForm, props.saleReturnReason);
        state.types = props.saleReturnReason.types;
    }
});
</script>
