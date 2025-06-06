<template>
    <div class="py-5 md:py-0 overflow-x-hidden">
        <MobileMenu
            :company-logo="pageProps.company_logo"
            :company-name="pageProps.company_name"
            :login-user="pageProps.logged_in_user_name"
            :staff-id="pageProps.logged_in_user_staff_id"
            :home-url="route('warehouse_manager.dashboard')"
            :menus="menus"
            :has-permission="false"
        />

        <TopBar
            class="top-bar-boxed--top-menu"
            :login-user="pageProps.logged_in_user_name"
            :staff-id="pageProps.logged_in_user_staff_id"
            :company-name="pageProps.company_name"
            :company-logo="pageProps.company_logo"
            :navbar-logo="pageProps.settings.navbar_logo"
        />

        <div
            v-if="pageProps.environment"
            class="text-center mt-24 mb-8 ml-5 rounded z-50 bg-red-600 text-white p-1 w-40 capitalize font-extrabold text-lg drop-shadow-2xl"
        >
            {{ pageProps.environment }}
        </div>

        <nav
            class="top-nav"
            :class="pageProps.environment ? '!p-0' : ''"
        >
            <ul>
                <li
                    v-for="(menu, menuKey) in menus"
                    :key="menuKey"
                >
                    <Link
                        class="top-menu w-full"
                        :class="getActiveMenuClass(menu)"
                        :href="redirectToRoute(menu)"
                    >
                        <div class="top-menu__icon">
                            <component :is="menuIcons[menu.icon]" />
                        </div>
                        <div class="top-menu__title">
                            {{ menu.title }}
                        </div>
                    </Link>
                </li>
            </ul>
        </nav>

        <div v-if="isDashboardRoute()">
            <slot />
        </div>

        <div
            v-else
            class="content content--top-nav mx-5"
        >
            <slot />
        </div>

        <div
            v-show="state.scrollY > 100"
            class="fixed right-4 bottom-4 z-50 rounded-full bg-primary cursor-pointer text-white w-10 h-10 flex items-center justify-center transition ease-in-out delay-150 hover:-translate-y-1 hover:scale-110 hover:bg-primary/90 duration-300 border sm:border-white sm:border md:border-0"
            @click="BackToTop"
        >
            <ChevronUp class="w-6 h-6" />
        </div>
    </div>
</template>

<script setup>
import TopBar from '@warehouseManagerComponents/TopBar.vue';
import MobileMenu from '@commonComponents/MobileMenu.vue';
import menus from '@warehouseManager/navbar';
import { usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive } from 'vue';
import dom from '@left4code/tw-starter/dist/js/dom';
import { evaluateValidationErrors, evaluateFlashMessagesToast } from '@commonServices/displayErrors';
import { route } from 'ziggy';
import ObjectStorage from '@commonServices/storage.js';
import { menuIcons } from '@commonServices/menuIcons';
import { ChevronUp } from 'lucide-vue-next';

const pageProps = computed(() => usePage().props);

const state = reactive({
    scrollTimer: 0,
    scrollY: 0,
});

onMounted(() => {
    dom('body').removeClass('login').addClass('main');
    window.addEventListener('scroll', handleScroll);

    if (!ObjectStorage.get('warehouse-manager-warehouse-id')) {
        router.get(route('warehouse_manager.warehouse_selection'));
    }
});

evaluateFlashMessagesToast(pageProps);

evaluateValidationErrors(pageProps);

const getActiveMenuClass = (menu) => {
    const splitRouteName = pageProps.value.current_route_name.split('.');
    const currentURL = decodeURI(document.URL).split('/');
    const currentSelectedSectionTitle = currentURL[currentURL.length - 1];

    if (menu.title === currentSelectedSectionTitle) {
        return 'top-menu--active';
    }

    if (menu.subMenu && menu.subMenu.length) {
        for (const key in menu.subMenu) {
            if (menu.subMenu[key].route_name.startsWith(splitRouteName[0] + '.' + splitRouteName[1])) {
                return 'top-menu--active';
            }

            if (menu.subMenu[key].subSubMenu && menu.subMenu[key].subSubMenu.length) {
                for (const childKey in menu.subMenu[key].subSubMenu) {
                    if (menu.subMenu[key].subSubMenu[childKey].route_name.startsWith(
                        splitRouteName[0] + '.' + splitRouteName[1])
                    ) {
                        return 'top-menu--active';
                    }
                }
            }
        }

        return;
    }

    const routeName = splitRouteName[1].replaceAll('_', ' ');
    const menuRouteTitle = menu.title.toLowerCase();
    return routeName.includes(menuRouteTitle) ? 'top-menu--active' : '';
};

const redirectToRoute = (menu) => {
    if (menu.route_name) {
        return route(menu.route_name);
    }

    return route('warehouse_manager.menu_page', { pageUrl: menu.title });
};

const isDashboardRoute = () => {
    const dashboardRoutes = [
        'warehouse_manager.stock_overview',
    ];

    if (dashboardRoutes.includes(pageProps.value.current_route_name)) {
        return true;
    }

    return false;
};

const handleScroll = () => {
    const scrollDebounceTime = 100;
    
    state.scrollTimer = setTimeout(() => {
        state.scrollY = window.scrollY;
        clearTimeout(state.scrollTimer);
        state.scrollTimer = 0;
    }, scrollDebounceTime);
};

const BackToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
};
</script>
