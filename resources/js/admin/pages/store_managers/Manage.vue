<template>
    <PageTitle :title="storeManager ? 'Edit Store Manager' : 'Add Store Manager'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Store Managers
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="storeManager">Edit Store Manager</span>
                        <span v-else>Add Store Manager</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form
                    @submit.prevent="saveStoreManager();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="storeManagerForm.employee_id"
                                    :records="employees"
                                    input-label="Employee"
                                    :required="true"
                                    validation-field-name="employee_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="storeManagerForm.username"
                                    input-name="username"
                                    input-label="Username"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="!storeManager"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="storeManagerForm.password"
                                    :required="true"
                                    type="password"
                                    title="To login to the store manager panel and not for POS"
                                    input-name="password"
                                    input-label="Password"
                                />
                            </div>
                            <div
                                v-if="!storeManager"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="storeManagerForm.password_confirmation"
                                    type="password"
                                    :required="true"
                                    input-name="password_confirmation"
                                    input-label="Confirm Password"
                                />
                            </div>
                            <div
                                v-if="!storeManager"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="storeManagerForm.passcode"
                                    :required="true"
                                    input-name="passcode"
                                    input-label="Passcode (To authorize manual price override, complimentary item sale, and void sale, Cash Movement, used for POS, credit note refund)"
                                    placeholder="Enter Passcode"
                                    type="password"
                                />
                            </div>

                            <div
                                v-if="allowPriceOverrideCartLevel"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="storeManagerForm.price_override_limit_percentage_for_cart"
                                    input-name="price_override_limit_percentage_for_cart"
                                    input-label="Price Override Limit Percentage For Cart"
                                    input-group-suffix="%"
                                    :required="true"
                                    title="If the cart price is RM50 and you apply a 40% discount, the minimum price that the store manager can offer is RM30. It's important to note that any price overrides will be calculated based on the cart price before any cashback discounts are applied. Therefore, the final price will be calculated after all other discounts and vouchers have been accounted for."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="storeManagerForm.price_override_type"
                                    :records="priceOverrideTypes"
                                    input-label="Price Override Type"
                                    validation-field-name="price_override_type"
                                    :required="true"
                                    @update:selected-record="storeManagerForm.reset('price_override_limit_percentage_for_item')"
                                />
                            </div>

                            <div
                                v-if="priceOverridePercentage === storeManagerForm.price_override_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="storeManagerForm.price_override_limit_percentage_for_item"
                                    input-name="price_override_limit_percentage_for_item"
                                    input-label="Price Override Limit Percentage For Item"
                                    input-group-suffix="%"
                                    title="If the original price of a product is RM50 and you apply a 40% discount, the minimum price that can be offered by the store manager is RM30. It's important to note that any price overrides will always be calculated based on the product's original price, not the discounted price."
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="storeManagerForm.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    :required="true"
                                    title="For store manager panel as well as POS authorization"
                                    validation-field-name="location_ids"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="storeManagerForm.brands"
                                    :records="brands"
                                    input-label="Brands"
                                    title="For POS authorization"
                                    validation-field-name="brand_ids"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="storeManagerForm.roles"
                                    :records="roles"
                                    input-label="Roles"
                                    placeholder="Please select Roles"
                                    validation-field-name="role_ids"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="storeManagerForm.can_manage_wholesale"
                                    input-label="Can manage orders?"
                                    title="The store manager cannot manage orders on the store manager panel when this option is switched off."
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.store_managers.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="storeManager ? 'Update' : 'Submit'"
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
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    storeManager: {
        type: Object,
        default: null,
    },
    employees: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    allowPriceOverrideCartLevel: {
        type: Boolean,
        required: true,
    },
    priceOverrideTypes: {
        type: Array,
        required: true,
    },
    priceOverridePercentage: {
        type: Number,
        required: true,
    },
    roles: {
        type: Object,
        required: true,
    },
});

const storeManagerForm = useForm({
    employee_id: null,
    username: null,
    password: null,
    password_confirmation: null,
    passcode: null,
    price_override_type: props.priceOverridePercentage,
    price_override_limit_percentage_for_item: null,
    price_override_limit_percentage_for_cart: 0,
    can_manage_wholesale: false,
    location_ids: [],
    locations: [],
    brand_ids: [],
    brands: [],
    watchEnabled: true,
    roles: [],
    role_ids: [],
});

const saveStoreManager = () => {
    prepareCashierFormDetails();

    storeManagerForm.watchEnabled = false;
    removeLocalStorage('storeManager');

    if (props.storeManager) {
        storeManagerForm.put(route('admin.store_managers.update', props.storeManager.id));
        return;
    }
    storeManagerForm.post(route('admin.store_managers.store'));
};

const prepareCashierFormDetails = () => {
    storeManagerForm.location_ids = storeManagerForm.locations.map((location) => {
        return location.id;
    });

    storeManagerForm.brand_ids = storeManagerForm.brands.map((brand) => {
        return brand.id;
    });

    storeManagerForm.role_ids = storeManagerForm.roles.map((role) => {
        return role.id;
    });
};

onMounted(() => {
    if (props.storeManager) {
        removeLocalStorage('storeManager');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(storeManagerForm, JSON.parse(JSON.stringify(props.storeManager)));
    } else {
        setLocalStorage('storeManager', storeManagerForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.storeManager) {
        saveLocalStorage('storeManager', storeManagerForm);
    }
};

const clearFormData = () => {
    storeManagerForm.reset();
};

watch(storeManagerForm, () => {
    if (storeManagerForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
