<template>
    <PageTitle title=" B2b Order" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            B2b Order
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-x-5 mt-5">
        <div class="col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-7 2xl:col-span-8">
            <div class="rounded-none intro-x box p-5 pt-0.5">
                <div>
                    <FormInput
                        v-model:input-value="state.productUpc"
                        type="search"
                        placeholder="Search Product"
                        input-label="Search Product"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:input-value="debounceSearch($event)"
                    />
                </div>

                <div v-if="state.productUpc !== null">
                    <div
                        v-if="state.isDataFetching"
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-0 sm:gap-5 mt-10"
                    >
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="border p-4 rounded-lg hover:bg-slate-100 cursor-pointer space-y-2 mb-4 sm:mb-0"
                        >
                            <div class="animated-background rounded-lg" />
                            <div class="animated-background rounded-lg" />
                            <div class="animated-background rounded-lg" />
                        </div>
                    </div>

                    <div
                        else
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-0 sm:gap-5 mt-10"
                    >
                        <div
                            v-for="product in state.products"
                            :key="product.id"
                            class="border p-4 rounded-lg hover:bg-slate-100 cursor-pointer space-y-2 mb-4 sm:mb-0"
                            @click="addToCart(product.id, product.box_product_id)"
                        >
                            <div v-if="product.box_product_id !== null">
                                <span class="text-sm font-bold text-center">Box Product</span>
                            </div>

                            <div class="space-x-3">
                                <span class="text-sm font-bold">Name :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.name }}</span>
                            </div>

                            <div class="space-x-3">
                                <span class="text-sm font-bold">Price :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{
                                    displayAmountWithCurrencySymbol(product.price) }}</span>
                            </div>

                            <div class="space-x-3">
                                <span class="text-sm font-bold">Upc :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.upc }}</span>
                            </div>

                            <div class="space-x-3">
                                <span class="text-sm font-bold">Brand :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.brand }}</span>
                            </div>

                            <div class="space-x-3">
                                <span class="text-sm font-bold">Size :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.size }}</span>
                            </div>

                            <div class="space-x-3">
                                <span class="text-sm font-bold">Color :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.color }}</span>
                            </div>

                            <div
                                v-if="product.box_product_id !== null"
                                class="space-x-3"
                            >
                                <span class="text-sm font-bold">Units :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.units }}</span>
                            </div>

                            <div
                                v-if="product.box_product_id !== null"
                                class="space-x-3"
                            >
                                <span class="text-sm font-bold">Package Type :</span>
                                <span class="text-sm text-gray-500 dark:text-white/70">{{ product.package_type_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="state.products.length <= 0 && state.isDataFetching === false"
                        class="text-center bg-slate-100 rounded-lg p-8"
                    >
                        <p class="text-base font-medium">
                            No Records Found.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="ordersForm.order_items.length > 0"
            class="col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-5 2xl:col-span-4"
        >
            <div class="rounded-none intro-x box p-5">
                <div class="flex justify-between items-center border-b pb-2">
                    <p class="font-bold text-lg">
                        Items
                    </p>

                    <div class="flex">
                        <Tippy
                            tag="p"
                            content="Items"
                            class="rounded item-quantity-count"
                        >
                            <JBadge
                                :label="ordersForm.order_items.length"
                                type="success"
                            />
                        </Tippy>
                    </div>
                </div>

                <div
                    :class="{
                        'overflow-y-auto h-96': ordersForm.order_items.length >= 5,
                    }"
                >
                    <div
                        v-for="(item, index) in ordersForm.order_items"
                        :key="item.id"
                        class="block sm:flex items-center py-4 border-b"
                    >
                        <div class="flex items-center w-full sm:w-[40%]">
                            <p
                                :title="getPromoterNames(item.promoter_name)"
                                class="mr-2"
                            >
                                <Info class="text-cyan-400 w-4" />
                            </p>
                            <p class="mr-2">
                                {{ item.product_name }}
                            </p>
                        </div>
                        <div class="flex sm:justify-center w-full sm:w-[35%] py-4 sm:py-0">
                            <FormInputNumber
                                :input-value="item.quantity"
                                :is-maximum-increment-required="false"
                                decrement-button-class="btn w-8 h-8 border-primary bg-primary text-white ml-1 mr-1 rounded-full"
                                increment-button-class="btn w-8 h-8 border-primary bg-primary text-white ml-1 mr-1 rounded-full"
                                input-class="form-control text-center w-20 h-8"
                                @update:input-value="updateQuantity($event, item, index)"
                            />
                        </div>
                        <div class="flex flex-col sm:items-end sm:justify-end w-full sm:w-[25%]">
                            <p class="mx-2">
                                {{ displayAmountWithCurrencySymbol(getItemPrice(item) * item.quantity) }}
                            </p>

                            <p
                                v-if="getItemWithCartDiscount(item)"
                                class="mx-2 text-xs text-success"
                            >
                                {{ displayAmountWithCurrencySymbol(getItemWithCartDiscount(item)) }}
                            </p>
                        </div>

                        <div class="flex items-center w-full sm:w-[4%] pt-4 sm:pt-0">
                            <Dropdown class="dropdown">
                                <DropdownToggle
                                    tag="a"
                                    class="w-5 h-5 block"
                                    href="javascript:;"
                                >
                                    <MoreVertical class="w-5 h-5 text-slate-500" />
                                </DropdownToggle>
                                <DropdownMenu class="w-60">
                                    <DropdownContent>
                                        <DropdownItem
                                            v-if="item.product_type === props.staticUseProductTypes.regularProduct && ((item.price_override_amount === 0 || item.price_override_amount === null) && item.complimentary_item_discount === null)"
                                            @click="manageItemDiscountModalShow(item)"
                                        >
                                            Custom Discount
                                        </DropdownItem>

                                        <DropdownItem
                                            v-if="item.price_override_amount !== 0 && item.price_override_amount !== null"
                                            @click="state.selectedProduct = item; removeTheItemDiscount()"
                                        >
                                            Remove Discount
                                        </DropdownItem>

                                        <DropdownItem
                                            v-if="item.product_type === props.staticUseProductTypes.regularProduct && item.complimentary_item_discount === null"
                                            @click="complimentaryItemReasonModalShow(item.id)"
                                        >
                                            Complimentary Item
                                        </DropdownItem>

                                        <DropdownItem
                                            v-if="item.complimentary_item_discount !== null"
                                            @click="state.selectedProduct = item; removeTheComplimentaryDiscount()"
                                        >
                                            Remove Complimentary Item
                                        </DropdownItem>

                                        <DropdownItem @click="attachPromoterModalShow(item.id)">
                                            Attach Promoter
                                        </DropdownItem>

                                        <DropdownItem
                                            v-if="item.has_batch"
                                            @click="addBatchDetailsModalShow(item)"
                                        >
                                            Add Batch Details
                                        </DropdownItem>
                                    </DropdownContent>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center border-b mt-6">
                        <p class="font-bold text-lg">
                            Subtotals
                        </p>

                        <button
                            v-if="details.allow_price_override_cart_level && state.cartLevelDiscount === 0"
                            class="underline my-auto pl-2 sm:pl-0 text-right"
                            @click="manageCartDiscountModalShow()"
                        >
                            Apply Custom Discount
                        </button>

                        <button
                            v-if="details.allow_price_override_cart_level && state.cartLevelDiscount !== 0"
                            class="underline my-auto pl-2 sm:pl-0 text-right"
                            @click="removeTheCartDiscount()"
                        >
                            Remove Custom Discount
                        </button>
                    </div>

                    <div class="mt-5 text-base">
                        <div class="flex justify-between sm:space-y-2 items-center">
                            <span class="font-medium">Subtotal: </span>
                            <p class="font-normal">
                                {{ displayAmountWithCurrencySymbol(getSubtotalAmount()) }}
                            </p>
                        </div>

                        <div class="flex justify-between sm:space-y-2 items-center text-success">
                            <span class="font-medium">Discount: </span>
                            <p class="font-normal">
                                {{ displayAmountWithCurrencySymbol(getDiscountAmount()) }}
                            </p>
                        </div>

                        <div class="flex justify-between sm:space-y-2 items-center">
                            <span class="font-medium">Tax: </span>
                            <p class="font-normal">
                                {{ displayAmountWithCurrencySymbol(getTaxAmount()) }}
                            </p>
                        </div>

                        <div class="flex justify-between sm:space-y-2 items-center">
                            <span class="font-medium">Round Off: </span>
                            <p class="font-normal">
                                {{ displayAmountWithCurrencySymbol(ordersForm.order_round_off_amount) }}
                            </p>
                        </div>

                        <div class="flex justify-between sm:space-y-2 border-t items-center">
                            <span class="font-bold text-lg text-danger">Total: </span>
                            <p class="font-bold text-lg text-danger">
                                {{ displayAmountWithCurrencySymbol(getTotalAmount) }}
                            </p>
                        </div>

                        <div
                            v-if="state.changeDue > 0"
                            class="flex justify-between sm:space-y-2 border-t items-center"
                        >
                            <span class="font-bold text-lg text-info">Change Due: </span>
                            <p class="font-bold text-lg text-info">
                                {{ displayAmountWithCurrencySymbol(state.changeDue) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x">
                    <div class="block sm:flex items-center">
                        <FormAjaxSelect
                            :selected-record="state.selectedMember"
                            :search-records="searchMembers"
                            placeholder="Member Name to search..."
                            input-label="Member"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            class="w-full"
                            @update:selected-record="updateMemberId"
                        />

                        <button
                            v-if="ordersForm.member_id === null"
                            type="button"
                            class="shadow-md ml-0 sm:ml-2 mt-2 sm:mt-11 p-2 rounded bg-primary text-white text-center"
                            @click="showMemberModal"
                        >
                            <UserPlus />
                        </button>

                        <button
                            v-else
                            type="button"
                            class="shadow-md ml-0 sm:ml-2 mt-2 sm:mt-11 p-2 rounded bg-danger text-white text-center"
                            @click="ordersForm.member_id = null; state.selectedMember = null;"
                        >
                            <UserMinus />
                        </button>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl intro-x flex justify-between">
                    <JSwitch
                        v-if="!state.hasOnlyOneComplimentaryDiscount"
                        v-model:is-checked="ordersForm.is_layaway"
                        input-label="Is Layaway Order?"
                        :disabled="ordersForm.is_credit"
                        class="text-lg"
                    />

                    <JSwitch
                        v-if="!state.hasOnlyOneComplimentaryDiscount"
                        v-model:is-checked="ordersForm.is_credit"
                        input-label="Is Credit Order?"
                        :disabled="ordersForm.is_layaway"
                        class="text-lg"
                    />
                </div>

                <div v-if="!state.hasOnlyOneComplimentaryDiscount">
                    <p class="font-bold text-lg border-b mt-6 mb-5">
                        Payment Types
                    </p>

                    <div
                        :class="{
                            'overflow-y-auto h-64 mt-5': Object.keys(paymentTypes).length >= 5,
                        }"
                    >
                        <div
                            v-for="paymentType in paymentTypes"
                            :key="paymentType.id"
                            class="block sm:flex items-center py-2 px-4 rounded-xl mb-2 bg-slate-50 border border-gray-200 hover:border-primary focus:border-primary justify-between hover:bg-slate-200 focus:bg-slate-200"
                        >
                            <button
                                type="button"
                                class="block sm:inline-flex items-center justify-between w-full bg-transparent border-none text-black p-0 mx-0 my-0 shadow-none outline-0 hover:ring-transparent focus:ring-transparent text-sm disabled:opacity-50"
                                :class="getPaymentButtonClass(paymentType)"
                                :disabled="getPaymentTotal() === parseFloat(getTotalAmount)"
                                @click="displayPaymentTypeModal(paymentType)"
                            >
                                <div class="flex items-center">
                                    <img
                                        :src="paymentType.image_name"
                                        class="w-8 h-8 bg-primary rounded-lg p-1 mr-3"
                                    >

                                    <p class="text-black">
                                        {{ paymentType.name }}
                                    </p>
                                </div>

                                <div class="text-sm font-normal items-center text-black text-left mt-2 sm:mt-0">
                                    {{ displayAmountWithCurrencySymbol(getEachPaymentTotal(paymentType)) }}
                                </div>
                            </button>
                            <button
                                v-if="getEachPaymentTotal(paymentType) > 0.0"
                                type="button"
                                class="ml-0 sm:ml-2 bg-danger border-danger btn-sm rounded-lg text-white"
                                @click="removePaymentFromOrdersPayments(paymentType.id)"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="border-t flex justify-between text-lg mt-6 pt-4 font-bold items-center"
                    :class="{
                        'text-success': numberFormat(getPaymentTotal()) === getTotalAmount,
                        'text-danger': parseFloat(numberFormat(getPaymentTotal())) < getTotalAmount,
                    }"
                >
                    <div>
                        Total:
                    </div>

                    <div>
                        {{ displayAmountWithCurrencySymbol(getPaymentTotal()) }}
                    </div>
                </div>

                <div
                    v-if="ordersForm.is_layaway"
                    class="border-t flex justify-between text-lg mt-6 pt-4 font-bold items-center"
                    :class="{
                        'text-success': numberFormat(getPaymentTotal()) === getTotalAmount,
                        'text-danger': parseFloat(numberFormat(getPaymentTotal())) < getTotalAmount,
                    }"
                >
                    <div>
                        Pending Layaway:
                    </div>

                    <div>
                        {{ displayAmountWithCurrencySymbol(getPendingLayawayAmount()) }}
                    </div>
                </div>

                <div
                    v-if="ordersForm.is_credit"
                    class="border-t flex justify-between text-lg mt-6 pt-4 font-bold items-center"
                    :class="{
                        'text-success': numberFormat(getPaymentTotal()) === getTotalAmount,
                        'text-danger': parseFloat(numberFormat(getPaymentTotal())) < getTotalAmount,
                    }"
                >
                    <div>
                        Pending Credit:
                    </div>

                    <div>
                        {{ displayAmountWithCurrencySymbol(getPendingCreditAmount()) }}
                    </div>
                </div>

                <div>
                    <p class="font-bold text-lg border-b mt-6 mb-5">
                        Extra Details
                    </p>

                    <div>
                        <FormInput
                            v-model:input-value="ordersForm.bill_reference_number"
                            input-label="Bill Reference Number"
                            input-name="bill_reference_number"
                            class="w-full"
                            :required="details.is_bill_reference_number_mandatory"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                        />
                    </div>

                    <div>
                        <FormTextArea
                            v-model:input-value="ordersForm.notes"
                            input-label="Remarks"
                            input-name="notes"
                            class="w-full"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                        />
                    </div>
                </div>

                <div class="text-right mt-4">
                    <PrimaryButton
                        type="button"
                        text="Complete Order"
                        class="w-36"
                        @click="completeOrder()"
                    />
                </div>
            </div>
        </div>
    </div>

    <OpenPriceModal
        :modal-show="state.openPriceModal"
        :product-minimum-price="state.productMinimumPrice"
        @add-open-price="updateOpenPrice"
        @close-open-price-modal="closeOpenPriceModal"
    />

    <Receipt
        v-if="Object.keys(state.orderData).length"
        :order="state.orderData"
        :print-receipt-data="state.printReceiptData"
    />

    <PaymentModal
        v-if="state.paymentTypeModalShow"
        :modal-show="state.paymentTypeModalShow"
        :payment-type="state.paymentType"
        :total-amount="parseFloat(getTotalAmount)"
        :payments="ordersForm.payments"
        @close-payment-modal="hidePaymentTypeModal"
        @add-payment-type="updatePaymentData"
    />

    <PriceOverrideDiscount
        :modal-show="state.manageItemLevelDiscount"
        discount-type="Item Wise Price Override Discount"
        :price-override-limit="getItemPriceOverrideLimit()"
        :price-override-type="props.details.item_wise_price_override"
        :price-override-types="priceOverrideTypes"
        @update-price-override-discount="manageItemDiscount($event)"
        @close-price-override-modal="hideItemLevelDiscountModal"
    />

    <PriceOverrideDiscount
        :modal-show="state.manageCartLevelDiscount"
        discount-type="Cart Wide Price Override Discount"
        :price-override-limit="parseFloat(props.details.price_override_limit_percentage_for_cart)"
        :price-override-types="priceOverrideTypes"
        @update-price-override-discount="updateCartLevelDiscount($event)"
        @close-price-override-modal="hideCartLevelDiscountModal"
    />

    <ComplimentaryItemDiscount
        :modal-show="state.complimentaryItemReasonModalShow"
        :complimentary-item-reasons="complimentaryItemReasons"
        @update-complimentary-item-discount="updateComplimentaryItemReason($event)"
        @close-complimentary-item-modal="hideComplimentaryItemReasonModal"
    />

    <AttachPromoters
        :modal-show="state.attachPromoterModalShow"
        :promoters="promoters"
        :selected-product="state.selectedProduct"
        @attach-to-all-items="attachToAllItems($event)"
        @remove-to-all-items="removeToAllItems()"
        @update-attach-promoter="updateAttachedPromoter($event)"
        @close-attach-promoter-modal="hideAttachPromoterModal"
    />

    <CreateMember
        :modal-show="state.memberModalShow"
        :member-type-corporate="memberTypeCorporate"
        :location-id="details.location_id"
        @update-member="updateMemberDetails($event)"
        @close-member-modal="hideMemberModal"
    />

    <BatchDetails
        v-if="state.selectedProduct"
        :modal-show="state.addBatchDetailsModalShow"
        :selected-product="state.selectedProduct"
        @update-batch-details="updateBatchDetails"
        @close-batch-details-modal="addBatchDetailsModalHide"
    />
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormInputNumber from '@commonComponents/FormInputNumber.vue';
import FormTextArea from '@commonComponents/FormTextarea.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import Receipt from '@commonComponents/Receipt.vue';
import { displayAmountWithCurrencySymbol, numberFormat } from '@commonServices/helper';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { router, useForm } from '@inertiajs/vue3';
import AttachPromoters from '@storeManagerPages/orders/partials/AttachPromoters.vue';
import BatchDetails from '@storeManagerPages/orders/partials/BatchDetails.vue';
import ComplimentaryItemDiscount from '@storeManagerPages/orders/partials/ComplimentaryItemDiscount.vue';
import CreateMember from '@storeManagerPages/orders/partials/CreateMember.vue';
import OpenPriceModal from '@storeManagerPages/orders/partials/OpenPriceModal.vue';
import PaymentModal from '@storeManagerPages/orders/partials/PaymentModal.vue';
import PriceOverrideDiscount from '@storeManagerPages/orders/partials/PriceOverrideDiscount.vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { Info, MoreVertical, UserMinus, UserPlus, X } from 'lucide-vue-next';
import onScan from 'onscan.js/onscan.js';
import { computed, nextTick, onMounted, onUnmounted, reactive } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    promoters: {
        type: Object,
        required: true
    },
    memberTypeCorporate: {
        type: Number,
        required: true
    },
    complimentaryItemReasons: {
        type: Object,
        required: true
    },
    paymentTypes: {
        type: Object,
        required: true
    },
    staticUsePaymentTypes: {
        type: Object,
        required: true
    },
    staticUseProductTypes: {
        type: Object,
        required: true
    },
    details: {
        type: Object,
        required: true
    },
    channelType: {
        type: Number,
        required: true
    },
    orderType: {
        type: Number,
        required: true
    },
    priceOverrideTypes: {
        type: Object,
        required: true
    },
});

