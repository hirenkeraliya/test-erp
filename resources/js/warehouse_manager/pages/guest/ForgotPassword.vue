<template>
    <PageTitle title="Forgot Password" />

    <form @submit.prevent="forgotPasswordForm.post(route('warehouse_manager.send_forgot_password_email'))">
        <div class="block xl:grid grid-cols-2 gap-4">
            <GuestSidebar
                first-message="No problem. Everyone forgets."
                second-message="We have got your back."
            />
            <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
                <div class="my-auto mx-auto xl:ml-20 bg-white xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                    <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                        Forgot Password
                    </h2>

                    <div class="intro-x mt-8">
                        <GuestFormInput
                            v-model:input-value="forgotPasswordForm.username"
                            type="text"
                            placeholder="Username"
                            input-name="username"
                            :required="true"
                        />
                    </div>

                    <div class="intro-x flex text-slate-600 text-xs sm:text-sm mt-4">
                        <div class="flex items-center mr-auto" />
                        <Link :href="route('warehouse_manager.login')">
                            Back to Login
                        </Link>
                    </div>

                    <div class="intro-x mt-5 xl:mt-8 text-center xl:text-left">
                        <PrimaryButton
                            type="submit"
                            text="Send Password Reset Link"
                            class="py-3 px-4 w-full xl:mr-3 align-top"
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import GuestFormInput from '@commonComponents/GuestFormInput.vue';
import GuestSidebar from '@commonComponents/GuestSidebar.vue';
import { route } from 'ziggy';

const forgotPasswordForm = useForm({
    username: null,
});
</script>
