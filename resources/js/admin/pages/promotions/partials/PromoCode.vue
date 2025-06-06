<template>
    <div>
        <div class="font-medium text-lg py-5 border-b mb-4">
            Promo Code
        </div>
        <div class="grid grid-cols-12 gap-0 sm:gap-6">
            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                <JSwitch
                    input-label="Is Automatic?"
                    :is-checked="promotionForm.is_automatic"
                    @update:is-checked="updateColumnOnlyForIsAutomatic('is_automatic', $event)"
                />
            </div>

            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                <InfoAlert
                    v-if="!promotionForm.is_automatic"
                    color="primary"
                >
                    <span class="flex">
                        Promo codes cannot contain any of the following characters: 'space(a b)', '>', '&lt;', '|', '^', '`(grave accent)', '/' or '\' characters.
                    </span>
                </InfoAlert>
            </div>

            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                <FormSelectBox
                    v-if="!promotionForm.is_automatic"
                    :selected-record="promotionForm.usage_type"
                    :records="promotionUsageTypes"
                    input-label="Usage"
                    @update:selected-record="updateColumnDetails('usage_type', $event)"
                />
            </div>

            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-9">
                <div
                    v-if="!promotionForm.is_automatic"
                    class="block lg:flex flex-col sm:flex-row -mx-3"
                >
                    <div class="w-full lg:w-1/2 px-3">
                        <FormInput
                            v-model:input-value="state.totalPromoCodes"
                            type="number"
                            input-label="How Many Promo Codes?"
                            min="1"
                            @update:input-value="updateAddNewPromoCodes"
                        />
                    </div>
                    <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
                        <PrimaryButton
                            type="button"
                            text="Generate Promo Codes"
                            class="w-auto sm:w-30 md:w-1/1"
                            @click="generatePromoCodes()"
                        />

                        <PrimaryButton
                            type="button"
                            text="Upload Promo Codes"
                            class="w-auto sm:w-30 md:w-1/1 ml-2"
                            @click="uploadPromoCodes()"
                        />
                    </div>
                </div>
            </div>

            <div
                v-for="(promoCode, index) in promotionForm.promo_codes"
                :key="index"
                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
            >
                <FormInput
                    v-if="!promotionForm.is_automatic"
                    :placeholder="'Enter Promo Code'"
                    input-label="Promo Code"
                    :input-value="promoCode"
                    input-name="promo_codes"
                    :validation-field-name="`promo_codes.${index}`"
                    @update:input-value="updatePromoCodeDetails($event, index)"
                />
            </div>
        </div>
    </div>

    <ImportPromoCode
        v-if="state.displayPromoCodeModal"
        :modal-show="state.displayPromoCodeModal"
        :promotion-form="promotionForm"
        :promotion-id="promotionId"
        @update:valid-promo-codes-via-upload="updateValidPromoCodes(columnName, $event)"
        @close-modal="closeModal"
    />
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { reactive } from 'vue';
import axios from 'axios';
import { route } from 'ziggy';
import ImportPromoCode from '@adminPages/promotions/partials/ImportPromoCode.vue';
import { showErrorNotification } from '@commonServices/notifier';

const props = defineProps({
    promotionId: {
        type: Number,
        default: null,
    },
    promotionForm: {
        type: Object,
        required: true,
    },
    promotionUsageTypes: {
        type: Array,
        required: true,
    },
    promotionSingleUsage: {
        type: Number,
        required: true
    }
});

const state = reactive({
    totalPromoCodes: props.promotionForm.promo_codes.length ?? 1,
    displayPromoCodeModal: false
});

const emits = defineEmits([
    'update:column-details',
    'add-new-promo-code',
    'clear-promo-codes',
    'set-promo-codes',
    'update-promo-code-details',
    'update:valid-promo-codes-via-upload'
]);

const updateColumnDetails = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const updateValidPromoCodes = (columnName, data) => {
    emits('update:valid-promo-codes-via-upload', data);
};

const updateColumnOnlyForIsAutomatic = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
    state.totalPromoCodes = 1;
    emits('clear-promo-codes', {});
};

const updateAddNewPromoCodes = () => {
    emits('clear-promo-codes');

    for (let index = 1; index < state.totalPromoCodes; index++) {
        emits('add-new-promo-code');
    }
};

const updatePromoCodeDetails = (promoCode, promoCodeKey) => {
    if (promoCode.toString().includes('/') ||
        promoCode.toString().includes('\\') ||
        promoCode.toString().includes('>') ||
        promoCode.toString().includes('<') ||
        promoCode.toString().includes('|') ||
        promoCode.toString().includes('^') ||
        promoCode.toString().includes('`') ||
        promoCode.toString().includes(' ')
    ) {
        showErrorNotification('The promo code contains an invalid character.');
        return;
    }

    emits('update-promo-code-details', {
        key: promoCodeKey,
        value: promoCode,
    });
};

const generatePromoCodes = () => {
    axios.get(route('admin.promotions.generate_promo_codes', state.totalPromoCodes))
        .then((response) => {
            emits('set-promo-codes', response.data.promo_codes);
        });
};

const uploadPromoCodes = () => {
    state.displayPromoCodeModal = true;
};

const closeModal = () => {
    state.displayPromoCodeModal = false;
};

</script>
