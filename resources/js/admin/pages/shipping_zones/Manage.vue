<template>
    <PageTitle :title="shippingZone ? 'Edit Shipping Zone' : 'Add Shipping Zone'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Shipping Zones
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ shippingZone ? 'Edit' : 'Add' }} Shipping Zone
                    </h2>
                </div>
                <form @submit.prevent="saveShippingZone();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="shippingZoneForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="shippingZoneForm.country_id"
                                    :records="countries"
                                    input-label="Country"
                                    :required="true"
                                    validation-field-name="country_id"
                                    @update:selected-record="fetchStates"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 -mt-2">
                                <JMultiSelect
                                    :selected-records="shippingZoneForm.selected_states"
                                    :records="state.states"
                                    input-label="States"
                                    label-class="block font-medium text-base text-primary-p3"
                                    placeholder="Please select States"
                                    :required="true"
                                    validation-field-name="state_ids"
                                    @update:selected-records="updateStates"
                                />
                            </div>

                            <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
                                <PrimaryButton
                                    v-if="state.states.length > 0"
                                    type="button"
                                    text="Select all"
                                    class="w-auto sm:w-24 md:w-1/1"
                                    @click="selectAllStates"
                                />

                                <PrimaryButton
                                    v-if="shippingZoneForm.selected_states.length > 0"
                                    type="button"
                                    text="Clear All"
                                    class="w-auto sm:w-24 md:w-1/1 mt-2"
                                    @click="clearAllStates"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.shipping_zones.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="shippingZone ? 'Update' : 'Submit'"
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
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';

const props = defineProps({
    shippingZone: {
        type: Object,
        default: null,
    },
    countries: {
        type: Array,
        required: true,
    },
});

const shippingZoneForm = useForm({
    name: null,
    country_id: null,
    state_ids: [],
    selected_states: [],
});

const state = reactive({
    states: [],
});

const fetchStates = () => {
    state.states = [];
    shippingZoneForm.selected_states = [];
    if (shippingZoneForm.country_id) {
        axios.get(route('admin.states.get_states', shippingZoneForm.country_id)).then((response) => {
            state.states = response.data.states;
        });
    }
};

const updateStates = (states) => {
    shippingZoneForm.selected_states = states;
};

const selectAllStates = () => {
    shippingZoneForm.selected_states = state.states;
};

const clearAllStates = () => {
    shippingZoneForm.selected_states = [];
    shippingZoneForm.state_ids = [];
};

const saveShippingZone = () => {
    shippingZoneForm.state_ids = shippingZoneForm.selected_states.map((state) => {
        return state.id;
    });

    if (props.shippingZone) {
        shippingZoneForm.post(route('admin.shipping_zones.update', props.shippingZone.data.id));
        return;
    }
    shippingZoneForm.post(route('admin.shipping_zones.store'));
};

onMounted(() => {
    if (props.shippingZone) {
        state.states = props.shippingZone.data.states;
        Object.assign(shippingZoneForm, props.shippingZone.data);
    }
});
</script>
