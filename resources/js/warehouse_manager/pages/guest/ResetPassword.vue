<template>
    <PageTitle title="Reset Password" />

    <form @submit.prevent="resetPasswordForm.post(route('warehouse_manager.password_update'))">
        <div class="block xl:grid grid-cols-2 gap-4">
            <GuestSidebar
                first-message="No problem. Everyone forgets."
                second-message="We have got your back."
            />
            <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
                <div class="my-auto mx-auto xl:ml-20 bg-white xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                    <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                        Reset Password
                    </h2>

                    <div class="intro-x mt-8">
                        <GuestFormInput
                            v-model:input-value="resetPasswordForm.password"
                            type="password"
                            placeholder="Password"
                            input-name="password"
                            :required="true"
                        />
                    </div>

                    <div class="intro-x mt-8">
                        <GuestFormInput
                            v-model:input-value="resetPasswordForm.password_confirmation"
                            type="password"
                            placeholder="Confirm Password"
                            input-name="password_confirmation"
                            :required="true"
                        />
                    </div>

                    <div class="intro-x mt-5 xl:mt-8 text-center xl:text-left">
                        <PrimaryButton
                            type="submit"
                            text="Reset Password"
                            class="py-3 px-4 w-full xl:mr-3 align-top"
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import GuestFormInput from '@commonComponents/GuestFormInput.vue';
import GuestSidebar from '@commonComponents/GuestSidebar.vue';
import { route } from 'ziggy';
import { computed } from 'vue';

const pageProps = computed(() => usePage().props);

const resetPasswordForm = useForm({
    token: pageProps.value.token,
    password: null,
    password_confirmation: null,
});
</script>
