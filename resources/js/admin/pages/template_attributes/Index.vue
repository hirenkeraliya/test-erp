<template>
    <PageTitle title="Template Attributes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Template Attributes
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.template_attributes.create')">
                <PrimaryButton
                    text="Add New Attribute"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.template_attributes.fetch')"
        :columns="state.columns"
        search-title="Search by name or field type"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.template_attributes.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <button
                    class="flex items-center mr-3"
                    @click.prevent="deleteAttributes(data.item.id)"
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
import { CheckSquare, Trash } from 'lucide-vue-next';

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        }, {
            key: 'field_type',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const deleteAttributes = (attributeId) => {
    const message = 'Are you sure you want to delete the selected attribute?';

    confirmDialogBox(message, () => {
        router.post(route('admin.template_attributes.delete', attributeId), {}, {
            onSuccess: () => router.get(route('admin.template_attributes.index'))
        });
    });
};

</script>
