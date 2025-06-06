<template>
    <PageTitle :title="location ? 'Edit Location' : 'Add Location'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Locations
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ location ? 'Edit' : 'Add' }} Location
                    </h2>

                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveLocation();">
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
                            <div>
                                <label
                                    for="created_store"
                                    class="form-label text-base mb-3"
                                >
                                    Selected Location : <span class="font-medium text-primary">{{ getLocationType(locationForm.type_id) }}</span>
                                </label>
                                <JTabs
                                    v-if="!location"
                                    :records="locationTypes"
                                    return-selected-record="id"
                                    :selected-record="locationForm.type_id"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    @update:selected-record="updateLocationTypes"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.code"
                                    input-name="code"
                                    input-label="Code"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="locationForm.type_id === staticLocationTypes.store"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="locationForm.brands"
                                    :records="brands"
                                    input-label="Brands"
                                    :required="true"
                                    validation-field-name="brand_ids"
                                    title="Brands: Only selected brands’ products will be available to this particular location."
                                />
                            </div>

                            <div
                                v-if="locationForm.type_id === staticLocationTypes.store"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div class="block sm:flex items-center">
                                    <FormSelectBox
                                        v-model:selected-record="locationForm.region_id"
                                        :records="regions"
                                        input-label="Region"
                                        validation-field-name="region_id"
                                        class="w-full"
                                    />
                                    <PrimaryButton
                                        text="+"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        title="Add New Region"
                                        @click="addNewRegion"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.registration_number"
                                    input-name="registration_number"
                                    input-label="Registration Number"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.sst_number"
                                    input-name="sst_number"
                                    input-label="SST Number"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="locationForm.email"
                                        type="email"
                                        input-name="email"
                                        input-label="Email"
                                        :required="true"
                                    />
                                    <Tippy
                                        v-if="location ? ! location.is_email_verified && locationForm.email : locationForm.email"
                                        :content="'Your email will require verification.'"
                                    >
                                        <TriangleAlert
                                            class="text-red-400 ml-2 mt-7"
                                            :size="20"
                                        />
                                    </Tippy>
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.phone"
                                    input-name="phone"
                                    input-label="Phone"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.mobile"
                                    input-name="mobile"
                                    input-label="Mobile"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.fax"
                                    input-name="fax"
                                    input-label="Fax"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.address_line_1"
                                    input-name="address_line_1"
                                    input-label="Address Line 1"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.address_line_2"
                                    input-name="address_line_2"
                                    input-label="Address Line 2"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.area_code"
                                    input-name="area_code"
                                    input-label="Area Code"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-if="! location || ! location.country"
                                    v-model:selected-record="locationForm.country_id"
                                    :records="countries"
                                    input-label="Country"
                                    :required="true"
                                    validation-field-name="country_id"
                                    @update:selected-record="fetchStates"
                                />
                                <div
                                    v-else
                                    class="mt-3"
                                >
                                    <div class="input-group">
                                        <label>
                                            Country:
                                        </label>
                                    </div>
                                    <div class="font-medium">
                                        {{ location.country.name }}
                                    </div>
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="locationForm.state_id"
                                    :records="state.states"
                                    input-label="State"
                                    :required="true"
                                    validation-field-name="state_id"
                                    @update:selected-record="fetchCities"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="locationForm.city_id"
                                    :records="state.cities"
                                    input-label="City"
                                    :required="true"
                                    validation-field-name="city_id"
                                />
                            </div>

                            <div
                                v-if="allowSmartTransfer"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="locationForm.minimum_stock_threshold"
                                    placeholder="Enter Minimum Stock Threshold"
                                    input-name="minimum_stock_threshold"
                                    input-label="Minimum Stock Threshold"
                                    required="true"
                                />
                            </div>

                            <div
                                v-if="allowSmartTransfer"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="locationForm.maximum_stock_threshold"
                                    placeholder="Enter Maximum Stock Threshold"
                                    input-name="maximum_stock_threshold"
                                    input-label="Maximum Stock Threshold"
                                    required="true"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="locationForm.type_id === staticLocationTypes.store"
                        class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                    />

                    <div
                        v-if="locationForm.type_id === staticLocationTypes.store"
                        class="p-5"
                    >
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.web_site"
                                    input-name="web_site"
                                    input-label="Website"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.sales_tax_percentage"
                                    input-name="sales_tax_percentage"
                                    input-label="Sales Tax"
                                    input-group-suffix="%"
                                    title="1. No other GST/SST applied 2. You can put 0 if you do not need to levy extra tax"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.sales_return_days_limit"
                                    input-name="sales_return_days_limit"
                                    input-label="Sales Return Days Limit"
                                    title="Set Zero (0) if you don't want to set a limit."
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.credit_note_expiration_days"
                                    input-name="credit_note_expiration_days"
                                    input-label="Credit Note Expiration Days"
                                    title="1) Set Zero (0) if you don't want to set a limit. 2) Cannot be used or refunded after expiry."
                                />
                            </div>

                            <div class="hidden input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.loyalty_point_expiration_days"
                                    input-name="loyalty_point_expiration_days"
                                    input-label="Loyalty Point Expiration Days"
                                    title="1) Set Zero (0) if you don't want to set a limit. 2) Cannot be used after expiry."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.receipt_footer"
                                    input-name="receipt_footer"
                                    input-label="Receipt Footer"
                                    title="Specified text will be shown on the generated receipt at the bottom."
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="locationForm.disclaimer"
                                    input-name="disclaimer"
                                    input-label="Disclaimer"
                                    :required="true"
                                    class="mt-2"
                                    title="Specified text will be shown on the generated receipt at the bottom."
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.cash_out_limit_info"
                                    input-name="cash_out_limit_info"
                                    input-label="Cash Out Limit Info"
                                    :input-group-prefix="currencySymbol"
                                    class="mt-2"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.cash_out_limit_warning"
                                    input-name="cash_out_limit_warning"
                                    input-label="Cash Out Limit Warning"
                                    :input-group-prefix="currencySymbol"
                                    class="mt-2"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.cash_out_limit_restrict"
                                    input-name="cash_out_limit_restrict"
                                    input-label="Cash Out Limit Restrict"
                                    :input-group-prefix="currencySymbol"
                                    class="mt-2"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="locationForm.price_fall_down_percentage"
                                    input-name="price_fall_down_percentage"
                                    input-label="Price Fall Down Percentage"
                                    input-group-suffix="%"
                                    title="When an item is sold below the % configured, the notifications are triggered to the store managers."
                                    class="mt-2"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="locationForm.sale_channels"
                                    :records="saleChannels"
                                    input-label="Sale Channels"
                                    validation-field-name="sale_channels"
                                    title="Brands: Only selected brands’ products will be available to this particular location."
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                                <JSwitch
                                    v-model:is-checked="locationForm.share_inventory_to_external_companies"
                                    input-label="Share Inventory To External Companies?"
                                />
                            </div>
                            <div
                                v-if="locationForm.type_id === staticLocationTypes.store"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-if="locationForm.type_id === staticLocationTypes.store"
                                    v-model:is-checked="locationForm.is_automatic_day_close"
                                    input-label="Is Automatic Day Closed?"
                                    title="Specified time automatic day close will be trigger and close the counters."
                                />
                            </div>

                            <div
                                v-if="locationForm.is_automatic_day_close"
                                class="input-form col-span-12 sm:col-span-9 md:col-span-9 lg:col-span-9 xl:col-span-9"
                            >
                                <Tippy
                                    tag="label"
                                    content="An email will be sent to all the store managers of the location if any of the counters are open and the day close cannot be performed."
                                >
                                    Automatic Day Close Time
                                    <span class="text-danger">*</span>

                                    <Info
                                        class="text-cyan-400 inline-block ml-2"
                                        :size="15"
                                    />
                                </Tippy>

                                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4">
                                        <FormSelectBox
                                            :records="[
                                                { id: '00', name: '00' },
                                                { id: '01', name: '01' },
                                                { id: '02', name: '02' },
                                                { id: '03', name: '03' },
                                                { id: '04', name: '04' },
                                                { id: '05', name: '05' },
                                                { id: '06', name: '06' },
                                                { id: '07', name: '07' },
                                                { id: '08', name: '08' },
                                                { id: '09', name: '09' },
                                                { id: '10', name: '10' },
                                                { id: '11', name: '11' },
                                            ]"
                                            validation-field-name="automatic_day_close_time"
                                            :selected-record="locationForm.hour"
                                            placeholder="Please select hour"
                                            @update:selected-record="updateFormField($event, 'hour')"
                                        />
                                    </div>

                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4">
                                        <FormSelectBox
                                            :records="[
                                                { id: '00', name: '00' },
                                                { id: '05', name: '05' },
                                                { id: '10', name: '10' },
                                                { id: '15', name: '15' },
                                                { id: '20', name: '20' },
                                                { id: '25', name: '25' },
                                                { id: '30', name: '30' },
                                                { id: '35', name: '35' },
                                                { id: '40', name: '40' },
                                                { id: '45', name: '45' },
                                                { id: '50', name: '50' },
                                                { id: '55', name: '55' },
                                            ]"
                                            :selected-record="locationForm.minute"
                                            placeholder="Please select minute"
                                            @update:selected-record="updateFormField($event, 'minute')"
                                        />
                                    </div>

                                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4">
                                        <FormSelectBox
                                            :records="[
                                                { id: 'AM', name: 'AM' },
                                                { id: 'PM', name: 'PM' },
                                            ]"
                                            :selected-record="locationForm.format"
                                            placeholder="Please select format"
                                            @update:selected-record="updateFormField($event, 'format')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <h2
                            v-if="locationForm.type_id === staticLocationTypes.store"
                            class="font-medium text-base  mt-2"
                        >
                            Operational Time
                        </h2>
                        <div
                            v-if="locationForm.type_id === staticLocationTypes.store"
                            class="grid grid-cols-12 gap-0 sm:gap-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="locationForm.open_time"
                                    type="time"
                                    input-name="open_time"
                                    input-label="Open Time"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="locationForm.close_time"
                                    type="time"
                                    input-name="close_time"
                                    input-label="Close Time"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.locations.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="location ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>

                    <RegionModal
                        v-if="state.regionModalShow"
                        :region-modal-show="state.regionModalShow"
                        @update:hide-region-modal="hideRegionModal"
                        @new:record="newRegion"
                    />
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import JTabs from '@commonComponents/JTabs.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { removeLocalStorage, saveLocalStorage, setLocalStorage } from '@commonServices/helper';
import { useForm, usePage } from '@inertiajs/vue3';
import { Info, TriangleAlert } from 'lucide-vue-next';
import { reactive, computed, nextTick, onMounted, watch } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import RegionModal from '@adminPages/locations/partials/RegionModal.vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    location: {
        type: Object,
        default: null,
    },
    brands: {
        type: Object,
        required: true,
    },
    regions: {
        type: Object,
        required: true,
    },
    storeTimings: {
        type: Object,
        required: true,
    },
    countries: {
        type: Object,
        required: true,
    },
    saleChannels: {
        type: Object,
        required: true,
    },
    states: {
        type: Array,
        default: () => {},
    },
    cities: {
        type: Array,
        default: () => {},
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
    allowSmartTransfer: {
        type: Boolean,
        required: false,
    },
});

