<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Similar Products
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody
            class="p-5 sm:p-10 text-center"
        >
            <div
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <JSimpleTable
                    :columns="state.columns"
                    :records="matchProducts"
                >
                    <template #categories="data">
                        <span
                            v-if="data.item.categories.length"
                            class="font-medium"
                        >
                            <span
                                v-for="(category, index) in data.item.categories"
                                :key="index"
                            >
                                {{ category.name }}

                                <span v-if="index != data.item.categories.length - 1">
                                    >
                                </span>
                            </span>
                        </span>

                        <span v-else>
                            N/A
                        </span>
                    </template>
                </JSimpleTable>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    matchProducts: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'categories',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'season',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'brand',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'color',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'size',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'style',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'department',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'sub_department',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'article_number',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'retail_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'type_id',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'unit_of_measure_id',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'ean',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'custom_sku',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'manufacturer_sku',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'franchise_price_1',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'franchise_price_2',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'franchise_price_3',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'wholesale_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'company_or_tender_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'branch_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'minimum_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'original_capital_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'capital_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'staff_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'purchase_cost',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'is_temporarily_unavailable',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'has_batch',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'is_non_inventory',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'is_non_selling_item',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'is_available_in_pos',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'is_available_in_ecommerce',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'online_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        },
        {
            key: 'status',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            isDisplay: true,
        },
    ],
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
