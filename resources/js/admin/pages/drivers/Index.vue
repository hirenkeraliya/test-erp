<template>
    <PageTitle title="Drivers" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Drivers
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.drivers.create')">
                <PrimaryButton
                    text="Add New Driver"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.drivers.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by name, ID number, email or mobile"
    >
        <template #mobile_number="data">
            {{ data.item.country_code }}{{ data.item.mobile_number }}
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
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.drivers.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JTable from '@commonComponents/JTable.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { CheckSquare } from 'lucide-vue-next';
import { confirmDialogBox, showSuccessNotification } from '@commonServices/notifier';

const state = reactive({
    columns: [
        { label: 'Name', key: 'name', sortable: true },
        { label: 'ID Number', key: 'id_number', sortable: true },
        { label: 'Email', key: 'email', sortable: true },
        { label: 'Mobile Number', key: 'mobile_number', sortable: true },
        { label: 'Status', key: 'status', sortable: true, headerClass: "text-center" },
        { label: 'Action', key: 'action', sortable: false, headerClass: "text-center" }
    ],
    refreshTableData: 0
});

const setStatus = async (driverId) => {
    confirmDialogBox('Are you sure you want to change the status?', () => {
        router.post(route('admin.drivers.change_status', driverId), {}, {
            onSuccess: () => {
                showSuccessNotification('Driver Status updated successfully.');
                state.refreshTableData++;
            }
        });
    });
};
</script>
