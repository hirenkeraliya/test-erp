<template>
    <nav class="side-nav !bg-slate-100">
        <ul>
            <li
                v-for="(dashboardPermissionRoute, index) in state.dashboardPermissionRoutes"
                :key="index"
            >
                <Link
                    class="side-menu cursor-pointer"
                    :class="getActiveMenuClass(dashboardPermissionRoute.route)"
                    :href="route(dashboardPermissionRoute.route)"
                >
                    <div class="side-menu__icon">
                        <Gauge
                            width="25"
                            height="25"
                        />
                    </div>
                    <div class="side-menu__title">
                        {{ dashboardPermissionRoute.title }}
                    </div>
                </Link>
            </li>
        </ul>
    </nav>
</template>

<script setup>
import { Gauge } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const state = reactive({
    dashboardPermissionRoutes: [
        {
            title: 'Stock Overview',
            route: 'warehouse_manager.stock_overview',
        }
    ]
});

const getActiveMenuClass = (routeName) => {
    const splitRouteName = pageProps.value.current_route_name.split('.');

    if (routeName.startsWith(splitRouteName[0] + '.' + splitRouteName[1])) {
        return 'side-menu--active';
    }

    return '';
};
</script>
