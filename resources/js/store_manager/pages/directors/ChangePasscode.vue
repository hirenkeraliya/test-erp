<template>
    <PageTitle title="Change Passcode" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Change Passcode
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Change Passcode</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="changePasscode();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="changePasscodeForm.new_passcode"
                                    type="password"
                                    input-label="New Passcode"
                                    input-name="new_passcode"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="changePasscodeForm.new_passcode_confirmation"
                                    type="password"
                                    input-label="Confirm New Passcode"
                                    input-name="new_passcode_confirmation"
                                    :required="true"
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
    directorId: {
        type: Number,
        default: null,
    },
});

const changePasscodeForm = useForm({
    new_passcode: null,
    new_passcode_confirmation: null,
});

const changePasscode = () => {
    changePasscodeForm.put(route('store_manager.directors.update_passcode', props.directorId));
};
</script>
