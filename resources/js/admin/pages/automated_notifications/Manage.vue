<template>
    <PageTitle :title="automatedNotification ? 'Edit Automated Notification' : 'Add Automated Notification'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Automated Notifications
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="automatedNotification">Edit Automated Notification</span>
                        <span v-else>Add Automated Notification</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveAutomatedNotification();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="automatedNotificationForm.type_id"
                                    :records="automatedNotificationTypes"
                                    input-label="Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                    @update:selected-record="clearConfigurations()"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="automatedNotificationForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="automatedNotificationForm.description"
                                    input-name="description"
                                    input-label="Description"
                                />
                            </div>
                        </div>

                        <hr class="mt-5">
                        <div
                            v-if="automatedNotificationForm.type_id === automatedNotificationStaticTypes.lowStockCompany || automatedNotificationForm.type_id === automatedNotificationStaticTypes.lowStockLocation || automatedNotificationForm.type_id === automatedNotificationStaticTypes.lowStockProduct"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6 mb-2"
                        >
                            <JSwitch
                                v-model:is-checked="automatedNotificationForm.sent_notification"
                                input-label="Send Notification?"
                                title="Notifications are sent to the store/warehouse manager of respective location."
                                @update:is-checked="clearTimeFrame()"
                            />
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                v-if="automatedNotificationForm.sent_notification ||
                                    (automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockCompany
                                        && automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockLocation
                                        && automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockProduct)"
                                class="input-form col-span-6 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="automatedNotificationForm.timeframe_type_id"
                                    :records="automatedNotificationTimeframeTypes"
                                    input-label="Timeframe"
                                    validation-field-name="timeframe_type_id"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="automatedNotificationForm.sent_notification || (automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockCompany
                                    && automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockLocation
                                    && automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockProduct)"
                                class="input-form col-span-6 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    :selected-records="state.automated_email_recipients"
                                    :records="automatedEmailReceipts"
                                    label="receiver_name"
                                    track-by="receiver_name"
                                    input-label="Email Recipients"
                                    title="Send followup email to selected email recipients."
                                    validation-field-name="automated_email_recipients"
                                    @update:selected-records="updateEmailRecipients"
                                />
                            </div>
                        </div>

                        <TimeframeDetails
                            v-if="automatedNotificationForm.sent_notification || automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockCompany || automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockLocation|| automatedNotificationForm.type_id !== automatedNotificationStaticTypes.lowStockProduct"
                            :automated-notification-timeframe-static-details="automatedNotificationTimeframeStaticDetails"
                            :automated-notification-form="automatedNotificationForm"
                            @remove:week-day="removeWeekDay"
                            @add:new-week-day="addNewWeekDay"
                            @remove:month-date="removeMonthDate"
                            @add:new-month-date="addNewMonthDate"
                            @clear:columns="clearColumns"
                        />
                        <div>
                            <span class="pl-5">
                                <div
                                    v-if="automatedNotificationForm.type_id === automatedNotificationStaticTypes.lowStockCompany"
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                >
                                    <p class="form-label font-bold text-lg">
                                        Company configuration:
                                    </p>

                                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                            <FormInput
                                                v-model:input-value="automatedNotificationForm.low_stock_alert_threshold"
                                                placeholder="Enter Low Stock Alert Threshold"
                                                input-name="low_stock_alert_threshold"
                                                input-label="Low Stock Alert Threshold"
                                                :required="true"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="automatedNotificationForm.type_id === automatedNotificationStaticTypes.lowStockLocation"
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0 mt-5"
                                >
                                    <p class="form-label font-bold text-lg">
                                        Location configuration:
                                    </p>

                                    <StoreSelection
                                        :automated-notification-form="automatedNotificationForm"
                                        :edit-selected-locations="automatedNotificationForm.locations"
                                        :allow-to-clear-selected-locations="true"
                                        :allow-to-download-selected-locations="automatedNotificationForm.hasOwnProperty('id')"
                                        @update:location-codes="updateColumnsDetails"
                                        @clear-selected-locations="clearSelectedLocations"
                                        @download-selected-locations="downloadExcelRecords"
                                    />
                                </div>

                                <div
                                    v-if="automatedNotificationForm.type_id === automatedNotificationStaticTypes.lowStockProduct"
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0 mt-5"
                                >
                                    <p class="form-label font-bold text-lg">
                                        Product configuration:
                                    </p>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5 mb-5">
                                        <div class="mt-3">
                                            <JTabs
                                                :records="locationTypes"
                                                :selected-record="state.typeId"
                                                return-selected-record="id"
                                                input-label="Location Selection"
                                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                                @update:selected-record="updateLocationType"
                                            />
                                        </div>

                                        <div>
                                            <TabPanel v-if="state.typeId === staticLocationTypes.store">
                                                <JMultiSelect
                                                    :selected-records="state.selectedLocations"
                                                    :records="stores"
                                                    validation-field-name="stores"
                                                    placeholder="Please select stores"
                                                    input-label="Stores"
                                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                                    @update:selected-records="selectLocations"
                                                />
                                            </TabPanel>

                                            <TabPanel v-if="state.typeId === staticLocationTypes.warehouse">
                                                <JMultiSelect
                                                    :selected-records="state.selectedLocations"
                                                    :records="warehouses"
                                                    placeholder="Please select warehouse"
                                                    input-label="Warehouses"
                                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                                    @update:selected-records="selectLocations"
                                                />
                                            </TabPanel>
                                        </div>
                                        <div class="w-full md:w-1/2 px-3 mt-2 sm:mt-2 md:mt-8">
                                            <PrimaryButton
                                                type="button"
                                                text="Select all"
                                                class="w-24 md:w-1/1 mt-4"
                                                @click="selectAllLocations"
                                            />

                                            <PrimaryButton
                                                v-if="state.selectedLocations.length > 0"
                                                type="button"
                                                text="Clear All"
                                                class="w-24 md:w-1/1 mt-2"
                                                @click="clearAllLocations"
                                            />
                                        </div>
                                    </div>

                                    <ProductWithStoreSelection
                                        :automated-notification-form="automatedNotificationForm"
                                        :edit-selected-products="automatedNotificationForm.products"
                                        :allow-to-clear-selected-products="true"
                                        :allow-to-download-selected-products="automatedNotificationForm.hasOwnProperty('id')"
                                        validation-field-name="product_locations_file"
                                        @update:product-upc="updateColumnsDetails"
                                        @clear-selected-products="clearSelectedProducts"
                                        @download-selected-products="downloadExcelProductRecords"
                                        @get-upload-file="getUploadFile"
                                    />
                                </div>
                            </span>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.automated_notifications.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="automatedNotification ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import ProductWithStoreSelection from '@adminPages/automated_notifications/partials/ProductWithStoreSelection.vue';
