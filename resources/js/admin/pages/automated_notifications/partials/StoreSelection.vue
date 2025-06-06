<template>
    <FileUploadAndDisplayRecordsForStore
        :unmatched-locations="state.unmatchedLocations"
        :matched-locations-list="state.matchedLocationsList"
        :selected-locations="state.selectedLocations"
        :data-property-names="['low_stock_alert_threshold']"
        :input-label="label"
        :validation-field-name="validationFieldName"
        location-code-url="admin.locations.get_matching_code_locations"
        file-path="/files/automated-notifications-location-file.xlsx"
        @display-selected-locations-modal="openSelectedLocationsModal"
        @update:column-details="updateColumnDetails"
        @display-unmatched-locations-modal="openUnmatchedLocationsModal"
    />

    <SelectedStores
        :modal-show="state.displaySelectedLocationsModal"
        :columns="state.fields"
        :records="state.selectedLocations"
        :allow-to-clear-selected-locations="allowToClearSelectedLocations"
        :allow-to-download-selected-locations="allowToDownloadSelectedLocations"
        @close-modal="closeModal"
        @clear-selected-locations="clearSelectedLocations"
        @download-selected-locations="downloadExcelRecords"
    />

    <UnmatchedStores
        :modal-show="state.displayUnmatchedLocationsModal"
        :records="state.unmatchedLocations"
        @close-modal="closeModal"
    />
</template>

<script setup>
import SelectedStores from '@commonComponents/SelectedStores.vue';
import UnmatchedStores from '@commonComponents/UnmatchedStores.vue';
import { onMounted, onUpdated, reactive } from 'vue';
import FileUploadAndDisplayRecordsForStore from '@commonComponents/FileUploadAndDisplayRecordsForStore.vue';

const props = defineProps({
    automatedNotificationForm: {
        type: Object,
        required: true,
    },
    columnName: {
        type: String,
        default: 'locations'
    },
    label: {
        type: String,
        default: 'Select Locations'
    },
    validationFieldName: {
        type: String,
        default: ''
    },
    editSelectedLocations: {
        type: Object,
        default: () => {},
    },
    allowToClearSelectedLocations: {
        type: Boolean,
        default: false,
    },
    allowToDownloadSelectedLocations: {
        type: Boolean,
        default: false,
    },
});

const state = reactive({
    displayUnmatchedLocationsModal: false,
    unmatchedLocations: [],
    displaySelectedLocationsModal: false,
    selectedLocations: [],
    matchedLocationsList: [],
    fields: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'code',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'low_stock_alert_threshold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],
});

const emits = defineEmits([
    'update:location-codes',
    'clear-selected-locations',
    'download-selected-locations',
]);

const openSelectedLocationsModal = () => {
    state.displaySelectedLocationsModal = true;
};

const openUnmatchedLocationsModal = () => {
    state.displayUnmatchedLocationsModal = true;
};

const closeModal = () => {
    if (state.displaySelectedLocationsModal) {
        state.displaySelectedLocationsModal = false;
        return;
    }
    if (state.displayUnmatchedLocationsModal) {
        state.displayUnmatchedLocationsModal = false;
    }
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const clearSelectedLocations = () => {
    emits('clear-selected-locations');
};

onUpdated(() => {
    if (state.matchedLocationsList.length) {
        emits('update:location-codes', {
            column_name: props.columnName,
            value: state.matchedLocationsList
        });
    }
});

onMounted(() => {
    state.selectedLocations = props.editSelectedLocations;
});

const downloadExcelRecords = () => {
    emits('download-selected-locations');
};
</script>
