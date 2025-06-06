<template>
    <PageTitle title="Manual Notifications" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Manual Notifications
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.manual_notifications.create')">
                <PrimaryButton
                    text="Add New Manual Notification"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.manual_notifications.fetch')"
        :columns="state.columns"
        search-title="Search by title,message,type or status"
    >
        <template #info="record">
            <div class="flex items-center justify-center cursor-pointer">
                <List
                    @click="showManualNotificationDetailsModal(record.item)"
                />
            </div>
        </template>
    </JTable>
    <Details
        :modal-show="state.displayManualNotificationDetailsModal"
        :manual-notification-details="state.manualNotificationDetails"
        :columns-for-manual-notification-details="state.columnsForManualNotificationDetails"
        :type="state.type"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import Details from '@adminPages/manual_notifications/Details.vue';
import axios from 'axios';
import { List } from 'lucide-vue-next';

const state = reactive({
    columns: [
        {
            key: 'title',
            sortable: true
        }, {
            key: 'message',
            sortable: true
        }, {
            key: 'type_id',
            label: 'Type',
            sortable: true
        }, {
            key: 'status',
            sortable: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'info',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    columnsForManualNotificationDetails: [
        {
            key: 'name',
            sortable: true
        },
    ],
    displayManualNotificationDetailsModal: false,
    manualNotificationDetails: [],
    type: '',
});

const showManualNotificationDetailsModal = (manualNotification) => {
    state.manualNotificationDetails = [];

    axios.get(route('admin.manual_notifications.fetch_details', manualNotification.id))
        .then((response) => {
            state.manualNotificationDetails = response.data.manual_notification_details;
            state.type = response.data.manual_notification_type;
        });

    state.displayManualNotificationDetailsModal = true;
};

const closeModal = () => {
    state.manualNotificationDetails = [];
    state.displayManualNotificationDetailsModal = false;
};
</script>
