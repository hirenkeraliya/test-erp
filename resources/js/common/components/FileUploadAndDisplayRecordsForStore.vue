<template>
    <div class="block sm:flex flex-col mb-3 -mx-3 sm:flex-row">
        <div class="w-full px-3">
            <JFileUpload
                v-model:input-file="state.uploaded_file"
                accept=".xlsx, .xls, .ods"
                :input-label="inputLabel"
                :validation-field-name="validationFieldName"
                @update:input-file="importRecords($event)"
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

    <div class="block sm:flex justify-between w-full my-2">
        <div class="w-full pr-0 sm:pr-3">
            <div class="flex items-center">
                <p class="py-3 text-2xl font-medium text-primary">
                    {{ selectedLocations.length }}
                </p>
                <p class="ml-4 text-base font-medium text-black">
                    Selected Locations
                </p>
            </div>
        </div>
        <div class="w-full pl-0 text-left sm:pl-3">
            <button
                :disabled="! selectedLocations.length"
                class="px-8 text-sm font-bold rounded-r-lg btn py-18 text-black-40 bg-slate-300"
                type="button"
                @click="openSelectedLocations"
            >
                View All
            </button>
        </div>
    </div>
</template>

<script setup>
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import XLSX from 'xlsx';
import { route } from 'ziggy';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import { isEmpty } from 'lodash';
import { reactive } from 'vue';

const props = defineProps({
    selectedLocations: {
        type: Array,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    filePath: {
        type: String,
        default: null,
    },
    unmatchedLocations: {
        type: Array,
        default: null,
    },
    getRecordName: {
        type: String,
        default: null,
    },
    dataPropertyNames: {
        type: Array,
        default: () => []
    },
    matchedLocationsList: {
        type: Array,
        default: () => []
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    locationCodeUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    uploaded_file: null,
});

const emits = defineEmits([
    'display-selected-locations-modal',
    'display-unmatched-locations-modal',
    'update:column-details',
    'get-upload-file',
]);

const openSelectedLocations = () => {
    emits('display-selected-locations-modal');
};

const openUnmatchedLocations = () => {
    emits('display-unmatched-locations-modal');
};

const updateColumnsDetails = (promotionTypeDetails) => {
    emits('update:column-details', promotionTypeDetails);
};

const importRecords = (files) => {
    updateColumnsDetails({
        column_name: 'unmatchedLocations',
        value: [],
    });
    updateColumnsDetails({
        column_name: 'selectedLocations',
        value: [],
    });
    updateColumnsDetails({
        column_name: 'records',
        value: [],
    });

    const reader = new FileReader();

    const matchedLocationsList = [];
    const selectedRecords = [];
    const selectedLocations = [];

    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const records = JSON.parse(JSON.stringify(XLSX.utils.sheet_to_json(worksheet, {
            blankRows: false,
            defval: null,
        })).replace(/"\s+|\s+"/g, '"'));
        const importLocations = [];

        for (const key in records) {
            if (!records[key].code) {
                showErrorNotification('Location Code is required.');
                if (props.validationFieldName) {
                    document.getElementById(props.validationFieldName).value = '';
                }
                return;
            }

            if (!records[key].low_stock_alert_threshold) {
                showErrorNotification('Low stock alert threshold is required.');
                if (props.validationFieldName) {
                    document.getElementById(props.validationFieldName).value = '';
                }
                return;
            }

            importLocations.push(records[key].code.toString());
        }

        axios.post(route(props.locationCodeUrl), {
            import_store_codes: importLocations
        }).then((response) => {
            showSuccessNotification('The file processed successfully.');
            const locations = response.data.locations;
            const matchedLocations = [];
            for (const key in locations) {
                const matchStore = records.find(records => String(records.code) === String(locations[key].code));
                matchedLocationsList.push({ id: locations[key].id, low_stock_alert_threshold: matchStore.low_stock_alert_threshold });
                matchedLocations.push(locations[key].code);
            }

            if (props.matchedLocationsList) {
                updateColumnsDetails({
                    column_name: 'matchedLocationsList',
                    value: matchedLocationsList,
                });
            }

            const unmatchedLocations = importLocations.filter((code) => {
                return !matchedLocations.includes(code);
            });

            updateColumnsDetails({
                column_name: 'unmatchedLocations',
                value: unmatchedLocations,
            });

            if (unmatchedLocations.length) {
                openUnmatchedLocations();
            }

            records.forEach((record) => response.data.locations.forEach((location) => {
                if (record.code.toString() === location.code.toString()) {
                    const recordDetails = { id: location.id, name: location.name, code: location.code, low_stock_alert_threshold: record.low_stock_alert_threshold };

                    if (props.dataPropertyNames.length > 0) {
                        props.dataPropertyNames.forEach((column) => {
                            recordDetails[column] = record[column];
                        });
                    }

                    if (!isEmpty(props.getRecordName)) {
                        recordDetails[props.getRecordName] = record[props.getRecordName];
                    }

                    selectedLocations.push(recordDetails);

                    updateColumnsDetails({
                        column_name: 'selectedLocations',
                        value: selectedLocations,
                    });
                }
            }));

            if (!isEmpty(props.dataPropertyNames)) {
                if (unmatchedLocations.length > 0) {
                    for (const record in records) {
                        if (!unmatchedLocations.includes(records[record].code.toString())) {
                            selectedRecords.push(records[record]);
                        }
                    }
                } else {
                    for (const key in records) {
                        if (props.unmatchedLocations.length <= 0) {
                            selectedRecords.push(records[key]);
                        }
                    }
                }

                updateColumnsDetails({
                    column_name: 'records',
                    value: selectedRecords,
                });
            }
        });

        if (props.validationFieldName) {
            document.getElementById(props.validationFieldName).value = '';
        }

        emits('get-upload-file', state.uploaded_file);
    };

    reader.readAsArrayBuffer(files);
};
</script>
