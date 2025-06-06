<template>
    <Modal
        v-slot="{ dismiss }"
        size="modal-xl"
        :show="state.openModalForNotification"
        @hidden="hideNotificationModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Notifications
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideNotificationModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <div>
                <div
                    v-if="state.notifications.length"
                    :class="state.notifications.length > 3 ? 'overflow-y-scroll h-72' : ''"
                    class="mb-4 pr-3"
                >
                    <div
                        v-for="(notification, index) in state.notifications"
                        :key="index"
                        class="cursor-pointer relative"
                        :class="{ index }"
                    >
                        <div class="flex items-start group relative w-full py-3 hover:bg-slate-50 rounded-lg">
                            <div class="mt-3">
                                <Tippy
                                    content="Mark as Read"
                                >
                                    <FormCheckbox
                                        :check-value="notification.is_read"
                                        class="ml-2"
                                        @change="updateCheckboxForRead(notification.is_read, index)"
                                    />
                                </Tippy>
                            </div>
                            <div class="flex-none flex items-center justify-center bg-primary/20 text-primary w-10 h-10 rounded-full mr-3">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <h1 class="flex items-center justify-between mb-0.5 text-primary text-sm font-normal">
                                    <span
                                        class="text-primary font-medium"
                                        v-html="notification.message"
                                    />
                                </h1>
                                <Tippy
                                    tag="a"
                                    class="text-xs text-slate-400"
                                    :content="notification.time"
                                >
                                    {{ notification.human_time }}
                                </Tippy>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <p class="text-sm text-center text-slate-700 mb-3">
                        No new notifications at this time.
                    </p>
                </div>
            </div>

            <button
                v-if="Object.keys(state.notificationIds).length > 0"
                class="flex items-center text-success justify-center w-full text-sm font-medium bg-slate-100 p-3 hover:text-dark rounded-lg"
                @click="markAsRead()"
            >
                <Check class="mr-1 w-4 h-4" />Mark as read
            </button>

            <button
                v-if="Object.keys(state.notifications).length > 0"
                class="flex items-center text-success justify-center w-full text-sm font-medium bg-slate-100 p-3 hover:text-dark rounded-lg mt-2"
                @click="markAllAsRead(dismiss)"
            >
                <Check class="mr-1 w-4 h-4" />Mark all as read
            </button>
        </ModalBody>
    </Modal>
    <Dropdown
        v-slot="{ dismiss }"
        class="intro-x mr-4 sm:mr-4"
    >
        <DropdownToggle
            tag="div"
            role="button"
            class="notification cursor-pointer"
        >
            <div
                v-if="state.notifications.length"
                class="flex absolute h-5 w-5 -top-3 -right-0.5 z-10"
            >
                <span class="animate-ping absolute flex h-full w-full rounded-full bg-red-600/80 opacity-80" />
                <span class="relative flex rounded-full h-5 w-5 bg-red-600 text-white justify-center items-center text-xs p-1 -tracking-[1px]">{{ state.notifications.length }}</span>
            </div>

            <Bell
                class="notification__icon text-white"
                :class="state.notifications.length > 0 ? 'animate-bell' : ''"
            />
        </DropdownToggle>

        <DropdownMenu class="notification-content notification-content-transform pt-[18px]">
            <DropdownContent
                tag="div"
                class="notification-content__box"
            >
                <div>
                    <div class="notification-content__title w-full text-base font-medium bg-slate-100 p-1 text-dark rounded-md text-center flex items-center justify-center">
                        Notifications
                    </div>

                    <div>
                        <TabGroup class="md:ml-0 md:pl-0 mb-5">
                            <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-1 items-center">
                                <Tab
                                    class="w-full py-2 px-2 leading-none active"
                                    tag="button"
                                    @click="allUnreadMessage()"
                                >
                                    Inbox
                                </Tab>
                                <Tab
                                    class="w-full py-2 px-2 leading-none"
                                    tag="button"
                                    @click.prevent="allMessage()"
                                >
                                    Archived
                                </Tab>
                            </TabList>

                            <TabPanels class="mt-5 float-clean">
                                <TabPanel class="w-full active">
                                    <div
                                        v-if="state.notifications.length"
                                        :class="state.notifications.length > 3 ? 'overflow-y-scroll h-72' : ''"
                                        class="mb-4 pr-3"
                                    >
                                        <div
                                            v-for="(notification, index) in state.notifications"
                                            :key="index"
                                            class="cursor-pointer relative"
                                            :class="{ index }"
                                        >
                                            <div class="flex items-start group relative w-full py-3 hover:bg-slate-50 rounded-lg">
                                                <div class="mt-3">
                                                    <Tippy
                                                        content="Mark as Read"
                                                    >
                                                        <FormCheckbox
                                                            :check-value="notification.is_read"
                                                            class="ml-2"
                                                            @change="updateCheckboxForRead(notification.is_read, index)"
                                                        />
                                                    </Tippy>
                                                </div>
                                                <div class="flex-none flex items-center justify-center bg-primary/20 text-primary w-10 h-10 rounded-full mr-3">
                                                    <User class="w-4 h-4" />
                                                </div>
                                                <div>
                                                    <h1 class="flex items-center justify-between mb-0.5 text-primary text-sm font-normal">
                                                        <span
                                                            class="text-primary font-medium"
                                                            v-html="notification.message"
                                                        />
                                                    </h1>
                                                    <Tippy
                                                        tag="a"
                                                        class="text-xs text-slate-400"
                                                        :content="notification.time"
                                                    >
                                                        {{ notification.human_time }}
                                                    </Tippy>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else>
                                        <p class="text-sm text-center text-slate-700 mb-3">
                                            No new notifications at this time.
                                        </p>
                                    </div>
                                </TabPanel>

                                <TabPanel class="w-full leading-relaxed">
                                    <div class="w-full flex justify-center">
                                        <div
                                            v-if="state.allNotifications.length"
                                            :class="state.allNotifications.length > 3 ? 'overflow-y-scroll h-72' : ''"
                                            class="mb-4 pr-3"
                                        >
                                            <div
                                                v-for="(allNotification, index) in state.allNotifications"
                                                :key="index"
                                                class="cursor-pointer relative"
                                                :class="{ index }"
                                            >
                                                <div class="flex items-start group relative w-full py-3 hover:bg-slate-50 rounded-lg">
                                                    <div class="mt-3">
                                                        <Tippy
                                                            content="Mark as UnRead"
                                                        >
                                                            <FormCheckbox
                                                                :check-value="allNotification.is_unread"
                                                                class="ml-2"
                                                                @change="updateCheckboxForUnRead(allNotification.is_unread, index)"
                                                            />
                                                        </Tippy>
                                                    </div>
                                                    <div class="flex-none flex items-center justify-center bg-primary/20 text-primary w-10 h-10 rounded-full mr-3">
                                                        <User class="w-4 h-4" />
                                                    </div>
                                                    <div>
                                                        <h1 class="flex items-center justify-between mb-0.5 text-primary text-sm font-normal">
                                                            <span
                                                                class="text-primary font-medium"
                                                                v-html="allNotification.message"
                                                            />
                                                        </h1>
                                                        <Tippy
                                                            tag="p"
                                                            class="text-xs text-slate-400"
                                                            :content="allNotification.time"
                                                        >
                                                            {{ allNotification.human_time }}
                                                        </Tippy>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <p class="text-sm text-slate-700 mb-3">
                                                No new notifications at this time.
                                            </p>
                                        </div>
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </TabGroup>
                    </div>

                    <button
                        v-if="Object.keys(state.notificationIds).length > 0"
                        class="flex items-center text-success justify-center w-full text-sm font-medium bg-slate-100 p-3 hover:text-dark rounded-lg"
                        @click="markAsRead()"
                    >
                        <Check class="mr-1 w-4 h-4" />Mark as read
                    </button>

                    <button
                        v-if="Object.keys(state.notifications).length > 0"
                        class="flex items-center text-success justify-center w-full text-sm font-medium bg-slate-100 p-3 hover:text-dark rounded-lg mt-2"
                        @click="markAllAsRead(dismiss)"
                    >
                        <Check class="mr-1 w-4 h-4" />Mark all as read
                    </button>

                    <button
                        v-if="Object.keys(state.allNotificationIds).length > 0"
                        class="flex items-center text-success justify-center w-full text-sm font-medium bg-slate-100 p-3 hover:text-dark rounded-lg mt-2"
                        @click="markAsUnRead()"
                    >
                        <Check class="mr-1 w-4 h-4" />Mark as Unread
                    </button>
                </div>
            </DropdownContent>
        </DropdownMenu>
    </Dropdown>
