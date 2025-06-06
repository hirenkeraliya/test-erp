<template>
    <PageTitle title="Member Groups" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Member Groups
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.member_groups.create')">
                <PrimaryButton
                    text="Add New Member Group"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.member_groups.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name or price override limit percentage"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex mr-3"
                    :href="route('admin.member_groups.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <div
                    class="flex mr-3 cursor-pointer"
                    @click="showMailTemplates(data.item.id, data.item.name)"
                >
                    <Mails class="w-4 h-4 mr-2" />
                    Send Email
                </div>

                <div
                    v-if="data.item.upload_status == statuses.completed && data.item.upload_status !== 'N/A' && data.item.type_id == groupTypes.smartGroup && !isSynced"
                    class="flex mr-3 cursor-pointer"
                    title="Sync the pending members"
                    @click="syncMembers(data.item.id)"
                >
                    <RefreshCw class="w-4 h-4 mr-2" />
                    Sync Members
                </div>
            </div>
        </template>
    </JTable>

    <Modal
        size="modal-xl"
        :show="state.show_email_template_model"
        @hidden="state.show_email_template_model = false"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Email Templates
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="state.show_email_template_model = false"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <InfoAlert
                color="primary"
            >
                Selected Email Template will be use to send email to all members which belongs to the selected member group: {{ state.member_group_name }}
            </InfoAlert>

            <FormSelectBox
                v-model:selected-record="state.email_template_id"
                :records="state.email_templates"
                input-label="Email Templates"
                label-class="block font-medium text-base text-primary-p3 mb-2"
            />

            <div class="text-left mt-5">
                <PrimaryButton
                    v-if="state.email_template_id"
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="sendEmailsToMemberGroup"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, RefreshCw, Mails, X } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords } from '@commonServices/helper';
import axios from 'axios';
import { showSuccessNotification, showErrorNotification, confirmDialogBox } from '@commonServices/notifier';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    groupTypes: {
        type: Object,
        required: true,
    },
    isSynced: {
        type: Boolean,
        default: false,
    }
});

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'name',
            sortable: true
        }, {
            key: 'pending_members',
        }, {
            key: 'members',
        }, {
            key: 'lifetime_value_of_members',
        }, {
            key: 'code',
            sortable: true
        }, {
            key: 'type',
            sortable: false
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    email_templates: [],
    email_template_id: null,
    member_group_id: null,
    member_group_name: null,
    show_email_template_model: false,
    refreshTableData: Math.random(),
    disableRefreshButton: props.hasPendingSyncTransaction,
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-member-groups/',
        'member-groups.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-member-groups/',
        'member-groups.xlsx',
        params,
        props.exportPermission
    );
};

const syncData = (id, dismiss) => {
    axios.get(route('admin.member_groups.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};

const showMailTemplates = (memberGroupId, memberGroupName) => {
    state.email_template_id = null;
    state.member_group_id = null;
    state.member_group_name = null;

    if (! state.email_templates.length) {
        fetchEmailTemplates();
    }
    state.member_group_id = memberGroupId;
    state.member_group_name = memberGroupName;
    state.show_email_template_model = true;
};

const syncMembers = (memberGroupId) => {
    axios.post(route('admin.member_groups.sync_member'), { member_group_id: memberGroupId })
        .then(() => {
            showSuccessNotification('It will take sometimes.');
            window.location.reload();
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        }) ;
};

const fetchEmailTemplates = () => {
    axios.get(route('admin.email_templates.get_all'))
        .then((response) => {
            state.email_templates = response.data.email_templates;
        });
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const memberGroupTableReload = () => {
    refreshTable();
};

const memberGroupInterval = 10000;
setInterval(memberGroupTableReload, memberGroupInterval);

const sendEmailsToMemberGroup = () => {
    state.show_email_template_model = false;

    if (!state.member_group_id && !state.email_template_id) {
        showErrorNotification('Please select Member Group or Email Template');
        return;
    }

    const message = 'Are you sure you want to send an email to all members associated with this Member Group ` ' + state.member_group_name + ' `?';

    confirmDialogBox(message , () => {
        axios.post(route('admin.members.send_emails'), {
            member_group_id: state.member_group_id,
            email_template_id: state.email_template_id
        })
            .then(() => {
                showSuccessNotification('Email Sent Successfully.');
            }).catch((error) => {
                if (error.response.data.message) {
                    showErrorNotification(error.response.data.message);
                }
            });
    });
};
</script>
