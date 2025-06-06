<template>
    <nav class="side-nav !bg-slate-100">
        <ul>
            <li
                v-for="(dashboardPermissionRoute, index) in state.dashboardPermissionRoutes"
                :key="index"
            >
                <Link
                    v-if="canAccess(dashboardPermissionRoute.permission) || dashboardPermissionRoute.permission === true"
                    :href="route(dashboardPermissionRoute.route)"
                    class="side-menu cursor-pointer"
                    :class="getActiveMenuClass(dashboardPermissionRoute.route)"
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
import { canAccess } from '@commonServices/helper.js';

const pageProps = computed(() => usePage().props);

const demandForecastingDashboardVisibility = computed(() => pageProps.value.demand_forecasting_dashboard_visibility);

const environment = pageProps.value.environment;

const state = reactive({
    dashboardPermissionRoutes: [
        {
            title: 'Orders',
            route: 'admin.dashboard',
            permission: environment === 'local' ? true : 'dashboard_operational',
        }, {
            title: 'Revenue',
            route: 'admin.revenue_view',
            permission: environment === 'local' ? true : 'dashboard_store_revenue',
        }, {
            title: 'Product',
            route: 'admin.store_revenue',
            permission: environment === 'local' ? true : 'dashboard_store_revenue',
        }, {
            title: 'Company',
            route: 'admin.business_view',
            permission: environment === 'local' ? true : 'dashboard_business',
        }, {
            title: 'Stock',
            route: 'admin.stock_overview',
            permission: environment === 'local' ? true : 'dashboard_stock_overview',
        }, {
            title: 'Performance',
            route: 'admin.sale_target',
            permission: environment === 'local' ? true : 'dashboard_sale_target',
        }, {
            title: 'Intelligence',
            route: 'admin.demand_forecasting',
            permission: demandForecastingDashboardVisibility,
        }, {
            title: 'Season',
            route: 'admin.seasonal',
            permission: environment === 'local' ? true : 'dashboard_seasonal',
        }, {
            title: 'Basket Analysis',
            route: 'admin.basket_analysis',
            permission: demandForecastingDashboardVisibility,
        }, {
            title: 'Data Analytics',
            route: 'admin.data_analysis',
            permission: demandForecastingDashboardVisibility,
        }, {
            title: 'Members',
            route: 'admin.member_dashboard_index',
            permission: environment === 'local' ? true : 'dashboard_member',
        },
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
