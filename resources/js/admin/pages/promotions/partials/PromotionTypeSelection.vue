<template>
    <FormInput
        v-if="staticDetails.type_percentage === promotionForm.discount_type_id"
        class="intro-y"
        type="number"
        input-name="percentage"
        input-label="Percentage"
        input-group-suffix="%"
        :input-value="promotionForm.percentage"
        @update:input-value="updateColumnDetails('percentage', $event)"
    />

    <FormInput
        v-else
        type="number"
        class="intro-y"
        input-name="flat_amount"
        input-label="Flat"
        :input-group-prefix="currencySymbol"
        :input-value="promotionForm.flat_amount"
        :title="'A flat discount will be applicable on each of the product quantities For Example Product retail price is '+currencySymbol+'10, quantity is 5, And flat '+currencySymbol+'5 then the flat discount will be '+currencySymbol+'25'"
        @update:input-value="updateColumnDetails('flat_amount', $event)"
    />

    <InfoAlert
        color="primary"
        class="mt-5 mb-0"
    >
        <span
            v-if="staticDetails.type_percentage === promotionForm.discount_type_id"
            class="flex"
        >
            Get “{{ promotionForm.percentage ?? 'X' }}%” discount on any of the selected {{ getModuleName() }}.
        </span>
        <span
            v-else
            class="flex"
        >
            Get an “{{ currencySymbol }}{{ promotionForm.flat_amount ?? 'XX' }}” discount on any of the selected {{ getModuleName() }}.
        </span>
    </InfoAlert>
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    promotionForm: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:column-details',
]);

const updateColumnDetails = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};
const getModuleName = () => {
    if (props.staticDetails.limited_to_products === props.promotionForm.item_wise_promotion_type_id) {
        return 'products';
    }

    if (props.staticDetails.limited_to_categories === props.promotionForm.item_wise_promotion_type_id) {
        return 'categories';
    }
    if (props.staticDetails.limited_to_tags === props.promotionForm.item_wise_promotion_type_id) {
        return 'tags';
    }
    if (props.staticDetails.limited_to_product_collection === props.promotionForm.item_wise_promotion_type_id) {
        return 'product collection';
    }

    return 'brands';
};
</script>
