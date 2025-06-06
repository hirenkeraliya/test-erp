<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Partially Receive Delivery Notes Item Details
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
            <JSimpleTable
                v-if="partialReceiveDetails"
                :columns="columnsForPartiallyReceiveItemDetails"
                :records="partialReceiveDetails"
            >
                <template 
                    v-if="pageProps.product_variant" 
                    #product_variant_values="data"
                >
                    <span v-if="pageProps.product_variant">
                        <p 
                            v-for="(product_variant, index) in data.item.product_variant_values" 
                            :key="index" 
                            class="flex"
                        >
                            {{ product_variant.attribute.name }} : {{ product_variant.value }}
                        </p>
                    </span>
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    partialReceiveDetails: {
        type: Object,
        required: true,
    },
    columnsForPartiallyReceiveItemDetails: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
