<template>
    <PageTitle title="Product Collections" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Product Collections
        </h2>

        <div
            v-if="saleChannel > 0"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync Data"
                class="btn btn-outline-primary"
                @click="syncData()"
            >
                <RefreshCw class="text-primary w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.product_collections.create')">
                <PrimaryButton
                    text="Add New Collection"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.product_collections.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by name"
    >
        <template #extra-header-data>
            <Tippy
                v-if="!autoIncludeFlag"
                content="Sync Product Collections"
                class="btn btn-outline-primary mr-3"
                @click="syncProductCollections()"
            >
                <RefreshCw class="text-primary w-5" />
            </Tippy>
        </template>

        <template #last_sync="data">
            {{ data.item.last_sync ? data.item.last_sync : 'N/A' }}
        </template>

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id)"
                />
            </div>
        </template>
        <template #action="data">
            <div
                class="flex justify-center items-center"
            >
                <div v-if="data.item.upload_status !== statuses.completed && data.item.upload_status !== 'N/A'">
                    <span class="flex flex-col gap-2">
                        <ProgressBar
                            :percentage="completionPercentage(data.item.total_records, data.item.total_records_imported, data.item.total_records_failed)"
                        />
                    </span>
                </div>

                <div
                    v-else
                    class="flex justify-center items-center"
                >
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.product_collections.edit', data.item.id)"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                    <button
                        class="flex items-center mr-3"
                        @click="deleteRecord(data.item.id)"
                    >
                        <Archive class="w-4 h-4 mr-1" />
                        Delete
                    </button>
                </div>

                <div>
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.product_collections.manage_media_view', data.item.id)"
                    >
                        <Upload class="w-4 h-4 mr-2" />
                        Manage Images
                    </Link>
                </div>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Archive, RefreshCw, Upload } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import axios from 'axios';
import { showSuccessNotification, showErrorNotification, confirmDialogBox } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import ProgressBar from '@commonComponents/ProgressBar.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { getProductCollectionHelpText } from '@commonStores/documentation';

const props = defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    productCollectionModelMappingType: {
        type: String,
        required: true,
    },
    autoIncludeFlag: {
        type: Boolean,
        required: true,
    },
    saleChannel: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    columns: [
        {
            key: 'name',
            sortable: true,
        }, {
            key: 'items',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'pending_products',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'last_sync',
        }, {
            key: 'created_by',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    productCollectionInCompleteStatusExists: true,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const fetchProductCollectionImportRecordsPendingStatuses = () => {
    if (!state.productCollectionInCompleteStatusExists) {
        return;
    }

    axios.get(route('admin.import_records.get_import_record_pending_statuses', props.productCollectionModelMappingType))
        .then(response => {
            const pendingCounts = response.data.pending_counts || 0;

            if (pendingCounts > 0) {
                refreshTable();
            } else if (state.productCollectionInCompleteStatusExists) {
                state.productCollectionInCompleteStatusExists = false;
                refreshTable();
            }
        });
};

const fetchInterval = 10000;

setInterval(fetchProductCollectionImportRecordsPendingStatuses, fetchInterval);

const setStatus = (productCollectionId) => {
    axios.post(route('admin.product_collections.change_status'), { productCollectionId }).then(() => {
        showSuccessNotification('Status updated successfully.');
    }).catch((error) => {
        if (error.message) {
            showErrorNotification(error.message);
        }
    });
};

const deleteRecord = (productCollectionId) => {
    const message = 'Are you sure want to delete?';
    confirmDialogBox(message, () => {
        router.post(route('admin.product_collections.delete', productCollectionId), {}, {
            onSuccess: () => refreshTable()
        });
    });
};

const completionPercentage = (totals, totalImported, totalRecordsFailed) => {
    const percentageMultiplier = 100;
    const percentage = ((parseInt(totalImported) + parseInt(totalRecordsFailed)) / totals) * percentageMultiplier;

    if (isNaN(percentage)) {
        return 0;
    }

    return Math.round(percentage);
};

const syncProductCollections = () => {
    axios.post(route('admin.product_collections.sync_product_collections')).then(() => {
        showSuccessNotification('Product Collections Sync Started In Background.');
        refreshTable();
    }).catch((error) => {
        if (error.message) {
            showErrorNotification(error.message);
        }
    });
};

const syncData = () => {
    axios.get(route('admin.product_collections.sync_data'))
        .then(() => {
            showSuccessNotification('Successfully Synchronized');
        });
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(getProductCollectionHelpText());
</script>
