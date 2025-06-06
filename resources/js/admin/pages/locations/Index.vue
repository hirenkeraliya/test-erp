<template>
    <PageTitle title="Locations" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Locations
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.locations.create')">
                <PrimaryButton
                    text="Add New Location"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayLocationsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.type_id"
                    :records="locationTypes"
                    placeholder="Please select type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Types"
                    @update:selected-record="updateLocationTypes($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.locations.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name, code, phone, email, or city"
    >
        <template #region="data">
            <div>
                {{ data.item.region ? data.item.region.name : 'N/A' }}
            </div>
        </template>

        <template #name="data">
            <div class="flex">
                <div>
                    {{ data.item.name }}
                </div>
            </div>
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
                    :href="route('admin.locations.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
                <Link
                    v-if="!data.item.is_email_verified && data.item.email"
                    class="flex items-center"
                    :href="route('admin.locations.resend_verification_email', data.item.id)"
                >
                    <Tippy
                        :content="'Resend mail'"
                    >
                        <Mail class="w-4 h-5 mr-2" />
                    </Tippy>
                </Link>
                <Dropdown
                    v-if="data.item.type === staticLocationTypes.store"
                    class="flex items-center mr-3"
                >
                    <DropdownToggle
                        tag="a"
                        class="w-5 h-5 block"
                        href="javascript:;"
                    >
                        <MoreHorizontal class="w-5 h-5 text-slate-500" />
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                v-if="companyIOICityMallConfiguration"
                                class="flex items-center mr-3"
                                @click="showIOICityMallModal(data.item.id)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Enable IOI Configuration
                            </DropdownItem>

                            <DropdownItem
                                v-if="companyTRXMallConfiguration"
                                class="flex items-center mr-3"
                                @click="showTRXMallModal(data.item.id)"
                            >
                                <CheckSquare class="w-4 h-4 mr-2" />Enable TRX Configuration
                            </DropdownItem>

                            <DropdownItem
                                class="flex items-center mr-3"
                                @click="generateQrCode(data.item.id)"
                            >
                                <QrCode class="w-4 h-4 mr-2" />
                                Member Registration Qr-Code
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayLocationsFilter = !state.displayLocationsFilter"
                />
            </p>
        </template>
    </JTable>

    <Modal
        size="modal-lg"
        :show="state.isIOICityMall"
        @hidden="hideIOICityMallModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Enable IOI City Mall Configuration
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideIOICityMallModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <form
                @submit.prevent="saveIOICityMallConfiguration();"
            >
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                        <InfoAlert>
                            <ul class="space-y-2 list-decimal ml-3">
                                <li>
                                    Please share the Machine ID provided by the mall when you enable this integration.
                                </li>
                                <li>
                                    The system decides whether the SST is enabled by looking at the tax rate of the location. If it is more than 0(zero), we assume that the SST is enabled.
                                </li>
                                <li>
                                    The data and file preparation is done as per the specifications of the mall automatically. No manual process is needed after the integration is enabled.
                                </li>
                                <li>
                                    File generation (for all the locations where it is enabled) happens every day for the previous day's sales data.
                                </li>
                                <li>
                                    File upload happens every day. All the pending files are uploaded via SFTP. You need to provide SFTP details to the developers in advance.
                                </li>
                                <li>
                                    <p v-if="iosCityMallSalesFileNotificationEmail">
                                        Notifications are sent to '{{ iosCityMallSalesFileNotificationEmail }}' after file generation and file uploads.
                                    </p>
                                    <p v-else>
                                        Email notifications can be sent after file generation and file upload. Please contact support team to enable them.
                                    </p>
                                </li>
                                <li>
                                    Only the regular sales and 'returns with purchase' sales are included in the sales data. Exchange, Layaway, Credit, Booking payments, etc. are not considered.
                                </li>
                                <li>
                                    The following payment types are accepted by the mall. Cash, Tng, Visa, Mastercard, Amex, Voucher, and Others. If a payment type matches as per the configuration, the sale payment amount is included in the respective payment type. All the other payments are included in the 'Others' category.
                                </li>
                            </ul>
                        </InfoAlert>
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <JSwitch
                            v-model:is-checked="locationIOIConfiguration.enable_ioi_city_mall_data_sharing"
                            input-label="Enable Ioi City Mall Data Sharing?"
                            title="Enables Location In The IOI City Mall Lists."
                            class="mt-0 sm:mt-1 md:mt-1 lg:mt-4"
                        />
                    </div>

                    <div
                        v-if="locationIOIConfiguration.enable_ioi_city_mall_data_sharing"
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                    >
                        <FormInput
                            v-model:input-value="locationIOIConfiguration.ioi_city_mall_machine_id"
                            input-name="ioi_city_mall_machine_id"
                            input-label="Machine Id"
                            :required="true"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <SecondaryButton
                        type="button"
                        text="Cancel"
                        class="w-24 mr-1"
                        @click="hideIOICityMallModal()"
                    />

                    <PrimaryButton
                        type="submit"
                        text="Update"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>

    <Modal
        size="modal-lg"
        :show="state.isTRXMall"
        @hidden="hideTRXMallModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Enable TRX Mall Configuration
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideTRXMallModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <form
                @submit.prevent="saveTRXMallConfiguration();"
            >
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                        <InfoAlert>
                            <ul class="space-y-2 list-decimal ml-3">
                                <li>
                                    Please share the Machine ID provided by the mall when you enable this integration.
                                </li>
                                <li>
                                    The system decides whether the GST is enabled by looking at the tax rate of the location. If it is more than 0(zero), we assume that the GST is enabled.
                                </li>
                                <li>
                                    The data and file preparation is done as per the specifications of the mall automatically. No manual process is needed after the integration is enabled.
                                </li>
                                <li>
                                    Only the regular sales are included in the sales data. Exchange, Layaway, Credit, Booking payments, etc. are not considered.
                                </li>
                                <li>
                                    The following payment types are accepted by the mall. Cash, Tng, Visa, Mastercard, Amex, Voucher, and Others. If a payment type matches as per the configuration, the sale payment amount is included in the respective payment type. All the other payments are included in the 'Others' category.
                                </li>
                            </ul>
                        </InfoAlert>
                    </div>
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <JSwitch
                            v-model:is-checked="locationTRXConfiguration.enable_trx_mall_data_sharing"
                            input-label="Enable TRX Mall Data Sharing?"
                            title="Enables Location In The TRX Mall Lists."
                            class="mt-0 sm:mt-1 md:mt-1 lg:mt-4"
                        />
                    </div>

                    <div
                        v-if="locationTRXConfiguration.enable_trx_mall_data_sharing"
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                    >
                        <FormInput
                            v-model:input-value="locationTRXConfiguration.trx_mall_machine_id"
                            input-name="trx_mall_machine_id"
                            input-label="Machine Id"
                            :required="true"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <SecondaryButton
                        type="button"
                        text="Cancel"
                        class="w-24 mr-1"
                        @click="hideTRXMallModal()"
                    />

                    <PrimaryButton
                        type="submit"
                        text="Update"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, QrCode, X, MoreHorizontal, TriangleAlert, Mail } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import axios from 'axios';