const state = reactive({
    isDataFetching: false,
    hasComplimentaryDiscount: false,
    hasOnlyOneComplimentaryDiscount: false,
    productUpc: null,
    products: {},
    selectedMember: null,
    selectedProductId: null,
    selectedProduct: null,
    cartLevelDiscount: 0,

    changeDue: 0,
    productMinimumPrice: 0,

    paymentType: null,
    memberModalShow: false,
    paymentTypeModalShow: false,
    manageItemLevelDiscount: false,
    manageCartLevelDiscount: false,
    complimentaryItemReasonModalShow: false,
    attachPromoterModalShow: false,
    addBatchDetailsModalShow: false,
    selectedPromoters: null,
    isScanProduct: false,
    openPriceModal: false,
    printReceiptData: Math.random(),
    orderData: {},
});

const ordersForm = useForm({
    member_id: null,
    order_items: [],
    payments: [],
    cart_level_discount: 0.0,
    cart_price_override_amount: 0.0,
    cart_price_override_percentage: 0.0,
    order_round_off_amount: 0.0,
    bill_reference_number: null,
    notes: null,
    order_type: props.orderType,
    channel_type: props.channelType,
    total_tax_amount: null,
    is_layaway: false,
    layaway_pending_amount: null,
    is_credit: false,
    credit_pending_amount: null,
});

