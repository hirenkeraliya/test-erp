<template>
    <div
        v-if="state.isLoading"
        class="mt-5"
    >
        <div
            v-for="n in 4"
            :key="n"
        >
            <div class="animated-background !h-16 rounded-xl px-4 py-2 mb-3 ml-3" />
        </div>
    </div>

    <div
        v-else
        class="mt-5"
    >
        <div
            v-for="(liveTopTenStore, index) in state.liveTopTenLocations"
            :key="index"
            class="grid grid-cols-2 gap-1 cursor-pointer intro box px-4 py-2 mb-3 ml-3 zoom-in font-medium text-xl h-16"
            :class="liveTopTenStore.class"
            @click="showSaleDetailsModal(liveTopTenStore.region_id, liveTopTenStore.name)"
        >
            <div>
                <b>{{ liveTopTenStore.name }}</b>
            </div>
            <div>
                {{ currencySymbol }}{{ currencyFormat(liveTopTenStore.total_sales) }}
            </div>
        </div>
    </div>

    <StoreSaleDetails
        v-if="state.displaySaleDetailsModal"
        :modal-show="state.displaySaleDetailsModal"
        :sales="state.sales"
        :region-name="state.regionName"
        @close-modal="closeModal"
    />
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { currencyFormat } from '@commonServices/helper';
import StoreSaleDetails from '@adminPages/dashboards/StoreSaleDetails.vue';
import axios from 'axios';
import { route } from 'ziggy';
import { usePage } from '@inertiajs/vue3';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    brandId: {
        type: Number,
        default: 0
    },
    refreshSalesByRegions: {
        type: Number,
        default: null,
    }
});

const state = reactive({
    colorClass: 600,
    liveTopTenLocations: [],
    displaySaleDetailsModal: false,
    regionName: '',
    isLoading: false,
});

onMounted(() => {
    fetchRecords();
});

const fetchRecords = () => {
    state.isLoading = true;
    state.liveTopTenLocations = [];
    state.colorClass = 600;
    axios.get(route('admin.get_live_top_ten_stores', props.brandId))
        .then((response) => {
            state.isLoading = false;
            state.liveTopTenLocations = response.data.liveTopTenLocations;
            state.liveTopTenLocations.forEach((liveTopTenStore) => {
                liveTopTenStore.class = `bg-pink-${state.colorClass}`;
                state.colorClass -= 100;
            });
        });
};

const closeModal = () => {
    state.sales = [];
    state.regionName = '';
    state.displaySaleDetailsModal = false;
};

const showSaleDetailsModal = (regionId, regionName) => {
    state.sales = [];
    state.regionName = regionName;
    axios.get(route('admin.get_store_sales_by_region', { regionId, brandId: props.brandId }))
        .then((response) => {
            state.sales = response.data.location_sales;
        });

    state.displaySaleDetailsModal = true;
};

watch(() => props.refreshSalesByRegions,
    () => {
        fetchRecords();
    }
);
</script>
