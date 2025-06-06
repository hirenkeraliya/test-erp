<template>
    <PageTitle title="Pos Advertisements" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Pos Advertisements
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.pos_advertisements.create')">
                <PrimaryButton
                    text="Add New Pos Advertisement"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.pos_advertisements.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by name"
    >
        <template #locations="record">
            {{ prepareImplodedNames(record.item.locations) }}
        </template>

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id, $event)"
                />
            </div>
        </template>

        <template #photo_url="record">
            <div
                v-if="record.item.photo_url"
                class=" flex justify-center"
            >
                <img
                    :src="record.item.photo_url"
                    width="50"
                >
            </div>

            <div v-if="record.item.video_url">
                <Tippy
                    content="Video Play"
                    class="cursor-pointer flex justify-center"
                    @click="openVideoPlayModal(record.item.video_url)"
                >
                    <PlayCircle class="text-indigo-900" />
                </Tippy>
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.pos_advertisements.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>

    <VideoPlay
        v-if="state.displayVideoPlayModal"
        :modal-show="state.displayVideoPlayModal"
        :video-url="state.videoUrl"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { prepareImplodedNames, exportRecords } from '@commonServices/helper';
import { CheckSquare, PlayCircle } from 'lucide-vue-next';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import VideoPlay from '@adminPages/pos_advertisement/partials/VideoPlay.vue';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
        }, {
            key: 'type',
        }, {
            key: 'photo_url',
            label: 'Photo/Video',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'locations',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    displayVideoPlayModal: false,
    videoUrl: null
});

const setStatus = (posAdvertisementId, status) => {
    router.post(route('admin.pos_advertisements.set_status', [posAdvertisementId, status ? 1 : 0]));
};

const openVideoPlayModal = (data) => {
    state.displayVideoPlayModal = true;
    state.videoUrl = data;
};

const closeModal = () => {
    state.displayVideoPlayModal = false;
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-pos-advertisements/',
        'pos-advertisements.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-pos-advertisements/',
        'pos-advertisements.xlsx',
        params,
        props.exportPermission
    );
};

</script>