const hideMemberModal = () => {
    state.memberModalShow = false;
    onScanProductCheck();
};

const hidePaymentTypeModal = () => {
    state.paymentTypeModalShow = false;
    state.paymentType = null;
};

const getPaymentButtonClass = (paymentType) => {
    const payment = ordersForm.payments.find((ordersPayment) => ordersPayment.type_id === paymentType.id);

    if (
        typeof payment !== 'undefined' && payment.amount > 0
    ) {
        return 'btn btn-success my-1 w-28 text-white';
    }

    return 'btn btn-primary my-1 w-28';
};

const displayPaymentTypeModal = (paymentType) => {
    if (getPaymentTotal() === parseFloat(getTotalAmount.value)) {
        return showErrorNotification('The Payment Is Complete, Processed Further.');
    }
    state.paymentType = paymentType;
    state.paymentTypeModalShow = true;
};

const hideComplimentaryItemReasonModal = () => {
    state.complimentaryItemReasonModalShow = false;
};

const hideAttachPromoterModal = () => {
    state.attachPromoterModalShow = false;
};

const hideItemLevelDiscountModal = () => {
    state.manageItemLevelDiscount = false;
};

const hideCartLevelDiscountModal = () => {
    state.manageCartLevelDiscount = false;
};

const complimentaryItemReasonModalShow = async (selectedProductId) => {
    await confirmPaymentUpdates();

    state.complimentaryItemReasonModalShow = true;
    state.selectedProductId = selectedProductId;
};

