<template>
    <div
        v-if="isExportFileInProgress"
        class="bg-slate-300 rounded p-1 w-12 flex justify-center"
    >
        <LoaderSvg />
    </div>

    <div
        v-else
        class="text-center focus:ring-0"
    >
        <Dropdown
            v-slot="{ dismiss }"
            class="dropdown"
        >
            <DropdownToggle
                tag="a"
                class="btn btn-primary"
                href="javascript:;"
            >
                Export
            </DropdownToggle>

            <DropdownMenu
                class="w-60"
            >
                <DropdownContent>
                    <DropdownItem
                        v-if="allowCsvExport"
                        @click="exportCsvFile(dismiss)"
                    >
                        CSV
                    </DropdownItem>

                    <DropdownItem
                        v-if="allowExcelExport"
                        @click="exportExcelFile(dismiss)"
                    >
                        EXCEL
                    </DropdownItem>
                </DropdownContent>
            </DropdownMenu>
        </Dropdown>
    </div>
</template>

<script setup>
import LoaderSvg from '@svg/LoaderSvg.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

defineProps({
    allowCsvExport: Boolean,
    allowExcelExport: Boolean,
    isExportFileInProgress: Boolean,
});

const emits = defineEmits([
    'update:export-csv-file',
    'update:export-excel-file',
]);

const exportCsvFile = (dismiss) => {
    emits('update:export-csv-file');
    dismiss();
};
const exportExcelFile = (dismiss) => {
    emits('update:export-excel-file');
    dismiss();
};

</script>
