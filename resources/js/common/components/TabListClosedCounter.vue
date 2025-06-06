<template>
    <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-2 items-center">
        <Tab
            class="w-full py-2 px-2 leading-none active"
            tag="button"
        >
            Denomination
        </Tab>
        <Tab
            class="w-full py-2 px-2 leading-none"
            tag="button"
        >
            Sales
        </Tab>
        <Tab
            class="w-full py-2 px-2 leading-none"
            tag="button"
        >
            Payment
        </Tab>
        <Tab
            class="w-full py-2 px-2 leading-none"
            tag="button"
        >
            Cash Transaction
        </Tab>
        <Tab
            class="w-full py-2 px-2 leading-none"
            tag="button"
        >
            Attempts
        </Tab>
        <Tab
            class="w-full py-2 px-2 leading-none"
            tag="button"
        >
            Counter Log
        </Tab>
    </TabList>
</template>

<script setup>
import { Tab, TabList } from '@commonVendor/tab';
</script>
