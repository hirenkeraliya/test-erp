<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
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
            <form
                @submit.prevent="saveMemberAddresses();"
            >
                <div>
                    <div class="grid grid-cols-12 gap-0 sm:gap-6">
                        <div
                            v-for="(address, key) in memberAddressForm.member_addresses"
                            :key="key"
                            class="input-form col-span-12"
                        >
                            <div class="intro-y box p-5">
                                <DeleteButton
                                    class="float-right"
                                    @click="removeTierDetailsOf(key, address.id)"
                                />
                                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.name"
                                            input-name="name"
                                            input-label="Name"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            :required="true"
                                            @update:input-value="updateTierValueDetails(key, 'name', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.contact_mobile_number"
                                            input-name="contact_mobile_number"
                                            input-label="Contact Mobile Number"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            :required="true"
                                            :validation-field-name="'member_addresses.' + key + '.contact_mobile_number'"
                                            @update:input-value="updateTierValueDetails(key, 'contact_mobile_number', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.contact_email"
                                            input-name="contact_email"
                                            input-label="Contact Email"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            @update:input-value="updateTierValueDetails(key, 'contact_email', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.address_line_1"
                                            input-name="address_line_1"
                                            input-label="Address Line 1"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            :required="true"
                                            @update:input-value="updateTierValueDetails(key, 'address_line_1', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.address_line_2"
                                            input-name="address_line_2"
                                            input-label="Address Line 2"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            @update:input-value="updateTierValueDetails(key, 'address_line_2', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormSelectBox
                                            v-model:selected-record="address.country_id"
                                            input-name="country_id"
                                            input-label="Country"
                                            :records="countries"
                                            :validation-field-name="'member_addresses.' + key + '.country_id'"
                                            @update:selected-record="handleCountryChange(key, $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormSelectBox
                                            v-model:selected-record="address.state_id"
                                            input-name="state_id"
                                            input-label="State"
                                            :records="address.availableStates || []"
                                            :disabled="!address.country_id"
                                            :validation-field-name="'member_addresses.' + key + '.state_id'"
                                            @update:selected-record="handleStateChange(key, $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormSelectBox
                                            v-model:selected-record="address.city_id"
                                            input-name="city_id"
                                            input-label="City"
                                            :records="address.availableCities || []"
                                            :disabled="!address.state_id"
                                            :validation-field-name="'member_addresses.' + key + '.city_id'"
                                            @update:selected-record="handleCityChange(key, $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.city_name"
                                            input-name="city_name"
                                            input-label="City Name (if not in list)"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            @update:input-value="updateTierValueDetails(key, 'city_name', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <FormInput
                                            v-model:input-value="address.area_code"
                                            input-name="area_code"
                                            input-label="Area Code"
                                            label-class="form-label w-full flex flex-col sm:flex-row"
                                            :required="true"
                                            @update:input-value="updateTierValueDetails(key, 'area_code', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                        <JSwitch
                                            v-model:is-checked="address.is_primary"
                                            :disabled="address.is_disabled"
                                            input-label="Is Primary?"
                                            input-name="is_primary"
                                            :validation-field-name="'member_addresses.' + key + '.is_primary'"
                                            @update:is-checked="updateColumnIsPrimaryRequired('is_primary', key, $event)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-flow-col grid-rows-1 gap-4 mt-5">
                        <OutlinePrimaryButton
                            text="+ Add New Address"
                            type="button"
                            class="border-dashed"
                            @click="addNewAddress()"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <SecondaryButton
                        type="button"
                        text="Cancel"
                        class="w-24 mr-1"
                        @click="closeModal"
                    />

                    <PrimaryButton
                        type="submit"
                        text="Update"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { showSuccessNotification } from '@commonServices/notifier';
import { onMounted } from 'vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import axios from 'axios';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    memberAddresses: {
        type: Object,
        required: true,
    },
    memberId: {
        type: Number,
        required: true,
    },
    title: {
        type: String,
        default: 'Update Member Addresses'
    },
    countries: {
        type: Array,
        required: true,
    },
});

const memberAddressForm = useForm({
    member_addresses: [],
});

const saveMemberAddresses = () => {
    memberAddressForm.put(route('admin.members.update_member_addresses', props.memberId), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            showSuccessNotification('Member Addresses updated successfully.');
            closeModal();
        },
    });
};

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal', true);
};

const addNewAddress = () => {
    memberAddressForm.member_addresses.push({
        name: null,
        contact_mobile_number: null,
        contact_email: null,
        address_line_1: null,
        address_line_2: null,
        country_id: null,
        state_id: null,
        city_id: null,
        city_name: null,
        area_code: null,
        is_primary: false,
        is_disabled: false
    });
};

const removeTierDetailsOf = (key, memberAddressId) => {
    if (memberAddressId) {
        axios.get(route('admin.members.delete_member_address', memberAddressId));
    }
    memberAddressForm.member_addresses.splice(key, 1);
};

const updateTierValueDetails = (key, columnName, value) => {
    memberAddressForm.member_addresses[key][columnName] = value;

    // Clear dependent fields when country/state changes
    if (columnName === 'country_id') {
        memberAddressForm.member_addresses[key].state_id = null;
        memberAddressForm.member_addresses[key].city_id = null;
    } else if (columnName === 'state_id') {
        memberAddressForm.member_addresses[key].city_id = null;
    }
};

const updateColumnIsPrimaryRequired = (columnName, index, event) => {
    if (!event) {
        return;
    }
    memberAddressForm.member_addresses.forEach((address, i) => {
        if (i === index) {
            address.is_primary = true;
            address.is_disabled = true;
        } else {
            address.is_primary = false;
            address.is_disabled = false;
        }
    });
    updateTierValueDetails(index, columnName, event);
};

const getStatesForCountry = async (countryId) => {
    try {
        const response = await axios.get(route('admin.states.get_states', countryId));

        return response.data.states;
    } catch (error)  {
        return error;
    }
};

const getCitiesForState = async (stateId) => {
    try {
        const response = await axios.get(route('admin.cities.get_cities', stateId));
        return response.data.cities;
    } catch (error) {
        return error;
    }
};

const handleCountryChange = async (index, value, status = true) => {
    const address = memberAddressForm.member_addresses[index];
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
    const address = memberAddressForm.member_addresses[index];
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
    const address = memberAddressForm.member_addresses[index];
    address.city_id = value;
    if (value && address.availableCities) {
        const selectedCity = address.availableCities.find(city => city.id === value);
        if (selectedCity) {
            address.city_name = selectedCity.name;
        }
    }
};

onMounted(() => {
    if (props.memberAddresses) {
        memberAddressForm.member_addresses = props.memberAddresses;

        memberAddressForm.member_addresses.forEach((address, index) => {
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

    }
});

defineExpose({
    memberAddressForm,
});
</script>
