<template>
    <InfoAlert
        v-if="counterDetails.denominations === undefined ||
            counterDetails.denominations.length === 0"
        color="primary"
        class="mt-4 ml-1"
    >
        <span class="flex">
            There are no denominations for this closed counter.
        </span>
    </InfoAlert>

    <table
        v-else
        class="table table-striped -mt-2"
    >
        <thead>
            <tr>
                <th>Denomination</th>
                <th class="text-center">
                    Quantity
                </th>
            </tr>
        </thead>

        <tbody>
            <tr
                v-for="(denomination, index) in counterDetails.denominations"
                :key="index"
            >
                <td>
                    {{ displayAmountWithCurrencySymbol(denomination.denomination) }}
                </td>

                <td class="text-center">
                    {{ denomination.denomination_quantity }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="block sm:flex justify-between">
        <h3
            v-if="counterDetails.mismatch_amount"
            class="font-medium text-sm mt-4 ml-1"
        >
            Expected Closing Balance: {{ displayAmountWithCurrencySymbol(counterDetails.closing_balance - counterDetails.mismatch_amount) }}
        </h3>

        <h3 class="font-medium text-sm mt-4 ml-1">
            Closing Balance: {{ displayAmountWithCurrencySymbol(counterDetails.closing_balance) }}
        </h3>
    </div>

    <div v-if="counterDetails.amount_mismatch_reason">
        <h3 class="font-medium text-sm mt-4 ml-1">
            Mismatch:
            <span class="text-danger font-bold">
                {{ displayAmountWithCurrencySymbol(counterDetails.mismatch_amount) }}
            </span>
        </h3>

        <h3 class="font-medium text-sm mt-4 ml-1">
            Reason:
            <span class="text-danger font-bold">
                {{ counterDetails.amount_mismatch_reason }}
            </span>
        </h3>
    </div>
</template>
<script setup>
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import InfoAlert from '@commonComponents/InfoAlert.vue';

defineProps({
    counterDetails: {
        type: Object,
        required: true,
    },

});

</script>
