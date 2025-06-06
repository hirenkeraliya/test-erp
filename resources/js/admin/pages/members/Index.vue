<template>
    <PageTitle title="Members" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Members
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.members.create')">
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
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="memberStatuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
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
                    :selected-record="state.parameters.preferred_day"
                    :records="state.daySelections"
                    validation-field-name="preferred_day"
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
        :fetch-url="route('admin.members.fetch')"
        :columns="state.columns"
        :additional-query-params="state.parameters"
        :refresh-table-data="state.refreshTableData"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :confirmation-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        search-title="Search by email, first name, mobile number, or card number"
    >
        <template #update_loyalty_points="record">
            <div class="flex item-center">
                <Tippy
                    content="Loyalty Point"
                    class="cursor-pointer"
                    @click="openUpdateLoyaltyPointsModal(record.item)"
                >
                    <PlusCircle />
                </Tippy>

                <Tippy
                    content="Loyalty Point History "
                    class="cursor-pointer ml-2"
                    @click="openLoyaltyPointsHistoryModal(record.item.id, record.item.loyalty_points)"
                >
                    <History />
                </Tippy>
            </div>
        </template>

        <template #membership="record">
            {{ record.item.membership_id ? record.item.membership.name : 'N/A' }}
        </template>

        <template #last_purchase_date="record">
            <span v-html="record.item.last_purchase_date" />
        </template>

        <template #first_name="data">
            {{ data.item.first_name }}

            <JBadge
                v-if="data.item.employee_id"
                label="Employee"
                type="warning"
            />
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

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id)"
                />
            </div>
        </template>

        <template #action="data">
            <div class="flex items-center justify-center">
                <div
                    v-if="data.item.mobile_number !== staticMembers && data.item.status"
                    class="flex justify-center items-center"
                >
                    <Link
                        v-if="!data.item.is_email_verified && data.item.email"
                        class="flex items-center mr-8"
                        :href="route('admin.members.resend_verification_email', data.item.id)"
                    >
                        <Tippy
                            :content="'Resend mail'"
                        >
                            <Mail class="w-4 h-5 mr-2" />
                        </Tippy>
                    </Link>

                    <Dropdown
                        v-slot="{ dismiss }"
                        class="dropdown absolute"
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
                                    class="flex items-center mr-3"
                                    @click="editMember(data.item.id, dismiss)"
                                >
                                    <CheckSquare class="w-4 h-4 mr-2" />
                                    Edit
                                </DropdownItem>

                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="memberDetails(data.item.id, dismiss)"
                                >
                                    <FileText class="w-4 h-4 mr-2" />
                                    Details
                                </DropdownItem>

                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="openModalForMerge(data.item.id)"
                                >
                                    <GitMerge class="w-4 h-4 mr-1" />
                                    Merge
                                </DropdownItem>

                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="deleteRecord(data.item.id, dismiss)"
                                >
                                    <Archive class="w-4 h-4 mr-1" />
                                    Delete
                                </DropdownItem>

                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="showMemberAddressDetailsModal(data.item.id)"
                                >
                                    <Contact class="w-4 h-4 mr-1" />
                                    Member Addresses
                                </DropdownItem>
                            </DropdownContent>
                        </DropdownMenu>
                    </Dropdown>
                </div>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayMembersFilter = !state.displayMembersFilter"
                />
            </p>
        </template>
    </JTable>

    <Modal
        size="modal-lg"
        :show="state.openModalForMerge"
        @hidden="hideMergeModal()"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Merge Member
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideMergeModal()"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <InfoAlert
                color="primary"
                class="mb-3 mt-5"
            >
                1. The member details displayed with a red background will be deleted, while those shown with a green background will be updated. Please ensure you take a backup before proceeding.
                <br>
                2. The old member's address will be permanently deleted during the merge, while the new member's address will remain unchanged.
                <br>
                3. Loyalty points, vouchers, and spending records will be updated after the merge. The loyalty points from both the old and new accounts will be combined, as well as the vouchers and spending records.
                <br>
                4. If the selected new member is an active employee of the company, the merge will not proceed.
                <br>
                5. If the old member is an active employee and the new member is not, the new member will become an active employee after the merge.
            </InfoAlert>

            <div>
                <div class="mb-3">
                    <FormAjaxSelect
                        :selected-record="state.mergeMemberSelection"
                        :search-records="searchMembers"
                        placeholder="Enter Mobile Number,Email,Card Number or Name to search..."
                        input-label="Member"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateMember"
                    />
                </div>

                <div class="flex justify-between overflow-auto">
                    <div v-if="state.oldMemberDetails && state.newMemberDetails">
                        <table class="table mt-3 text-center">
                            <thead>
                                <tr>
                                    <th>Mobile Number</th>
                                    <th class="text-red-800 bg-red-50">
                                        {{ state.oldMemberDetails.mobile_number }}
                                    </th>
                                    <th class="text-green-800 bg-green-50">
                                        {{ state.newMemberDetails.mobile_number }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(column, key) in getColumnForProductMergeDetails()"
                                    :key="key"
                                >
                                    <td>{{ getFormattedColumnName(column) }}</td>
                                    <td class="text-red-800 bg-red-50">
                                        <span v-if="column === 'address'">
                                            <ul
                                                v-for="(address, index) in state.oldMemberDetails[column]"
                                                :key="index"
                                                class="list-outside list-disc"
                                            >
                                                <li class="border-b p-4">
                                                    <div>
                                                        <p>Name: {{ address.name }}</p>
                                                        <p>Contact Mobile Number: {{ address.contact_mobile_number }}</p>
                                                        <p>Contact Email: {{ address.contact_email }}</p>
                                                        <p>Address Line 1: {{ address.address_line_1 }}</p>
                                                        <p>Address Line 2: {{ address.address_line_2 }}</p>
                                                        <p>Country: {{ address.country ? address.country.name : 'N/A' }}</p>
                                                        <p>State: {{ address.state ? address.state.name : 'N/A' }}</p>
                                                        <p>City: {{ address.city_details ? address.city_details.name : (address.city_name || 'N/A') }}</p>
                                                        <p>Area Code: {{ address.area_code }}</p>
                                                        <p>Is Primary: {{ address.is_primary }}</p>
                                                    </div>
                                                </li>
                                            </ul>
                                        </span>

                                        <span v-else>
                                            {{ state.oldMemberDetails[column] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-green-800 bg-green-50">
                                        <span v-if="column === 'address'">
                                            <ul
                                                v-for="(address, index) in state.newMemberDetails[column]"
                                                :key="index"
                                                class="list-outside list-disc"
                                            >
                                                <li class="border-b p-4">
                                                    <div>
                                                        <p>Name: {{ address.name }}</p>
                                                        <p>Contact Mobile Number: {{ address.contact_mobile_number }}</p>
                                                        <p>Contact Email: {{ address.contact_email }}</p>
                                                        <p>Address Line 1: {{ address.address_line_1 }}</p>
                                                        <p>Address Line 2: {{ address.address_line_2 }}</p>
                                                        <p>Country: {{ address.country ? address.country.name : 'N/A' }}</p>
                                                        <p>State: {{ address.state ? address.state.name : 'N/A' }}</p>
                                                        <p>City: {{ address.city_details ? address.city_details.name : (address.city_name || 'N/A') }}</p>
                                                        <p>Area Code: {{ address.area_code }}</p>
                                                        <p>Is Primary: {{ address.is_primary }}</p>
                                                    </div>
                                                </li>
                                            </ul>
                                        </span>

                                        <span v-else>
                                            {{ state.newMemberDetails[column] ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5">
                    <PrimaryButton
                        type="submit"
                        text="Merge"
                        class="w-24"
                        @click="mergeAndDeleteMemberId"
                    />
                </div>
            </div>
        </ModalBody>
    </Modal>

    <UpdateLoyaltyPoints
        v-if="state.displayUpdateLoyaltyPointsModal"
        :modal-show="state.displayUpdateLoyaltyPointsModal"
        :member="state.member"
        @close-modal="closeModal"
    />

    <MemberLoyaltyPointsHistory
        v-if="state.displayLoyaltyPointsHistoryModal"
        :modal-show="state.displayLoyaltyPointsHistoryModal"
        :columns-for-loyalty-point-history="state.columnsForLoyaltyPointHistory"
        :loyalty-points-history="state.loyaltyPointsHistory"
        :member-loyalty-point="state.memberLoyaltyPoint"
        @close-modal="closeModal"
    />

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
import MemberLoyaltyPointsHistory from '@adminPages/members/partials/MemberLoyaltyPointsHistory.vue';
import UpdateLoyaltyPoints from '@adminPages/members/partials/UpdateLoyaltyPoints.vue';
import UpdateMemberAddresses from '@adminPages/members/partials/UpdateMemberAddresses.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JTable from '@commonComponents/JTable.vue';
import JBadge from '@commonComponents/JBadge.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import { confirmDialogBox, showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Archive, CheckSquare, Contact, FileText, GitMerge, History, MoreHorizontal, PlusCircle, TriangleAlert, X, Mail } from 'lucide-vue-next';
import { reactive,onMounted } from 'vue';
import { route } from 'ziggy';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { RefreshCw } from 'lucide-vue-next';

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
    staticMembers: {
        type: String,
        required: true,
        default: '',
    },
    memberStatuses: {
        type: Array,
        required: true,
    },
    statusAll: {
        type: Number,
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
    exportPermission: {
        type: String,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    displayUpdateLoyaltyPointsModal: false,
    displayLoyaltyPointsHistoryModal: false,
    displayMemberSaleDetailModal: false,
    displayMemberAddressDetailModal: false,
    member: null,
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
            key: 'update_loyalty_points',
            label: 'Loyalty Points'
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
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        },
        {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    columnsForLoyaltyPointHistory: [
        {
            key: 'description',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'module',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'points',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'closing_loyalty_points_balance',
            label: 'Closing Balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'happened_at',
            label: 'Date',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
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
        status: props.statusAll,
    },
    openModalForMerge: false,
    displayMembersFilter: false,
    loyaltyPointsHistory: null,
    memberLoyaltyPoint: null,
    memberId: null,
    memberAddresses: null,
    oldMemberId: null,
    newMemberId: null,
    oldMemberDetails: null,
    newMemberDetails: null,
    mergeMemberSelection: null,
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
    disableRefreshButton: props.hasPendingSyncTransaction,
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
    state.parameters.condition_operator_type_id = null;
    state.parameters.purchase_value = null;
    state.parameters.purchase_filter_type_id = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateColorId = (colorId) => {
    state.parameters.color_id = colorId;
    refreshTable();
};

const updateSizeId = (sizeId) => {
    state.parameters.size_id = sizeId;
    refreshTable();
};

const updateCategoryId = (categoryId) => {
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

const openUpdateLoyaltyPointsModal = (data) => {
    state.displayUpdateLoyaltyPointsModal = true;
    state.member = data;
};

const openLoyaltyPointsHistoryModal = (memberId, memberLoyaltyPoint) => {
    state.loyaltyPointsHistory = null;
    state.memberLoyaltyPoint = memberLoyaltyPoint;

    axios.get(route('admin.members.loyalty_points_history', memberId))
        .then((response) => {
            state.loyaltyPointsHistory = response.data.loyalty_points_history;
            state.displayLoyaltyPointsHistoryModal = true;
        });
};

const showMemberAddressDetailsModal = (memberId) => {
    state.memberId = memberId;
    axios.get(route('admin.members.fetch_member_addresses', memberId))
        .then((response) => {
            state.memberAddresses = response.data.member_addresses;
            state.displayMemberAddressDetailModal = true;
        });
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const closeModal = (isTableNeedToBeRefreshed) => {
    state.displayUpdateLoyaltyPointsModal = false;
    state.displayLoyaltyPointsHistoryModal = false;
    state.displayMemberSaleDetailModal = false;
    state.displayMemberAddressDetailModal = false;
    if (isTableNeedToBeRefreshed) {
        refreshTable();
    }
};

const exportCsvRecords = (params) => {
    return axios.get(route('admin.members.check_member_export_limit', params))
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
    return axios.get(route('admin.members.check_member_export_limit', params))
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
    printReport(route('admin.members.print_members', params), props.exportPermission);
};

const setStatus = (memberId) => {
    axios.post(route('admin.members.change_status'), { memberId }).then(() => {
        showSuccessNotification('Status updated successfully.');
        refreshTable();
    }).catch((error) => {
        if (error.message) {
            showErrorNotification(error.message);
        }
    });
};

const updateSelectedStatus = (status) => {
    if (status === null) {
        state.parameters.status = props.statusAll;
    }

    if (status !== null) {
        state.parameters.status = status;
    }
    refreshTable();
};
const editMember = (memberId, dismiss) => {
    router.get(route('admin.members.edit', memberId));
    dismiss();
};

const memberDetails = (memberId, dismiss) => {
    router.get(route('admin.members.member_details', memberId));
    dismiss();
};

const deleteRecord = (memberId) => {
    const message = 'Are you sure want to delete?';
    confirmDialogBox(message, () => {
        router.post(route('admin.members.delete', memberId), {}, {
            onSuccess: () => refreshTable()
        });
    });
};

const openModalForMerge = (oldMemberId) => {
    axios.get(route('admin.members.fetch_member_details_for_merge', oldMemberId)).then((response) => {
        state.oldMemberDetails = response.data.member;
    });
    state.oldMemberId = oldMemberId;
    state.openModalForMerge = true;
    state.newMemberDetails = null;
};

const mergeAndDeleteMemberId = () => {
    axios.post(route('admin.members.merge_members', [
        state.oldMemberDetails,
        state.newMemberId
    ]))
        .then((response) => {
            showSuccessNotification(response.data.message);
            state.openModalForMerge = false;
            state.oldMemberDetails = null;
            state.newMemberId = null;
            refreshTable();
        })
        .catch((error) => {
            showErrorNotification(error.response.data.message);
        });
};

const hideMergeModal = () => {
    state.openModalForMerge = false;
    state.newMemberId = null;
    state.mergeMemberSelection = null;
};

const getColumnForProductMergeDetails = () => {
    const copiedObject = { ...state.oldMemberDetails };

    const removeIdColumns = ['id', 'mobile_number'];

    removeIdColumns.forEach(key => delete copiedObject[key]);

    return Object.keys(copiedObject);
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };

    axios.get(route('admin.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const updateMember = (selectMember) => {
    state.mergeMemberSelection = selectMember;
    if (selectMember !== null) {
        state.newMemberId = selectMember.id;
        axios.get(route('admin.members.fetch_member_details_for_merge', state.newMemberId)).then((response) => {
            state.newMemberDetails = response.data.member;
        });
    }
};

const getFormattedColumnName = (columnName) => {
    return columnName.replace(/_/g, ' ')
        .toLowerCase()
        .replace(/(?:^|\s)\w/g, match => match.toUpperCase());
};

const searchProducts = (searchText, componentState) => {
    axios.get(route('admin.get_filtered_inventory_products'), {
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

const syncData = (id, dismiss) => {
    axios.get(route('admin.members.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};

onMounted(() => {
    const daysInMonth = 31;
    for (let i = 1; i <= daysInMonth; i++) {
        state.dateSelections.push({
            id: i,
            name: i,
        });
    }
});
</script>