const state = reactive({
    country: null,
    states: props.states ? props.states : [],
    cities: props.cities ? props.cities : [],
});

const fetchStates = () => {
    state.states = [];
    state.cities = [];
    locationForm.state_id = null;
    locationForm.city_id = null;
    if (locationForm.country_id) {
        axios.get(route('admin.states.get_states', locationForm.country_id)).then((response) => {
            state.states = response.data.states;
        }).catch(() => {
            state.states = [];
        });
    }
};

const fetchCities = () => {
    state.cities = [];
    locationForm.city_id = null;
    if (locationForm.state_id) {
        axios.get(route('admin.cities.get_cities', locationForm.state_id)).then((response) => {
            state.cities = response.data.cities;
        }).catch(() => {
            state.cities = [];
        });
    }
};

const locationForm = useForm({
    name: null,
    type_id: props.staticLocationTypes.store,
    code: null,
    registration_number: null,
    sst_number: null,
    email: null,
    phone: null,
    mobile: null,
    fax: null,
    address_line_1: null,
    address_line_2: null,
    city: null,
    area_code: null,
    web_site: null,
    sales_tax_percentage: null,
    sales_return_days_limit: 0,
    loyalty_point_expiration_days: 0,
    credit_note_expiration_days: 0,
    receipt_footer: null,
    disclaimer: null,
    brand_ids: [],
    brands: [],
    is_automatic_day_close: false,
    share_inventory_to_external_companies: false,
    automatic_day_close_time: null,
    hour: null,
    minute: null,
    format: null,
    region_id: null,
    cash_out_limit_info: null,
    cash_out_limit_warning: null,
    cash_out_limit_restrict: null,
    price_fall_down_percentage: 80,
    watchEnabled: true,
    open_time: props.storeTimings.openTime,
    close_time: props.storeTimings.closeTime,
    country_id: null,
    regionModalShow: false,
    regions: [],
    sale_channels: null,
    sale_channel_ids: null,
    state_id: null,
    city_id: null,
    minimum_stock_threshold: 0,
    maximum_stock_threshold: 0,
});

