<template>
    <PageTitle title="Menus" />
    <MenuNavigation
        :menus="menus"
        :has-permission="false"
    />
</template>

<script setup>
import menus from '@superAdmin/navbar';
import MenuNavigation from '@commonComponents/MenuNavigation.vue';
</script>
