<template>
    <PageTitle title="Sale Seasons" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Seasons
        </h2>

        <div class="w-full sm:w-auto flex mt-1 sm:mt-0">
            <Link :href="route('admin.sale_seasons.create')">
                <PrimaryButton
                    text="Add New Sale Season"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>
    <JTable
        :fetch-url="route('admin.sale_seasons.fetch')"
        :columns="state.columns"
        :additional-query-params="state.parameters"
        search-title="Search by name"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.sale_seasons.edit', data.item.id)"
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
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Archive } from 'lucide-vue-next';
import { route } from 'ziggy';
import { confirmDialogBox } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        }, {
            key: 'start_date',
            sortable: true
        }, {
            key: 'end_date',
            sortable: true
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const deleteRecord = (saleSeasonId) => {
    const message = 'Are you sure want to delete?';
    confirmDialogBox(message, () => {
        router.post(route('admin.sale_seasons.delete', saleSeasonId), {}, {
            onSuccess: () => router.get(route('admin.sale_seasons.index'))
        });
    });
};
</script>
