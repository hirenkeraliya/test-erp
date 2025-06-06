<template>
    <PageTitle title="Login" />

    <form @submit.prevent="warehouseManagerLogin()">
        <div class="block xl:grid grid-cols-2 gap-4">
            <GuestSidebar />
            <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
                <div class="my-auto mx-auto xl:ml-20 bg-white xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                    <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                        Sign In
                    </h2>

                    <div class="intro-x mt-8">
                        <GuestFormInput
                            v-model:input-value="loginForm.username"
                            placeholder="Username"
                            input-name="username"
                            :required="true"
                        />
                        <GuestFormInput
                            v-model:input-value="loginForm.password"
                            type="password"
                            placeholder="Password"
                            input-name="password"
                            :required="true"
                        />
                    </div>

                    <div class="intro-x flex text-slate-600 text-xs sm:text-sm mt-4">
                        <!-- temporary hide the below option
                            <div class="flex items-center mr-auto">
                                <FormCheckbox
                                    v-model:check-value="loginForm.remember"
                                    checkbox-name="remember"
                                    check-label="Remember me"
                                />
                            </div>
                        -->
                        <Link :href="route('warehouse_manager.forgot_password')">
                            Forgot Password?
                        </Link>
                    </div>

                    <div class="intro-x mt-5 xl:mt-8 text-center xl:text-left">
                        <PrimaryButton
                            type="submit"
                            text="Login"
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestFormInput from '@commonComponents/GuestFormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import GuestSidebar from '@commonComponents/GuestSidebar.vue';
import { route } from 'ziggy';

const loginForm = useForm({
    username: null,
    password: null,
    remember: false,
});

const warehouseManagerLogin = () => {
    loginForm.post(route('warehouse_manager.login_user'));
};
</script>
