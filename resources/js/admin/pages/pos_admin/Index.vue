<template>
    <PageTitle title="App Releases" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            App Releases
        </h2>
    </div>

    <JSimpleTable
        :columns="state.columns"
        :records="appReleases ?? []"
        :allow-search="true"
    >
        <template #link="data">
            <a
                :href="data.item.link"
                target="_blank"
            >
                <PrimaryButton
                    text="Download App"
                    class="shadow-md"
                />
            </a>
        </template>
    </JSimpleTable>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';

defineProps({
    appReleases: {
        type: Object,
        default: null,
    }
});

const state = reactive({
    columns: [
        {
            key: 'released_at',
            sortable: true
        }, {
            key: 'version',
            bodyClass: 'text-center',
            sortable: true
        }, {
            key: 'link',
            label: 'Download',
            bodyClass: 'text-center',
            sortable: true
        },
    ],
});
</script>