import { exportRecords } from '@commonServices/helper';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const iosCityMallSalesFileNotificationEmail = computed(() => usePage().props.ioi_city_mall_sales_file_notification_email);

const locationIOIConfiguration = useForm({
    ioi_city_mall_machine_id: null,
    enable_ioi_city_mall_data_sharing: false,
});

const locationTRXConfiguration = useForm({
    trx_mall_machine_id: null,
    enable_trx_mall_data_sharing: false,
});

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    companyIOICityMallConfiguration: {
        type: Boolean,
        required: true,
    },
    companyTRXMallConfiguration: {
        type: Boolean,
        required: true,
    },
    locationTypes: {
        type: Array,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const state = reactive({
    columns: [
        {
            key: 'type',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'name',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'code',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'phone',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'email',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'city',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'region',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    parameters: {
        type_id: null,
    },
    isIOICityMall: false,
    isTRXMall: false,
    locationId: null,
    refreshTableData: Math.random(),
    displayLocationsFilter: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const generateQrCode = (locationId) => {
    axios.get(route('admin.locations.generate_qr_code', locationId), { responseType: 'arraybuffer' }).then((response) => {
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

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-locations/',
        'locations.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-locations/',
        'locations.xlsx',
        params,
        props.exportPermission
    );
};

const showIOICityMallModal = (locationId) => {
    axios.get(route('admin.locations.fetch_store_ioi_city_mall_configuration', locationId))
        .then((response) => {
            Object.assign(locationIOIConfiguration, JSON.parse(JSON.stringify(response.data.locationIOIConfiguration)));
            state.isIOICityMall = true;
            state.locationId = locationId;
        }).catch((error) => {
            showErrorNotification(error.response.data.message);
        });
};

const showTRXMallModal = (locationId) => {
    axios.get(route('admin.locations.fetch_store_trx_mall_configuration', locationId))
        .then((response) => {
            Object.assign(locationTRXConfiguration, JSON.parse(JSON.stringify(response.data.locationTRXConfiguration)));
            state.isTRXMall = true;
            state.locationId = locationId;
        }).catch((error) => {
            showErrorNotification(error.response.data.message);
        });
};

const hideIOICityMallModal = () => {
    state.isIOICityMall = false;
    state.locationId = null;
    locationIOIConfiguration.reset();
};

const hideTRXMallModal = () => {
    state.isTRXMall = false;
    state.locationId = null;
    locationTRXConfiguration.reset();
};

const saveIOICityMallConfiguration = () => {
    locationIOIConfiguration.post(route('admin.locations.update_ioi_city_mall_configuration', state.locationId), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            showSuccessNotification('Location IOI City Mall Configuration updated successfully.');
            hideIOICityMallModal();
        },
    });
};

const saveTRXMallConfiguration = () => {
    locationTRXConfiguration.post(route('admin.locations.update_trx_mall_configuration', state.locationId), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            showSuccessNotification('Location TRX Mall Configuration updated successfully.');
            hideTRXMallModal();
        },
    });
};

const updateLocationTypes = (locationType) => {
    state.parameters.type_id = locationType;
    refreshTable();
};

const clearAll = () => {
    state.parameters.type_id = null;
    refreshTable();
};

</script>
