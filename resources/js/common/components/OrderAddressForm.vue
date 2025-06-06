<template>
    <Modal
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Update Order Address
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
            <form @submit.prevent="submitAddressForm()">
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                        <FormInput
                            v-model:input-value="OrderAddressForm.first_name"
                            input-name="first_name"
                            input-label="First Name"
                            :required="true"
                            label-class="form-label w-full flex flex-col sm:flex-row"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                        <FormInput
                            v-model:input-value="OrderAddressForm.last_name"
                            input-name="last_name"
                            input-label="Last Name"
                            label-class="form-label w-full flex flex-col sm:flex-row"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                        <FormInput
                            v-model:input-value="OrderAddressForm.phone"
                            input-name="phone"
                            input-label="Phone"
                            :required="true"
                            label-class="form-label w-full flex flex-col sm:flex-row"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                        <FormInput
                            v-model:input-value="OrderAddressForm.area_code"
                            input-name="area_code"
                            input-label="Area Code"
                            :required="true"
                            label-class="form-label w-full flex flex-col sm:flex-row"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6">
                        <FormInput
                            v-model:input-value="OrderAddressForm.address_line_1"
                            input-name="address_line_1"
                            input-label="Address Line 1"
                            :required="true"
                            label-class="form-label w-full flex flex-col sm:flex-row"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6">
                        <FormInput
                            v-model:input-value="OrderAddressForm.address_line_2"
                            input-name="address_line_2"
                            input-label="Address Line 2"
                            label-class="form-label w-full flex flex-col sm:flex-row"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-4">
                        <FormSelectBox
                            v-model:selected-option="OrderAddressForm.country_id"
                            input-name="country_id"
                            input-label="Country"
                            :options="countries"
                            option-label="name"
                            option-value="id"
                            :allow-empty="true"
                            @update:selected-option="handleCountryChange"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-4">
                        <FormSelectBox
                            v-model:selected-option="OrderAddressForm.state_id"
                            input-name="state_id"
                            input-label="State"
                            :options="states"
                            option-label="name"
                            option-value="id"
                            :allow-empty="true"
                            :disabled="!OrderAddressForm.country_id"
                            @update:selected-option="handleStateChange"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-4">
                        <FormSelectBox
                            v-model:selected-option="OrderAddressForm.city_id"
                            input-name="city_id"
                            input-label="City"
                            :options="cities"
                            option-label="name"
                            option-value="id"
                            :allow-empty="true"
                            :disabled="!OrderAddressForm.state_id"
                            @update:selected-option="handleCityChange"
                        />
                    </div>
                    <div class="input-form col-span-12">
                        <FormInput
                            v-model:input-value="OrderAddressForm.city_name"
                            input-name="city_name"
                            input-label="City Name (if not in list)"
                            label-class="form-label w-full flex flex-col sm:flex-row"
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
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { route } from 'ziggy';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { watch, ref } from 'vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    orderAddress: {
        type: Object,
        required: true,
    },
    orderId: {
        type: Number,
        required: true,
    },
});

const states = ref([]);
const cities = ref([]);
const countries = ref([]);

const OrderAddressForm = useForm({
    _method:'post',
    id: null,
    first_name: null,
    last_name: null,
    address_line_1: null,
    address_line_2: null,
    phone: null,
    city_name: null,
    city_id: null,
    state_id: null,
    country_id: null,
    area_code: null,
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};

const loadCountries = async () => {
    try {
        const response = await axios.get(route('admin.countries.index'));
        countries.value = response.data;
    } catch (error) {
        return error;
    }
};

const loadStates = async (countryId) => {
    if (!countryId) {
        states.value = [];
        return;
    }
    try {
        const response = await axios.get(route('admin.states.by_country', countryId));
        states.value = response.data;
    } catch (error) {
        return error;
    }
};

const loadCities = async (stateId) => {
    if (!stateId) {
        cities.value = [];
        return;
    }
    try {
        const response = await axios.get(route('admin.cities.by_state', stateId));
        cities.value = response.data;
    } catch (error) {
        return error;
    }
};

const handleCountryChange = (value) => {
    OrderAddressForm.country_id = value;
    OrderAddressForm.state_id = null;
    OrderAddressForm.city_id = null;
    OrderAddressForm.city_name = null;
    loadStates(value);
};

const handleStateChange = (value) => {
    OrderAddressForm.state_id = value;
    OrderAddressForm.city_id = null;
    OrderAddressForm.city_name = null;
    loadCities(value);
};

const handleCityChange = (value) => {
    OrderAddressForm.city_id = value;
    if (value) {
        const selectedCity = cities.value.find(city => city.id === value);
        if (selectedCity) {
            OrderAddressForm.city_name = selectedCity.name;
        }
    }
};

watch(() => props.modalShow, (newValue) => {
    if (newValue) {
        Object.assign(OrderAddressForm, JSON.parse(JSON.stringify(props.orderAddress)));
        loadCountries();
        if (OrderAddressForm.country_id) {
            loadStates(OrderAddressForm.country_id);
        }
        if (OrderAddressForm.state_id) {
            loadCities(OrderAddressForm.state_id);
        }
    }
});

const submitAddressForm = () => {
    OrderAddressForm.watchEnabled = false;
    const SUCCESS_CODE = 200;

    axios.post(route('admin.orders.update_address', props.orderAddress.id), {
        ...OrderAddressForm,
        _method: 'post',
    }).then((response) => {
        if (response.status === SUCCESS_CODE) {
            showSuccessNotification('Order address updated successfully.');
            closeModal();
            return;
        }
    }).catch(() => {
        showErrorNotification('An error occurred while updating the address.');
    });
};
</script>
