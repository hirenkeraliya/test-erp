<template>
    <div class="container sm:px-10">
        <slot />
    </div>
</template>
<script setup>
import { computed, onMounted } from 'vue';
import dom from '@left4code/tw-starter/dist/js/dom';
import { usePage } from '@inertiajs/vue3';
import { evaluateValidationErrors, evaluateFlashMessagesToast } from '@commonServices/displayErrors';

onMounted(() => {
    dom('body').removeClass('main').addClass('login');
});

const pageProps = computed(() => usePage().props);

evaluateFlashMessagesToast(pageProps);

evaluateValidationErrors(pageProps);
</script>
