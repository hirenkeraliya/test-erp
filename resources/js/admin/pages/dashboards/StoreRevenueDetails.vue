<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Details Of The {{ moduleTitleName }}
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
            <div class="block">
                <button
                    v-for="(section, index) in subSectionLabels"
                    :key="index + section"
                    type="button"
                    class="mr-1 sm:mr-2 mb-1 sm:mb-1 md:mb-1 focus:ring-0"
                    :class="state.selectedSection === section ? 'btn btn-primary' : 'btn btn-secondary'"
                    @click="getSelectedSectionBasedData(section)"
                >
                    {{ section }}
                </button>
            </div>

            <JSimpleTable
                :columns="state.columns"
                :records="records"
                :allow-search="true"
            >
                <template #extra-header-data>
                    <Tippy
                        content="totalUnitsSold"
                    >
                        <JBadge
                            v-if="totalUnitsSold"
                            :label="`Units Sold: ${numberFormat(totalUnitsSold)}`"
                            class="mb-2 sm:mb-0"
                        />
                    </Tippy>

                    <Tippy
                        content="totalSales"
                    >
                        <JBadge
                            v-if="totalSales"
                            :label="`Sales: ${displayAmountWithCurrencySymbol(totalSales)}`"
                        />
                    </Tippy>
                </template>

                <template #total_units_sold="data">
                    {{ numberFormat(data.item.total_units_sold) }}
                </template>

                <template #total_sales="data">
                    {{ displayAmountWithCurrencySymbol(data.item.total_sales) }}
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithCurrencySymbol, numberFormat } from '@commonServices/helper';
import { X } from 'lucide-vue-next';
import { onUpdated, reactive } from 'vue';
import JBadge from '@commonComponents/JBadge.vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    records: {
        type: Object,
        default: () => { },
    },
    moduleTitleName: {
        type: String,
        default: null,
    },
    totalSales: {
        type: Number,
        default: 0,
    },
    totalUnitsSold: {
        type: Number,
        default: 0,
    },
    subSectionLabels: {
        type: Object,
        required: true
    },
    defaultSubSectionsLabel: {
        type: String,
        required: true
    }
});

const state = reactive({
    columns: [
        {
            key: 'name',
            isDisplay: true,
            headerClass: 'text-left',
        }, {
            key: 'total_units_sold',
            label: 'Units Sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_sales',
            label: 'Sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }
    ],

    selectedSection: props.defaultSubSectionsLabel,
});

onUpdated(() => {
    if (props.modalShow) {
        state.selectedSection = props.defaultSubSectionsLabel;
    }
});

const emits = defineEmits(['close-modal', 'get-records-based-on-selected-sections']);

const closeModal = () => {
    emits('close-modal');
};

const getSelectedSectionBasedData = (selectedSection) => {
    state.selectedSection = selectedSection;
    emits('get-records-based-on-selected-sections', selectedSection);
};
</script>