</template>

<script setup>
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import { TabPanel, TabGroup, TabPanels, Tab, TabList } from '@commonVendor/tab';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { route } from 'ziggy';
import { User, Check, Bell, X } from 'lucide-vue-next';
import { onMounted, reactive } from 'vue';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
} from '@commonVendor/dropdown';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';

const props = defineProps({
    markAllAsReadUrl: {
        type: String,
        required: true
    },
    fetchUrl: {
        type: String,
        required: true
    },
    fetchReadNotificationUrl: {
        type: String,
        required: true
    },
    markAsReadUrl: {
        type: String,
        required: true
    },
    markAsUnreadUrl: {
        type: String,
        required: true
    }
});

const state = reactive({
    notifications: [],
    allNotifications: [],
    notificationIds: [],
    allNotificationIds: [],
    openModalForNotification: false,
});

const markAllAsRead = (dismiss) => {
    state.notifications = [];
    router.post(route(props.markAllAsReadUrl));
    dismiss();
};

const allUnreadMessage = (openModel = false) => {
    state.allNotificationIds = [];
    state.notificationIds = [];
    axios.get(route(props.fetchUrl)).then((response) => {
        state.notifications = response.data.notifications;
        if (state.notifications.length > 0) {
            state.openModalForNotification = openModel;
        }
    });
};

