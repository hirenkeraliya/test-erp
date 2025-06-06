<template>
    <PageTitle title="Mystery Gifts" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Mystery Gifts
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.mystery_gifts.create')">
                <PrimaryButton
                    text="Add New Mystery Gift"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.mystery_gifts.fetch')"
        :columns="state.columns"
        :allow-csv-export="false"
        :allow-excel-export="false"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by name"
    >
        <template #name="record">
            {{ record.item.name }}
        </template>

        <template #start_date="record">
            {{ record.item.start_date }}
        </template>

        <template #end_date="record">
            {{ record.item.end_date }}
        </template>

        <template #minimum_spend="record">
            {{ displayAmountWithCurrencySymbol(record.item.minimum_spend) }}
        </template>

        <template #action="record">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.mystery_gifts.edit', record.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <a
                    class="flex items-center mr-3 cursor-pointer"
                    @click="generateQrCode(record.item.id)"
                >
                    <QrCode class="w-4 h-4 mr-2" />
                    Generate QR code
                </a>
            </div>
        </template>

        <template #status="record">
            <JSwitch
                input-class="ml-0 mt-0"
                :is-checked="record.item.status"
                class="mt-[0px]"
                @update:is-checked="setStatus(record.item.id, $event)"
            />
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, QrCode } from 'lucide-vue-next';
import axios from 'axios';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import { displayAmountWithCurrencySymbol} from '@commonServices/helper';
import { showErrorNotification } from '@commonServices/notifier';

const state = reactive({
    columns: [
        {
            key: 'name',
        },
        {
            key: 'start_date',
        },
        {
            key: 'end_date',
        },
        {
            key: 'minimum_spend',
        },
        {
            key: 'status',
        },
        {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ],
    refreshTableData: Math.random(),
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const setStatus = (mysteryGiftId, status) => {
    const delayMs = 1000;
    router.post(route('admin.mystery_gifts.set_status', [mysteryGiftId, status ? 1 : 0]), {}, {
        onSuccess: () => setTimeout(() => {
            refreshTable();
        }, delayMs)
    });
};


const generateQrCode = (locationId) => {
    axios.get(route('admin.mystery_gifts.generate_qr_code', locationId), { responseType: 'arraybuffer' }).then((response) => {
        if (response.data) {
            state.qrCode = response.data;
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'qr-code.png');
            document.body.appendChild(link);
            link.click();
            link.remove();
        }
    }).catch((error) => {
        let errorMessage = 'An error occurred while processing your request.';
        if (error.response && error.response.data) {
            const errorData = new TextDecoder().decode(error.response.data);
            const parsedError = JSON.parse(errorData);
            errorMessage = parsedError.message;

        }
        showErrorNotification(errorMessage);
    });
};
</script>
