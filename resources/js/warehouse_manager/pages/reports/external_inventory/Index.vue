<template>
    <ExternalInventoryReport
        :external-companies="externalCompanies"
        :stock-types="stockTypes"
        :export-permission="exportPermission"
        :product-statuses="productStatuses"
        location-type="warehouse"
        :location-ids="state.location_ids"
        :refresh-table-data="state.refreshTableData"
        :is-clear="state.isClear"
        :regions="state.regions"
        local-storage-key="warehouse_manager-external-inventory-reports-columns"
        external-inventory-reports-fetch-url="warehouse_manager.external_inventory_reports.fetch"
        external-locations-url="warehouse_manager.external_inventory_reports.get_warehouses_and_regions"
        external-inventory-products-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_products"
        external-inventory-categories-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_categories"
        external-inventory-brands-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_brands"
        external-inventory-sizes-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_sizes"
        external-inventory-colors-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_colors"
        external-inventory-departments-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_departments"
        external-inventory-article-numbers-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_articleNumbers"
        external-inventory-tags-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_tags"
        external-inventory-styles-url="warehouse_manager.external_inventory_reports.get_filtered_external_inventory_styles"
        @update:external-company-id="setExternalCompanyId"
        @update:warehouses="setWarehouses"
        @update:regions="setRegions"
        @clear-all="clearAll"
    >
        <div>
            <TabPanel v-if="state.external_company_main_id">
                <JMultiSelect
                    :selected-records="state.selectedLocations"
                    :records="state.warehouses"
                    validation-field-name="warehouses"
                    placeholder="Please select warehouses"
                    input-label="Warehouses"
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
    warehouses: [],
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

const setWarehouses = (warehouses) => {
    state.warehouses = warehouses;
};

const setRegions = (regions) => {
    state.regions = regions;
};

const clearAll = () => {
    state.warehouses = [];
    state.external_company_main_id = null;
    state.selectedLocations = null;
    state.location_ids = [];
    state.refreshTableData = Math.random();
    state.isClear = false;
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
