<template>
    <div
        v-if="Object.keys(closeCounter).length"
        class="receipt-container"
    >
        <div class="text-center">
            <div style="font-size: smaller;">
                {{ closeCounter.location.company.name }}
            </div>
            <div class="heading-part dashed-underline">
                {{ closeCounter.location.name }} <br>
                {{ closeCounter.location.address_line_1 }} <br>
                {{ closeCounter.location.address_line_2 }}
                --
            </div>
        </div>

        <div class="text-center second-part dashed-underline">
            {{ closeCounter.location.city }} - {{ closeCounter.location.area_code }}<br>
            TEL:{{ closeCounter.location.phone ?? 'N/A' }}
        </div>

        <div
            class="text-center second-part dashed-underline"
            style="font-size: 14px;"
        >
            DATE: {{ closeCounter.date }}
        </div>

        <div class="text-center dashed-underline">
            CASHIER DECLARATION
        </div>

        <div class="flex-and-between font-weight-light">
            <div>Counter </div>
            <div>{{ closeCounter.counter }} </div>
        </div>
        <div class="flex-and-between dashed-underline font-weight-light">
            <div>Cashier </div>
            <div>{{ closeCounter.cashier }} </div>
        </div>

        <div
            v-for="(counterAttemptDetail, index) in closeCounter.counter_attempt_details"
            :key="index"
        >
            <table id="table">
                <thead>
                    <tr>
                        <td>
                            <p class="text-left font-weight-bold">
                                {{ index + 1 }}. {{ counterAttemptDetail.payment_type }}
                            </p>
                        </td>
                    </tr>
                </thead>

                <tbody style="width: 100%;">
                    <tr>
                        <td :colspan="2">
                            <div
                                v-if="counterAttemptDetail.denominations !== null"
                            >
                                <div
                                    v-for="(denomination, indexDenomination) in counterAttemptDetail.denominations"
                                    :key="indexDenomination"
                                    class="flex-and-between"
                                >
                                    <div class="first-denomination">
                                        >> {{ displayAmountWithCurrencySymbol(denomination.denomination) }}
                                    </div>
                                    <div class="second-denomination text-center">
                                        &#x2715; {{ denomination.quantity }}
                                    </div>
                                    <div class="third-denomination">
                                        {{ currencyFormat(denomination.quantity * denomination.denomination) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex-and-between">
                                <div class="ml-5">
                                    >> Total
                                </div>
                                <div>
                                    {{ displayAmountWithCurrencySymbol(counterAttemptDetail.calculated_amount) }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div
                v-if="closeCounter.counter_attempt_details.length === 0"
                class="intro-x"
            >
                <span
                    class="w-40 text-center"
                >
                    There are no records to show.
                </span>
            </div>
        </div>
        <p class="dashed-underline" />
        <div class="flex-and-between dashed-underline">
            <span>Grand Total </span>
            <span>{{ displayAmountWithCurrencySymbol(closeCounter.grand_total) }}</span>
        </div>

        <div class="second-part dashed-underline" />

        <div class="text-center">
            <div class="bottom-part dashed-underline">
                {{ closeCounter.location.receipt_footer }}--
            </div>
            <div class="dashed-underline font-weight-light">
                <p class="text-center dashed-underline">
                    Printed on : {{ closeCounter.date }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { displayAmountWithCurrencySymbol, currencyFormat } from '@commonServices/helper';

defineProps({
    closeCounter: {
        type: Object,
        required: true,
    },
});
</script>
