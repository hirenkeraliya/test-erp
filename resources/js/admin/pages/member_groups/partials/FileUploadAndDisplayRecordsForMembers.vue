<template>
    <div class="block sm:flex flex-col mb-3 -mx-3 sm:flex-row">
        <div class="w-full px-3">
            <JFileUpload
                v-model:input-file="state.uploaded_file"
                accept=".xlsx, .xls, .ods"
                input-label="Upload Members"
                validation-field-name="member_file"
                @update:input-file="importRecords"
            />
        </div>

        <div
            v-if="filePath"
            class="w-full px-3 mt-4 sm:mt-0"
        >
            <JFileDownload
                :file-path="filePath"
                input-label="Download Sample File"
            />
        </div>
    </div>

    <div class="block sm:flex w-full my-2">
        <div class="w-full pr-0 sm:pr-3" />
        <div class="w-full pl-0 text-left sm:pl-3">
            <button
                :disabled="! selectedMembers.length"
                class="px-8 text-sm font-bold rounded-r-lg btn py-18 text-black-40 bg-slate-300"
                type="button"
                @click="openSelectedMembers"
            >
                View All Imported Members
            </button>
        </div>
    </div>

    <Modal
        size="modal-xl"
        :show="state.display_selected_members_modal"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Members
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="sm:p-10 sm:pt-1">
            <div
                v-if="allowToClearSelectedProducts"
                class="row text-right mb-2"
            >
                <OutlineDangerButton
                    v-if="allowToClearSelectedProducts"
                    type="button"
                    text="Clear Members"
                    class="btn-sm w-30 h-10 mt-3 mr-2"
                    @click="clearSelectedMembers"
                />
            </div>

            <JSimpleTable
                :allow-search="true"
                :columns="state.fields"
                :records="selectedMembers"
                :totals="selectedMembers"
                :allow-pagination-and-sorting="allowPaginationAndSorting"
                first-div-class="pb-2 sm:pb-5 mt-0 intro-y"
            >
                <template
                    v-for="column in state.fields"
                    :key="column.key"
                    #[column.key]="record"
                >
                    <slot
                        :name="column.key"
                        :item="record.item"
                    />
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { reactive } from 'vue';

defineProps({
    selectedMembers: {
        type: Array,
        default: () => {},
    },
    filePath: {
        type: String,
        default: "",
    },
    allowToClearSelectedProducts: {
        type: Boolean,
        default: false,
    },
    allowPaginationAndSorting: {
        type: Boolean,
        default: true,
    },
});

const state = reactive({
    uploaded_file: null,
    display_selected_members_modal: false,
    fields: [
        {
            key: 'first_name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'mobile_number',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'card_number',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
    ],
});

const emits = defineEmits([
    'get-members-upload-file',
    'clear-selected-members',
]);

const closeModal = () => {
    if (state.display_selected_members_modal) {
        state.display_selected_members_modal = false;
        return;
    }
};

const openSelectedMembers = () => {
    state.display_selected_members_modal = true;
};

const importRecords = (event) => {
    emits('get-members-upload-file', event);
};

const clearSelectedMembers = () => {
    closeModal();
    emits('clear-selected-members');
};
</script>