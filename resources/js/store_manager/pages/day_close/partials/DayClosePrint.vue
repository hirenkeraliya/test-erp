<template>
    <div
        v-if="dayClose"
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
                <title>Day Close Counter</title>

                <link
                    rel="stylesheet"
                    href="/css/receipt.css"
                >
            </head>

            <body>
                <div class="receipt-container">
                    <div class="text-center">
                        <div class="heading-part">
                            <h2>Day Close (Z Report)</h2>
                        </div>
                    </div>

                    <div class="dashed-underline">
                        <div class="second-part">
                            Opened at: {{ dayClose.opened_at }} <br>
                            Closed at: {{ dayClose.closed_at }} <br>
                            Closed by: {{ dayClose.store_manager.employee.first_name }} {{ dayClose.store_manager.employee.last_name }}
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="heading-part">
                            <h3>Sales Details</h3>
                        </div>
                    </div>

                    <div>
                        <table id="table">
                            <tbody>
                                <tr class="tabletitle">
                                    <td colspan="3">
                                        <h5>Type of Sales</h5>
                                    </td>
                                    <td class="qty">
                                        <h5>Amount</h5>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Sales Collection Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.sales_collection_amount }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Sale Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_sales }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Sales:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_sales_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Return Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_sale_returns }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Item Wise Discounts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_item_wise_discount_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Cart Wide Discounts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_cart_wide_discount_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Layaway Sale Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_layaway_sales }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Layaway Sales Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_layaway_sales_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Cancel Layaway Sale Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_cancel_layaway_sales }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Cancel Layaway Sales Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_cancel_layaway_sales_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Credit Sale Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_credit_sales }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Credit Sales Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_credit_sales_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Voided Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_voided_sales }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Voided Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_voided_sales_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Sales Round Off Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_sales_round_off) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Returns Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_sale_returns_amount) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Returns Round Off Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_sale_returns_round_off) }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Used Booking Payments:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_used_booking_payments }}
                                        </div>
                                    </td>
                                </tr>

                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        New Booking Payments:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_new_booking_payments }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center">
                        <div class="heading-part">
                            <h3>Orders Details</h3>
                        </div>
                    </div>

                    <div>
                        <table id="table">
                            <tbody>
                                <tr class="tabletitle">
                                    <td colspan="3">
                                        <h5>Type of Orders</h5>
                                    </td>
                                    <td class="qty">
                                        <h5>Amount</h5>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Orders Collection Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.orders_collection_amount }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Orders:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_orders }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Orders Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_orders_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Order Returns:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_order_returns }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Item Wise Discounts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_order_item_wise_discount_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Cart Wide Discount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_order_cart_wide_discount_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Layaway Order Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_layaway_orders }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Layaway Orders Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_layaway_orders_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Credit Order Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_credit_orders }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Credit Orders Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_credit_orders_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Cancelled Order Receipts:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ dayClose.total_cancelled_orders }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Cancelled Orders Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_cancelled_orders_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Orders Round Off Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_orders_round_off) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Returns Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_order_returns_amount) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Order Returns Round Off Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_order_returns_round_off) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Complimentary Item Discount Used:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_order_complimentary_item_discount_used) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="dashed-underline">
                                    <td colspan="3">
                                        Complimentary Item Discount Amount:
                                    </td>
                                    <td>
                                        <div class="text-right">
                                            {{ displayAmountWithCurrencySymbol(dayClose.total_order_complimentary_item_discount_amount) }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="dashed-underline mt-5">
                        <div class="second-part">
                            {{ dayClose.location_receipt_footer }}

                            <br>
                            Disclaimer: <br>
                            {{ dayClose.location_disclaimer }}
                        </div>
                    </div>
                </div>
            </body>
        </html>
    </div>
</template>

<script setup>
import { displayAmountWithCurrencySymbol, printHtml } from '@commonServices/helper';
import { nextTick } from 'vue';

defineProps({
    dayClose: {
        type: Object,
        required: true,
    },
});

nextTick(() => {
    printHtml();
});
</script>
