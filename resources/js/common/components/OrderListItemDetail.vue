<template>
    <JSimpleTable
        :columns="state.orderItemColumns"
        :records="orderItems"
    >
        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>
    </JSimpleTable>
</template>
<script setup>
import { reactive, computed } from 'vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

defineProps({
    orderItems: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    orderItemColumns: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'article_number',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]
            : [
                {
                    key: 'color',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
                {
                    key: 'size',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]),
    ],
});

</script>