const saveLocation = () => {
    locationFormDetails();
    locationForm.watchEnabled = false;
    removeLocalStorage('location');

    if (props.location) {
        locationForm.post(route('admin.locations.update', props.location.id));
        return;
    }
    locationForm.post(route('admin.locations.store'));
};

const pmHourOffset = 12;

const locationFormDetails = () => {
    locationForm.brand_ids = locationForm.brands.map((brand) => {
        return brand.id;
    });

    if (locationForm.sale_channels) {
        locationForm.sale_channel_ids = locationForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }

    locationForm.automatic_day_close_time = null;
    if (locationForm.is_automatic_day_close) {
        if (locationForm.format === 'PM') {
            locationForm.automatic_day_close_time = (parseInt(locationForm.hour) + pmHourOffset) + ':' +
                locationForm.minute + ':00';

            return;
        }

        locationForm.automatic_day_close_time = locationForm.hour + ':' +
            locationForm.minute + ':00';
    }
};

const formatTimings = () => {
    if (!locationForm.is_automatic_day_close) {
        return;
    }

    const splitTiming = locationForm.automatic_day_close_time.split(':');
    let hour = splitTiming[0];
    const minute = splitTiming[1];
    locationForm.format = hour > pmHourOffset ? 'PM' : 'AM';
    const zeroPaddingThreshold = 10;

    if (hour > pmHourOffset) {
        if ((hour - pmHourOffset).toString() < zeroPaddingThreshold) {
            hour = '0' + (hour - pmHourOffset).toString();
        } else {
            hour = (hour - pmHourOffset).toString();
        }
    } else {
        hour = hour.toString();
    }

    locationForm.hour = hour;
    locationForm.minute = minute;
};

const updateFormField = (data, columnName) => {
    locationForm[columnName] = data;
};

onMounted(() => {
    state.regions = props.regions;
    if (props.location) {
        removeLocalStorage('location');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(locationForm, JSON.parse(JSON.stringify(props.location)));

        const Country = props.countries.find(country => country.id === props.location.country_id);
        state.country = Country ? Country.name : null;

        nextTick(() => {
            formatTimings();
        });
    } else {
        setLocalStorage('location', locationForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.location) {
        saveLocalStorage('location', locationForm);
    }
};

const clearFormData = () => {
    locationForm.reset();
    locationForm.type_id = props.staticLocationTypes.store;
};

const updateLocationTypes = (typeId) => {
    clearFormData();
    locationForm.type_id = typeId;
};

const addNewRegion = () => {
    state.regionModalShow = true;
};

const hideRegionModal = () => {
    state.regionModalShow = false;
};

const newRegion = (region) => {
    state.regions.push(region);
    locationForm.region_id = region.id;
};

const getLocationType = (locationTypeId) => {
    if (locationTypeId === props.staticLocationTypes.store) {
        return 'Store';
    }

    return 'Warehouse';
};

watch(locationForm, () => {
    if (locationForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
