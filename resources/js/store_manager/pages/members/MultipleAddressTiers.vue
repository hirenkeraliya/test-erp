<template>
    <h2 class="font-medium text-base ml-5 mt-2">
        Member Address Details
    </h2>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6 px-5"
    >
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.name"
                input-label="Name"
                :validation-field-name="'member_addresses.' + index + '.name'"
                @update:input-value="updateTierValueDetails($event, index, 'name')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.first_name"
                input-label="First Name"
                :validation-field-name="'member_addresses.' + index + '.first_name'"
                @update:input-value="updateTierValueDetails($event, index, 'first_name')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.last_name"
                input-label="Name"
                :validation-field-name="'member_addresses.' + index + '.last_name'"
                @update:input-value="updateTierValueDetails($event, index, 'last_name')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.contact_mobile_number"
                input-label="Contact Mobile Number"
                :required="true"
                :validation-field-name="'member_addresses.' + index + '.contact_mobile_number'"
                @update:input-value="updateTierValueDetails($event, index, 'contact_mobile_number')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.contact_email"
                input-label="Contact Email"
                :validation-field-name="'member_addresses.' + index + '.contact_email'"
                @update:input-value="updateTierValueDetails($event, index, 'contact_email')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.address_line_1"
                input-label="Address Line 1"
                :required="true"
                :validation-field-name="'member_addresses.' + index + '.address_line_1'"
                @update:input-value="updateTierValueDetails($event, index, 'address_line_1')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.address_line_2"
                input-label="Address Line 2"
                :validation-field-name="'member_addresses.' + index + '.address_line_2'"
                @update:input-value="updateTierValueDetails($event, index, 'address_line_2')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.country_id"
                input-label="Country"
                :records="countries"
                :validation-field-name="'member_addresses.' + index + '.country_id'"
                @update:selected-record="handleCountryChange(index, $event)"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.state_id"
                input-label="State"
                :records="tier.availableStates || []"
                :disabled="!tier.country_id"
                :validation-field-name="'member_addresses.' + index + '.state_id'"
                @update:selected-record="handleStateChange(index, $event)"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.city_id"
                input-label="City"
                :records="tier.availableCities || []"
                :disabled="!tier.state_id"
                :validation-field-name="'member_addresses.' + index + '.city_id'"
                @update:selected-record="handleCityChange(index, $event)"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
            <FormInput
                v-model:input-value="tier.city_name"
                input-label="City Name (if not in list)"
                label-class="form-label w-full flex flex-col sm:flex-row"
                :validation-field-name="'member_addresses.' + index + '.city_name'"
                @update:input-value="updateTierValueDetails(key, 'city_name', index)"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                :input-value="tier.area_code"
                input-label="Area Code"
                :required="true"
                :validation-field-name="'member_addresses.' + index + '.area_code'"
                @update:input-value="updateTierValueDetails($event, index, 'area_code')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="flex">
                <JSwitch
                    input-label="Is Primary?"
                    :is-checked="tier.is_primary"
                    :disabled="tier.is_primary"
                    :validation-field-name="'member_addresses.' + index + '.is_primary'"
                    class="mt-6"
                    @update:is-checked="updateColumnIsPrimaryRequired('is_primary', index, $event)"
                />

                <DeleteButton
                    type="button"
                    class="sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8 ml-6"
                    :disabled="tiers.length <= 1 || tier.is_primary"
                    @click="removeTierDetailsOf(index, tier.id)"
                />
            </div>
        </div>
        <div
            v-if="tiers.length > 1"
            class="col-span-12"
        >
            <hr class="my-4 border-gray-300">
        </div>
    </div>

    <div class="grid grid-flow-col grid-rows-1 gap-2 px-5">
        <OutlinePrimaryButton
            text="+ Add Member Address"
            type="button"
            class="border-dashed mt-4"
            @click="addNewTierDetails()"
        />
    </div>
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import axios from 'axios';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { onMounted } from 'vue';

const props = defineProps({
    tiers: {
        type: Object,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:tier-value-details',
    'add:new-tier-details',
    'remove:tier-details-of',
]);

const updateTierValueDetails = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: event,
        column_name: columnName,
    });
};

const addNewTierDetails = () => {
    emits('add:new-tier-details');
};

const removeTierDetailsOf = (index, memberAddressId) => {
    if (memberAddressId) {
        axios.get(route('store_manager.members.delete_member_address', memberAddressId));
    }

    emits('remove:tier-details-of', index);
};

const updateColumnIsPrimaryRequired = (columnName, index, event) => {
    if (!event) {
        return;
    }
    props.tiers.forEach((address, i) => {
        if (i === index) {
            address.is_primary = true;
            address.is_disabled = true;
        } else {
            address.is_primary = false;
            address.is_disabled = false;
        }
    });
    updateTierValueDetails(event, index, columnName);
};

const handleCountryChange = async (index, value, status = true) => {
    const address = props.tiers[index];
    address.country_id = value;
    if (status) {
        address.state_id = null;
        address.city_id = null;
        address.city_name = null;
    }

    if (value) {
        const states = await getStatesForCountry(value);
        address.availableStates = states;
    }
};

const handleStateChange = async (index, value, status = true) => {
    const address = props.tiers[index];
    address.state_id = value;
    if (status) {
        address.city_id = null;
        address.city_name = null;
    }

    if (value) {
        const cities = await getCitiesForState(value);
        address.availableCities = cities;
    }
};

const handleCityChange = (index, value) => {
    const address = props.tiers[index];
    address.city_id = value;
    if (value && address.availableCities) {
        const selectedCity = address.availableCities.find(city => city.id === value);
        if (selectedCity) {
            address.city_name = selectedCity.name;
        }
    }
};

const getStatesForCountry = async (countryId) => {
    try {
        const response = await axios.get(route('store_manager.states.get_states', countryId));

        return response.data.states;
    } catch (error)  {
        return error;
    }
};

const getCitiesForState = async (stateId) => {
    try {
        const response = await axios.get(route('store_manager.cities.get_cities', stateId));
        return response.data.cities;
    } catch (error) {
        return error;
    }
};

onMounted(() => {
    if (props.tiers) {
        let timeOut = 500;
        setTimeout(() => {
            props.tiers.forEach((address, index) => {
                if (address.country_id) {
                    handleCountryChange(index, address.country_id, false);
                }
                if (address.state_id) {
                    handleStateChange(index, address.state_id, false);
                }
                if (address.city_id) {
                    handleCityChange(index, address.city_id);
                }
            });
        }, timeOut);
    }
});
</script>
