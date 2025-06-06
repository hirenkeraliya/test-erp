<template>
    <div class="hidden xl:flex flex-col min-h-screen">
        <div class="my-auto">
            <img
                v-if="pageProps.settings.login_page_logo"
                alt="logo"
                class="-intro-x w-1/2 -mt-16"
                :src="pageProps.settings.login_page_logo"
            >

            <div
                class="-intro-x text-white font-medium text-2xl leading-tight mt-10"
                v-text="pageProps.settings.login_page_tagline"
            />

            <div
                class="-intro-x mt-5 text-lg text-white text-opacity-70"
                v-text="pageProps.settings.login_page_sub_tagline"
            />
        </div>
    </div>
</template>
<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
defineProps({
    firstMessage: {
        type: String,
        default: 'Just a few clicks away',
    },
    secondMessage: {
        type: String,
        default: 'from accessing your account.',
    },
});

const pageProps = computed(() => usePage().props);

</script>
