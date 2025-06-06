<template>
    <table class="table table-striped -mt-2">
        <tbody>
            <tr>
                <td>
                    Collection
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.sales_collection_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Regular Sales
                </td>

                <td class="text-right">
                    {{ counterDetails.total_sales }}
                </td>
            </tr>

            <tr>
                <td>
                    Regular Sale Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_sales_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Layaway
                </td>

                <td class="text-right">
                    {{ counterDetails.total_layaway_sales }}
                </td>
            </tr>

            <tr>
                <td>
                    Layaway Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_layaway_sales_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cancel Layaway
                </td>

                <td class="text-right">
                    {{ counterDetails.total_cancel_layaway_sales }}
                </td>
            </tr>

            <tr>
                <td>
                    Cancel Layaway Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cancel_layaway_sales_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Sales
                </td>

                <td class="text-right">
                    {{ counterDetails.total_credit_sales }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_credit_sales_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Void
                </td>

                <td class="text-right">
                    {{ counterDetails.total_voided_sales }}
                </td>
            </tr>

            <tr>
                <td>
                    Void Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_voided_sales_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Item Wise Discount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_item_wise_discount_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cart Wide Discount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cart_wide_discount_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Tax
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_tax_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Round Off
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_sales_round_off) }}
                </td>
            </tr>

            <tr>
                <td>
                    Returns
                </td>

                <td class="text-right">
                    {{ counterDetails.total_sale_returns }}
                </td>
            </tr>

            <tr>
                <td>
                    Returns Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_sale_returns_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Notes Used
                </td>

                <td class="text-right">
                    {{ counterDetails.total_credit_notes_used }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Notes Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_credit_notes_used_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Notes Refunded
                </td>

                <td class="text-right">
                    {{ counterDetails.total_credit_notes_refunded }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Notes Refunded Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_credit_notes_refunded_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Sale Returns Round Off
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_sale_returns_round_off) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cashbacks
                </td>

                <td class="text-right">
                    {{ counterDetails.total_cashback }}
                </td>
            </tr>

            <tr>
                <td>
                    Cashback Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cashback_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Vouchers Generated
                </td>

                <td class="text-right">
                    {{ counterDetails.total_vouchers_generated }}
                </td>
            </tr>

            <tr>
                <td>
                    Vouchers Used
                </td>

                <td class="text-right">
                    {{ counterDetails.total_vouchers_used }}
                </td>
            </tr>

            <tr>
                <td>
                    New Booking Payments
                </td>

                <td class="text-right">
                    {{ counterDetails.total_new_booking_payments }}
                </td>
            </tr>

            <tr>
                <td>
                    Booking Payment Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_booking_payment_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Used Booking Payments
                </td>

                <td class="text-right">
                    {{ counterDetails.total_used_booking_payments }}
                </td>
            </tr>

            <tr>
                <td>
                    Booking Payment Used Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_booking_payment_used_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Booking Payment Refunded Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_booking_payment_refunded_amount) }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';

defineProps({
    counterDetails: {
        type: Object,
        required: true,
    },
});

</script>
