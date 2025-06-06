<template>
    <PageTitle title="Vehicles" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Vehicles
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.vehicles.create')">
                <PrimaryButton
                    text="Add New Vehicle"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.vehicles.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by name, plate number or vehicle type"
    >
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
                    :href="route('admin.vehicles.edit', data.item.id)"
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
        { label: 'Plate No', key: 'plate_no', sortable: true },
        { label: 'Type of Vehicle', key: 'type_of_vehicle', sortable: true },
        { label: 'Status', key: 'status', sortable: true, headerClass: "text-center", },
        { label: 'Action', key: 'action', sortable: false, headerClass: "text-center", }
    ],
    refreshTableData: 0
});

const setStatus = async (vehicleId) => {
    confirmDialogBox('Are you sure you want to change the status?', () => {
        router.post(route('admin.vehicles.change_status', vehicleId), {}, {
            onSuccess: () => {
                showSuccessNotification('Vehicle Status updated successfully.');
                state.refreshTableData++;
            }
        });
    });
};
</script>
