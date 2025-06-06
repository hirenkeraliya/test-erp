<template>
    <PageTitle :title="driver ? 'Edit Driver' : 'Add Driver'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Driver
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="driver">Edit Driver</span>
                        <span v-else>Add Driver</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveDriver();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <FormInput
                                    v-model:input-value="driverForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <FormInput
                                    v-model:input-value="driverForm.id_number"
                                    input-name="id_number"
                                    input-label="ID Number"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <FormInput
                                    v-model:input-value="driverForm.email"
                                    input-name="email"
                                    input-label="Email"
                                    input-type="email"
                                    :required="false"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <PhoneInput
                                    :input-value="driverForm.mobile_number"
                                    :isd-code="driverForm.country_code"
                                    input-name="contact_number"
                                    input-label="Contact Number"
                                    validation-field-name="mobile_number"
                                    :required="true"
                                    @update:input-value="driverForm.mobile_number = $event"
                                    @update:isd-code="driverForm.country_code = $event"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <JSwitch
                                    input-label="Status"
                                    :is-checked="driverForm.status"
                                    class="mt-3"
                                    @update:is-checked="driverForm.status = $event"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-right">
                                <Link :href="route('admin.drivers.index')">
                                    <SecondaryButton
                                        type="button"
                                        text="Cancel"
                                        class="w-24 mr-1"
                                    />
                                </Link>
                                <PrimaryButton
                                    type="submit"
                                    :text="driver ? 'Update' : 'Save'"
                                    :disabled="driverForm.processing"
                                    class="w-24"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { route } from 'ziggy';
import { Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import PhoneInput from '@commonComponents/PhoneInput.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';

const props = defineProps({
    driver: {
        type: Object,
        default: null
    }
});

const driverForm = useForm({
    name: '',
    id_number: '',
    email: '',
    mobile_number: '',
    country_code: '',
    status: true
});

const clearFormData = () => {
    driverForm.reset();
    driverForm.clearErrors();
};

const saveDriver = () => {
    if (props.driver) {
        driverForm.put(route('admin.drivers.update', props.driver.data.id), {
            onSuccess: () => {
                clearFormData();
            }
        });
    } else {
        driverForm.post(route('admin.drivers.store'), {
            onSuccess: () => {
                clearFormData();
            }
        });
    }
};

onMounted(() => {
    if (props.driver) {
        Object.assign(driverForm, props.driver.data);
    }
});
</script>
