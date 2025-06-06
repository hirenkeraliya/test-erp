<template>
    <PageTitle title="Attributes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Attributes of Template: <span class="text-primary">{{ templateName }}</span>
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.templates.index')">
                <SecondaryButton
                    text="Back to List of Templates"
                    class="shadow-md mx-2"
                />
            </Link>

            <Link :href="route('admin.attributes.create', templateId)">
                <PrimaryButton
                    text="Add Attribute"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.attributes.fetch', templateId)"
        :columns="state.columns"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.attributes.edit', [templateId, data.item.id])"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <button
                    class="flex items-center mr-3"
                    @click.prevent="deleteAttribute(data.item.id)"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Trash } from 'lucide-vue-next';
import { confirmDialogBox } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    templateId: {
        type: Number,
        required: true
    },
    templateName: {
        type: String,
        required: true
    },
});

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
    ],
});

const deleteAttribute = (attributeId) => {
    const message = 'Are you sure you want to delete the selected attribute?';

    confirmDialogBox(message, () => {
        router.post(route('admin.attributes.delete', [props.templateId, attributeId]), {}, {
            onSuccess: () => router.get(route('admin.attributes.index', props.templateId))
        });
    });
};
</script>
