<template>
    <table class="table table-striped -mt-2">
        <tbody>
            <tr>
                <td>
                    Opening Balance
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.opening_balance) }}
                </td>
            </tr>

            <tr>
                <td>
                    Sales (Cash)
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cash_amount_in_sales) }}
                </td>
            </tr>

            <tr>
                <td>
                    Booking Payments (Cash)
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cash_amount_in_booking_payment) }}
                </td>
            </tr>

            <tr>
                <td>
                    Booking Payment Refunds (Cash)
                </td>

                <td class="text-right">
                    -{{ displayAmountWithCurrencySymbol(counterDetails.total_cash_amount_in_booking_payment_refunded) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cash Ins
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cash_ins_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cash Outs
                </td>

                <td class="text-right">
                    -{{ displayAmountWithCurrencySymbol(counterDetails.total_cash_outs_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Note Refunds (Cash)
                </td>

                <td class="text-right">
                    -{{ displayAmountWithCurrencySymbol(counterDetails.total_cash_amount_in_credit_note_refunded) }}
                </td>
            </tr>

            <tr v-if="displayTotal">
                <th
                    colspan="8"
                    class="text-right"
                >
                    Total:
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cash_transaction) }}
                </th>
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
    displayTotal: {
        type: Boolean,
        default: false,
    },
});

</script>
