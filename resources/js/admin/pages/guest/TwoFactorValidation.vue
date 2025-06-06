<template>
    <PageTitle title="Validate 2FA" />

    <form @submit.prevent="submitOtp">
        <div class="block xl:grid grid-cols-2 gap-4">
            <GuestSidebar />
            <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
                <div class="my-auto mx-auto xl:ml-20 bg-white xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                    <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                        Enter Authenticator code
                    </h2>
                    <div class="intro-x mt-8">
                        <GuestFormInput
                            v-model:input-value="form.code"
                            placeholder="Enter Authenticator code"
                            input-name="code"
                            :required="true"
                        />
                    </div>
                    <p
                        v-if="error"
                        class="text-red-500"
                    >
                        {{ error }}
                    </p>

                    <div class="intro-x mt-5 xl:mt-8 text-center xl:text-left">
                        <PrimaryButton
                            type="submit"
                            text="Submit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import GuestFormInput from '@commonComponents/GuestFormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import GuestSidebar from '@commonComponents/GuestSidebar.vue';
import { route } from 'ziggy';

const code = ref("");
const error = ref("");

const form = useForm({ code: code });

const submitOtp = async () => {
    try {
        await form.post(route("admin.2fa.validateOTP"));
    } catch {
        error.value = "Invalid OTP. Please try again.";
    }
};
</script>
