<template>
    <PageTitle title="Menus" />
    <MenuNavigation
        :menus="menus"
        :has-permission="false"
    />
</template>

<script setup>
import menus from '@storeManager/navbar';
import MenuNavigation from '@commonComponents/MenuNavigation.vue';
</script>
