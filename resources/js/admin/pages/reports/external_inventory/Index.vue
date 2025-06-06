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
        :selling-types="sellingTypes"
        local-storage-key="admin-external-inventory-reports-columns"
        external-inventory-reports-fetch-url="admin.external_inventory_reports.fetch"
        external-locations-url="admin.external_inventory_reports.get_stores_warehouses_and_regions"
        external-inventory-products-url="admin.external_inventory_reports.get_filtered_external_inventory_products"
        external-inventory-categories-url="admin.external_inventory_reports.get_filtered_external_inventory_categories"
        external-inventory-brands-url="admin.external_inventory_reports.get_filtered_external_inventory_brands"
        external-inventory-sizes-url="admin.external_inventory_reports.get_filtered_external_inventory_sizes"
        external-inventory-colors-url="admin.external_inventory_reports.get_filtered_external_inventory_colors"
        external-inventory-departments-url="admin.external_inventory_reports.get_filtered_external_inventory_departments"
        external-inventory-article-numbers-url="admin.external_inventory_reports.get_filtered_external_inventory_articleNumbers"
        external-inventory-tags-url="admin.external_inventory_reports.get_filtered_external_inventory_tags"
        external-inventory-styles-url="admin.external_inventory_reports.get_filtered_external_inventory_styles"
        external-inventory-attributes-url="admin.external_inventory_reports.get_filtered_external_inventory_attributes"
        @update:external-company-id="setExternalCompanyId"
        @update:stores="setStores"
        @update:warehouses="setWarehouses"
        @update:regions="setRegions"
        @clear-all="clearAll"
    >
        <div
            v-if="state.external_company_main_id"
            class="mt-3"
        >
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
            <TabPanel v-if="state.typeId === staticLocationTypes.store && state.external_company_main_id">
                <JMultiSelect
                    :selected-records="state.selectedLocations"
                    :records="state.stores"
                    validation-field-name="stores"
                    placeholder="Please select stores"
                    input-label="Stores"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="selectLocations"
                />
            </TabPanel>

            <TabPanel v-if="state.typeId === staticLocationTypes.warehouse && state.external_company_main_id">
                <JMultiSelect
                    :selected-records="state.selectedLocations"
                    :records="state.warehouses"
                    placeholder="Please select warehouse"
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
import JTabs from '@commonComponents/JTabs.vue';
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
    sellingTypes: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const state = reactive({
    stores: [],
    warehouses: [],
    regions: [],
    external_company_main_id: null,
    typeId: props.staticLocationTypes.store,
    location_ids: [],
    refreshTableData: Math.random(),
    isClear: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.location_ids = [];
    state.selectedLocations = null;
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

const setStores = (stores) => {
    state.stores = stores;
};

const setWarehouses = (warehouses) => {
    state.warehouses = warehouses;
};

const setRegions = (regions) => {
    state.regions = regions;
};

const clearAll = () => {
    state.stores = [];
    state.warehouses = [];
    state.external_company_main_id = null;
    state.typeId = props.staticLocationTypes.store;
    state.selectedLocations = null;
    state.location_ids = [];
    state.refreshTableData = Math.random();
    state.isClear = false;
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
