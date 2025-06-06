<template>
    <div
        id="print-receipt-container"
        class="hidden"
        frameborder="0"
    >
        <!DOCTYPE html>

        <html lang="en">
            <head>
                <meta
                    http-equiv="Content-Type"
                    content="text/html; charset=UTF-8"
                >
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                >
                <title>{{ getTitle() }}</title>

                <link
                    rel="stylesheet"
                    href="/css/receipt.css"
                >
            </head>

            <body>
                <SaleReceipt
                    v-if="sale"
                    :sale="sale"
                />
                <OrderReceipt
                    v-if="order"
                    :order="order"
                />
                <OrderReturnReceipt
                    v-if="orderReturn"
                    :order-return="orderReturn"
                />
                <SaleReturnReceipt
                    v-if="saleReturn"
                    :sale-return="saleReturn"
                />
                <CloseCounterReceipt
                    v-if="closeCounterDetails"
                    :close-counter="closeCounterDetails"
                />
            </body>
        </html>
    </div>
</template>

<script setup>
import SaleReceipt from '@commonComponents/SaleReceipt.vue';
import OrderReceipt from '@commonComponents/OrderReceipt.vue';
import OrderReturnReceipt from '@commonComponents/OrderReturnReceipt.vue';
import SaleReturnReceipt from '@commonComponents/SaleReturnReceipt.vue';
import CloseCounterReceipt from '@commonComponents/CloseCounterReceipt.vue';
import { watch } from 'vue';
import { printHtml } from '@commonServices/helper';

const props = defineProps({
    sale: {
        type: Object,
        default: null,
    },
    order: {
        type: Object,
        default: null,
    },
    orderReturn: {
        type: Object,
        default: null,
    },
    saleReturn: {
        type: Object,
        default: null,
    },
    closeCounterDetails: {
        type: Object,
        default: null,
    },
    printReceiptData: {
        type: Number,
        default: null,
    },
});

const getTitle = () => {
    if (props.closeCounterDetails) {
        return 'Print Counter Update Details';
    }

    if (props.saleReturn) {
        return 'Print Sale Return Receipt';
    }

    if (props.order) {
        return 'Print Order Receipt';
    }

    return 'Print Sale Receipt';
};

watch(() => props.printReceiptData,
    () => {
        printHtml();
    }
);
</script>