import StoreSelection from '@adminPages/automated_notifications/partials/StoreSelection.vue';
import TimeframeDetails from '@adminPages/automated_notifications/partials/TimeframeDetails.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { exportRecords } from '@commonServices/helper';
import { confirmDialogBox, showSuccessNotification } from '@commonServices/notifier';
import { autoMatedNotificationHelpText } from '@commonStores/documentation';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { TabPanel } from '@commonVendor/tab';
import JTabs from '@commonComponents/JTabs.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';


const props = defineProps({
    automatedNotification: {
        type: Object,
        default: null,
    },
    automatedEmailReceipts: {
        type: Object,
        required: true,
    },
    automatedNotificationTypes: {
        type: Array,
        required: true,
    },
    automatedNotificationTimeframeTypes: {
        type: Array,
        required: true,
    },
    automatedNotificationTimeframeStaticDetails: {
        type: Object,
        required: true,
    },
    automatedNotificationStaticTypes: {
        type: Object,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
    stores: {
        type: Object,
        default: () => {},
    },
    warehouses: {
        type: Object,
        default: () => {},
    },
    preSelectedLocationType: {
        type: Number,
        default: null,
    },
});

const automatedNotificationForm = useForm({
    name: null,
    description: null,
    type_id: null,
    timeframe_type_id: null,
    week_days: [],
    month_dates: [],
    automated_email_recipients: [],
    sent_notification: false,
    low_stock_alert_threshold: null,
    locations: [],
    products: [],
    product_ids: [],
    product_location_ids: [],
    product_locations_file: null,
    location_type_id: null,
    selected_locations: [],
});

const state = reactive({
    selectedLocations: [],
    automated_email_recipients: [],
    records: [],
    fields: [
        {
            key: 'id',
        }, {
            key: 'name',
        }, {
            key: 'upc'
        }, {
            key: 'color'
        }, {
            key: 'size'
        }
    ],
    typeId: props.preSelectedLocationType ?? props.staticLocationTypes.store,
});

const saveAutomatedNotification = () => {
    prepareAutomatedNotificationFormDetails();

    automatedNotificationForm.automated_email_recipients = state.automated_email_recipients.map((automatedEmailRecipient) => {
        return automatedEmailRecipient.id;
    });

    if (props.automatedNotification) {
        automatedNotificationForm.post(route('admin.automated_notifications.update', props.automatedNotification.data.id));
        return;
    }

    automatedNotificationForm.post(route('admin.automated_notifications.store'));
};

const selectLocations = (selectedLocations) => {
    state.selectedLocations = selectedLocations;
};

const addNewWeekDay = (weekDay) => {
    automatedNotificationForm.week_days.push(weekDay);
};

const removeWeekDay = (weekDayKey) => {
    automatedNotificationForm.week_days.splice(weekDayKey, 1);
};

const addNewMonthDate = (monthDate) => {
    automatedNotificationForm.month_dates.push(monthDate);
};

const removeMonthDate = (monthDateKey) => {
    automatedNotificationForm.month_dates.splice(monthDateKey, 1);
};

const clearColumns = (columnDetails) => {
    for (const key in columnDetails) {
        automatedNotificationForm[key] = columnDetails[key];
    }
};

const clearTimeFrame = () => {
    automatedNotificationForm.timeframe_type_id = null;
    automatedNotificationForm.week_days = [];
    automatedNotificationForm.month_dates = [];
    automatedNotificationForm.automated_email_recipients = [];
    state.automated_email_recipients = [];
};

const clearConfigurations = () => {
    automatedNotificationForm.low_stock_alert_threshold = null;
    automatedNotificationForm.locations = [];
    automatedNotificationForm.products = [];
    automatedNotificationForm.timeframe_type_id = null;
    automatedNotificationForm.week_days = [];
    automatedNotificationForm.month_dates = [];
    automatedNotificationForm.automated_email_recipients = [];
    automatedNotificationForm.sent_notification = false;
    state.automated_email_recipients = [];
};

const updateColumnsDetails = (details) => {
    const columnName = details.column_name;
    automatedNotificationForm[columnName] = details.value;
};

const clearSelectedLocations = () => {
    confirmDialogBox('Do you want to clear the selected locations?', () => {
        if (props.automatedNotification) {
            router.put(route('admin.automated_notifications.remove_selected_stores', props.automatedNotification.data.id), {}, {
                onSuccess: () => {
                    showSuccessNotification('The selected locations have been removed successfully.');
                    window.location.reload();
                }
            });
        } else {
            window.location.reload();
        }
    });
};

const clearSelectedProducts = () => {
    confirmDialogBox('Do you want to clear the selected products?', () => {
        if (props.automatedNotification) {
            router.put(route('admin.automated_notifications.remove_selected_products', props.automatedNotification.data.id), {}, {
                onSuccess: () => {
                    showSuccessNotification('The selected products have been removed successfully.');
                    window.location.reload();
                }
            });
        } else {
            window.location.reload();
        }
    });
};

const downloadExcelRecords = () => {
    return exportRecords(
        'export-automated-notification-stores/',
        'automated-notification-locations.xlsx',
        { id: automatedNotificationForm.id }
    );
};

const downloadExcelProductRecords = () => {
    return exportRecords(
        'export-automated-notification-products/',
        'automated-notification-locations.xlsx',
        { id: automatedNotificationForm.id }
    );
};

const getUploadFile = (file) => {
    automatedNotificationForm.product_locations_file = file;
};

const updateEmailRecipients = (emailRecipients) => {
    state.automated_email_recipients = emailRecipients;
};

const prepareAutomatedNotificationFormDetails = () => {
    automatedNotificationForm.product_location_ids = state.selectedLocations.map((location) => {
        return location.id;
    });
};

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.selectedLocations = [];
    automatedNotificationForm.location_type_id = typeId;
};

onMounted(() => {
    if (props.automatedNotification) {
        Object.assign(automatedNotificationForm, JSON.parse(JSON.stringify(props.automatedNotification.data)));
        state.automated_email_recipients = automatedNotificationForm.automated_email_recipients;
    }
});

const selectAllLocations = () => {
    if (state.typeId === props.staticLocationTypes.warehouse) {
        state.selectedLocations = props.warehouses;
        automatedNotificationForm.product_location_ids = props.warehouses.map((warehouse) => {
            return warehouse.id;
        });
        return;
    }

    state.selectedLocations = props.stores;
    automatedNotificationForm.product_location_ids = props.stores.map((store) => {
        return store.id;
    });
};

const clearAllLocations = () => {
    state.selectedLocations = [];
    automatedNotificationForm.product_location_ids = [];
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(autoMatedNotificationHelpText());
</script>
