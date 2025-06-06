<template>
    <div class="flex items-center mt-10">
        <h2 class="text-base font-medium truncate">
            Sell Through Report By {{ reportTitle }}
        </h2>

        <div class="ml-4">
            <TabList class="items-center p-2 rounded-md nav nav-pills">
                <Tab
                    class="px-1 py-1 mr-2 leading-none bg-slate-200 active"
                    tag="button"
                >
                    <Table
                        :size="22"
                        class="w-4 h-4 sm:w-5 md:w-5 lg:w-5 xl:w-5 2xl:w-5 sm:h-5 md:h-5 lg:h-5 xl:h-5 2xl:h-5"
                    />
                </Tab>

                <Tab
                    class="px-1 py-1 leading-none bg-slate-200"
                    tag="button"
                >
                    <BarChart3
                        :size="22"
                        class="w-4 h-4 sm:w-5 md:w-5 lg:w-5 xl:w-5 2xl:w-5 sm:h-5 md:h-5 lg:h-5 xl:h-5 2xl:h-5"
                    />
                </Tab>
            </TabList>
        </div>
    </div>
</template>

<script setup>
import { Tab, TabList } from '@commonVendor/tab';
import { BarChart3, Table } from 'lucide-vue-next';

defineProps({
    reportTitle: {
        type: String,
        required: true,
    }
});
</script>
