<template>
    <PageTitle title="Email Templates" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Email Templates
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.email_templates.create')">
                <PrimaryButton
                    class="shadow-md"
                    text="Add New Email Template"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.email_templates.fetch')"
        :columns="state.columns"
        search-title="Search by receiver name"
    >
        <template #revenue="record">
            {{ displayAmountWithCurrencySymbol(record.item.revenue) }}
        </template>
        <template #action="data">
            <div class="flex">
                <Link
                    :href="route('admin.email_templates.edit', data.item.id)"
                    class="flex mr-3"
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
import { CheckSquare } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        },
        {
            key: 'usage',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'clicks',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'revenue',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'conversion',
            label: 'conversion (%)',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'created_at',
            sortable: true
        },
        {
            key: 'action',
        }
    ],
});
</script>
