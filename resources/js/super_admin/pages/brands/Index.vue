<template>
    <PageTitle title="Brands" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Brands
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center mr-2"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <Link :href="route('super_admin.brands.create')">
            <PrimaryButton
                text="Add New Brand"
                class="shadow-md"
            />
        </Link>
    </div>

    <JTable
        :fetch-url="route('super_admin.brands.fetch_brands')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.brands.edit_brand', data.item.id)"
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
import { reactive } from 'vue';
import { CheckSquare } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { BrandHelpText } from '@commonStores/documentation';
import { showSuccessNotification } from '@commonServices/notifier';
import axios from 'axios';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { RefreshCw } from 'lucide-vue-next';

const props = defineProps({
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
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
            key: 'code',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    disableRefreshButton: props.hasPendingSyncTransaction,
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-brands/',
        'brands.csv',
        params,
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-brands/',
        'brands.xlsx',
        params,
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(BrandHelpText());

const syncData = (id, dismiss) => {
    axios.get(route('super_admin.brands.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};
</script>
