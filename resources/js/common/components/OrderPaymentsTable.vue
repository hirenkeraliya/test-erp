<template>
    <table class="table table-striped -mt-2">
        <thead>
            <tr>
                <th>Payment Type</th>
                <th class="text-center">
                    No. of Transactions
                </th>
                <th class="text-right">
                    Amount
                </th>
            </tr>
        </thead>

        <tbody>
            <tr
                v-for="(payment) in counterDetails.payments"
                :key="'payment-' + payment.id"
                class="intro-x"
            >
                <td>
                    {{ payment.payment_type }}
                </td>

                <td class="text-center">
                    {{ payment.total_order_transactions }}
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(payment.total_order_amount) }}
                </td>
            </tr>

            <tr
                v-if="counterDetails.payments && counterDetails.payments.length === 0"
                class="intro-x"
            >
                <td
                    colspan="3"
                    class="w-40 text-center"
                >
                    There are no records to show.
                </td>
            </tr>
        </tbody>

        <tfoot v-if="displayTotal">
            <tr>
                <th
                    colspan="8"
                    class="text-right"
                >
                    Total:
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_order_payments) }}
                </th>
            </tr>
        </tfoot>
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
