<template>
    <div class="bg-slate-200 rounded-xl p-5">
        <h1
            class="col-span-12 lg:col-span-12 md:col-span-12 font-bold text-xl mb-4"
            :class="titleColor"
        >
            {{ title }}
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div
                v-if="topSellingProducts.length === 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-2"
            >
                <div
                    v-for="n in 10"
                    :key="'loading-product-content-' + n"
                >
                    <div>
                        <div class="animated-background !h-[136.5px] !rounded-xl !p-0" />
                    </div>
                </div>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-2"
            >
                <div
                    v-for="(topSellingProduct, index) in topSellingProducts"
                    :key="index"
                    class="cursor-pointer"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div
                            class="mr-2.5"
                            @click="showProductData(type, topSellingProduct.id)"
                        >
                            <p class="text-lg text-slate-700">
                                {{ topSellingProduct.name }}
                            </p>

                            <Tippy
                                tag="p"
                                class="mt-1 text-lg font-semibold flex items-center"
                                content="Formula: Units Sold - Units Returned"
                            >
                                Units Sold:
                                {{ truncateDecimal(topSellingProduct.total_units_sold) }}
                                <Info
                                    class="ml-1 text-primary"
                                    :size="15"
                                />
                            </Tippy>
                            <Tippy
                                tag="p"
                                class="mt-1 text-lg font-semibold flex items-center"
                                content="Formula: Sales - Sale Returns"
                            >
                                Sales:
                                {{ displayAmountWithCurrencySymbol(topSellingProduct.total_sales)
                                }}
                                <Info
                                    class="ml-1 text-primary"
                                    :size="15"
                                />
                            </Tippy>
                        </div>

                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTop10Products(index)"
                        >
                            <img
                                v-if="topSellingProduct.image_url"
                                :src="topSellingProduct.image_url"
                                class="rounded"
                                :alt="topSellingProduct.name"
                                @click="displayImageModal(topSellingProduct)"
                            >

                            <Package
                                v-else
                                class="w-4 h-4 lg:h-6 lg:w-6"
                                :class="getIconColorForTop10Products(index)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <TopSellingProductDetailsModal
            v-if="state.showImageModal"
            :modal-show="state.showImageModal"
            :product-data="state.topSellingProductDetails"
            @update:hide-modal="closeModal()"
        />
    </div>
</template>

<script setup>

import TopSellingProductDetailsModal from '@commonComponents/TopSellingProductDetailsModal.vue';
import { displayAmountWithCurrencySymbol, truncateDecimal } from '@commonServices/helper';
import { router } from '@inertiajs/vue3';
import { Info, Package } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';

import { getBackgroundColorForTop10Products, getIconColorForTop10Products } from '@commonServices/top10ProductsHelper.js';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    titleColor: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        required: true,
    },
    topSellingProducts: {
        type: Object,
        required: true,
    },
    locationId: {
        type: Number,
        default: 0,
    },
    productReportUrl: {
        type: String,
        required: true,
    },
    isStoreManagerPanel: {
        type: Boolean,
        default: false
    }
});

const state = reactive({
    showImageModal: false,
    topSellingProductDetails: []
});

const showProductData = (type, productId) => {
    if (props.isStoreManagerPanel) {
        router.get(route(props.productReportUrl, { type, product_id: productId }));
        return;
    }

    router.get(route(props.productReportUrl, { location_id: props.locationId, type, product_id: productId }));
};

const displayImageModal = (productDetails) => {
    state.showImageModal = true;
    state.topSellingProductDetails = productDetails;
};

const closeModal = () => {
    state.showImageModal = false;
    state.topSellingProductDetails = [];
};
</script>
