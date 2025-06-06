<template>
    <PageTitle title="Unit of Measure Derivatives" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Derivatives of Unit of Measure: <span class="text-primary">{{ unitOfMeasureName }}</span>
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.unit_of_measures.index')">
                <SecondaryButton
                    text="Back to List of Unit of measures"
                    class="shadow-md mx-2"
                />
            </Link>

            <Link :href="route('admin.unit_of_measure_derivatives.create', unitOfMeasureId)">
                <PrimaryButton
                    text="Add Derivative"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.unit_of_measure_derivatives.fetch', unitOfMeasureId)"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
    >
        <template #ratio="data">
            {{ allowDecimalQty ? data.item.ratio: truncateDecimal((data.item.ratio)) }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.unit_of_measure_derivatives.edit', [unitOfMeasureId, data.item.id])"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecordsUsingRouter, truncateDecimal } from '@commonServices/helper';

const props = defineProps({
    unitOfMeasureId: {
        type: Number,
        default: null,
    },
    unitOfMeasureName: {
        type: String,
        default: null
    },
    exportPermission: {
        type: String,
        required: true,
    },
    allowDecimalQty: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'name',
            sortable: true
        }, {
            key: 'ratio',
            sortable: true
        }, {
            key: 'action',
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecordsUsingRouter(
        route('admin.unit_of_measure_derivatives.export_derivatives_unit_of_measures', [props.unitOfMeasureId, 'derivatives-of-unit-of-measures.csv']),
        'derivatives-of-unit-of-measures.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecordsUsingRouter(
        route('admin.unit_of_measure_derivatives.export_derivatives_unit_of_measures', [props.unitOfMeasureId, 'derivatives-of-unit-of-measures.xlsx']),
        'derivatives-of-unit-of-measures.xlsx',
        params,
        props.exportPermission
    );
};
</script>
