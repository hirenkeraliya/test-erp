<template>
    <div class="top-bar-boxed h-[120px] sm:h-[80px] md:h-[120px] lg:h-[65px] z-[51] border-b border-white/[0.08] mt-16 md:mt-0 -mx-3 sm:-mx-8 md:-mx-0 px-3 md:border-b-0 relative md:fixed md:inset-x-0 md:top-0 sm:px-8 md:px-10 md:pt-10 md:bg-gradient-to-b md:from-slate-100 md:to-transparent">
        <div class="h-auto sm:h-auto md:h-full flex items-center">
            <Link
                :href="route('warehouse_manager.dashboard')"
                class="logo -intro-x hidden md:flex items-center xl:w-[180px] mr-2"
            >
                <img
                    alt="logo"
                    class="logo__image w-10 text-white"
                    :src="companyLogo ? companyLogo : navbarLogo"
                >
                <span class="logo__text text-white text-lg ml-2 sm:hidden md:hidden lg:hidden xl:block">{{ companyName }}</span>
            </Link>

            <div class="ml-0 mr-auto sm:ml-auto sm:mr-3 md:mr-4 md:ml-auto block sm:flex md:block lg:flex items-center w-full sm:w-auto">
                <SearchBar
                    :menu="menus"
                    class="z-50"
                />

                <div class="-intro-x relative w-full md:w-56 mt-2 sm:mt-0 md:mt-2 lg:mt-0">
                    <div class="search block mr-3 sm:mr-0">
                        <FormSelectBox
                            :selected-record="locationSelection.location_id"
                            :records="state.locations"
                            validation-field-name="location_id"
                            class="single-select-box mt-[0px]"
                            placeholder="Please select location"
                            @update:selected-record="saveSelectedLocation"
                        />
                    </div>
                </div>
            </div>

            <p class="d-flex flex-column mb-3 mt-3 text-white intro-x relative ml-0 mr-3 sm:mr-6 hidden md:block">
                <span class="block border-b border-white pb-1">{{ loginUser }} ({{ staffId }}) </span>
                <span class="block font-bold pt-1">{{ getDisplayDateTime() }}</span>
            </p>

            <Notification
                mark-all-as-read-url="warehouse_manager.notifications.mark_all_as_read"
                fetch-url="warehouse_manager.notifications.fetch"
                fetch-read-notification-url="warehouse_manager.notifications.fetch_read_notification"
                mark-as-read-url="warehouse_manager.notifications.mark_as_read"
                mark-as-unread-url="warehouse_manager.notifications.mark_as_unread"
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
                        alt="Warehouse Manager Photo"
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
                                Edit Profile
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
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';
import { LogOut, User } from 'lucide-vue-next';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy';
import ObjectStorage from '@commonServices/storage.js';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { onMounted, reactive } from 'vue';
import axios from 'axios';
import { recordExistsInList, lastVisitedPage } from '@commonServices/helper';
import SearchBar from '@commonComponents/SearchBar.vue';
import menus from '@warehouseManager/navbar';
import Notification from '@commonComponents/Notification.vue';
import RecentlyVisitedPages from '@commonComponents/RecentlyVisitedPages.vue';
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
    companyName: {
        type: String,
        default: null,
    },
    companyLogo: {
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

const editProfile = (dismiss) => {
    router.get(route('warehouse_manager.edit_profile'));
    dismiss();
};

const logout = (dismiss) => {
    ObjectStorage.remove('warehouse-manager-warehouse-id');
    router.post(route('warehouse_manager.logout'));
    dismiss();
};

const locationSelection = useForm({
    location_id: null,
});

const state = reactive({
    locations: [],
    currentDateTime: null,
    localStorageName: 'warehouse_manager_recently_visited_page',
});

const saveSelectedLocation = (selectedLocation) => {
    locationSelection.location_id = selectedLocation;

    locationSelection.post(route('warehouse_manager.set_selected_warehouse'), {
        onSuccess: () =>
            ObjectStorage.save('warehouse-manager-warehouse-id', parseInt(selectedLocation)),
    });
};

const fetchCurrentTime = () => {
    axios.get(route('get_current_date_time')).then((response) => {
        state.currentDateTime = new Date(response.data);
    });
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

fetchCurrentTime();

const millisecondsInterval = 10000;

setInterval(() => {
    const secondsIncrement = 10;
    const currentTime = new Date(state.currentDateTime);
    currentTime.setSeconds(currentTime.getSeconds() + secondsIncrement);

    state.currentDateTime = currentTime;
}, millisecondsInterval);

onMounted(() => {
    axios
        .get(route('warehouse_manager.get_authorized_warehouses'))
        .then(function (response) {
            state.locations = response.data.locations;

            const locationId = ObjectStorage.get('warehouse-manager-warehouse-id');

            if (locationId && recordExistsInList(state.locations, locationId)) {
                locationSelection.location_id = locationId;
            }
        });

    const navigationDelay = 100;

    router.on('navigate', () => {
        setTimeout(() => {
            lastVisitedPage(state.localStorageName);
        }, navigationDelay);
    });
});
</script>
