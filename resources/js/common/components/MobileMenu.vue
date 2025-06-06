<template>
    <div
        class="mobile-menu md:hidden"
        :class="state.activeMobileMenu ? 'mobile-menu--active' : ''"
    >
        <div class="mobile-menu-bar">
            <a
                :href="homeUrl"
                class="flex mr-2"
            >
                <img
                    alt="logo"
                    class="w-6"
                    :src="companyLogo ? companyLogo : '/images/logo.png'"
                >
            </a>

            <p class="d-flex flex-column mb-3 mt-2 text-white relative mx-auto text-center text-xs sm:text-sm">
                <span class="block border-b border-white pb-1">{{ loginUser }} ({{ staffId }}) </span>
                <span class="block font-bold pt-1">{{ getDisplayDateTime() }}</span>
            </p>

            <button
                href="javascript:;"
                class="mobile-menu-toggler ml-2"
                @click="hideActiveMobileMenu()"
            >
                <BarChart2
                    class="w-8 h-8 text-white transform -rotate-90"
                />
            </button>
        </div>

        <div class="scrollable">
            <a
                href="javascript:;"
                class="mobile-menu-toggler"
            >
                <XCircle
                    class="w-8 h-8 text-white transform -rotate-90"
                    @click="hideActiveMobileMenu()"
                />
            </a>

            <ul class="scrollable__content py-2">
                <li class="border-b-[1px] border-slate-500 mb-8 pb-2">
                    <button class="menu menu--active">
                        <div class="menu__icon">
                            <a
                                :href="homeUrl"
                                class="flex"
                            >
                                <img
                                    alt="logo"
                                    class="w-14"
                                    :src="companyLogo ? companyLogo : '/images/logo.png'"
                                >
                            </a>
                        </div>
                        <div class="menu__title text-left text-xs sm:text-sm">
                            {{ companyName === '' ? appName : companyName }}
                        </div>
                    </button>
                </li>
                <li
                    v-for="(menu, menuKey) in state.menus"
                    :key="menuKey"
                >
                    <button
                        v-if="checkMobileMenuPermission(menu, hasPermission)"
                        class="menu menu--active"
                        :class="menu.is_open ? 'menu--open' : ''"
                        @click.prevent="menu.title !== 'Dashboard' ? linkTo(menu) : menu.is_open = !menu.is_open"
                    >
                        <div class="menu__icon">
                            <component :is="menuIcons[menu.icon]" />
                        </div>

                        <div class="menu__title">
                            {{ menu.title }}

                            <ChevronUp
                                v-if="menu.is_open"
                            />

                            <ChevronDown
                                v-if="!menu.is_open && menu.subMenu"
                                class="w-5 h-5"
                            />
                        </div>
                    </button>

                    <transition
                        @enter="enter"
                        @leave="leave"
                    >
                        <ul v-if="menu.subMenu && menu.is_open">
                            <span
                                v-for="(submenu, submenuKey) in menu.subMenu"
                                :key="submenuKey"
                            >
                                <li>
                                    <button
                                        v-if="checkMobileMenuPermission(submenu, hasPermission)"
                                        class="menu menu--active"
                                        :class="submenu.is_open ? 'menu--open' : ''"
                                        @click.prevent="linkTo(submenu)"
                                    >
                                        <div class="menu__icon">
                                            <component :is="menuIcons[submenu.icon]" />
                                        </div>

                                        <div class="menu__title">
                                            {{ submenu.title }}

                                            <ChevronDown
                                                v-if="!submenu.is_open && submenu.subSubMenu"
                                                class="w-5 h-5"
                                            />
                                            <ChevronUp
                                                v-if="submenu.is_open"
                                            />
                                        </div>
                                    </button>

                                    <transition
                                        @enter="enter"
                                        @leave="leave"
                                    >
                                        <ul v-if="submenu.subSubMenu && submenu.is_open">
                                            <span
                                                v-for="(lastSubMenu, lastSubMenuKey) in submenu.subSubMenu"
                                                :key="lastSubMenuKey"
                                            >
                                                <!-- v-if="lastSubMenu?.permission === undefined ? !canAccess(lastSubMenu?.permission) : canAccess(lastSubMenu?.permission)" -->
                                                <li>
                                                    <button
                                                        v-if="checkMobileMenuPermission(lastSubMenu, hasPermission)"
                                                        class="menu menu--active"
                                                        :class="lastSubMenu.is_open ? 'menu--open' : ''"
                                                        @click.prevent="linkTo(lastSubMenu)"
                                                    >
                                                        <div class="menu__icon">
                                                            <component :is="menuIcons[lastSubMenu.icon]" />
                                                        </div>

                                                        <div class="menu__title">
                                                            {{ lastSubMenu.title }}

                                                            <ChevronDown
                                                                v-if="!lastSubMenu.is_open && lastSubMenu.subSubSubMenu"
                                                                class="w-5 h-5"
                                                            />
                                                            <ChevronUp
                                                                v-if="lastSubMenu.is_open"
                                                            />
                                                        </div>
                                                    </button>

                                                    <transition
                                                        @enter="enter"
                                                        @leave="leave"
                                                    >
                                                        <ul v-if="lastSubMenu.subSubSubMenu && lastSubMenu.is_open">
                                                            <span
                                                                v-for="(lastSubSubMenu, lastSubSubMenuKey) in lastSubMenu.subSubSubMenu"
                                                                :key="lastSubSubMenuKey"
                                                            >
                                                                <li
                                                                    v-if="canAccess(lastSubSubMenu.permission)"
                                                                >
                                                                    <button
                                                                        class="menu menu--active"
                                                                        :class="lastSubSubMenu.is_open ? 'menu--open' : ''"
                                                                        @click.prevent="linkTo(lastSubSubMenu)"
                                                                    >
                                                                        <div class="menu__icon">
                                                                            <component :is="menuIcons[lastSubSubMenu.icon]" />
                                                                        </div>

                                                                        <div class="menu__title">
                                                                            {{ lastSubSubMenu.title }}
                                                                        </div>
                                                                    </button>
                                                                </li>
                                                            </span>
                                                        </ul>
                                                    </transition>
                                                </li>
                                            </span>
                                        </ul>
                                    </transition>
                                </li>
                            </span>
                        </ul>
                    </transition>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { canAccess, checkMobileMenuPermission } from '@commonServices/helper.js';
