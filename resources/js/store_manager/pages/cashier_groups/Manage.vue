<template>
    <PageTitle :title="cashierGroup ? 'Edit Cashier Group' : 'Add Cashier Group'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cashier Groups
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="cashierGroup">Edit Cashier Group</span>
                        <span v-else>Add Cashier Group</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveCashierGroup();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="cashierGroupForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="cashierGroupForm.permission_details"
                                    :records="permissionTypes"
                                    input-label="Permissions"
                                    :required="true"
                                    validation-field-name="permission_ids"
                                />
                            </div>

                            <div
                                v-if="allowPriceOverrideCartLevel"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="cashierGroupForm.price_override_limit_percentage_for_cart"
                                    input-name="price_override_limit_percentage_for_cart"
                                    input-label="Price Override Limit Percentage For Cart"
                                    input-group-suffix="%"
                                    :required="true"
                                    title="If the cart price is RM50 and you apply a 40% discount, the minimum price that the cashier can offer is RM30. It's important to note that any price overrides will be calculated based on the cart price before any cashback discounts are applied. Therefore, the final price will be calculated after all other discounts and vouchers have been accounted for."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="cashierGroupForm.price_override_type"
                                    :records="priceOverrideTypes"
                                    input-label="Price Override Type"
                                    validation-field-name="price_override_type"
                                    :required="true"
                                    @update:selected-record="cashierGroupForm.reset('price_override_limit_percentage_for_item')"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-if="priceOverridePercentage === cashierGroupForm.price_override_type"
                                    v-model:input-value="cashierGroupForm.price_override_limit_percentage_for_item"
                                    :required="true"
                                    input-name="price_override_limit_percentage_for_item"
                                    input-label="Price override limit percentage for item"
                                    input-group-suffix="%"
                                    title="If the original price of a product is RM50 and you apply a 40% discount, the minimum price that can be offered by the cashier group is RM30. It's important to note that any price overrides will always be calculated based on the product's original price, not the discounted price."
                                    type="number"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('store_manager.cashier_groups.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="cashierGroup ? 'Update' : 'Submit'"
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
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

const props = defineProps({
    cashierGroup: {
        type: Object,
        default: null,
    },
    permissionTypes: {
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

const cashierGroupForm = useForm({
    name: null,
    permission_ids: [],
    permission_details: [],
    price_override_type: props.priceOverridePercentage,
    price_override_limit_percentage_for_item: null,
    price_override_limit_percentage_for_cart: 0,
});

const saveCashierGroup = () => {
    prepareCashierGroupFormDetails();

    if (props.cashierGroup) {
        router.put(route('store_manager.cashier_groups.update', props.cashierGroup.id), cashierGroupForm);
        return;
    }

    router.post(route('store_manager.cashier_groups.store'), cashierGroupForm);
};

const prepareCashierGroupFormDetails = () => {
    if (cashierGroupForm.permission_details) {
        cashierGroupForm.permission_ids = cashierGroupForm.permission_details.map((permission) => {
            return permission.id;
        });
    }
};

onMounted(() => {
    if (props.cashierGroup) {
        Object.assign(cashierGroupForm, props.cashierGroup);
    }
});
</script>
