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
                    Credit Note Refunds (Cash)
                </td>

                <td class="text-right">
                    -{{ displayAmountWithCurrencySymbol(counterDetails.total_cash_amount_in_credit_note_refunded) }}
                </td>
            </tr>

            <tr>
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
});

</script>
