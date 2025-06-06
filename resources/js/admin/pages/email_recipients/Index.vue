<template>
    <PageTitle title="Email Recipients" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Email Recipients
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.email_recipients.create')">
                <PrimaryButton
                    text="Add New Email Recipient"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.email_recipients.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by receiver name, receiver email, or email type"
    >
        <template #receiver_email="data">
            {{ data.item.receiver_email }}
            <Tippy
                v-if="!data.item.is_email_verified && data.item.receiver_email"
                :content="'Updating your email will require re-verification.'"
            >
                <TriangleAlert
                    class="text-red-400 ml-2"
                    :size="15"
                />
            </Tippy>
        </template>
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.email_recipients.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
                <Link
                    v-if="!data.item.is_email_verified && data.item.receiver_email"
                    class="flex items-center"
                    :href="route('admin.email_recipients.resend_verification_email', data.item.id)"
                >
                    <Tippy
                        :content="'Resend mail'"
                    >
                        <Mail class="w-4 h-5 mr-2" />
                    </Tippy>
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Mail, TriangleAlert } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'receiver_name',
            sortable: true
        }, {
            key: 'receiver_email',
            sortable: true
        }, {
            key: 'email_type',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-email-recipients/',
        'email-recipients.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-email-recipients/',
        'email-recipients.xlsx',
        params,
        props.exportPermission
    );
};
</script>
