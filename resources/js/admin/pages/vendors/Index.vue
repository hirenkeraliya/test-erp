<template>
    <PageTitle title="Vendors" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Vendors
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.vendors.create')">
                <PrimaryButton
                    text="Add New Vendor"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.vendors.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name, code, phone, email, or city"
    >
        <template #code="data">
            {{ data.item.code ? data.item.code: 'N/A' }}
        </template>

        <template #email="data">
            {{ data.item.email }}
            <Tippy
                v-if="!data.item.is_email_verified && data.item.email"
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
                    :href="route('admin.vendors.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
                <Link
                    v-if="!data.item.is_email_verified && data.item.email"
                    class="flex items-center mr-8"
                    :href="route('admin.vendors.resend_verification_email', data.item.id)"
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
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Mail, TriangleAlert } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
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
            key: 'name',
            sortable: true
        }, {
            key: 'code',
            sortable: true
        }, {
            key: 'phone',
            sortable: true
        }, {
            key: 'email',
            sortable: true
        }, {
            key: 'city',
            sortable: true
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ]
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-vendors/',
        'vendors.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-vendors/',
        'vendors.xlsx',
        params,
        props.exportPermission
    );
};
</script>
