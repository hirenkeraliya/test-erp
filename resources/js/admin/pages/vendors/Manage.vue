<template>
    <PageTitle :title="vendor ? 'Edit Vendor' : 'Add Vendor'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Vendors
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ vendor ? 'Edit' : 'Add' }} vendor
                    </h2>
                </div>
                <form @submit.prevent="saveVendor();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.registration_number"
                                    input-name="registration_number"
                                    input-label="Registration Number"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.sst_number"
                                    input-name="sst_number"
                                    input-label="SST Number"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="vendorForm.email"
                                        type="email"
                                        input-name="email"
                                        input-label="Email"
                                        :required="true"
                                    />
                                    <Tippy
                                        v-if="vendor ? !vendor.is_email_verified && vendorForm.email : vendorForm.email"
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
                                    v-model:input-value="vendorForm.phone"
                                    input-name="phone"
                                    input-label="Phone"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.mobile"
                                    input-name="mobile"
                                    input-label="Mobile"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.fax"
                                    input-name="fax"
                                    input-label="Fax"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.website"
                                    type="url"
                                    input-name="website"
                                    input-label="Website"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.address_line_1"
                                    input-name="address_line_1"
                                    input-label="Address Line 1"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.address_line_2"
                                    input-name="address_line_2"
                                    input-label="Address Line 2"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.city"
                                    input-name="city"
                                    input-label="City"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="vendorForm.area_code"
                                    input-name="area_code"
                                    input-label="Area Code"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-7">
                                <JSwitch
                                    v-model:is-checked="vendorForm.is_consignment"
                                    input-label="Consignment"
                                    title="The product from the supplier are consignments. We are mapping products to supplier."
                                />
                            </div>
                            <div
                                v-if="vendorForm.is_consignment"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="vendorForm.commission_percentage"
                                    input-name="commission_percentage"
                                    input-label="Commission Percentage"
                                    :required="true"
                                    input-group-suffix="%"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.vendors.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="vendor ? 'Update' : 'Submit'"
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
import JSwitch from '@commonComponents/JSwitch.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    vendor: {
        type: Object,
        default: null,
    }
});

const vendorForm = useForm({
    name: null,
    code: null,
    registration_number: null,
    sst_number: null,
    email: null,
    phone: null,
    mobile: null,
    fax: null,
    website: null,
    address_line_1: null,
    address_line_2: null,
    city: null,
    area_code: null,
    is_consignment: false,
    commission_percentage: null,

});

const saveVendor = () => {
    if (props.vendor) {
        vendorForm.put(route('admin.vendors.update', props.vendor.id));
        return;
    }
    vendorForm.post(route('admin.vendors.store'));
};

onMounted(() => {
    if (props.vendor) {
        Object.assign(vendorForm, props.vendor);
    }
});
</script>
