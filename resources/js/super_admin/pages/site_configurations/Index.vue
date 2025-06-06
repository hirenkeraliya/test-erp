<template>
    <PageTitle title="Site Configurations" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Site Configurations
        </h2>
    </div>

    <div>
        <JTable
            :fetch-url="route('super_admin.site_configurations.fetch')"
            :columns="state.columns"
        >
            <template #value="record">
                <img
                    v-if="record.item.image_url"
                    :src="record.item.image_url"
                    :alt="record.item.value"
                    width="50"
                    class="bg-gray-300"
                >

                <div v-if="record.item.value.startsWith('#')">
                    <span
                        :style="'background-color:' + record.item.value"
                        class="pt-1 pb-1 pr-10 mr-1"
                    >
                    &nbsp;
                    </span>
                    {{ record.item.value }}
                </div>

                <span
                    v-else
                    v-text="record.item.value"
                />
            </template>

            <template #action="data">
                <div class="flex justify-center items-center">
                    <Link
                        class="flex items-center mr-3"
                        :href="route('super_admin.site_configurations.edit', data.item.id)"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                </div>
            </template>
        </JTable>
    </div>
</template>

<script setup>
import { computed, reactive, onMounted } from 'vue';
import { route } from 'ziggy';
import JTable from '@commonComponents/JTable.vue';
import { CheckSquare } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import dom from '@left4code/tw-starter/dist/js/dom';

const pageProps = computed(() => usePage().props);

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'type'
        }, {
            key: 'value',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
});

onMounted(() => {
    dom('html')
        .attr('class', pageProps.value.settings.color);

    const favicon = document.querySelector('link[rel="icon"]') || document.createElement('link');
    favicon.rel = 'icon';
    favicon.href = pageProps.value.settings.fav_icon;

    document.head.appendChild(favicon);
});

</script>
