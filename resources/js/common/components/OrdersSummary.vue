<template>
    <table class="table table-striped -mt-2">
        <tbody>
            <tr>
                <td>
                    Order Collection Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.orders_collection_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Orders
                </td>

                <td class="text-right">
                    {{ counterDetails.total_orders }}
                </td>
            </tr>

            <tr>
                <td>
                    Order Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_orders_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Layaway Orders
                </td>

                <td class="text-right">
                    {{ counterDetails.total_layaway_orders }}
                </td>
            </tr>

            <tr>
                <td>
                    Layaway Orders Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_layaway_orders_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Orders
                </td>

                <td class="text-right">
                    {{ counterDetails.total_credit_orders }}
                </td>
            </tr>

            <tr>
                <td>
                    Credit Orders Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_credit_orders_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cancel Orders
                </td>

                <td class="text-right">
                    {{ counterDetails.total_cancelled_orders }}
                </td>
            </tr>

            <tr>
                <td>
                    Cancel Orders Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_cancelled_orders_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Item Wise Discount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_order_item_wise_discount_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Cart Wide Discount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_order_cart_wide_discount_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Tax Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_order_tax_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Orders Round Off
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_orders_round_off) }}
                </td>
            </tr>

            <tr>
                <td>
                    Order Returns
                </td>

                <td class="text-right">
                    {{ counterDetails.total_order_returns }}
                </td>
            </tr>

            <tr>
                <td>
                    Order Returns Amount
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_order_returns_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Order Returns Round Off
                </td>

                <td class="text-right">
                    {{ displayAmountWithCurrencySymbol(counterDetails.total_order_returns_round_off) }}
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
