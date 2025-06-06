<template>
    <PageTitle title="Denominations" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Denominations
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.denominations.create')">
                <PrimaryButton
                    text="Add New Denomination"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.denominations.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by denomination"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.denominations.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <button
                    class="flex items-center mr-3"
                    @click.prevent="deleteDenomination(data.item.id)"
                >
                    <Trash class="w-4 h-4 mr-2" />
                    Delete
                </button>
            </div>
        </template>
        <template #denomination="data">
            {{ displayAmountWithCurrencySymbol(data.item.denomination) }}
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Trash } from 'lucide-vue-next';
import { confirmDialogBox } from '@commonServices/notifier';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'denomination',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const deleteDenomination = (denominationId) => {
    const message = 'Are you sure you want to delete the selected denomination?';

    confirmDialogBox(message, () => {
        router.post(route('admin.denominations.delete', denominationId), {}, {
            onSuccess: () => router.get(route('admin.denominations.index'))
        });
    });
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-denominations/',
        'denominations.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-denominations/',
        'denominations.xlsx',
        params,
        props.exportPermission
    );
};
</script>
