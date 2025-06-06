<template>
    <PageTitle :title="director ? 'Edit Director' : 'Add Director'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Directors
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ director ? 'Edit' : 'Add' }} Director
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form @submit.prevent="saveDirector();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="directorForm.employee_id"
                                    :records="employees"
                                    input-label="Employee"
                                    :required="true"
                                    validation-field-name="employee_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="directorForm.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    :required="true"
                                    validation-field-name="location_ids"
                                />
                            </div>
                            <div
                                v-if="!director"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="directorForm.passcode"
                                    :required="true"
                                    type="password"
                                    input-name="passcode"
                                    input-label="Passcode (To authorize manual price override, Cash Movement and used for POS)"
                                />
                            </div>

                            <div
                                v-if="allowPriceOverrideCartLevel"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="directorForm.price_override_limit_percentage_for_cart"
                                    input-name="price_override_limit_percentage_for_cart"
                                    input-label="Price Override Limit Percentage For Cart"
                                    input-group-suffix="%"
                                    :required="true"
                                    title="If the cart price is RM50 and you apply a 40% discount, the minimum price that the director can offer is RM30. It's important to note that any price overrides will be calculated based on the cart price before any cashback discounts are applied. Therefore, the final price will be calculated after all other discounts and vouchers have been accounted for."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="directorForm.price_override_type"
                                    :records="priceOverrideTypes"
                                    input-label="Price Override Type"
                                    validation-field-name="price_override_type"
                                    :required="true"
                                    @update:selected-record="directorForm.reset('price_override_limit_percentage_for_item')"
                                />
                            </div>

                            <div
                                v-if="priceOverridePercentage === directorForm.price_override_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="directorForm.price_override_limit_percentage_for_item"
                                    :required="true"
                                    input-name="price_override_limit_percentage_for_item"
                                    input-label="Price override limit percentage for item"
                                    input-group-suffix="%"
                                    title="If the original price of a product is RM50 and you apply a 40% discount, the minimum price that can be offered by the director is RM30. It's important to note that any price overrides will always be calculated based on the product's original price, not the discounted price."
                                    type="number"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.directors.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="director ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    director: {
        type: Object,
        default: null,
    },
    locations: {
        type: Array,
        required: true,
    },
    employees: {
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
});

const directorForm = useForm({
    employee_id: null,
    location_ids: [],
    locations: [],
    passcode: null,
    price_override_type: props.priceOverridePercentage,
    price_override_limit_percentage_for_item: null,
    price_override_limit_percentage_for_cart: 0,
    watchEnabled: true,
});

const saveDirector = () => {
    prepareDirectorFormDetails();

    directorForm.watchEnabled = false;
    removeLocalStorage('director');

    if (props.director) {
        directorForm.put(route('admin.directors.update', props.director.id));
        return;
    }
    directorForm.post(route('admin.directors.store'));
};

const prepareDirectorFormDetails = () => {
    directorForm.location_ids = directorForm.locations.map((location) => {
        return location.id;
    });
};

onMounted(() => {
    if (props.director) {
        removeLocalStorage('director');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(directorForm, JSON.parse(JSON.stringify(props.director)));
    } else {
        setLocalStorage('director', directorForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.director) {
        saveLocalStorage('director', directorForm);
    }
};

const clearFormData = () => {
    directorForm.reset();
};

watch(directorForm, () => {
    if (directorForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
