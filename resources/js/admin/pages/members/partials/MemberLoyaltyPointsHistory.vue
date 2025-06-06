<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <JSimpleTable
                :columns="columnsForLoyaltyPointHistory"
                :records="loyaltyPointsHistory"
            >
                <template #extra-header-data>
                    <JBadge
                        v-if="memberLoyaltyPoint"
                        :label="'Available Point:' + memberLoyaltyPoint"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import JBadge from '@commonComponents/JBadge.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    loyaltyPointsHistory: {
        type: Array,
        required: true,
    },
    memberLoyaltyPoint: {
        type: Number,
        required: true,
    },
    title: {
        type: String,
        default: 'Loyalty Points History'
    },
    columnsForLoyaltyPointHistory: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal');
};
</script>
