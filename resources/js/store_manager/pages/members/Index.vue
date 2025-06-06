<template>
    <PageTitle title="Members" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium">
            Members
        </h2>

        <div class="flex mt-4 sm:mt-0 ml-auto">
            <Link :href="route('store_manager.members.create')">
                <PrimaryButton
                    text="Add New Member"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayMembersFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    placeholder="Please select location"
                    multi="true"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateLocations"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.memberships"
                    :records="memberships"
                    placeholder="Please select membership"
                    multi="true"
                    input-label="Memberships"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateMemberships"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.member_groups"
                    :records="memberGroups"
                    placeholder="Please select member group"
                    multi="true"
                    input-label="Member Groups"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateMemberGroups"
                />
            </div>

            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
                />
            </div>

            <div>
                <FormAjaxSelect
                    input-label="Products"
                    :selected-record="state.selectedProduct"
                    :search-records="searchProducts"
                    placeholder="Product Name/UPC to search..."
                    @update:selected-record="selectProduct"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.preference_id"
                    :records="preferences"
                    validation-field-name="preference_id"
                    input-label="Preference"
                    placeholder="Please select preference"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="getPreferenceId($event)"
                />
            </div>

            <div v-if="preferencesStaticUse.preferredColor === state.parameters.preference_id">
                <FormSelectBox
                    :selected-record="state.parameters.color_id"
                    :records="colors"
                    validation-field-name="color_id"
                    input-label="Color"
                    placeholder="Please select color"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateColorId($event)"
                />
            </div>

            <div v-if="preferencesStaticUse.preferredSize === state.parameters.preference_id">
                <FormSelectBox
                    :selected-record="state.parameters.size_id"
                    :records="sizes"
                    validation-field-name="size_id"
                    input-label="Size"
                    placeholder="Please select size"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateSizeId($event)"
                />
            </div>

            <div v-if="preferencesStaticUse.preferredCategory === state.parameters.preference_id">
                <FormSelectBox
                    :selected-record="state.parameters.category_id"
                    :records="categories"
                    validation-field-name="category_id"
                    input-label="Category"
                    placeholder="Please select category"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateCategoryId($event)"
                />
            </div>

            <div v-if="preferencesStaticUse.preferredDate === state.parameters.preference_id">
                <FormSelectBox
                    :selected-record="state.parameters.preferred_date"
                    :records="state.dateSelections"
                    validation-field-name="preferred_date"
                    input-label="Date"
                    placeholder="Please select date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updatePreferredDate($event)"
                />
            </div>

            <div v-if="preferencesStaticUse.preferredDay === state.parameters.preference_id">
                <FormSelectBox
                    :selected-record="state.parameters.preferred_date"
                    :records="state.daySelections"
                    validation-field-name="preferred_date"
                    input-label="Day"
                    placeholder="Please select day"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updatePreferredDay($event)"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.purchase_filter_type_id"
                    :records="purchaseFilterTypes"
                    validation-field-name="purchase_filter_type_id"
                    input-label="Purchase Filters"
                    placeholder="Please select purchase filter"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="getPurchaseFilterTypeId($event)"
                />
            </div>

            <div v-if="state.parameters.purchase_filter_type_id">
                <FormSelectBox
                    :selected-record="state.parameters.condition_operator_type_id"
                    :records="conditionOperatorTypes"
                    validation-field-name="condition_operator_type_id"
                    input-label="Condition"
                    placeholder="Please select condition"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateConditionOperatorTypeId($event)"
                />
            </div>

            <div v-if="state.parameters.condition_operator_type_id">
                <FormInput
                    v-model:input-value="state.parameters.purchase_value"
                    input-name="purchase_value"
                    input-label="Purchase Value"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    type="number"
                    @update:input-value="updatePurchaseValue($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route('store_manager.members.fetch')"
        :columns="state.columns"
        :additional-query-params="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        search-title="Search by email, first name, mobile number, or card number"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('store_manager.members.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('store_manager.members.member_details', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Details
                </Link>

                <Tippy
                    content="Addresses"
                    class="cursor-pointer ml-2"
                    @click="showMemberAddressDetailsModal(data.item.id)"
                >
                    <Contact />
                </Tippy>
            </div>
        </template>

        <template #membership="record">
            {{ record.item.membership_id ? record.item.membership.name : 'N/A' }}
        </template>

        <template #last_purchase_date="record">
            <span v-html="record.item.last_purchase_date" />
        </template>

        <template #extra-header-data>
            <a
                target="_blank"
                :href="route('store_manager.members.member_registration')"
            >
                <p class="mr-2">
                    <PrimaryButton
                        text="Member Registration"
                        class="shadow-md"
                    />
                </p>
            </a>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayMembersFilter = !state.displayMembersFilter"
                />
            </p>
        </template>
    </JTable>

    <UpdateMemberAddresses
        v-if="state.displayMemberAddressDetailModal"
        :modal-show="state.displayMemberAddressDetailModal"
        :member-addresses="state.memberAddresses"
        :member-id="state.memberId"
        :countries="countries"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive,onMounted } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Contact } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import UpdateMemberAddresses from '@storeManagerPages/members/partials/UpdateMemberAddresses.vue';
