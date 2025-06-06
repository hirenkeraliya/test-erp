<template>
    <PageTitle title="Menus" />
    <MenuNavigation
        :menus="menus"
        :has-permission="pageProps.permissions ? true : false"
    />
</template>

<script setup>
import menus from '@admin/navbar';
import MenuNavigation from '@commonComponents/MenuNavigation.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const pageProps = computed(() => usePage().props);
</script>
