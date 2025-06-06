<template>
    <PageTitle :title="membership ? 'Edit Membership' : 'Add Membership'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Memberships
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="membership">Edit Membership</span>
                        <span v-else>Add Membership</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveMembership();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="membershipForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="membershipForm.lifetime_value"
                                    type="number"
                                    input-name="lifetime_value"
                                    input-label="Lifetime Value"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                    title="The memberships of members/employees are assigned/upgraded automatically based on their lifetime value."
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="membershipForm.loyalty_points_per_currency_unit"
                                    type="number"
                                    input-name="loyalty_points_per_currency_unit"
                                    :input-label="`Loyalty Points Per Currency unit (${currencySymbol})`"
                                    :required="true"
                                    title="How many Loyalty points are to be debited per RM1 during the sales? For example, If this value is 5 and the member with this membership buys items worth RM100, 500 loyalty points will be used from his account to pay for the sale."
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="membershipForm.min_loyalty_points_for_redemption"
                                    type="number"
                                    input-name="min_loyalty_points_for_redemption"
                                    input-label="Minimum Loyalty Points for Redemption"
                                    :required="true"
                                    title="Minimum 200 loyalty points that member can redeem."
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="membershipForm.max_loyalty_points_for_redemption"
                                    type="number"
                                    input-name="max_loyalty_points_for_redemption"
                                    input-label="Maximum Loyalty Points for Redemption"
                                    :required="true"
                                    title="Maximum 40000 loyalty points that member can redeem."
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.memberships.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="membership ? 'Update' : 'Submit'"
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
import { useForm, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { computed, onMounted } from 'vue';
import { route } from 'ziggy';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    membership: {
        type: Object,
        default: null,
    },
});

const membershipForm = useForm({
    name: null,
    lifetime_value: null,
    loyalty_points_per_currency_unit: null,
    min_loyalty_points_for_redemption: null,
    max_loyalty_points_for_redemption: null,
});

const saveMembership = () => {
    if (props.membership) {
        membershipForm.put(route('admin.memberships.update', props.membership.id));
        return;
    }
    membershipForm.post(route('admin.memberships.store'));
};

onMounted(() => {
    if (props.membership) {
        Object.assign(membershipForm, props.membership);
    }
});
</script>
