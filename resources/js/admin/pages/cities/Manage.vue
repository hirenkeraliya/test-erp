<template>
    <PageTitle :title="city ? 'Edit City' : 'Add City'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cities
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ city ? 'Edit' : 'Add' }} City
                    </h2>
                </div>
                <form @submit.prevent="saveCity();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="cityForm.country_id"
                                    :records="countries"
                                    input-label="Country"
                                    :required="true"
                                    validation-field-name="country_id"
                                    @update:selected-record="fetchStates"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="cityForm.state_id"
                                    :records="state.states"
                                    input-label="State"
                                    :required="true"
                                    validation-field-name="state_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="cityForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div
                                    v-if="city"
                                    class="mt-4"
                                >
                                    <div class="input-group">
                                        <label>
                                            Country Code:
                                        </label>
                                    </div>
                                    <div class="font-medium">
                                        {{ city.country_code }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.cities.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="city ? 'Update' : 'Submit'"
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
import { onMounted } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
    city: {
        type: Object,
        default: null,
    },
    countries: {
        type: Array,
        required: true,
    },
    states: {
        type: Array,
        default: () => {},
    },
});

const state = reactive({
    states: props.states ? props.states : [],
});

const cityForm = useForm({
    country_id: null,
    state_id: null,
    name: null,
});

const saveCity = () => {
    if (props.city) {
        cityForm.put(route('admin.cities.update', props.city.id));
        return;
    }
    cityForm.post(route('admin.cities.store'));
};

onMounted(() => {
    if (props.city) {
        Object.assign(cityForm, props.city);
    }
});

const fetchStates = () => {
    state.states = [];
    cityForm.state_id = null;
    if (cityForm.country_id) {
        axios.get(route('admin.states.get_states', cityForm.country_id)).then((response) => {
            state.states = response.data.states;
        }).catch(() => {
            state.states = [];
        });
    }
};
</script>
