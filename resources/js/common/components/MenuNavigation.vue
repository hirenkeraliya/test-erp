<template>
    <div class="flex overflow-hidden">
        <div class="content content--top-nav !rounded px-[0!important]">
            <div class="mt-6">
                <div class="mb-12 grid gap-y-10 gap-x-6 md:grid-cols-2 xl:grid-cols-5">
                    <template
                        v-for="(menu, menuKey) in state.menu.subMenu"
                        :key="menuKey"
                    >
                        <div
                            v-if="menu.subSubMenu && checkPermission(menu.subSubMenu)"
                            class="relative flex flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md"
                        >
                            <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-primary-500/40 shadow-lg absolute -mt-4 grid h-10 w-10 place-items-center right-0">
                                <component
                                    :is="menuIcons[menu.icon]"
                                    class="h-5 w-5"
                                />
                            </div>
                            <div class="p-4 pr-20 text-left">
                                <h4
                                    v-if="loyaltyCampaignConfigurationsVisibility === true && menu.title === 'Campaign Management'"
                                    class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                                    v-text="'Loyalty Campaign'"
                                />

                                <h4
                                    v-else
                                    class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                                    v-text="menu.title"
                                />
                            </div>

                            <div class="border-t border-gray-200 p-4">
                                <ul>
                                    <span
                                        v-for="(subMenu, subMenuKey) in menu.subSubMenu"
                                        :key="subMenuKey"
                                    >
                                        <li
                                            v-if="subMenu.subSubSubMenu && checkPermission(subMenu.subSubSubMenu)"
                                            class="mb-4 pb-3 border-b border-gray-200 bg-slate-100 p-4 rounded-xl"
                                        >
                                            <div class="top-menu w-full flex items-center text-left mb-6">
                                                <div
                                                    class="top-menu__title text-base"
                                                    v-text="subMenu.title"
                                                />
                                            </div>
                                            <ul class="pl-4">
                                                <span
                                                    v-for="(subSubMenu, subSubMenuKey) in subMenu.subSubSubMenu"
                                                    :key="subSubMenuKey"
                                                >
                                                    <li
                                                        v-if="hasPermission(subSubMenu.permission)"
                                                        :class="{ 'mb-3 pb-3 border-b border-gray-200 test-2': subSubMenuKey < (subMenu.subSubSubMenu.length - 1) }"
                                                    >
                                                        <Link
                                                            class="top-menu w-full flex items-center text-left"
                                                            :href="redirectToRoute(subSubMenu)"
                                                        >
                                                            <div class="top-menu__icon mr-2">
                                                                <component
                                                                    :is="menuIcons[subSubMenu.icon]"
                                                                    class="text-primary"
                                                                />
                                                            </div>
                                                            <div
                                                                class="top-menu__title"
                                                                v-text="subSubMenu.title"
                                                            />
                                                        </Link>
                                                    </li>
                                                </span>
                                            </ul>
                                        </li>
                                        <li
                                            v-if="validateAccess(menu, subMenu) && subMenu.route_name && subMenu.title !== 'Ecommerce'"
                                            :class="{ 'mb-3 pb-3 border-b border-gray-200 test-1': subMenuKey < (menu.subSubMenu.length - 1) }"
                                        >
                                            <a
                                                v-if="loyaltyCampaignConfigurationsVisibility === true && subMenu.route_name === 'cx_pulse'"
                                                class="top-menu w-full flex items-center text-left cursor-pointer"
                                                @click="openInNewTab()"
                                            >
                                                <div class="top-menu__icon mr-2">
                                                    <component
                                                        :is="menuIcons[subMenu.icon]"
                                                        class="text-primary"
                                                    />
                                                </div>
                                                <div
                                                    class="top-menu__title"
                                                    v-text="subMenu.title"
                                                />
                                            </a>

                                            <Link
                                                v-else
                                                class="top-menu w-full flex items-center text-left"
                                                :href="redirectToRoute(subMenu)"
                                            >
                                                <div class="top-menu__icon mr-2">
                                                    <component
                                                        :is="menuIcons[subMenu.icon]"
                                                        class="text-primary"
                                                    />
                                                </div>
                                                <div
                                                    class="top-menu__title"
                                                    v-text="subMenu.title"
                                                />
                                            </Link>
                                        </li>
                                    </span>
                                </ul>
                            </div>
                        </div>
                    </template>
                    <template
                        v-for="(menu, menuKey) in state.menu.subMenu"
                        :key="menuKey"
                    >
                        <div v-if="!menu.subSubMenu && hasPermission(menu.permission)">
                            <Link
                                class="relative flex items-center flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md w-full"
                                :href="redirectToRoute(menu)"
                            >
                                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-primary-500/40 shadow-lg absolute -mt-4 grid h-10 w-10 place-items-center">
                                    <component
                                        :is="menuIcons[menu.icon]"
                                        class="h-5 w-5"
                                    />
                                </div>
                                <div class="pt-16 pb-14 p-4 text-center">
                                    <h4
                                        class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                                        v-text="menu.title"
                                    />
                                </div>
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { canAccess, checkMenuPermission } from '@commonServices/helper.js';
import { menuIcons } from '@commonServices/menuIcons';
import { onMounted, reactive, computed } from 'vue';
import { route } from 'ziggy';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const loyaltyCampaignConfigurationsVisibility = computed(() => pageProps.value.loyalty_campaign_configurations_visibility);
const productVariant = computed(() => pageProps.value.product_variant);

const props = defineProps({
    menus: {
        type: Object,
        required: true,
    },
    hasPermission: {
        type: Boolean,
        required: true,
    }
});
const state = reactive({
    menu: [],
});

const redirectToRoute = (menu) => {
    if (menu.route_name === 'cx_pulse') {
        return '';
    }

    if (menu.route_name) {
        return route(menu.route_name);
    }
};

const openInNewTab = () => {
    window.open('http://workflow.artisanscloud.com', '_blank');
};

onMounted(() => {
    const menu = props.menus.find(menu => menu.title === route().params.pageUrl);
    state.menu = menu;
});

const checkPermission = (menu) => {
    return props.hasPermission ? checkMenuPermission(menu) : true;
};

const hasPermission = (permissionName) => {
    if (
        loyaltyCampaignConfigurationsVisibility.value === false &&
        permissionName === 'loyalty_campaign_configuration_read_record'
    ) {
        return false;
    }

    if (
        loyaltyCampaignConfigurationsVisibility.value === false &&
        permissionName === 'rewards_read_record'
    ) {
        return false;
    }

    if (
        loyaltyCampaignConfigurationsVisibility.value === true &&
        permissionName === 'loyalty_campaign_read_record'
    ) {
        return false;
    }

    if (
        loyaltyCampaignConfigurationsVisibility.value === true &&
        permissionName === 'rewards_read_record'
    ) {
        return true;
    }

    if (
        loyaltyCampaignConfigurationsVisibility.value === true &&
        permissionName === 'cx_pulse_read_record'
    ) {
        return true;
    }

    if (
        productVariant.value === false &&
        permissionName === 'master_product_read_record'
    ) {
        return false;
    }


    return props.hasPermission ? canAccess(permissionName) : true;
};

const validateAccess = (menu, subMenu) => {
    if (pageProps.value.ecommerceStoreCount <= 0 && subMenu.title === 'Ecommerce') {
        return false;
    }

    return props.hasPermission ? menu.subSubMenu.length > 0 && subMenu.permission && hasPermission(subMenu.permission) : true;
};
</script>
