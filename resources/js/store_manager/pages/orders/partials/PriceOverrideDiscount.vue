<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ discountType }} {{ priceOverrideLimit }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="px-5 sm:p-10">
            <InfoAlert
                v-if="priceOverrideType === 0 || priceOverrideType === priceOverrideTypes.percentage"
                color="primary"
                class="mb-3"
            >
                {{ discountType }} Is Available Upto {{
                    displayAmountWithPercentageSymbol(priceOverrideLimit) }}
            </InfoAlert>

            <InfoAlert
                v-if="priceOverrideType === priceOverrideTypes.flat"
                color="primary"
                class="mb-3"
            >
                {{ discountType }} Is Available Upto {{
                    displayAmountWithCurrencySymbol(priceOverrideLimit) }}
            </InfoAlert>

            <div>
                <FormInputNumber
                    v-model:input-value="state.priceOverridePercentage"
                    :is-button-required="false"
                    input-label="Apply Custom Discount"
                    input-class="form-control"
                    :maximum-increment-value="parseFloat(priceOverrideLimit)"
                />
            </div>
            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="updatePriceOverrideDiscount()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithPercentageSymbol, displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';
import FormInputNumber from '@commonComponents/FormInputNumber.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    discountType: {
        type: String,
        default: 'Price Override',
    },
    priceOverrideLimit: {
        type: Number,
        required: true,
    },
    priceOverrideTypes: {
        type: Object,
        required: true,
    },
    priceOverrideType: {
        type: Number,
        default: 0,
    },
});

const state = reactive({
    priceOverridePercentage: 0,
});

const emits = defineEmits(['close-price-override-modal', 'update-price-override-discount']);

const closeModal = () => {
    state.priceOverridePercentage = 0;
    emits('close-price-override-modal');
};

const updatePriceOverrideDiscount = () => {
    emits('update-price-override-discount', state.priceOverridePercentage);
    closeModal();
};
</script>
