<template>
    <PageTitle title="Banners" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Banners
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.banners.create')">
                <PrimaryButton
                    text="Add New Banner"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.banners.fetch')"
        :columns="state.columns"
        search-title="Search by name or description"
    >
        <template #image="record">
            <div class="flex justify-center items-center">
                <img
                    v-if="record.item.image"
                    :src="record.item.image"
                    :alt="record.item.name"
                    width="100"
                    class="m-auto"
                >
                <span
                    v-else
                    class=""
                >N/A</span>
            </div>
        </template>

        <template #action_type="data">
            <div class="">
                {{ capitalize(data.item.action_type) }}
            </div>
        </template>

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="updateStatus(data.item.id, $event)"
                />
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.banners.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { CheckSquare, RefreshCw } from 'lucide-vue-next';
import JSwitch from '@commonComponents/JSwitch.vue';
import { capitalize } from '@commonServices/helper';
import { route } from 'ziggy';
import axios from 'axios';
import { showSuccessNotification } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const updateStatus = (bannerId, status) => {
    router.post(route('admin.banners.update_status', [bannerId, status ? 1 : 0]));
};

const props = defineProps({
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'image',
            label: 'Image',
            bodyClass: 'text-center',
            headerClass: 'text-center',
            isDisplay: true,
        },
        {
            key: 'name',
            sortable: true,
        },
        {
            key: 'description',
            sortable: true,
        },
        {
            key: 'action_type',
        },
        {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    disableRefreshButton: props.hasPendingSyncTransaction,
});

const syncData = (id, dismiss) => {
    axios.get(route('admin.banners.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};
</script>
