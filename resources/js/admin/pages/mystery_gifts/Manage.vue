<template>
    <PageTitle :title="mysteryGift ? 'Edit Mystery Gift' : 'Add MysteryGift'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Mystery Gifts
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ mysteryGift ? 'Edit' : 'Add' }} Mystery Gift
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form @submit.prevent="saveMysteryGift();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="mysteryGiftForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JDatePicker
                                    v-model:input-value="mysteryGiftForm.start_date"
                                    input-label="Start date"
                                    input-name="start_date"
                                    :required="true"
                                    validation-field-name="start_date"
                                    :max-date="mysteryGiftForm.end_date"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JDatePicker
                                    v-model:input-value="mysteryGiftForm.end_date"
                                    input-label="End date"
                                    input-name="end_date"
                                    :required="true"
                                    validation-field-name="end_date"
                                    :min-date="mysteryGiftForm.start_date"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="mysteryGiftForm.minimum_spend"
                                    input-name="minimum_spend"
                                    input-label="Minimum spend to avail mystery gift"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="mysteryGiftForm.is_flat_amount"
                                    input-label="Flat amount?"
                                    title="Get flat discount between 1 to your entered maximum flat discount."
                                    @update:is-checked="clearIsFlatAmount()"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-if="mysteryGiftForm.is_flat_amount"
                                    v-model:input-value="mysteryGiftForm.max_flat_amount"
                                    input-name="max_flat_amount"
                                    input-label="Maximum flat discount"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                    title="Set the maximum flat discount a user can receive."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-if="mysteryGiftForm.is_flat_amount"
                                    v-model:input-value="mysteryGiftForm.minimum_spend_amount_for_flat_amount"
                                    input-name="minimum_spend_amount_for_flat_amount"
                                    input-label="Minimum spend to avail flat discount"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="mysteryGiftForm.is_percentage"
                                    input-label="Percentage ?"
                                    title="Get percentage discount between 1 to 100"
                                    @update:is-checked="clearIsPercentage()"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-if="mysteryGiftForm.is_percentage"
                                    v-model:input-value="mysteryGiftForm.max_percentage"
                                    input-name="max_percentage"
                                    input-label="Maximum percentage discount"
                                    :required="true"
                                    title="Set the maximum percentage discount a user can receive."
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-if="mysteryGiftForm.is_percentage"
                                    v-model:input-value="mysteryGiftForm.minimum_spend_amount_for_percentage"
                                    input-name="minimum_spend_amount_for_percentage"
                                    input-label="Minimum spend to avail percentage discount"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="mysteryGiftForm.is_free_product"
                                    input-label="Free Product?"
                                    title="Customer can get free product as per the specification"
                                    @update:is-checked="clearIsFreeProduct()"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <MysteryGiftProductSelection
                                    v-if="mysteryGiftForm.is_free_product"
                                    :promotion-form="mysteryGiftForm"
                                    :edit-selected-products="mysteryGiftForm.uploaded_products"
                                    column-name="uploaded_products"
                                    validation-field-name="uploaded_products"
                                    :allow-to-clear-selected-products="true"
                                    :allow-to-download-selected-products="mysteryGiftForm.hasOwnProperty('id')"
                                    @update:product-ids="updateColumnsDetails"
                                    @clear-selected-products="clearSelectedProducts"
                                    @download-selected-products="downloadExcelRecords"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.mystery_gifts.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="mysteryGift ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { showErrorNotification } from '@commonServices/notifier';
import MysteryGiftProductSelection from '@adminPages/mystery_gifts/partials/MysteryGiftProductSelection.vue';
import { clearSelectedProductData, exportRecords } from '@commonServices/helper';
const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    mysteryGift: {
        type: Object,
        default: null,
    },
});

const mysteryGiftForm = useForm({
    name: null,
    max_flat_amount: null,
    max_percentage: null,
    start_date: null,
    end_date: null,
    is_flat_amount: false,
    is_percentage: false,
    is_free_product: false,
    uploaded_products: [],
    promotion_id: null,
    minimum_spend:null,
    minimum_spend_amount_for_free_product: 0,
    minimum_spend_amount_for_percentage: 0,
    minimum_spend_amount_for_flat_amount: 0,
});

const saveMysteryGift = () => {
    removeLocalStorage('mystery_gift');

    if (props.mysteryGift) {
        mysteryGiftForm.put(route('admin.mystery_gifts.update', props.mysteryGift.data.id), {
            onError: (error) => {
                if (error.is_flat_amount) {
                    showErrorNotification(error.is_flat_amount);
                }
            }
        });
        return;
    }
    mysteryGiftForm.post(route('admin.mystery_gifts.store'), {
        onError: (error) => {
            if (error.is_flat_amount) {
                showErrorNotification(error.is_flat_amount);
            }
        }
    });
};

const clearIsFlatAmount = () => {
    mysteryGiftForm.minimum_spend_amount_for_flat_amount = 0;
    mysteryGiftForm.max_flat_amount = null;
};

const clearIsPercentage = () => {
    mysteryGiftForm.minimum_spend_amount_for_percentage = 0;
    mysteryGiftForm.max_percentage = null;
};

const clearIsFreeProduct = () => {
    mysteryGiftForm.minimum_spend_amount_for_free_product = 0;
    mysteryGiftForm.uploaded_products = [];
};

onMounted(() => {
    if (props.mysteryGift) {
        removeLocalStorage('mystery_gift');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(mysteryGiftForm, JSON.parse(JSON.stringify(props.mysteryGift.data)));
    } else {
        setLocalStorage('mystery_gift', mysteryGiftForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.mysteryGift) {
        saveLocalStorage('mystery_gift', mysteryGiftForm);
    }
};

const clearFormData = () => {
    mysteryGiftForm.reset();
};

watch(mysteryGiftForm, () => {
    if (mysteryGiftForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

const clearSelectedProducts = () => {
    clearSelectedProductData(route('admin.mystery_gifts.remove_selected_products'), mysteryGiftForm.id);
};

const downloadExcelRecords = () => {
    return exportRecords(
        'export-mystery-gifts-products-details/',
        'promotions-mystery-gifts-details.xlsx',
    );
};

const updateColumnsDetails = (details) => {
    mysteryGiftForm[details.column_name] = details.value;
};
</script>
