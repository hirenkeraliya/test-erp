<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Stock" />

        <div class="content content--top-nav mr-5">
            <div class="items-center block my-auto mt-5 2xl:flex xl:block lg:block md:block sm:block intro-y">
                <div class="block sm:flex flex-wrap mr-auto justify-items-start">
                    <div
                        class="sm:flex ml-0 2xl:ml-2 xl:ml-0 lg:ml-0 md:ml-0 sm:ml-0 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-0 sm:mt-0"
                    >
                        <FormSelectBox
                            class="w-full mr-2 2xl:w-96 md:w-72 sm:w-60"
                            :selected-record="state.locationId"
                            :records="locations"
                            :placeholder="'Please select Location'"
                            @update:selected-record="getLocationData"
                        />
                    </div>
                </div>

                <div class="mt-6 ml-0 sm:mt-0 flex w-full mb-3 sm:ml-3 sm:w-auto">
                    <Tippy
                        content="Refresh Data"
                        class="btn btn-outline-primary"
                        @click="refresh()"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>

                    <p class="ml-2 text-xs">
                        <span class="text-sm font-medium">Last Update:</span><br>{{ state.lastUpdate }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-5 bg-slate-200 rounded-xl p-5">
                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-2">
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.no_stock)"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg">
                                    No stock Items
                                </p>

                                <p class="mt-1 text-lg font-semibold">
                                    {{ state.noStockItemCount }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
                            >
                                <PackageX class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
                            </div>
                        </div>

                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showStockData(stockTypes.negative_stock)"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg">
                                    Negative stock Items
                                </p>

                                <p class="mt-1 text-lg font-semibold">
                                    {{ state.negativeStockItemCount }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
                            >
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
                                    <p class="text-lg text-slate-700">
                                        {{ purchaseRequest.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                                    <p class="text-lg text-slate-700">
                                        {{ transferRequest.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                                    <p class="text-lg text-slate-700">
                                        {{ salesOrder.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                                    <p class="text-lg text-slate-700">
                                        {{ salesDeliveryOrder.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                                    <p class="text-lg text-slate-700">
                                        {{ purchaseOrder.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                                    <p class="text-lg text-slate-700">
                                        {{ purchaseDeliveryOrder.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                        v-else
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
                                    <p class="text-lg text-slate-700">
                                        {{ transferOrder.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
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
                        v-if="state.requestOrders.length === 0"
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

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                        <div
                            v-for="(requestOrder, index) in state.requestOrders"
                            :key="index"
                            class="cursor-pointer"
                            @click="showStockTransferData(transferTypes.request_order, requestOrder.id)"
                        >
                            <div class="rounded-xl bg-white p-4 flex items-center justify-between h-full">
                                <div class="mr-2.5">
                                    <p class="text-lg text-slate-700">
                                        {{ requestOrder.name }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
                                        {{ requestOrder.count }}
                                    </p>
                                </div>
                                <div
                                    v-if="requestOrder.transfer_in_count || requestOrder.transfer_out_count"
                                    class="border-l border-gray-300 pl-3 text-center"
                                >
                                    <div>
                                        <Tippy
                                            tag="p"
                                            class="text-sm text-gray-700"
                                            content="Transfer IN Counts"
                                        >
                                            IN: {{ requestOrder.transfer_in_count ?? 0.0 }}
                                        </Tippy>
                                        <Tippy
                                            tag="p"
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
                </div>
            </div>

            <div class="bg-slate-200 rounded-xl p-1 lg:p-5 mt-10">
                <div class="flex justify-end">
                    <div>
                        <FormSelectBox
                            class="w-full mr-2 2xl:w-96 md:w-72 sm:w-60"
                            :selected-record="state.brandId"
                            :records="brands"
                            :placeholder="'Please select Brand'"
                            @update:selected-record="getBrandData"
                        />
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2"
                >
                    <TopSellingProduct
                        title="Top 10 selling products for current month"
                        title-color="text-cyan-700"
                        type="month"
                        :top-selling-products="state.thisMonthTopSellingProducts"
                        :location-id="state.locationId"
                        product-report-url="admin.products_report.index"
                    />

                    <TopSellingProduct
                        title="Top 10 selling products for current year"
                        title-color="text-teal-700"
                        type="year"
                        :top-selling-products="state.thisYearTopSellingProducts"
                        :location-id="state.locationId"
                        product-report-url="admin.products_report.index"
                    />
                </div>

                <div
                    class="grid grid-cols-1 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2"
                >
                    <WorstSellingProduct
                        title="Worst 10 selling products for current month"
                        title-color="text-cyan-700"
                        type="month"
                        :worst-selling-products="state.thisMonthWorstSellingProducts"
                        :location-id="state.locationId"
                        product-report-url="admin.products_report.index"
                    />

                    <WorstSellingProduct
                        title="Worst 10 selling products for current year"
                        title-color="text-teal-700"
                        type="year"
                        :worst-selling-products="state.thisYearWorstSellingProducts"
                        :location-id="state.locationId"
                        product-report-url="admin.products_report.index"
                    />
                </div>

                <div
                    class="grid grid-cols-1 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2 mt-10"
                >
                    <TopSellingColor
                        title="Top 10 selling colors for current month"
                        title-color="text-cyan-700"
                        type="month"
                        :top-selling-colors="state.thisMonthTopSellingColors"
                        :location-id="state.locationId"
                        product-report-url="admin.products_report.index"
                    />

                    <TopSellingColor
                        title="Top 10 selling colors for current year"
                        title-color="text-teal-700"
                        type="year"
                        :top-selling-colors="state.thisYearTopSellingColors"
                        :location-id="state.locationId"
                        product-report-url="admin.products_report.index"
                    />
                </div>

                <div
                    class="grid grid-cols-1 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 2xl:grid-cols-2 mt-10"
                >
                    <TopAccumulatedSTR
                        :top-ranking-products="state.topRankingProducts"
                        :location-id="state.locationId"
                        accumulated-sale-through-report-url="admin.sell_through_aggregate_reports.index"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import TopAccumulatedSTR from '@commonComponents/TopAccumulatedSTR.vue';
import TopSellingColor from '@commonComponents/TopSellingColor.vue';
import TopSellingProduct from '@commonComponents/TopSellingProduct.vue';
import WorstSellingProduct from '@commonComponents/WorstSellingProduct.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Info, PackageX, RefreshCw, TrendingDown, PackageMinus } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';

import { getBackgroundColorForDeliveryOrder, getDeliveryOrderIconColors, getDeliveryOrderIcons } from '@commonServices/deliveryOrderHelper.js';

import { getBackgroundColorForPurchaseOrder, getPurchaseOrderIconColors, getPurchaseOrderIcons } from '@commonServices/purchaseOrderHelper.js';

import { getBackgroundColorForTransferAndRequestOrder, getTransferAndRequestOrderIconColors, getTransferAndRequestOrderIcons } from '@commonServices/transferAndRequestOrderHelper.js';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    stockTypes: {
        type: Object,
        required: true,
    },
    transferTypes: {
        type: Object,
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
    brands: {
        type: Array,
        required: true,
    },
    brandId: {
        type: Number,
        default: 0,
    },
    locationId: {
        type: Number,
        default: 0,
    },
    activeStatus: {
        type: String,
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
    thisMonthTopSellingProducts: [],
    thisYearTopSellingProducts: [],
    thisMonthWorstSellingProducts: [],
    thisYearWorstSellingProducts: [],
    thisMonthTopSellingColors: [],
    thisYearTopSellingColors: [],
    topRankingProducts: [],
    locationId: props.locationId,
    brandId: props.brandId,
    lastUpdate: null,
    refresh: false,
    status: props.activeStatus,
});

const showStockData = (stockType) => {
    router.get(route('admin.inventory_reports.index', { location_id: state.locationId, stock_type: stockType, status: state.status, selling_type: props.sellingType }));
};

const showStockTransferData = (transferType, transferStatus) => {
    const isFromStockOverview = state.locationId !== 0;

    router.get(route('admin.stock_transfers.index', { location_id: state.locationId, transfer_type: transferType, select_status: transferStatus, is_from_stock_overview: isFromStockOverview }));
};

const showPurchaseOrderData = (orderType, status) => {
    router.get(route('admin.purchase_orders.index', { location_id: state.locationId, order_type: orderType, select_status: status }));
};

const showDeliveryOrderData = (status) => {
    router.get(route('admin.purchase_order_fulfillments.delivery_orders', { select_status: status, select_order_type: props.orderTypes.salesOrder }));
};

const showPurchaseDeliveryOrderData = (status) => {
    router.get(route('admin.purchase_order_fulfillments.delivery_orders', { select_status: status, select_order_type: props.orderTypes.purchaseOrder }));
};

const getLocationData = (locationId) => {
    axios.get(route('admin.get_stock_overview', locationId))
        .then((response) => {
            state.locationId = response.data.locationId;
        });

    state.locationId = locationId;
    getThisMonthTopSellingProducts();
    getThisYearTopSellingProducts();
    getThisMonthWorstSellingProducts();
    getThisYearWorstSellingProducts();
    getThisMonthTopSellingColors();
    getThisYearTopSellingColors();
    getTransferOrder(locationId);
    getPurchaseRequest(locationId);
    getTransferRequest(locationId);
    getSalesOrder(locationId);
    getPurchaseOrder(locationId);
    getRequestOrder(locationId);
    getLowStockOverview();
    getNoStockStockOverview();
    getNegativeStockStockOverview();
    getTopRankingProducts(locationId);
};

const getBrandData = (brandId) => {
    state.brandId = brandId;
    getThisMonthTopSellingProducts();
    getThisYearTopSellingProducts();
    getThisMonthWorstSellingProducts();
    getThisYearWorstSellingProducts();
    getThisMonthTopSellingColors();
    getThisYearTopSellingColors();
};

const refresh = () => {
    state.refresh = true;
    state.noStockItemCount = 0;
    state.negativeStockItemCount = 0;
    state.lowStockItemCount = 0;
    state.thisMonthTopSellingProducts = [];
    state.thisYearTopSellingProducts = [];
    state.thisMonthWorstSellingProducts = [];
    state.thisYearWorstSellingProducts = [];
    state.thisMonthTopSellingColors = [];
    state.thisYearTopSellingColors = [];
    state.topRankingProducts = [];
    getThisMonthTopSellingProducts();
    getThisYearTopSellingProducts();
    getThisMonthWorstSellingProducts();
    getThisYearWorstSellingProducts();
    getThisMonthTopSellingColors();
    getThisYearTopSellingColors();
    getLowStockOverview();
    getNoStockStockOverview();
    getNegativeStockStockOverview();
    getTopRankingProducts();
};

const getThisMonthTopSellingProducts = () => {
    axios.get(route('admin.get_this_month_top_selling_products', { location_id: state.locationId, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisMonthTopSellingProducts = response.data.thisMonthTopSellingProducts;
            state.lastUpdate = response.data.lastUpdate;
        });
};

const getThisYearTopSellingProducts = () => {
    axios.get(route('admin.get_this_year_top_selling_products', { location_id: state.locationId, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYearTopSellingProducts = response.data.thisYearTopSellingProducts;
        });
};

const getThisMonthWorstSellingProducts = () => {
    axios.get(route('admin.get_this_month_worst_selling_products', { location_id: state.locationId, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisMonthWorstSellingProducts = response.data.thisMonthWorstSellingProducts;
            state.lastUpdate = response.data.lastUpdate;
        });
};

const getThisYearWorstSellingProducts = () => {
    axios.get(route('admin.get_this_year_worst_selling_products', { location_id: state.locationId, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYearWorstSellingProducts = response.data.thisYearWorstSellingProducts;
        });
};

const getThisMonthTopSellingColors = () => {
    axios.get(route('admin.get_this_month_top_selling_colors', { location_id: state.locationId, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisMonthTopSellingColors = response.data.thisMonthTopSellingColors;
        });
};

const getThisYearTopSellingColors = () => {
    axios.get(route('admin.get_this_year_top_selling_colors', { location_id: state.locationId, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYearTopSellingColors = response.data.thisYearTopSellingColors;
        });
};

const getTransferOrder = (locationId) => {
    axios.get(route('admin.get_transfer_order', locationId))
        .then((response) => {
            state.transferOrders = response.data.transferOrders;
        });
};

const getPurchaseRequest = (locationId) => {
    axios.get(route('admin.get_purchase_request', locationId))
        .then((response) => {
            state.purchaseRequests = response.data.purchaseRequests;
        });
};

const getTransferRequest = (locationId) => {
    axios.get(route('admin.get_transfer_request', locationId))
        .then((response) => {
            state.transferRequests = response.data.transferRequests;
        });
};

const getSalesOrder = (locationId) => {
    axios.get(route('admin.get_sales_order', locationId))
        .then((response) => {
            state.salesOrders = response.data.salesOrders;
            state.salesDeliveryOrders = response.data.salesDeliveryOrders;
        });
};

const getPurchaseOrder = (locationId) => {
    axios.get(route('admin.get_purchase_order', locationId))
        .then((response) => {
            state.purchaseOrders = response.data.purchaseOrders;
            state.purchaseDeliveryOrders = response.data.purchaseDeliveryOrders;
        });
};

const getRequestOrder = (locationId) => {
    axios.get(route('admin.get_request_order', locationId))
        .then((response) => {
            state.requestOrders = response.data.requestOrders;
        });
};

const getLowStockOverview = () => {
    axios.get(route('admin.get_low_stock_overview', { location_id: state.locationId, refresh: state.refresh }))
        .then((response) => {
            state.lowStockCompanyCount = response.data.lowStockCompanyCount;
            state.lowStockLocationCount = response.data.lowStockLocationCount;
            state.lowStockProductCount = response.data.lowStockProductCount;
        });
};

const getNoStockStockOverview = () => {
    axios.get(route('admin.get_no_stock_stock_overview', { location_id: state.locationId, refresh: state.refresh }))
        .then((response) => {
            state.noStockItemCount = response.data.noStockItemCount;
        });
};

const getNegativeStockStockOverview = () => {
    axios.get(route('admin.get_negative_stock_stock_overview', { location_id: state.locationId, refresh: state.refresh }))
        .then((response) => {
            state.negativeStockItemCount = response.data.negativeStockItemCount;
        });
};


const getTopRankingProducts = (locationId) => {
    state.topRankingProducts =  [];

    axios.get(route('admin.get_top_ranking_products', locationId <= 0 ? null : locationId))
        .then((response) => {
            state.topRankingProducts = response.data.topRankingProducts;
        });
};

getLocationData(0);
getBrandData(0);

</script>
