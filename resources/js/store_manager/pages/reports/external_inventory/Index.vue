<template>
    <ExternalInventoryReport
        :external-companies="externalCompanies"
        :stock-types="stockTypes"
        :export-permission="exportPermission"
        :product-statuses="productStatuses"
        location-type="store"
        :location-ids="state.location_ids"
        :refresh-table-data="state.refreshTableData"
        :is-clear="state.isClear"
        :regions="state.regions"
        local-storage-key="store_manager-external-inventory-reports-columns"
        external-inventory-reports-fetch-url="store_manager.external_inventory_reports.fetch"
        external-locations-url="store_manager.external_inventory_reports.get_stores_and_regions"
        external-inventory-products-url="store_manager.external_inventory_reports.get_filtered_external_inventory_products"
        external-inventory-categories-url="store_manager.external_inventory_reports.get_filtered_external_inventory_categories"
        external-inventory-brands-url="store_manager.external_inventory_reports.get_filtered_external_inventory_brands"
        external-inventory-sizes-url="store_manager.external_inventory_reports.get_filtered_external_inventory_sizes"
        external-inventory-colors-url="store_manager.external_inventory_reports.get_filtered_external_inventory_colors"
        external-inventory-departments-url="store_manager.external_inventory_reports.get_filtered_external_inventory_departments"
        external-inventory-article-numbers-url="store_manager.external_inventory_reports.get_filtered_external_inventory_articleNumbers"
        external-inventory-tags-url="store_manager.external_inventory_reports.get_filtered_external_inventory_tags"
        external-inventory-styles-url="store_manager.external_inventory_reports.get_filtered_external_inventory_styles"
        @update:external-company-id="setExternalCompanyId"
        @update:stores="setLocations"
        @update:regions="setRegions"
        @clear-all="clearAll"
    >
        <div>
            <TabPanel v-if="state.external_company_main_id">
                <JMultiSelect
                    :selected-records="state.selectedLocations"
                    :records="state.locations"
                    validation-field-name="locations"
                    placeholder="Please select locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="selectLocations"
                />
            </TabPanel>
        </div>
    </ExternalInventoryReport>
</template>

<script setup>
import { TabPanel } from '@commonVendor/tab';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { reactive } from 'vue';
import ExternalInventoryReport from '@commonPages/ExternalInventoryReport.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    externalCompanies: {
        type: Array,
        required: true,
    },
    stockTypes: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    productStatuses: {
        type: Array,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const state = reactive({
    locations: [],
    regions: [],
    external_company_main_id: null,
    location_ids: [],
    refreshTableData: Math.random(),
    isClear: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const selectLocations = (selectedLocations) => {
    state.selectedLocations = selectedLocations;
    state.location_ids = selectedLocations.map(function (location) {
        return location.id;
    });

    refreshTable();

    state.isClear = true;
};

const setExternalCompanyId = (externalCompanyId) => {
    state.external_company_main_id = externalCompanyId;
};

const setLocations = (locations) => {
    state.locations = locations;
};

const setRegions = (regions) => {
    state.regions = regions;
};

const clearAll = () => {
    state.locations = [];
    state.external_company_main_id = null;
    state.selectedLocations = null;
    state.location_ids = [];
    state.refreshTableData = Math.random();
    state.isClear = false;
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