const attachPromoterModalShow = (selectedProductId) => {
    const selectedProduct = ordersForm.order_items.find((product) => product.id === selectedProductId);

    state.attachPromoterModalShow = true;
    state.selectedProductId = selectedProductId;
    state.selectedProduct = selectedProduct;
};

const manageItemDiscountModalShow = async (selectedProduct) => {
    await confirmPaymentUpdates();

    state.manageItemLevelDiscount = true;
    state.selectedProduct = selectedProduct;
};

const manageCartDiscountModalShow = async () => {
    await confirmPaymentUpdates();
    state.manageCartLevelDiscount = true;
};

const updateComplimentaryItemReason = (selectedComplimentaryItem) => {
    state.hasComplimentaryDiscount = true;

    ordersForm.order_items.forEach((product) => {
        if (product.id === state.selectedProductId) {
            if (product.price_override_amount !== 0 && product.price_override_percentage !== 0) {
                product.price_override_amount = 0;
                product.price_override_percentage = 0;
            }
            product.complimentary_item_reason_id = selectedComplimentaryItem;
            product.complimentary_item_discount = getItemSubtotal(product);
        }
    });
    state.complimentaryItemReasonModalShow = false;
};

const updateAttachedPromoter = (selectedPromoters) => {
    ordersForm.order_items.forEach((product) => {
        if (product.id === state.selectedProductId) {
            product.promoter_ids = selectedPromoters.map((promoter) => {
                return promoter.id;
            });
            product.promoter_name = selectedPromoters.map((promoter) => {
                return (promoter.name).trim();
            });
        }
    });

    state.attachPromoterModalShow = false;
};

