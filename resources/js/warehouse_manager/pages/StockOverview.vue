<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />
        <PageTitle title="Stock Overview" />

        <div class="content content--top-nav mr-5">
            <div class="items-center block mt-5 2xl:flex xl:flex lg:flex md:flex sm:flex intro-y">
                <h2 class="mr-auto text-xl font-semibold uppercase">
                    Stock Overview Dashboard
                </h2>
            </div>

            <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-5 bg-slate-200 rounded-xl p-5">
                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-2">
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.no_stock)"
                        >
                            <div class="mr-2.5">
                                <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                    No stock Items
                                </p>
                                <p class="mt-1 text-sm">
                                    {{ state.noStockItemCount }}
                                </p>
                            </div>
                            <div class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none">
                                <PackageX class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
                            </div>
                        </div>

                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.negative_stock)"
                        >
                            <div class="mr-2.5">
                                <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                    Negative stock Items
                                </p>
                                <p class="mt-1 text-sm">
                                    {{ state.negativeStockItemCount }}
                                </p>
                            </div>
                            <div class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none">
                                <PackageMinus class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
                            </div>
                        </div>

                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.low_stock_company)"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg text-slate-700">
                                    Low Stock Items By Company
                                </p>
                                <Tippy
                                    tag="p"
                                    class="mt-1 text-lg font-semibold flex items-center"
                                    content="Items whose inventory levels reach the low stock threshold set by the company level configuration."
                                >
                                    {{ state.lowStockCompanyCount }}
                                    <Info
                                        class="ml-1 text-primary"
                                        :size="15"
                                    />
                                </Tippy>
                            </div>
                            <div
                                class="rounded-full bg-red-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-red-100 border flex-none"
                            >
                                <TrendingDown class="w-4 h-4 lg:h-5 lg:w-5 text-red-700" />
                            </div>
                        </div>
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.low_stock_location)"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg text-slate-700">
                                    Low Stock Items By Location
                                </p>
                                <Tippy
                                    tag="p"
                                    class="mt-1 text-lg font-semibold flex items-center"
                                    content="Items whose inventory levels reach the low stock threshold set by the location level configuration."
                                >
                                    {{ state.lowStockLocationCount }}
                                    <Info
                                        class="ml-1 text-primary"
                                        :size="15"
                                    />
                                </Tippy>
                            </div>
                            <div
                                class="rounded-full bg-red-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-red-100 border flex-none"
                            >
                                <TrendingDown class="w-4 h-4 lg:h-5 lg:w-5 text-red-700" />
                            </div>
                        </div>
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.low_stock_product)"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg text-slate-700">
                                    Low Stock Items By Product
                                </p>
                                <Tippy
                                    tag="p"
                                    class="mt-1 text-lg font-semibold flex items-center"
                                    content="Items whose inventory levels reach the low stock threshold set by the product level configuration."
                                >
                                    {{ state.lowStockProductCount }}
                                    <Info
                                        class="ml-1 text-primary"
                                        :size="15"
                                    />
                                </Tippy>
                            </div>
                            <div
                                class="rounded-full bg-red-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-red-100 border flex-none"
                            >
                                <TrendingDown class="w-4 h-4 lg:h-5 lg:w-5 text-red-700" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-10 bg-slate-200 rounded-xl p-5">
                <h1
                    class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-sky-700"
                >
                    Purchase Requests
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.purchaseRequests.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="(purchaseRequest, index) in state.purchaseRequests"
                            :key="index"
                            class="cursor-pointer"
                            @click="showPurchaseOrderData(orderTypes.purchaseRequest, purchaseRequest.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                        {{ purchaseRequest.name }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{ purchaseRequest.count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForPurchaseOrder(purchaseRequest.name, purchaseOrderStatuses)"
                                >
                                    <component
                                        :is="getPurchaseOrderIcons(purchaseRequest.name, purchaseOrderStatuses)"
                                        :class="getPurchaseOrderIconColors(purchaseRequest.name, purchaseOrderStatuses)"
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
                    Transfer Requests
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.transferRequests.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="(transferRequest, index) in state.transferRequests"
                            :key="index"
                            class="cursor-pointer"
                            @click="showPurchaseOrderData(orderTypes.transferRequest, transferRequest.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                        {{ transferRequest.name }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{ transferRequest.count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForPurchaseOrder(transferRequest.name, purchaseOrderStatuses)"
                                >
                                    <component
                                        :is="getPurchaseOrderIcons(transferRequest.name, purchaseOrderStatuses)"
                                        :class="getPurchaseOrderIconColors(transferRequest.name, purchaseOrderStatuses)"
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
                    Sales Orders
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.salesOrders.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="(salesOrder, index) in state.salesOrders"
                            :key="index"
                            class="cursor-pointer"
                            @click="showPurchaseOrderData(orderTypes.salesOrder, salesOrder.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                        {{ salesOrder.name }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{ salesOrder.count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForPurchaseOrder(salesOrder.name, purchaseOrderStatuses)"
                                >
                                    <component
                                        :is="getPurchaseOrderIcons(salesOrder.name, purchaseOrderStatuses)"
                                        :class="getPurchaseOrderIconColors(salesOrder.name, purchaseOrderStatuses)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h1
                    class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-sky-700"
                >
                    Delivery Orders
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.salesDeliveryOrders.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="(salesDeliveryOrder, index) in state.salesDeliveryOrders"
                            :key="index"
                            class="cursor-pointer"
                            @click="showDeliveryOrderData(salesDeliveryOrder.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                        {{ salesDeliveryOrder.name }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{ salesDeliveryOrder.count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForDeliveryOrder(salesDeliveryOrder.name, fulfillmentStatuses)"
                                >
                                    <component
                                        :is="getDeliveryOrderIcons(salesDeliveryOrder.name, fulfillmentStatuses)"
                                        :class="getDeliveryOrderIconColors(salesDeliveryOrder.name, fulfillmentStatuses)"
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
                    Purchase Orders
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.purchaseOrders.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="(purchaseOrder, index) in state.purchaseOrders"
                            :key="index"
                            class="cursor-pointer"
                            @click="showPurchaseOrderData(orderTypes.purchaseOrder, purchaseOrder.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                        {{ purchaseOrder.name }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{ purchaseOrder.count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForPurchaseOrder(purchaseOrder.name, purchaseOrderStatuses)"
                                >
                                    <component
                                        :is="getPurchaseOrderIcons(purchaseOrder.name, purchaseOrderStatuses)"
                                        :class="getPurchaseOrderIconColors(purchaseOrder.name, purchaseOrderStatuses)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h1
                    class="col-span-12 lg:col-span-12 md:col-span-12 flex items-center zoom-in font-bold text-xl text-sky-700"
                >
                    Delivery Orders
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.purchaseDeliveryOrders.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="(purchaseDeliveryOrder, index) in state.purchaseDeliveryOrders"
                            :key="index"
                            class="cursor-pointer"
                            @click="showPurchaseDeliveryOrderData(purchaseDeliveryOrder.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                        {{ purchaseDeliveryOrder.name }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{ purchaseDeliveryOrder.count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForDeliveryOrder(purchaseDeliveryOrder.name, fulfillmentStatuses)"
                                >
                                    <component
                                        :is="getDeliveryOrderIcons(purchaseDeliveryOrder.name, fulfillmentStatuses)"
                                        :class="getDeliveryOrderIconColors(purchaseDeliveryOrder.name, fulfillmentStatuses)"
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
                    Transfer Order
                </h1>

                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div
                        v-if="state.transferOrders.length === 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="state.transferOrders.length > 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
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
                                    v-if="transferOrder.transfer_in_count || transferOrder.transfer_out_count"
                                    class="border-l border-gray-300 pl-3 text-center"
                                >
                                    <div>
                                        <Tippy
                                            tag="p"
                                            class="text-sm text-gray-700"
                                            content="Transfer IN Counts"
                                        >
                                            IN: {{ transferOrder.transfer_in_count ?? 0.0 }}
                                        </Tippy>
                                        <Tippy
                                            tag="p"
                                            class="text-sm text-gray-700"
                                            content="Transfer OUT Counts"
                                        >
                                            OUT: {{ transferOrder.transfer_out_count ?? 0.0 }}
                                        </Tippy>
                                    </div>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForTransferAndRequestOrder(transferOrder.name, stockTransferStatuses)"
                                >
                                    <component
                                        :is="getTransferAndRequestOrderIcons(transferOrder.name, stockTransferStatuses)"
                                        :class="getTransferAndRequestOrderIconColors(transferOrder.name, stockTransferStatuses)"
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
                    <div
                        v-if="!state.requestOrders"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
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
                                    v-if="requestOrder.transfer_in_count || requestOrder.transfer_out_count"
                                    class="border-l border-gray-300 pl-3 text-center"
                                >
                                    <div>
                                        <Tippy
                                            tag="a"
                                            class="text-sm text-gray-700"
                                            content="Transfer IN Counts"
                                        >
                                            IN: {{ requestOrder.transfer_in_count ?? 0.0 }}
                                        </Tippy>
                                        <Tippy
                                            tag="a"
                                            class="text-sm text-gray-700"
                                            content="Transfer OUT Counts"
                                        >
                                            OUT: {{ requestOrder.transfer_out_count ?? 0.0 }}
                                        </Tippy>
                                    </div>
                                </div>
                                <div
                                    class="rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border flex-none"
                                    :class="getBackgroundColorForTransferAndRequestOrder(requestOrder.name, stockTransferStatuses)"
                                >
                                    <component
                                        :is="getTransferAndRequestOrderIcons(requestOrder.name, stockTransferStatuses)"
                                        :class="getTransferAndRequestOrderIconColors(requestOrder.name, stockTransferStatuses)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="n in 8"
                            :key="'loading-transfer-order-content-' + n"
                        >
                            <div class="cp">
                                <div class="animated-background !rounded-xl !h-[78px]" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@warehouseManagerPages/DashboardMenu.vue';