import axios from 'axios';
import { showSuccessNotification } from '@commonServices/notifier';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormInput from '@commonComponents/FormInput.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },

    memberships: {
        type: Array,
        required: true,
    },
    memberGroups: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    preferences: {
        type: Array,
        required: true,
    },
    preferencesStaticUse: {
        type: Object,
        required: true,
    },
    purchaseFilterTypes: {
        type: Array,
        required: true,
    },
    conditionOperatorTypes: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    colors: {
        type: Array,
        required: true,
    },
    sizes: {
        type: Array,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
});
const state = reactive({
    columns: [
        {
            key: 'title',
        },
        {
            key: 'first_name',
            sortable: true
        },
        {
            key: 'email',
            sortable: true
        },
        {
            key: 'mobile_number',
            sortable: true
        },
        {
            key: 'type',
        },
        {
            key: 'card_number',
        },
        {
            key: 'last_purchase_date',
        },
        {
            key: 'membership',
        },
        {
            key: 'created_at'
        },
        {
            key: 'updated_at'
        },
        {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    parameters: {
        locations: [],
        location_ids: [],
        memberships: [],
        membership_ids: [],
        member_groups: [],
        member_group_ids: [],
        date_range: null,
        preference_id: null,
        color_id: null,
        size_id: null,
        category_id: null,
        preferred_date: null,
        preferred_day: null,
        product_id: null,
        purchase_filter_type_id: null,
        condition_operator_type_id: null,
        purchase_value: null,
    },
    displayMembersFilter: false,
    displayMemberAddressDetailModal: false,
    memberAddresses: null,
    memberId: null,
    selectedProduct: null,
    daySelections: [
        { id: 'Monday', name: 'Monday' },
        { id: 'Tuesday', name: 'Tuesday' },
        { id: 'Wednesday', name: 'Wednesday' },
        { id: 'Thursday', name: 'Thursday' },
        { id: 'Friday', name: 'Friday' },
        { id: 'Saturday', name: 'Saturday' },
        { id: 'Sunday', name: 'Sunday' },
    ],
    dateSelections: [],
});

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
    refreshTable();
};

const updateMemberships = (memberships) => {
    state.memberships = memberships;
    const membershipIds = memberships.map((location) => {
        return location.id;
    });
    state.parameters.membership_ids = membershipIds;
    refreshTable();
};

const updateMemberGroups = (memberGroups) => {
    state.member_groups = memberGroups;
    const memberGroupIds = memberGroups.map((memberGroup) => {
        return memberGroup.id;
    });
    state.parameters.member_group_ids = memberGroupIds;
    refreshTable();
};

const clearAll = () => {
    state.locations = null;
    state.memberships = null;
    state.parameters.location_ids = [];
    state.parameters.membership_ids = [];
    state.parameters.date_range = null;
    state.parameters.preference_id = null;
    state.parameters.color_id = null;
    state.parameters.size_id = null;
    state.parameters.category_id = null;
    state.parameters.preferred_date = null;
    state.parameters.preferred_day = null;
    state.parameters.product_id = null;
    state.selectedProduct = null;
    state.parameters.purchase_filter_type_id = null;
    state.parameters.condition_operator_type_id = null;
    state.parameters.purchase_value = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateColorId = (colorId) => {
    if (!colorId) {
        return;
    }

    state.parameters.color_id = colorId;
    refreshTable();
};

const updateSizeId = (sizeId) => {
    if (!sizeId) {
        return;
    }

    state.parameters.size_id = sizeId;
    refreshTable();
};

const updateCategoryId = (categoryId) => {
    if (!categoryId) {
        return;
    }

    state.parameters.category_id = categoryId;
    refreshTable();
};

const updatePreferredDate = (date) => {
    state.parameters.preferred_date = date;
    refreshTable();
};

const updatePreferredDay = (day) => {
    state.parameters.preferred_day = day;
    refreshTable();
};


const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const closeModal = () => {
    state.displayMemberAddressDetailModal = false;
};

const showMemberAddressDetailsModal = (memberId) => {
    state.memberId = memberId;
    axios.get(route('store_manager.members.fetch_member_addresses', memberId))
        .then((response) => {
            state.memberAddresses = response.data.member_addresses;
            state.displayMemberAddressDetailModal = true;
        });
};

const exportCsvRecords = (params) => {
    return axios.get(route('store_manager.members.check_member_export_limit', params))
        .then((response) => {
            if (! response.data.exceeds_limit) {
                return exportRecords(
                    'export-members/',
                    'members.csv',
                    params,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
        });
};

const exportExcelRecords = (params) => {
    return axios.get(route('store_manager.members.check_member_export_limit', params))
        .then((response) => {
            if (! response.data.exceeds_limit) {
                return exportRecords(
                    'export-members/',
                    'members.xlsx',
                    params,
                    props.exportPermission
                );
            }

            showSuccessNotification(response.data.message);
        });
};

const exportPDFRecords = (params) => {
    printReport(route('store_manager.members.print_members', params), props.exportPermission);
};

const searchProducts = (searchText, componentState) => {
    axios.get(route('store_manager.get_filtered_inventory_products'), {
        params: {
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const selectProduct = (selectedProduct) => {
    state.selectedProduct = selectedProduct;
    state.parameters.product_id = selectedProduct !== null ? selectedProduct.id : null;
    refreshTable();
};

const getPreferenceId = (preferenceId) => {

    state.parameters.color_id = null;
    state.parameters.size_id = null;
    state.parameters.category_id = null;
    state.parameters.preferred_date = null;
    state.parameters.preferred_day = null;

    state.parameters.preference_id = preferenceId;
};

const getPurchaseFilterTypeId = (purchaseOrderFilterTypeId) => {
    state.parameters.condition_operator_type_id = null;
    state.parameters.purchase_value = null;
    state.parameters.purchase_filter_type_id = purchaseOrderFilterTypeId;
};

const updateConditionOperatorTypeId = (conditionOperatorTypeId) => {
    state.parameters.purchase_value = null;
    state.parameters.condition_operator_type_id = conditionOperatorTypeId;
    refreshTable();
};

const updatePurchaseValue = (value) => {
    state.parameters.purchase_value = value;
    refreshTable();
};

onMounted(() => {
    const maxDays = 31;
    for (let i = 1; i <= maxDays; i++) {
        state.dateSelections.push({
            id: i,
            name: i,
        });
    }
});
</script>
