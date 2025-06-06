<template>
    <PageTitle title="Password Changed" />

    <div class="block xl:grid grid-cols-2 gap-4">
        <GuestSidebar
            first-message="No problem. Everyone forgets."
            second-message="We have got your back."
        />
        <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
            <div class="my-auto mx-auto xl:ml-20 bg-white xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                    Password Changed !
                </h2>

                <p class="mt-2">
                    Your password has been successfully changed! You can now log in to your account using your new password.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import GuestSidebar from '@commonComponents/GuestSidebar.vue';
</script>
