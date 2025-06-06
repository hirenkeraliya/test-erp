<template>
    <div>
        <JSimpleTable
            :columns="state.columns"
            :records="itemVariants"
        >
            <template #name="data">
                {{ data.item.name ? data.item.name : 'N/A' }}
            </template>

            <template #code="data">
                {{ data.item.code ? data.item.code : 'N/A' }}
            </template>

            <template #online_price="data">
                {{ displayAmountWithCurrencySymbol(data.item.online_price) }}
            </template>

            <template #attributes="data">
                {{ getAttributes(data.item.product_variant_values) }}
            </template>

            <template #description="data">
                {{ data.item.description ? data.item.description : 'N/A' }}
            </template>

            <template #action="data">
                <div class="flex justify-center items-center">
                    <Edit2
                        class="w-4 h-5 text-slate-400"
                        @click="editVariant(data.index, data.item)"
                    />

                    <X
                        class="w-6 h-6 text-slate-400"
                        @click="removeItemVariants(data.index, data.item.id)"
                    />
                </div>
            </template>
        </JSimpleTable>
    </div>
</template>

<script setup>
import { Edit2, X } from 'lucide-vue-next';
import { reactive, onMounted } from 'vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { confirmDialogBox, showSuccessNotification } from "@commonServices/notifier";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy";
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';

const emits = defineEmits([
    'edit:variant-data',
    'remove:variant-data'
]);

const state = reactive({
    columns: [
        {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        },
        {
            key: 'code',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        },
        {
            key: 'description',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        },
        {
            key: 'online_price',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        },
        {
            key: 'attributes',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        },
        {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        },
    ],
});

const props = defineProps({
    itemVariants: {
        type: Object,
        required: true,
    },
    itemForm: {
        type: Object,
        required: true,
    },
    variantAttributes: {
        type: [Array, Object],
        default: () => {},
    },
    isDraftProduct: {
        type: Boolean,
        default: false,
    },
    productId: {
        type: Number,
        default: 0
    },
});

const removeItemVariants = (key, itemVariantId) => {
    const message = 'Do you want to remove variant?';
    confirmDialogBox(message, () => {
        if (itemVariantId) {
            router.post(route('admin.master_products.remove_master_product_variant', [itemVariantId]), {}, {
                onSuccess: () => {
                    showSuccessNotification('The variant have been removed successfully.');
                }
            });
        }
        emits('remove:variant-data', key);
    });
};

const editVariant = (index, data) => {
    emits('edit:variant-data', {index, data});
};

const getAttributes = (selectedAttributes) => {
    const matchAttributes = [];
    for (const key in selectedAttributes) {
        const selectedAttribute = selectedAttributes[key];
        if (selectedAttribute.selected_value){
            const match = props.variantAttributes.find(variantAttribute => variantAttribute.id === selectedAttribute.id);
            if (match) {
                matchAttributes.push(match.name + ' : ' + selectedAttribute.selected_value);
            }
        }
    }
    return matchAttributes.join(', ');
};

onMounted(() => {
    const delayTime = 100;
    setTimeout(() => {
        props.itemVariants.forEach((variant, index) => {
            if (variant.id === props.productId) {
                editVariant(index, variant);
            }
        });
    }, delayTime);
});

</script>