const manageItemDiscount = (discountPercentage) => {
    if (parseFloat(discountPercentage) > getItemPriceOverrideLimit()) {
        showErrorNotification('Custom Discount Percentage Is Invalid');
        return;
    }

    ordersForm.order_items.forEach((orderItem) => {
        if (orderItem.id === state.selectedProduct.id) {
            const itemPrice = getItemPrice(orderItem);
            const priceOverrideAmount = getPriceOverrideDiscountAmount(itemPrice, discountPercentage);

            orderItem.price_override_amount = numberFormat(itemPrice - priceOverrideAmount);
            orderItem.price_override_percentage = discountPercentage;
        }
    });

    if (state.cartLevelDiscount > 0.0) {
        updateCartLevelDiscount(state.cartLevelDiscount);
    }

    state.manageItemLevelDiscount = false;
};

const percentageDivisor = 100;

const getPriceOverrideDiscountAmount = (itemPrice, discountPercentage) => {
    if (props.details.item_wise_price_override === props.priceOverrideTypes.flat) {
        if (discountPercentage > state.selectedProduct.wholesale_price) {
            return;
        }

        return discountPercentage;
    }

    return numberFormat((discountPercentage * itemPrice) / percentageDivisor);
};

const debounceDelay = 300;

const getSearchedProducts = debounce((productUpc) => {
    if (productUpc === '') {
        state.isDataFetching = false;
        state.products = [];
        return;
    }

    axios.get(route('store_manager.get_product_lists', {
        search_text: productUpc
    })).then((response) => {
        state.products = response.data.products;
        if (state.isScanProduct && response.data.products.length > 0) {
            addToCart(response.data.products[0].id);
            state.isScanProduct = false;
        }
        state.isDataFetching = false;
    });
}, debounceDelay);

const debounceSearch = (productUpc) => {
    state.isDataFetching = true;
    state.products = [];
    getSearchedProducts(productUpc);
};

const updateQuantity = async (quantity, product, index) => {
    quantity = parseFloat(quantity);

    if (!product.unit_of_measure.allow_decimal_qty) {
        quantity = parseInt(quantity);
    }

    if (quantity <= 0.0) {
        ordersForm.order_items.splice(index, 1);
        if (ordersForm.order_items.length <= 0) {
            ordersForm.cart_price_override_amount = 0.0;
            ordersForm.cart_price_override_percentage = 0.0;
            state.cartLevelDiscount = 0.0;
            ordersForm.payments = [];
        }
        return;
    }

    await confirmPaymentUpdates();
    if (quantity > 0) {
        if (state.cartLevelDiscount > 0.0) {
            updateCartLevelDiscount(state.cartLevelDiscount);
        }

        if (isNaN(quantity)) {
            quantity = 1;
        }

        product.quantity = quantity;

        if (product.complimentary_item_discount !== 0 && product.complimentary_item_reason_id !== null) {
            product.complimentary_item_discount = getItemSubtotal(product);
        }

        if (product.has_batch && product.batch_details.length > 0) {
            product.batch_details.forEach((batchDetails) => {
                batchDetails.quantity = quantity;
            });
        }
    }
};

const addToCart = async (productId, productBoxId = null) => {
    const selectedProduct = state.products.find((product) => product.id === productId && product.box_product_id === productBoxId);

    await confirmPaymentUpdates();

    if (selectedProduct) {
        const existingOrderItem = ordersForm.order_items.find((item) => item.id === selectedProduct.id && item.box_product_id === selectedProduct.box_product_id);

        if (existingOrderItem) {
            existingOrderItem.quantity++;

            ordersForm.order_items.forEach((product) => {
                if (product.id === existingOrderItem.id) {
                    if (existingOrderItem.complimentary_item_discount !== 0 && existingOrderItem.complimentary_item_reason_id !== null) {
                        existingOrderItem.complimentary_item_discount = getItemSubtotal(existingOrderItem);
                    }
                }
            });

            if (state.cartLevelDiscount > 0.0) {
                updateCartLevelDiscount(state.cartLevelDiscount);
            }
        } else {
            if (selectedProduct.type_id !== props.staticUseProductTypes.regularProduct && selectedProduct.type_id !== props.staticUseProductTypes.assemblyProduct) {
                state.selectedProduct = selectedProduct;
                state.productMinimumPrice = parseFloat(selectedProduct.minimum_price);
                state.openPriceModal = true;
                return;
            }

            if (selectedProduct.batch_details.length > 0) {
                selectedProduct.batch_details.forEach((batchDetails) => {
                    batchDetails.quantity = 1;
                });
            }

            ordersForm.order_items.push({
                id: selectedProduct.id,
                box_product_id: selectedProduct.box_product_id,
                product_name: selectedProduct.name,
                price: selectedProduct.price,
                total_price_paid: null,
                complimentary_item_reason_id: null,
                complimentary_item_discount: null,
                item_discount_amount: 0.0,
                cart_discount_amount: 0.0,
                price_override_amount: 0.0,
                price_override_percentage: 0.0,
                promoter_ids: [],
                promoter_name: [],
                quantity: 1,
                open_price: 0,
                wholesale_price: selectedProduct.wholesale_price,
                product_type: selectedProduct.type_id,
                unit_of_measure: selectedProduct.unit_of_measure,
                has_batch: selectedProduct.has_batch,
                batch_details: selectedProduct.batch_details,
            });
        }

        const animationDuration = 500;

        nextTick(() => {
            document.querySelectorAll('.item-quantity-count').forEach(element => {
                element.classList.add('animate-fade-in-up', 'animate-300');
                setTimeout(function () {
                    element.classList.remove('animate-fade-in-up', 'animate-300');
                }, animationDuration);
            });
        });
    }
};

const getChangeDue = (extraAmount) => {
    return extraAmount - getTotalAmount.value;
};

const getSubtotalAmount = () => {
    let subtotal = 0.0;

    ordersForm.order_items.forEach((product) => {
        const price = getItemPrice(product);
        subtotal += price * product.quantity;
    });

    checkHasComplimentaryDiscount();

    return subtotal;
};

const getDiscountAmount = () => {
    let discount = 0.0;

    ordersForm.order_items.forEach((orderItem) => {
        discount += parseFloat(getItemWiseDiscount(orderItem));
    });

    discount += parseFloat(getCartWideDiscount());

    return discount;
};

