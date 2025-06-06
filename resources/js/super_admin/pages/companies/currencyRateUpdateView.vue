<template>
    <PageTitle title="Companies" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Currency Rates
        </h2>
        <div>
            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                <JSwitch
                    :is-checked="currencyRateAutoUpdate"
                    input-label="Currency Rate Auto Update"
                    validation-field-name="currency_rate_auto_update"
                    :required="true"
                    title="If you enable the currency rate auto update, it will auto update once a day."
                    @update:is-checked="toggleCurrencyRateUpdate"
                />
            </div>
        </div>
    </div>
    <div class="mt-5">
        <h2 class="text-md font-medium mr-auto">
            Base Currency : {{ baseCurrency.name }} ({{ baseCurrency.symbol }}) ({{ baseCurrency.code }})
        </h2>
    </div>
    <div class="mt-5">
        <form
            @submit.prevent="updateRates()"
        >
            <table class="min-w-full bg-white border border-gray-200 rounded-md shadow-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">
                            Name
                        </th>
                        <th class="px-4 py-2 text-left">
                            Symbol
                        </th>
                        <th class="px-4 py-2 text-left">
                            Code
                        </th>
                        <th class="px-4 py-2 text-left">
                            Rate
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(currencyRate, index) in currencyRateForm.currency_data"
                        :key="index"
                        class="border-t"
                    >
                        <td class="px-4 py-2">
                            {{ currencyRate.name }}
                        </td>
                        <td class="px-4 py-2">
                            {{ currencyRate.symbol }}
                        </td>
                        <td class="px-4 py-2">
                            {{ currencyRate.code }}
                        </td>
                        <td class="px-4 py-2">
                            <FormInput
                                v-model:input-value="currencyRate.rate"
                                :validation-field-name="'currency_data.' + index + '.rate'"
                                :readonly="currencyRateAutoUpdate"
                                input-label=""
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="mt-5">
                <Link :href="route('super_admin.companies.index')">
                    <SecondaryButton
                        text="Cancel"
                        class="w-24 mr-1"
                    />
                </Link>

                <PrimaryButton
                    v-if="!currencyRateAutoUpdate"
                    type="submit"
                    text="Update"
                    class="w-24"
                />
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import axios from 'axios';
import { showSuccessNotification } from '@commonServices/notifier';

const props = defineProps({
    'currencyRates': {
        type: Object,
        required: true,
    },
    'companyId' : {
        type: Number,
        required: true,
    },
    'baseCurrency': {
        type: Object,
        required: true,
    },
    'currencyRateAutoUpdate': {
        type: Boolean,
        required: true,
    },
});

const currencyRateForm = useForm({
    currency_data: [],
    company_id: props.companyId,
});

const toggleCurrencyRateUpdate = () => {
    axios.post(route('super_admin.companies.currencyUpdateToggle', props.companyId))
        .then(() => {
            showSuccessNotification('Currency Rate Updated.');
            window.location.reload();
        });
};

const updateRates = () => {
    router.post(route('super_admin.companies.update_currency_rate'), currencyRateForm);
};

onMounted(() => {
    Object.assign(currencyRateForm.currency_data, JSON.parse(JSON.stringify(props.currencyRates.data)));
});
</script>