import { reactive } from 'vue';
import { PackageX, TrendingDown, PackageMinus } from 'lucide-vue-next';
import axios from 'axios';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';

import { getDeliveryOrderIcons, getDeliveryOrderIconColors, getBackgroundColorForDeliveryOrder } from '@commonServices/deliveryOrderHelper.js';

import { getPurchaseOrderIcons, getPurchaseOrderIconColors, getBackgroundColorForPurchaseOrder } from '@commonServices/purchaseOrderHelper.js';

import { getTransferAndRequestOrderIcons, getTransferAndRequestOrderIconColors, getBackgroundColorForTransferAndRequestOrder } from '@commonServices/transferAndRequestOrderHelper.js';

const props = defineProps({
    stockTypes: {
        type: Object,
        required: true,
    },
    transferTypes: {
        type: Object,
        required: true,
    },
    activeStatus: {
        type: String,
        required: true,
    },
    orderTypes: {
        type: Object,
        required: true,
    },
    fulfillmentStatuses: {
        type: Object,
        required: true,
    },
    purchaseOrderStatuses: {
        type: Object,
        required: true,
    },
    stockTransferStatuses: {
        type: Object,
        required: true,
    },
    sellingType: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    transferOrders: [],
    purchaseRequests: [],
    transferRequests: [],
    salesOrders: [],
    salesDeliveryOrders: [],
    purchaseDeliveryOrders: [],
    purchaseOrders: [],
    requestOrders: [],
    noStockItemCount: 0,
    negativeStockItemCount: 0,
    lowStockCompanyCount: 0,
    lowStockLocationCount: 0,
    lowStockProductCount: 0,
    status: props.activeStatus,
});