const getTaxAmount = () => {
    let tax = 0.0;
    tax = parseFloat(getCartSubtotalAfterDiscount() * parseFloat(props.details.sale_tax_percentage) / percentageDivisor);
    ordersForm.total_tax_amount = numberFormat(tax);

    return parseFloat(numberFormat(tax));
};

const getTotalAmount = computed(() => {
    let tax = 0.0;
    let totalPricePaid = 0.0;
    let mainSubtotal = 0.0;

    ordersForm.order_items.forEach((orderItem) => {
        let itemSubtotal = getItemSubtotal(orderItem);
        const itemDiscounts = getItemDiscountAmountFor(orderItem);
        itemSubtotal -= itemDiscounts.total_discount;
        let cartSubTotal = getCartSubtotal();

        cartSubTotal -= getTotalItemDiscountAmount();
        const cartDiscountAmountSplitByQuantity = cartSubTotal > 0 ? numberFormat(itemSubtotal * parseFloat(getCartDiscountAmountFor(cartSubTotal).total_discount) / cartSubTotal) : 0.0;
        const itemAmountAfterCartDiscountAmount = parseFloat(numberFormat(itemSubtotal - parseFloat(cartDiscountAmountSplitByQuantity)));
        const itemTax = getItemTax(itemAmountAfterCartDiscountAmount);
        mainSubtotal += itemSubtotal;

        orderItem.total_price_paid = parseFloat(numberFormat(itemAmountAfterCartDiscountAmount + parseFloat(itemTax)));
        totalPricePaid += parseFloat(numberFormat(itemAmountAfterCartDiscountAmount + parseFloat(itemTax)));
    });

    tax = getTaxAmount();

    const roundOffAmount = parseFloat(getLastDigitsAfterPoint(numberFormat(totalPricePaid).toString()));
    mainSubtotal -= getCartDiscountAmountFor(mainSubtotal).total_discount;

    return numberFormat(mainSubtotal + tax + roundOffAmount);
});

const getItemTax = (itemPriceAfterDiscount) => {
    return getCartSubtotalAfterDiscount() > 0 ? numberFormat((parseFloat(itemPriceAfterDiscount) * getTaxAmount()) / getCartSubtotalAfterDiscount()) : 0.0;
};

const getItemWiseDiscount = (orderItem) => {
    let discount = 0.0;

    if (orderItem.price_override_percentage > 0) {
        discount += parseFloat(numberFormat((getItemSubtotal(orderItem) * orderItem.price_override_percentage) / percentageDivisor));
    }

    if (orderItem.complimentary_item_discount > 0.0) {
        discount += parseFloat(orderItem.complimentary_item_discount);
    }

    return discount;
};

const getCartWideDiscount = () => {
    if (state.cartLevelDiscount === 0) {
        return 0;
    }

    let discount = 0.0;
    const subtotal = getSubtotalAmount();

    ordersForm.order_items.forEach((orderItem) => {
        discount += getItemWiseDiscount(orderItem);
    });

    return numberFormat((subtotal - discount) * state.cartLevelDiscount / percentageDivisor);
};

