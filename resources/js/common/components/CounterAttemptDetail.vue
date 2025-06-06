<template>
    <div
        v-if="counterDetails.counter_attempt_details !== null"
        class="row mb-5 flex justify-between"
    >
        <div class="text-2xl pt-4">
            Close Counter Attempts
        </div>

        <PrimaryButton
            type="button"
            text="PDF"
            class="btn-sm w-24 h-10 mt-3"
            @click="exportCloseCounterAttempts"
        />
    </div>

    <div
        v-if="counterDetails.counter_attempt_details !== null"
        class="grid grid-cols-7 gap-4"
    >
        <div class="col-span-1">
            <div
                v-for="(step, index) in counterDetails.counter_attempt_details"
                :key="index"
                class="intro-x flex items-center -mt-2 mb-6"
            >
                <button
                    type="button"
                    class="rounded btn leading-tight"
                    :class="[step.happened_at === state.currentHappenedAt ? 'btn-primary' : 'text-slate-500 bg-slate-50' ]"
                    @click="updateHappenedAt(step.happened_at)"
                >
                    {{ step.happened_at }}
                </button>
            </div>
        </div>

        <div class="col-span-6">
            <table
                v-if="state.counterUpdateDeclarationAttemptPayments"
                class="table table-striped -mt-2 border rounded"
            >
                <thead class="bg-gray-300">
                    <tr>
                        <th>#</th>
                        <th class="leading-tight">
                            Payment Type
                        </th>
                        <th class="leading-tight">
                            Declared
                        </th>
                        <th class="leading-tight">
                            Actual
                        </th>
                        <th class="leading-tight text-center">
                            Denominations
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(counterAttemptDetail, index) in state.counterUpdateDeclarationAttemptPayments"
                        :key="index"
                        class="intro-x"
                    >
                        <td>
                            {{ index + 1 }}
                        </td>

                        <td>
                            {{ counterAttemptDetail.payment_type }}
                        </td>
                        <td>
                            {{ displayAmountWithCurrencySymbol(counterAttemptDetail.declared_amount) }}
                        </td>
                        <td>
                            {{ displayAmountWithCurrencySymbol(counterAttemptDetail.calculated_amount) }}
                        </td>
                        <td class="text-center">
                            <div
                                v-if="counterAttemptDetail.denominations !== null"
                            >
                                <table class="border rounded">
                                    <thead class="bg-gray-300">
                                        <tr>
                                            <th class="text-center">
                                                Denomination
                                            </th>
                                            <th class="text-center">
                                                Quantity
                                            </th>
                                            <th class="text-center">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(denomination, indexDenomination) in counterAttemptDetail.denominations"
                                            :key="indexDenomination"
                                        >
                                            <td class="text-center">
                                                {{ displayAmountWithCurrencySymbol(denomination.denomination) }}
                                            </td>

                                            <td class="text-center">
                                                {{ denomination.quantity }}
                                            </td>

                                            <td class="text-center">
                                                {{ displayAmountWithCurrencySymbol(denomination.quantity * denomination.denomination) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-else
                            >
                                N/A
                            </div>
                        </td>
                    </tr>

                    <tr
                        v-if="state.counterUpdateDeclarationAttemptPayments.length === 0"
                        class="intro-x"
                    >
                        <td
                            colspan="5"
                            class="w-40 text-center"
                        >
                            There are no records to show.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div v-else>
        <h3 class="text-center">
            No Records Found.
        </h3>
    </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { displayAmountWithCurrencySymbol, printReport } from '@commonServices/helper';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { route } from 'ziggy';

const props = defineProps({
    counterDetails: {
        type: Object,
        required: true,
    },
    counterUpdateId: {
        type: Number,
        default: null,
    },
    printCounterUpdateAttemptUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    counterUpdateDeclarationAttemptPayments: [],
    currentHappenedAt: null,
});

const updateHappenedAt = (happenedAt) => {
    state.currentHappenedAt = happenedAt;
    state.counterUpdateDeclarationAttemptPayments = [];
    props.counterDetails.counter_attempt_details.forEach(counterAttempt => {
        if (counterAttempt.happened_at === state.currentHappenedAt) {
            state.counterUpdateDeclarationAttemptPayments = counterAttempt.counter_update_declaration_attempt_payments;
        }
    });
};

const exportCloseCounterAttempts = () => {
    printReport(route(props.printCounterUpdateAttemptUrl, props.counterUpdateId));
};

onMounted(() => {
    if (props.counterDetails.counter_attempt_details) {
        updateHappenedAt(props.counterDetails.counter_attempt_details[0].happened_at);
    }
});
</script>
