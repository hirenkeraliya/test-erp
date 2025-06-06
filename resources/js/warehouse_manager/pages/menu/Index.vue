<template>
    <PageTitle title="Menus" />
    <MenuNavigation
        :menus="menus"
        :has-permission="false"
    />
</template>

<script setup>
import MenuNavigation from '@commonComponents/MenuNavigation.vue';
import menus from '@warehouseManager/navbar';
</script>
