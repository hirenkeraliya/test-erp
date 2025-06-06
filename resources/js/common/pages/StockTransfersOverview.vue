<template>
    <PageTitle title="Stock Transfers Overview" />

    <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-10 bg-slate-200 rounded-xl p-5">
        <h1
            class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-sky-700"
        >
            Transfer Order
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="(transferOrder, index) in state.transferOrders"
                    :key="index"
                    class="cursor-pointer"
                    @click="showStockTransferData(transferTypes.transfer_order, transferOrder.id)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                {{ transferOrder.name }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ transferOrder.count }}
                            </p>
                        </div>
                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTransferAndRequestOrder(transferOrder.name)"
                        >
                            <component
                                :is="getTransferAndRequestOrderIcons(transferOrder.name)"
                                :class="getTransferAndRequestOrderIconColors(transferOrder.name)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-10 bg-slate-200 rounded-xl p-5">
        <h1
            class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-orange-700"
        >
            Request Order
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="(requestOrder, index) in state.requestOrders"
                    :key="index"
                    class="cursor-pointer"
                    @click="showStockTransferData(transferTypes.request_order, requestOrder.id)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                {{ requestOrder.name }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ requestOrder.count }}
                            </p>
                        </div>
                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTransferAndRequestOrder(requestOrder.name)"
                        >
                            <component
                                :is="getTransferAndRequestOrderIcons(requestOrder.name)"
                                :class="getTransferAndRequestOrderIconColors(requestOrder.name)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-10 bg-slate-200 rounded-xl p-5">
        <h1
            class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-sky-700"
        >
            Transfer Out
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="(transferOrder, index) in state.transferOuts"
                    :key="index"
                    class="cursor-pointer"
                    @click="showStockTransferData(transferTypes.transfer_out, transferOrder.id)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                {{ transferOrder.name }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ transferOrder.count }}
                            </p>
                        </div>
                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTransferAndRequestOrder(transferOrder.name)"
                        >
                            <component
                                :is="getTransferAndRequestOrderIcons(transferOrder.name)"
                                :class="getTransferAndRequestOrderIconColors(transferOrder.name)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-10 bg-slate-200 rounded-xl p-5">
        <h1
            class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-orange-700"
        >
            Transfer In
        </h1>

        <div class="col-span-12 lg:col-span-12 md:col-span-12">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="(transferOrder, index) in state.transferIns"
                    :key="index"
                    class="cursor-pointer"
                    @click="showStockTransferData(transferTypes.transfer_in, transferOrder.id)"
                >
                    <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                        <div class="mr-2.5">
                            <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                {{ transferOrder.name }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ transferOrder.count }}
                            </p>
                        </div>
                        <div
                            class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                            :class="getBackgroundColorForTransferAndRequestOrder(transferOrder.name)"
                        >
                            <component
                                :is="getTransferAndRequestOrderIcons(transferOrder.name)"
                                :class="getTransferAndRequestOrderIconColors(transferOrder.name)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Repeat, X, Truck, XCircle, XSquare, FileSignature, PackageCheck, PackageOpen } from 'lucide-vue-next';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy';

const props = defineProps({
    transferTypes: {
        type: Object,
        required: true,
    },
    stockTransferUrl: {
        type: String,
        required: true,
    },
    getTransferOrderUrl: {
        type: String,
        required: true,
    },
    getRequestOrderUrl: {
        type: String,
        required: true,
    },
    getTransferInUrl: {
        type: String,
        required: true,
    },
    getTransferOutUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    transferOrders: [],
    requestOrders: [],
    transferIns: [],
    transferOuts: [],
});

const showStockTransferData = (transferType, transferStatus) => {
    router.get(route(props.stockTransferUrl, { transfer_type: transferType, select_status: transferStatus }));
};

const getTransferOrder = () => {
    axios.get(props.getTransferOrderUrl)
        .then((response) => {
            state.transferOrders = response.data.transferOrders;
        });
};

const getRequestOrder = () => {
    axios.get(props.getRequestOrderUrl)
        .then((response) => {
            state.requestOrders = response.data.requestOrders;
        });
};

const getTransferIn = () => {
    axios.get(props.getTransferInUrl)
        .then((response) => {
            state.transferIns = response.data.transferIns;
        });
};

const getTransferOut = () => {
    axios.get(props.getTransferOutUrl)
        .then((response) => {
            state.transferOuts = response.data.transferOuts;
        });
};

getTransferOrder();
getRequestOrder();
getTransferOut();
getTransferIn();

const getTransferAndRequestOrderIcons = (name) => {
    if (name === 'Closed') {
        return X;
    }

    if (name === 'Discrepancy') {
        return Truck;
    }

    if (name === 'Received') {
        return XCircle;
    }

    if (name === 'Rejected') {
        return XSquare;
    }

    if (name === 'Cancelled') {
        return Repeat;
    }

    if (name === 'Shipped') {
        return FileSignature;
    }

    if (name === 'Draft') {
        return PackageCheck;
    }

    if (name === 'Open') {
        return PackageOpen;
    }

    return 'X';
};

const getTransferAndRequestOrderIconColors = (name) => {
    if (name === 'Closed') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
    }

    if (name === 'Discrepancy') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-red-700';
    }

    if (name === 'Received') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-yellow-700';
    }

    if (name === 'Rejected') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-green-700';
    }

    if (name === 'Cancelled') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-pink-700';
    }

    if (name === 'Shipped') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-sky-700';
    }

    if (name === 'Draft') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-fuchsia-700';
    }

    if (name === 'Open') {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-orange-700';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};

const getBackgroundColorForTransferAndRequestOrder = (name) => {
    if (name === 'Closed') {
        return 'bg-indigo-50 border-indigo-100';
    }

    if (name === 'Discrepancy') {
        return 'bg-red-50 border-red-100';
    }

    if (name === 'Received') {
        return 'bg-yellow-50 border-yellow-100';
    }

    if (name === 'Rejected') {
        return 'bg-green-50 border-green-100';
    }

    if (name === 'Cancelled') {
        return 'bg-pink-50 border-pink-100';
    }

    if (name === 'Shipped') {
        return 'bg-sky-50 border-sky-100';
    }

    if (name === 'Draft') {
        return 'bg-fuchsia-50 border-fuchsia-100';
    }

    if (name === 'Open') {
        return 'bg-orange-50 border-orange-100';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};
</script>