const updateMemberId = (selectMember) => {
    state.selectedMember = selectMember;
    if (selectMember !== null) {
        ordersForm.member_id = selectMember.id;
    }
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };

    axios.get(route('store_manager.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

onMounted(() => {
    onScanProductCheck();
});

onUnmounted(() => {
    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
});

const onScanProductCheck = () => {
    onScan.attachTo(document, {
        reactToPaste: true,
        onPaste: (pasteValue) => {
            getSearchedProducts(pasteValue);
            state.isScanProduct = true;
            state.productUpc = pasteValue;
        },
    });
};

const updateCartLevelDiscount = debounce((discountPercentage) => {
    if (parseFloat(discountPercentage) > props.details.price_override_limit_percentage_for_cart) {
        showErrorNotification('The New Discount Percentage Is Invalid.');
        return;
    }

    const subtotal = getSubtotalAmount();
    let itemDiscount = 0.0;

    ordersForm.order_items.forEach((orderItem) => {
        const itemWiseDiscount = getItemWiseDiscount(orderItem);
        itemDiscount += itemWiseDiscount;
    });

    const subtotalAfterItemDiscount = numberFormat(subtotal - itemDiscount);
    const priceOverrideAmount = numberFormat(subtotalAfterItemDiscount * discountPercentage / percentageDivisor);

    ordersForm.cart_price_override_amount = numberFormat(parseFloat(subtotalAfterItemDiscount) - parseFloat(priceOverrideAmount));
    ordersForm.cart_price_override_percentage = discountPercentage;
    state.cartLevelDiscount = discountPercentage;
    state.manageCartLevelDiscount = false;
}, debounceDelay);

const completeOrder = () => {
    let isAllValidationApp = true;

    if (props.details.min_promoters_per_item > 0) {
        ordersForm.order_items.filter((items) => {
            if (items.promoter_ids.length <= 0) {
                isAllValidationApp = false;
                return showErrorNotification(`Each item needs to have a minimum of ${props.details.min_promoters_per_item} promoters attached.`);
            }

            return null;
        });
    }

    if (!checkOrderIsNotCreditOrLayaway() && ordersForm.payments.length <= 0 && !state.hasOnlyOneComplimentaryDiscount) {
        isAllValidationApp = false;
        return showErrorNotification('To proceed, please complete the payment.');
    }

    if (!checkOrderIsNotCreditOrLayaway() && parseFloat(numberFormat(getPaymentTotal())) < parseFloat(numberFormat(getTotalAmount.value))) {
        isAllValidationApp = false;
        return showErrorNotification('Insufficient Funds: Please provide the full amount for this transaction.');
    }

    if (parseFloat(numberFormat(getPaymentTotal())) > parseFloat(numberFormat(getTotalAmount.value))) {
        isAllValidationApp = false;
        return showErrorNotification('The entered amount exceeds the allowable total.');
    }

    if (props.details.is_bill_reference_number_mandatory && ordersForm.bill_reference_number === null) {
        isAllValidationApp = false;
        return showErrorNotification('To proceed, enter the bill reference number.');
    }

    if (ordersForm.order_items.length > 0) {
        let quantity = 0;
        ordersForm.order_items.forEach(item => {
            if (item.has_batch) {
                quantity += item.quantity;
                if (item.batch_details.length <= 0) {
                    isAllValidationApp = false;
                    return showErrorNotification('The selected product has batch information, but the batch details are missing. To proceed, please enter the required batch details.');
                }

                const batchQuantity = item.batch_details.reduce((total, BatchDetail) => total + BatchDetail.quantity, 0);

                if (batchQuantity < quantity) {
                    isAllValidationApp = false;
                    return showErrorNotification('batch quantity cannot be less than the actual quantity.');
                }
            }
        });
    }

    if (isAllValidationApp) {
        axios.post(route('store_manager.orders.store'), ordersForm)
            .then((response) => {
                state.orderData = response.data.order;

                nextTick(() => {
                    state.printReceiptData = Math.random();
                    router.get(route('store_manager.orders.create'));
                });
            })
            .catch((error) => {
                if (error.response.data.message) {
                    showErrorNotification(error.response.data.message);
                }
            });
    }
};

const getLastDigitsAfterPoint = (amount) => {
    const lastCharacterIndex = -1;
    const lastCharacter = amount.slice(lastCharacterIndex);
    const decimalPlacesOfAmount = '.0' + lastCharacter;

    const roundOffValue = props.details.round_off_configurations.find((value) => value.decimal_place === decimalPlacesOfAmount);

    if (roundOffValue) {
        ordersForm.order_round_off_amount = roundOffValue.value;
        return roundOffValue.value;
    }

    return 0;
};

const getItemSubtotal = (orderItem) => {
    return numberFormat(getItemPrice(orderItem) * orderItem.quantity);
};

const getCartSubtotalAfterDiscount = () => {
    let cartSubtotal = parseFloat(getCartSubtotal());
    cartSubtotal -= getTotalItemDiscountAmount();

    const cartDiscount = getCartDiscountAmountFor(cartSubtotal);

    return parseFloat(numberFormat(cartSubtotal) - cartDiscount.total_discount);
};

const getCartSubtotal = () => {
    const cartSubtotal = ordersForm.order_items.reduce((accumulator, orderItem) => {
        return accumulator + parseFloat(getItemSubtotal(orderItem));
    }, 0);

    return numberFormat(cartSubtotal);
};

const getTotalItemDiscountAmount = () => {
    let totalItemDiscount = 0;
    ordersForm.order_items.forEach((orderItem) => {
        const itemDiscounts = getItemDiscountAmountFor(orderItem);
        totalItemDiscount += itemDiscounts.total_discount;
    });

    return parseFloat(numberFormat(totalItemDiscount));
};

const getItemDiscountAmountFor = (orderItem) => {
    const discounts = {
        complimentary_item_discount: 0.00,
        price_override_discount: 0.00,
        total_discount: 0.00,
    };

    if ('complimentary_item_reason_id' in orderItem && orderItem.complimentary_item_reason_id !== null) {
        const itemSubtotal = getItemSubtotal(orderItem);

        const discountAmount = itemSubtotal;
        discounts.complimentary_item_discount = orderItem.complimentary_item_discount;
        discounts.total_discount += parseFloat(discountAmount);

        return discounts;
    }

    if ('price_override_amount' in orderItem && orderItem.price_override_amount !== 0.00) {
        const itemPrice = getItemPrice(orderItem);
        const discount = getItemDiscountAmount(orderItem);
        const allowedPriceOverrideDiscountAmount = discount < 0 ? 0.00 : discount;

        discounts.price_override_discount = allowedPriceOverrideDiscountAmount;
        discounts.total_discount += allowedPriceOverrideDiscountAmount;
        orderItem.price = itemPrice;
    }

    return discounts;
};

const getItemDiscountAmount = (orderItem) => {
    const itemPrice = getItemPrice(orderItem);

    const discountAmount = parseFloat(numberFormat((itemPrice - orderItem.price_override_amount)) * orderItem.quantity);

    if (discountAmount < 0) {
        return 0.00;
    }

    return discountAmount;
};

const getCartDiscountAmountFor = (subtotal) => {
    const cartDiscount = {
        total_discount: 0,
        price_override_discount: 0,
    };

    if ('cart_price_override_amount' in ordersForm && parseFloat(ordersForm.cart_price_override_amount) !== 0.00) {
        const subtotalForDiscount = getCartSubtotalByDiscountApplicableType(subtotal);
        const discount = parseFloat(numberFormat(subtotalForDiscount - parseFloat(ordersForm.cart_price_override_amount)));
        const priceOverrideDiscount = discount < 0 ? 0.00 : discount;

        cartDiscount.price_override_discount = priceOverrideDiscount;
        cartDiscount.total_discount += priceOverrideDiscount;
    }

    return cartDiscount;
};

const getCartSubtotalByDiscountApplicableType = (cartSubtotal) => {
    if (props.details.discount_applicable_type === props.details.additional_discount_on_already_discounted_prices) {
        return cartSubtotal;
    }

    return getCartSubtotal();
};

const getItemWithCartDiscount = (orderItem) => {
    let discount = 0.0;

    if (orderItem.price_override_percentage !== 0 || orderItem.complimentary_item_discount > 0.0) {
        discount += getItemWiseDiscount(orderItem);
    }

    if (state.cartLevelDiscount !== 0.0) {
        discount += parseFloat(numberFormat((getItemSubtotal(orderItem) - discount) * state.cartLevelDiscount / percentageDivisor));
    }

    return discount;
};

const getItemPrice = (orderItem) => {
    return orderItem.price === 0 ? orderItem.open_price : orderItem.price;
};

const removeTheItemDiscount = () => {
    ordersForm.order_items.forEach((orderItem) => {
        if (orderItem.id === state.selectedProduct.id) {
            orderItem.price_override_amount = 0;
            orderItem.price_override_percentage = 0;
        }
    });
};

const removeTheCartDiscount = () => {
    state.cartLevelDiscount = 0;

    ordersForm.cart_price_override_amount = 0;
    ordersForm.cart_price_override_percentage = 0;
};

const removeTheComplimentaryDiscount = () => {
    state.selectedComplimentaryItemReason = null;
    ordersForm.order_items.forEach((orderItem) => {
        if (orderItem.id === state.selectedProduct.id) {
            orderItem.complimentary_item_reason_id = null;
            orderItem.complimentary_item_discount = null;
        }
    });
};

const getPaymentTotal = () => {
    let total = 0.0;
    ordersForm.payments.forEach((payment) => {
        total += payment.amount;
    });

    return parseFloat(total);
};

const getPendingLayawayAmount = () => {
    let total = 0.0;
    ordersForm.credit_pending_amount = 0.0;
    ordersForm.payments.forEach((payment) => {
        total += payment.amount;
    });

    ordersForm.layaway_pending_amount = parseFloat(getTotalAmount.value - total);

    return parseFloat(getTotalAmount.value - total);
};

const getPendingCreditAmount = () => {
    let total = 0.0;
    ordersForm.layaway_pending_amount = 0.0;
    ordersForm.payments.forEach((payment) => {
        total += payment.amount;
    });

    ordersForm.credit_pending_amount = parseFloat(getTotalAmount.value - total);

    return parseFloat(getTotalAmount.value - total);
};

const getEachPaymentTotal = (paymentType) => {
    let total = 0.0;
    ordersForm.payments.forEach((payment) => {
        if (payment.type_id === paymentType.id) {
            total += payment.amount;
        }
    });

    return parseFloat(total);
};

const notFoundIndex = -1;

const updatePaymentData = (data) => {
    const ordersPaymentIndex = ordersForm.payments.findIndex((payment) => payment.type_id === data.type_id);
    const payment = props.paymentTypes.find((payment) => payment.id === data.type_id);
    payment.amount = data.amount;

    if (ordersPaymentIndex > notFoundIndex) {
        ordersForm.payments.splice(ordersPaymentIndex, 1);
    }

    state.changeDue = getChangeDue(data.amount);

    if (data.amount > getTotalAmount.value) {
        data.amount = getTotalAmount.value;
    }

    ordersForm.payments.push(data);

    hidePaymentTypeModal();
};

const checkHasComplimentaryDiscount = () => {
    const ordersFormOrderItemCount = ordersForm.order_items.length;

    if (ordersFormOrderItemCount > 1) {
        state.hasOnlyOneComplimentaryDiscount = false;
        return;
    }

    if (ordersForm.order_items[0].complimentary_item_reason_id === null) {
        state.hasOnlyOneComplimentaryDiscount = false;
        return;
    }

    state.hasOnlyOneComplimentaryDiscount = true;
};

const closeOpenPriceModal = () => {
    state.openPriceModal = false;
};

const updateOpenPrice = (openPrice) => {
    state.openPriceModal = false;

    ordersForm.order_items.push({
        id: state.selectedProduct.id,
        product_name: state.selectedProduct.name,
        price: 0,
        total_price_paid: null,
        complimentary_item_reason_id: null,
        complimentary_item_discount: null,
        item_discount_amount: 0.0,
        cart_discount_amount: 0.0,
        price_override_amount: 0.0,
        price_override_percentage: 0.0,
        promoter_ids: [],
        promoter_name: [],
        quantity: 1,
        open_price: openPrice,
        product_type: state.selectedProduct.type_id
    });
};

const removePaymentFromOrdersPayments = (paymentTypeId) => {
    const paymentIndex = ordersForm.payments.findIndex((payment) => payment.type_id === paymentTypeId);
    const payment = props.paymentTypes.find((payment) => payment.id === paymentTypeId);
    state.changeDue -= payment.amount;
    payment.amount = 0.0;

    if (paymentIndex > notFoundIndex) {
        ordersForm.payments.splice(paymentIndex, 1);
    }
};

const confirmPaymentUpdates = async () => {
    if (getPaymentTotal() !== 0.0) {
        await new Promise((resolve, reject) => {
            confirmDialogBox('Are you sure you want to proceed with the changes you`ve made? If you confirm, the payment information will be reset.', () => {
                ordersForm.payments = [];
                resolve();
            }, () => {

                reject();
            });
        });
    }
};

const updateMemberDetails = (member) => {
    state.selectedMember = member;
    ordersForm.member_id = member.id;
};

const getItemPriceOverrideLimit = () => {
    if (props.priceOverrideTypes.flat === props.details.item_wise_price_override && state.selectedProduct) {
        return parseFloat(state.selectedProduct.wholesale_price);
    }

    return parseFloat(props.details.price_override_limit_percentage_for_item);
};

const checkOrderIsNotCreditOrLayaway = () => {
    return ordersForm.is_layaway || ordersForm.is_credit;
};

const attachToAllItems = (selectedPromoters) => {
    ordersForm.order_items.forEach((product) => {
        product.promoter_ids = selectedPromoters.map((promoter) => {
            return promoter.id;
        });
        product.promoter_name = selectedPromoters.map((promoter) => {
            return (promoter.name).trim();
        });
    });
};

const removeToAllItems = () => {
    ordersForm.order_items.forEach((product) => {
        product.promoter_ids = [];
        product.promoter_name = [];
    });

    hideAttachPromoterModal();
};

const showMemberModal = () => {
    state.memberModalShow = true;

    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
};

const getPromoterNames = (promoterNames) => {
    return Object.keys(promoterNames).length > 0 ? promoterNames.join(', ') : 'No Promoter Attached';
};

const addBatchDetailsModalShow = (selectedProduct) => {
    state.addBatchDetailsModalShow = true;

    state.selectedProduct = selectedProduct;
};

const addBatchDetailsModalHide = () => {
    state.addBatchDetailsModalShow = false;
    state.selectedProduct = null;
};

const updateBatchDetails = (BatchDetails) => {
    const selectedProduct = ordersForm.order_items.find((item) => item.id === BatchDetails.product_id);

    selectedProduct.batch_details = BatchDetails.batch_details;
};
</script>
