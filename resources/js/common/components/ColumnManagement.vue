<template>
    <Modal
        size="modal-lg"
        :show="isDisplayColumnManagementModal"
        @hidden="hideModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Customize Columns
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideModal"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 text-center">
            <div class="flex flex-row items-center justify-between rounded mb-3 py-2 px-2 bg-slate-100">
                <span class="font-medium">Select All</span>
                <FormCheckbox
                    :check-value="isAllSelected"
                    @update:check-value="selectAll"
                />
            </div>
            <div
                v-if="originalColumns.length <= 8"
            >
                <draggable
                    :list="originalColumns"
                    ghost-class="ghost-card"
                    item-key="id"
                    @start="state.drag=true"
                    @end="drag=updateAndSortFields"
                >
                    <template #item="{element}">
                        <label>
                            <div
                                class="flex flex-row items-center justify-between rounded mb-1 py-2 px-2 transition delay-150 duration-300 ease-in-out"
                                :class="element.isDisplay ? 'bg-slate-300' : 'bg-slate-200'"
                            >
                                <div class="flex flex-row items-center">
                                    <div class="mr-2">
                                        <Equal />
                                    </div>

                                    <span class="font-medium">
                                        {{ prepareColumnLabel(element) }}
                                    </span>
                                </div>

                                <div>
                                    <FormCheckbox
                                        :check-value="element.isDisplay"
                                        :is-disabled="element.isDisplay && isLastField"
                                        @update:check-value="updateDisplayColumnStatus(element.key)"
                                    />
                                </div>
                            </div>
                        </label>
                    </template>
                </draggable>
            </div>
            <draggable
                v-else
                class="overflow-y-auto h-[22rem]"
                :list="originalColumns"
                ghost-class="ghost-card"
                item-key="id"
                @start="state.drag=true"
                @end="drag=updateAndSortFields"
            >
                <template #item="{element}">
                    <label>
                        <div
                            class="flex flex-row items-center justify-between rounded mb-1 py-2 px-2 transition delay-150 duration-300 ease-in-out"
                            :class="element.isDisplay ? 'bg-slate-300' : 'bg-slate-200'"
                        >
                            <div class="flex flex-row items-center">
                                <div class="mr-2">
                                    <Equal />
                                </div>

                                <span class="font-medium">
                                    {{ prepareColumnLabel(element) }}
                                </span>
                            </div>

                            <div>
                                <FormCheckbox
                                    :check-value="element.isDisplay"
                                    :is-disabled="element.isDisplay && isLastField"
                                    @update:check-value="updateDisplayColumnStatus(element.key)"
                                />
                            </div>
                        </div>
                    </label>
                </template>
            </draggable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import Draggable from 'vuedraggable';
import { X, Equal } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { computed, reactive } from 'vue';
import { capitalize } from '@commonServices/helper';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';

const props = defineProps({
    isDisplayColumnManagementModal: {
        type: Boolean,
        default: false,
    },
    originalColumns: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:is-display-column-management-modal',
    'update:column-status',
    'update:column-fields',
]);

const state = reactive({
    drag: false,
});

const hideModal = () => {
    emits('update:is-display-column-management-modal', false);
};

const updateAndSortFields = () => {
    state.drag = false;

    emits('update:column-fields', props.originalColumns);
};

const prepareColumnLabel = (column) => {
    if (column.label) {
        return column.label;
    }

    return capitalize(column.key);
};

const updateDisplayColumnStatus = (columnKey) => {
    emits('update:column-status', columnKey);
};

const isLastField = computed(() => {
    const displayFields = props.originalColumns.filter((column) => {
        return column.isDisplay;
    });

    return displayFields.length === 1;
});

const isAllSelected = computed(() => {
    return props.originalColumns.every((column) => column.isDisplay);
});

const selectAll = (isSelected) => {
    props.originalColumns.forEach((column) => {
        column.isDisplay = isSelected;
    });

    ensureAtLeastOneSelected();
    emits('update:column-fields', props.originalColumns);
};

const ensureAtLeastOneSelected = () => {
    const selectedColumns = props.originalColumns.filter((col) => col.isDisplay);
    if (selectedColumns.length === 0) {
        const firstColumn = props.originalColumns[0];
        if (firstColumn) {
            firstColumn.isDisplay = true;
        }
    }
};
</script>
