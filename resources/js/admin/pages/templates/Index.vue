<template>
    <PageTitle title="Templates" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Templates
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.templates.create')">
                <PrimaryButton
                    text="Add New Template"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.templates.fetch')"
        :columns="state.columns"
        search-title="Search by name or code"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.attributes.index', data.item.id)"
                >
                    <List class="w-4 h-4 mr-1" />
                    Attributes
                </Link>
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.templates.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <button
                    class="flex items-center mr-3"
                    @click.prevent="deleteTemplate(data.item.id)"
                >
                    <Trash class="w-4 h-4 mr-2" />
                    Delete
                </button>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { confirmDialogBox } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import { CheckSquare, Trash, List } from 'lucide-vue-next';

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        }, {
            key: 'description',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const deleteTemplate = (templateId) => {
    const message = 'Are you sure you want to delete the selected template?';

    confirmDialogBox(message, () => {
        router.post(route('admin.templates.delete', templateId), {}, {
            onSuccess: () => router.get(route('admin.templates.index'))
        });
    });
};

</script>