import { menuIcons } from '@commonServices/menuIcons';
import dom from '@left4code/tw-starter/dist/js/dom';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { BarChart2, ChevronDown, ChevronUp, XCircle } from 'lucide-vue-next';
import SimpleBar from 'simplebar';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    companyLogo: {
        type: String,
        default: null,
    },
    companyName: {
        type: String,
        default: null,
    },
    loginUser: {
        type: String,
        default: null,
    },
    staffId: {
        type: String,
        default: null,
    },
    menus: {
        type: Object,
        required: true,
    },
    homeUrl: {
        type: String,
        required: true,
    },
    hasPermission: {
        type: Boolean,
        required: true,
    }
});

const state = reactive({
    activeMobileMenu: false,
    menus: props.menus
});

const hideActiveMobileMenu = () => {
    state.activeMobileMenu = !state.activeMobileMenu;
};

const linkTo = (menu) => {
    menu.is_open = !menu.is_open;
    if (menu.route_name) {
        router.get(route(menu.route_name));
        hideActiveMobileMenu();
    }
};

const animationDuration = 300;

const enter = (el) => {
    dom(el).slideDown(animationDuration);
};

const leave = (el) => {
    dom(el).slideUp(animationDuration);
};

onMounted(() => {
    if (dom('.mobile-menu .scrollable').length) {
        new SimpleBar(dom('.mobile-menu .scrollable')[0]);
    }

    fetchCurrentTime();
});

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

const intervalTime = 10000;

setInterval(() => {
    const currentTime = new Date(state.currentDateTime);
    const secondsIncrement = 10;

    currentTime.setSeconds(currentTime.getSeconds() + secondsIncrement);

    state.currentDateTime = currentTime;
}, intervalTime);

const appName = import.meta.env.VITE_APP_NAME;
</script>