const allMessage = () => {
    state.allNotificationIds = [];
    state.notificationIds = [];
    axios.get(route(props.fetchReadNotificationUrl)).then((response) => {
        state.allNotifications = response.data.read_notifications;
    });
    state.notifications = [];
};

const updateCheckboxForRead = (isRead, index) => {
    state.notifications[index].is_read = !isRead;
    const notificationId = state.notifications[index].id;

    if (isRead === false) {
        state.notificationIds.push(notificationId);
        return;
    }

    const removeNotificationIdIndex = state.notificationIds.findIndex((id) => id === notificationId);
    if (removeNotificationIdIndex < 0) return;
    state.notificationIds.splice(removeNotificationIdIndex, 1);
};

const updateCheckboxForUnRead = (isUnRead, index) => {
    state.allNotifications[index].is_unread = !isUnRead;

    const notificationId = state.allNotifications[index].id;

    if (isUnRead === false) {
        state.allNotificationIds.push(notificationId);
        return;
    }

    const removeNotificationIdIndex = state.allNotificationIds.findIndex((id) => id === notificationId);
    if (removeNotificationIdIndex < 0) return;
    state.allNotificationIds.splice(removeNotificationIdIndex, 1);
};

const markAsRead = () => {
    axios.post(route(props.markAsReadUrl, { notification_ids: state.notificationIds })).then(() => {
        allUnreadMessage();
    });
};

const markAsUnRead = () => {
    axios.post(route(props.markAsUnreadUrl, { notification_ids: state.allNotificationIds })).then(() => {
        allMessage();
    });
};

const hideNotificationModal = () => {
    state.openModalForNotification = false;
};

onMounted(() => {
    allUnreadMessage(true);
    allMessage();
});
</script>