const showStockData = (stockType) => {
    router.get(route('warehouse_manager.inventory_reports.index', { stock_type: stockType, status: state.status, selling_type: props.sellingType }));
};

const showPurchaseOrderData = (orderType, status) => {
    router.get(route('warehouse_manager.purchase_orders.index', { order_type: orderType, select_status: status }));
};

const showDeliveryOrderData = (status) => {
    router.get(route('warehouse_manager.purchase_order_fulfillments.delivery_orders', { select_status: status, select_order_type: props.orderTypes.salesOrder }));
};

const showPurchaseDeliveryOrderData = (status) => {
    router.get(route('warehouse_manager.purchase_order_fulfillments.delivery_orders', { select_status: status, select_order_type: props.orderTypes.purchaseOrder }));
};

const showStockTransferData = (transferType, transferStatus) => {
    router.get(route('warehouse_manager.stock_transfers.index', { transfer_type: transferType, select_status: transferStatus, is_from_stock_overview: true }));
};

const getPurchaseRequest = () => {
    axios.get(route('warehouse_manager.get_purchase_request'))
        .then((response) => {
            state.purchaseRequests = response.data.purchaseRequests;
        });
};

const getTransferRequest = () => {
    axios.get(route('warehouse_manager.get_transfer_request'))
        .then((response) => {
            state.transferRequests = response.data.transferRequests;
        });
};

const getSalesOrder = () => {
    axios.get(route('warehouse_manager.get_sales_order'))
        .then((response) => {
            state.salesOrders = response.data.salesOrders;
            state.salesDeliveryOrders = response.data.salesDeliveryOrders;
        });
};

const getPurchaseOrder = () => {
    axios.get(route('warehouse_manager.get_purchase_order'))
        .then((response) => {
            state.purchaseOrders = response.data.purchaseOrders;
            state.purchaseDeliveryOrders = response.data.purchaseDeliveryOrders;
        });
};

const getTransferOrder = () => {
    axios.get(route('warehouse_manager.get_transfer_order'))
        .then((response) => {
            state.transferOrders = response.data.transferOrders;
        });
};

const getRequestOrder = () => {
    axios.get(route('warehouse_manager.get_request_order'))
        .then((response) => {
            state.requestOrders = response.data.requestOrders;
        });
};

const getLowStockOverview = () => {
    axios.get(route('warehouse_manager.get_low_stock_overview'))
        .then((response) => {
            state.lowStockCompanyCount = response.data.lowStockCompanyCount;
            state.lowStockLocationCount = response.data.lowStockLocationCount;
            state.lowStockProductCount = response.data.lowStockProductCount;
        });
};

const getNoStockStockOverview = () => {
    axios.get(route('warehouse_manager.get_no_stock_stock_overview'))
        .then((response) => {
            state.noStockItemCount = response.data.noStockItemCount;
        });
};

const getNegativeStockStockOverview = () => {
    axios.get(route('warehouse_manager.get_negative_stock_stock_overview'))
        .then((response) => {
            state.negativeStockItemCount = response.data.negativeStockItemCount;
        });
};

getTransferOrder();
getRequestOrder();
getLowStockOverview();
getNoStockStockOverview();
getNegativeStockStockOverview();
getPurchaseRequest();
getTransferRequest();
getSalesOrder();
getPurchaseOrder();

</script>
