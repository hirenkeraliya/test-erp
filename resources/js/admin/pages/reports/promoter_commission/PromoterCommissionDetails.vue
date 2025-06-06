<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Promoter Commission Details
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
            <JTable
                :fetch-url="route('admin.promoter_commission.get_promoter_commission_details', promoterCommission.id)"
                :columns="state.columns"
                :refresh-table-data="state.refreshTableData"
                :additional-query-params="filters"
                :allow-csv-export="true"
                :allow-excel-export="true"
                :export-csv-records-callback="exportCsvRecords"
                :export-excel-records-callback="exportExcelRecords"
                search-title="Search by offline id, department, commission percentage or commission amount"
                first-div-class="py-2 sm:py-5 mt-0 intro-y"
                :is-modal-table="true"
            >
                <template #extra-header-data>
                    <div
                        v-if="promoterCommission.commission_amount || promoterCommission.amount"
                        class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0"
                    >
                        <JBadge
                            v-if="promoterCommission.commission_amount"
                            :label="'Commission: ' + displayAmountWithCurrencySymbol(promoterCommission.commission_amount)"
                            class="mb-1 lg:mb-1 xl:mb-0"
                        />

                        <JBadge
                            v-if="promoterCommission.amount"
                            :label="'Amount: ' + displayAmountWithCurrencySymbol(promoterCommission.amount)"
                        />
                    </div>

                    <PrimaryButton
                        type="button"
                        text="PDF"
                        class="mr-1 sm:mr-2 float-left sm:float-none"
                        @click="exportPDFPromoterCommissionDetailsReport"
                    />
                </template>
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

                <template
                    v-if="!pageProps.product_variant"
                    #product_color="record"
                >
                    {{ record.item.product_color }}
                </template>

                <template
                    v-if="!pageProps.product_variant"
                    #product_size="record"
                >
                    {{ record.item.product_size }}
                </template>

                <template #commission_amount="data">
                    {{ displayAmountWithCurrencySymbolToFourDigit(numberFormatToFourDigit(data.item.commission_amount)) }}
                </template>

                <template #commission_percentage="data">
                    {{ displayAmountWithPercentageSymbol(numberFormat(data.item.commission_percentage)) }}
                </template>

                <template #amount="data">
                    {{ displayAmountWithCurrencySymbol(numberFormat(data.item.amount)) }}
                </template>
            </JTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, displayAmountWithPercentageSymbol, numberFormat, exportRecords, printReport, numberFormatToFourDigit, displayAmountWithCurrencySymbolToFourDigit } from '@commonServices/helper';
import JBadge from '@commonComponents/JBadge.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    filters: {
        type: Object,
        required: true,
    },
    promoterCommission: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'offline_id',
            label: 'OfflineId',
        }, {
            key: 'brand',
        }, {
            key: 'product',
        }, 
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'color',
                },
                {
                    key: 'size',
                },
            ]),
        {
            key: 'department_id',
            label: 'Department',
        }, {
            key: 'amount',
            bodyClass: 'text-right',
        }, {
            key: 'commission_percentage',
            bodyClass: 'text-center',
        }, {
            key: 'commission_amount',
            bodyClass: 'text-right',
            label: 'Commission',
        },
    ],
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};

const exportCsvRecords = () => {
    return exportRecords(
        'export-promoter-commission-details/' + props.promoterCommission.id + '/',
        `${props.promoterCommission.name}-promoter-commission-details.csv`,
        props.filters,
        props.exportPermission
    );
};

const exportExcelRecords = () => {
    return exportRecords(
        'export-promoter-commission-details/' + props.promoterCommission.id + '/',
        `${props.promoterCommission.name}-promoter-commission-details.xlsx`,
        props.filters,
        props.exportPermission
    );
};

const exportPDFPromoterCommissionDetailsReport = () => {
    const filters = props.filters;
    filters.promoterCommissionId = props.promoterCommission.id;

    const url = route('admin.promoter_commission.print_promoter_commission_details', filters);
    printReport(url, props.exportPermission);
};

</script>
