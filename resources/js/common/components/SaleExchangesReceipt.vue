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
                <title>Print Exchanges Receipt</title>

                <link
                    rel="stylesheet"
                    href="/css/receipt.css"
                >
            </head>

            <body>
                <div
                    v-if="Object.keys(sale).length"
                    class="receipt-container"
                >
                    <div class="text-center">
                        <img
                            v-if="sale.company.logo"
                            :src="sale.company.logo"
                            class="img-fluid rounded"
                            alt="logo"
                            style="width: 115px;"
                        >

                        <div class="heading-part dashed-underline">
                            {{ sale.location.name }} <br>
                            {{ sale.location.address_line_1 }} <br>
                            {{ sale.location.address_line_2 }} <br>
                            {{ sale.location.city }} - {{ sale.location.area_code }} <br>
                            {{ sale.company.name }} <br>
                            CO. REG. ({{ sale.company.social_security_number }}) <br>
                            Reprint
                        </div>
                    </div>

                    <div class="second-part dashed-underline">
                        INVOICE NO: {{ sale.id }} <br>
                        DATE: {{ sale.sale_date }} {{ sale.sale_time }} <br>
                        COUNTER: {{ sale.counter }} <br>
                        MEMBER ID: {{ sale.member_id ? sale.member_id : 'N/A' }} <br>
                        MEMBER NAME: {{ sale.member ? sale.member : 'N/A' }} <br>
                        SALESPERSON NAME: {{ sale.cashier }} <br>
                    </div>

                    <div>
                        <table id="table">
                            <tbody>
                                <tr class="tabletitle">
                                    <td class="item">
                                        ITEM
                                    </td>
                                    <td class="qty">
                                        QTY
                                    </td>
                                    <td class="unit-price">
                                        UNIT PRICE
                                    </td>
                                    <td class="amount">
                                        AMOUNT
                                    </td>
                                </tr>

                                <tr
                                    v-for="(saleItem, index) in sale.sale_items"
                                    :key="saleItem.id + index"
                                >
                                    <td class="item">
                                        {{ saleItem.product }}
                                    </td>
                                    <td class="qty">
                                        {{ saleItem.quantity }}
                                    </td>
                                    <td class="unit-price">
                                        {{ saleItem.original_price_per_unit }}
                                    </td>
                                    <td class="amount">
                                        {{ numberFormat(saleItem.quantity * saleItem.original_price_per_unit) }}
                                    </td>
                                </tr>

                                <tr
                                    v-for="(returnProduct, index) in sale.sale_return_items"
                                    :key="returnProduct.id + index"
                                >
                                    <td class="item">
                                        {{ returnProduct.product }}
                                        ({{ returnProduct.sale_return_reason }})
                                    </td>

                                    <td class="qty">
                                        -{{ Math.abs(returnProduct.quantity) }}
                                    </td>

                                    <td class="unit-price">
                                        -{{ numberFormat(returnProduct.unit_price) }}
                                    </td>

                                    <td class="amount">
                                        -{{ numberFormat(returnProduct.quantity * returnProduct.unit_price) }}
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        GROSS TOTAL:
                                    </td>
                                    <td>{{ sale.gross_sales }}</td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Discount:
                                    </td>
                                    <td>{{ numberFormat(sale.total_discount_amount) }}</td>
                                </tr>

                                <tr
                                    v-if="sale.total_tax_amount"
                                    class="dashed-underline"
                                >
                                    <td colspan="3">
                                        Tax:
                                    </td>
                                    <td>{{ numberFormat(sale.total_tax_amount) }}</td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Net Total:
                                    </td>
                                    <td>{{ numberFormat(sale.net_total) }}</td>
                                </tr>

                                <tr
                                    v-for="(payment, index) in sale.payments"
                                    :key="index"
                                    :class="(index == sale.payments.length -1) ? 'dashed-underline' : ''"
                                >
                                    <td colspan="3">
                                        {{ payment.payment_type }}
                                        ({{ currencySymbol }})
                                    </td>

                                    <td>{{ payment.amount }}</td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        ROUND-OFF:
                                    </td>
                                    <td>{{ numberFormat(sale.round_off - sale.sale_return.round_off) }}</td>
                                </tr>

                                <tr
                                    v-if="sale.cashback_amount"
                                    class="dashed-underline"
                                >
                                    <td colspan="3">
                                        Cashback:
                                    </td>
                                    <td>{{ numberFormat(sale.cashback_amount) }}</td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        AMOUNT:
                                    </td>
                                    <td>{{ numberFormat(sale.total_amount_paid) }}</td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        NO. OF UNITS:
                                    </td>
                                    <td>{{ sale.units_sold }}</td>
                                </tr>

                                <tr v-if="sale.units_returned">
                                    <td colspan="2">
                                        RETURN UNITS:
                                    </td>
                                    <td>{{ sale.units_returned }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center">
                        <div class="bottom-part">
                            {{ sale.location.receipt_footer }}

                            <br>
                            Disclaimer: <br>
                            {{ sale.location.disclaimer }}
                        </div>
                    </div>
                </div>
            </body>
        </html>
    </div>
</template>

<script setup>
import { numberFormat, printHtml } from '@commonServices/helper';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    sale: {
        type: Object,
        required: true,
    },
    printReceiptData: {
        type: Number,
        default: null,
    },
});

watch(() => props.printReceiptData,
    () => {
        printHtml();
    }
);
</script>
