<template>
    <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-2 items-center">
        <Tab
            class="w-full py-2 px-2 leading-none active"
            tag="button"
        >
            Order Details
        </Tab>
        <Tab
            class="w-full py-2 px-2 leading-none"
            tag="button"
        >
            Order Item Details
        </Tab>
    </TabList>
</template>

<script setup>
import { Tab, TabList } from '@commonVendor/tab';
</script>
