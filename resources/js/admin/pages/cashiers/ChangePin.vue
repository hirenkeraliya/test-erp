<template>
    <PageTitle title="Change Pin" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Change Pin
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Change Pin</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="changePin();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="changePinForm.new_pin"
                                    type="password"
                                    input-label="New Pin"
                                    input-name="new_pin"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="changePinForm.new_pin_confirmation"
                                    type="password"
                                    input-label="Confirm New Pin"
                                    input-name="new_pin_confirmation"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.cashiers.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                text="Update"
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
import { route } from 'ziggy';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';

const props = defineProps({
    cashierId: {
        type: Number,
        default: null,
    },
});

const changePinForm = useForm({
    new_pin: null,
    new_pin_confirmation: null,
});

const changePin = () => {
    changePinForm.put(route('admin.cashiers.update_pin', props.cashierId));
};
</script>
