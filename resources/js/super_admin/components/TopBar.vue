<template>
    <div class="top-bar-boxed h-[55px] sm:h-[55px] md:h-[65px] z-[51] border-b border-white/[0.08] mt-16 md:mt-0 -mx-3 sm:-mx-8 md:-mx-0 px-3 md:border-b-0 relative md:fixed md:inset-x-0 md:top-0 sm:px-8 md:px-10 md:pt-10 md:bg-gradient-to-b md:from-slate-100 md:to-transparent before:h-[65px] after:h-[65px]">
        <div class="h-auto sm:h-auto md:h-full flex items-center">
            <Link
                :href="route('super_admin.dashboard')"
                class="logo -intro-x hidden md:flex items-center xl:w-[180px] mr-2"
            >
                <img
                    alt="logo"
                    class="logo__image w-10 text-white"
                    :src="navbarLogo"
                >
                <span class="logo__text text-white text-lg ml-2 sm:hidden md:hidden lg:hidden xl:block">{{ appName }}</span>
            </Link>

            <SearchBar
                class="ml-0 mr-auto md:mr-0 md:ml-auto z-50"
                :menu="menus"
            />

            <p class="d-flex flex-column mb-3 mt-3 text-white intro-x relative ml-0 mr-3 sm:mr-6 hidden md:block">
                <span class="block border-b border-white pb-1">{{ loginUser }}</span>
                <span class="block font-bold pt-1">{{ getDisplayDateTime() }}</span>
            </p>

            <Notification
                mark-all-as-read-url="super_admin.notifications.mark_all_as_read"
                fetch-url="super_admin.notifications.fetch"
                fetch-read-notification-url="super_admin.notifications.fetch_read_notification"
                mark-as-read-url="super_admin.notifications.mark_as_read"
                mark-as-unread-url="super_admin.notifications.mark_as_unread"
            />

            <HelpMenu />

            <RecentlyVisitedPages
                :local-storage-name="state.localStorageName"
            />

            <Dropdown
                v-slot="{ dismiss }"
                class="intro-x w-8 h-8"
            >
                <DropdownToggle
                    tag="div"
                    role="button"
                    class="w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in scale-100 md:scale-110"
                >
                    <img
                        alt="Super Admin Photo"
                        src="/images/user-avatar.png"
                    >
                </DropdownToggle>

                <DropdownMenu class="w-80">
                    <DropdownContent class="before:block before:absolute before:bg-black before:inset-0 before:rounded-md before:z-[-1]">
                        <div>
                            <figure class="flex items-center text-base font-medium rounded-[8px] bg-slate-100 py-4 px-4 mb-3">
                                <img
                                    class="mr-3 w-11 h-11 rounded-full"
                                    src="/images/profile-avatar.jpg"
                                    alt="user"
                                >
                                <figcaption>
                                    <h1 class="text-dark mb-0.5 text-sm">
                                        {{ loginUser }}
                                    </h1>
                                </figcaption>
                            </figure>
                            <DropdownItem
                                class="dropdown-item cursor-pointer inline-flex items-center text-black hover:bg-slate-50 hover:text-primary hover:pl-6 w-full py-3 text-sm transition-all ease-in-out delay-150 rounded mb-5"
                                @click="editProfile(dismiss)"
                            >
                                <User class="w-4 h-4 mr-2" />
                                Profile
                            </DropdownItem>
                            <DropdownItem
                                class="dropdown-item cursor-pointer inline-flex items-center text-black hover:bg-slate-50 hover:text-primary hover:pl-6 w-full py-3 text-sm transition-all ease-in-out delay-150 rounded mb-5"
                                @click="changePassword(dismiss)"
                            >
                                <Lock class="w-4 h-4 mr-2" />
                                Change Password
                            </DropdownItem>
                            <DropdownToggle
                                role="button"
                                class="cursor-pointer flex items-center justify-center text-black w-full text-sm font-medium bg-slate-100 hover:text-primary p-3 rounded-lg"
                                @click="logout(dismiss)"
                            >
                                <LogOut class="w-4 h-4 mr-2" />
                                Logout
                            </DropdownToggle>
                        </div>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>
    </div>
</template>

<script setup>
import menus from '@superAdmin/navbar';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import { Lock, LogOut, User } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy';
import SearchBar from '@commonComponents/SearchBar.vue';
import { onMounted, reactive } from 'vue';
import axios from 'axios';
import Notification from '@commonComponents/Notification.vue';
import RecentlyVisitedPages from '@commonComponents/RecentlyVisitedPages.vue';
import { lastVisitedPage } from '@commonServices/helper';
import HelpMenu from '@commonComponents/HelpMenu.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
const helpStore = useHelpCenterStore();

router.on('start', () => {
    helpStore.setHelpData(null);
});

defineProps({
    loginUser: {
        type: String,
        default: null,
    },
    staffId: {
        type: String,
        default: null,
    },
    navbarLogo: {
        type: String,
        default: null,
    },
});

const appName = import.meta.env.VITE_APP_NAME;

const state = reactive({
    currentDateTime: null,
    localStorageName: 'super_admin_recently_visited_page',
});

const fetchCurrentTime = () => {
    axios.get(route('get_current_date_time')).then((response) => {
        state.currentDateTime = new Date(response.data);
    });
};

const logout = (dismiss) => {
    router.post(route('super_admin.logout'));
    dismiss();
};

const changePassword = (dismiss) => {
    router.get(route('super_admin.change_password'));
    dismiss();
};

const editProfile = (dismiss) => {
    router.get(route('super_admin.super_admins.edit_profile'));
    dismiss();
};

const getDisplayDateTime = () => {
    if (!state.currentDateTime) return '';

    const options = { hour12: true, hour: 'numeric', minute: 'numeric', second: 'numeric' };
    const time = state.currentDateTime.toLocaleTimeString('en-US', options);
    const padLength = 2;

    const month = (state.currentDateTime.getMonth() + 1).toString().padStart(padLength, '0');
    const date = state.currentDateTime.getDate().toString().padStart(padLength, '0');
    const year = state.currentDateTime.getFullYear().toString();

    return `${date}-${month}-${year} ${time}`;
};

const secondsInMilliseconds = 1000;

setInterval(() => {
    const currentTime = new Date(state.currentDateTime);
    const secondsToAdd = 10;
    currentTime.setSeconds(currentTime.getSeconds() + secondsToAdd);

    state.currentDateTime = currentTime;
}, secondsInMilliseconds);

onMounted(() => {
    const timeoutDelay = 100;
    fetchCurrentTime();

    router.on('navigate', () => {
        setTimeout(() => {
            lastVisitedPage(state.localStorageName);
        }, timeoutDelay);
    });
});
</script>
