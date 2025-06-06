<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6 pl-5 pr-3 py-3 bg-slate-50">
        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-3">
            <FormInput
                :required="true"
                input-name="name"
                input-label="Promotion Name"
                :input-value="promotionForm.name"
                @update:input-value="updateColumnDetails('name', $event)"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-9">
            <div class="block lg:flex flex-col sm:flex-row -mx-3">
                <div class="w-full lg:w-1/2 px-3">
                    <JMultiSelect
                        :required="true"
                        :records="locations"
                        input-label="Locations"
                        validation-field-name="location_ids"
                        :selected-records="selectedLocations"
                        @update:selected-records="updateLocations"
                    />
                </div>

                <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
                    <PrimaryButton
                        type="button"
                        text="Select all"
                        class="w-auto sm:w-24 md:w-1/1"
                        @click="selectAllLocations"
                    />

                    <PrimaryButton
                        v-if="displayClearButton"
                        type="button"
                        text="Clear All"
                        class="w-auto sm:w-24 md:w-1/1 ml-2"
                        @click="clearAllLocations"
                    />
                </div>
            </div>
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <div class="font-medium text-lg py-5 border-b">
                Restrictions
            </div>

            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                <JSwitch
                    input-label="Applicable When Dream Price Is Applied"
                    :is-checked="promotionForm.dream_price_applicable"
                    @update:is-checked="updateColumnAllowWalkInMember('dream_price_applicable', $event)"
                />
            </div>
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-7 xl:col-span-7">
            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                <JSwitch
                    input-label="Allow Walk In Member?"
                    :is-checked="promotionForm.allow_walk_in_member"
                    @update:is-checked="updateColumnAllowWalkInMember('allow_walk_in_member', $event)"
                />
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                    <JSwitch
                        input-label="Allow Registered Member?"
                        :is-checked="promotionForm.allow_registered_member"
                        class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                        @update:is-checked="updateColumnIsMemberRequired('allow_registered_member', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                    <JMultiSelect
                        v-if="promotionForm.allow_registered_member"
                        :records="memberGroups"
                        input-label="Member Groups"
                        validation-field-name="member_groups_ids"
                        :selected-records="selectedMemberGroups"
                        @update:selected-records="updateMemberGroups"
                    />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                    <JSwitch
                        input-label="Allow Employees?"
                        :is-checked="promotionForm.allow_employee"
                        @update:is-checked="updateColumnOnlyForEmployee('allow_employee', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                    <JMultiSelect
                        v-if="promotionForm.allow_employee"
                        :records="employeeGroups"
                        input-label="Employee Groups"
                        validation-field-name="employee_groups_ids"
                        :selected-records="selectedEmployeeGroups"
                        @update:selected-records="updateEmployeeGroups"
                    />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                    <JSwitch
                        input-label="Is Membership Required?"
                        :is-checked="promotionForm.is_membership_required"
                        class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                        @update:is-checked="updateColumnIsMembershipRequired('is_membership_required', $event)"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                    <JMultiSelect
                        v-if="promotionForm.is_membership_required"
                        :records="memberships"
                        required="true"
                        input-label="Membership"
                        validation-field-name="membership_ids"
                        :selected-records="selectedMemberships"
                        @update:selected-records="updateMemberships"
                    />
                </div>
            </div>
            <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                >
                    <JSwitch
                        input-label="Is Available In Pos?"
                        :is-checked="promotionForm.is_available_in_pos"
                        @update:is-checked="updateIsAvailableInPos('is_available_in_pos', $event)"
                    />
                </div>

                <div
                    v-if="staticDetails.cart_type_as_per_amount === promotionForm.cart_wide_promotion_type_id"
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                >
                    <JSwitch
                        input-label="Is Available In Ecommerce?"
                        :is-checked="promotionForm.is_available_in_ecommerce"
                        @update:is-checked="updateIsAvailableInEcommerce('is_available_in_ecommerce', $event)"
                    />
                    <div
                        v-if="promotionForm.is_available_in_ecommerce"
                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                    >
                        <JMultiSelect
                            :records="saleChannels"
                            input-label="Sale Channels"
                            :required="true"
                            validation-field-name="sale_channel_ids"
                            :selected-records="selectedSaleChannels"
                            @update:selected-records="updateSaleChannels"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <PromoCode
                :promotion-usage-types="promotionUsageTypes"
                :promotion-single-usage="promotionSingleUsage"
                :promotion-form="promotionForm"
                @add-new-promo-code="addNewPromoCode"
                @clear-promo-codes="clearPromoCodes"
                @set-promo-codes="setPromoCodes"
                @update-promo-code-details="updatePromoCodeDetails"
                @update:column-details="updateColumnDetailsForPromoCode"
                @update:valid-promo-codes-via-upload="updateUploadColumnDetailsForPromoCode"
            />
        </div>
    </div>
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import PromoCode from '@adminPages/promotions/partials/PromoCode.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    memberGroups: {
        type: Array,
        required: true,
    },
    employeeGroups: {
        type: Array,
        required: true,
    },
    promotionForm: {
        type: Object,
        required: true,
    },
    selectedLocations: {
        type: Array,
        default: () => [],
    },
    selectedMemberGroups: {
        type: Array,
        default: () => [],
    },
    selectedEmployeeGroups: {
        type: Array,
        default: () => [],
    },
    displayClearButton: {
        type: Boolean,
        default: false,
    },
    promotionUsageTypes: {
        type: Array,
        required: true,
    },
    promotionSingleUsage: {
        type: Number,
        required: true
    },
    staticDetails: {
        type: Object,
        required: true
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    selectedSaleChannels: {
        type: Array,
        default: () => [],
    },
    memberships: {
        type: Array,
        required: true
    },
    selectedMemberships: {
        type: Array,
        default: () => [],
    },
});

const emits = defineEmits([
    'update:column-details',
    'update:selected-locations',
    'update:selected-member-groups',
    'update:selected-employee-groups',
    'update:selected-sale-channels',
    'add-new-promo-code',
    'clear-promo-codes',
    'set-promo-codes',
    'update-promo-code-details',
    'update-upload-column-details',
    'update:selected-memberships'
]);

const addNewPromoCode = () => {
    emits('add-new-promo-code');
};

const clearPromoCodes = () => {
    emits('clear-promo-codes');
};

const setPromoCodes = (promoCodes) => {
    emits('set-promo-codes', promoCodes);
};

const updatePromoCodeDetails = (promoCodeDetails) => {
    emits('update-promo-code-details', promoCodeDetails);
};

const updateUploadColumnDetailsForPromoCode = (uploadPromoCode) => {
    emits('update-upload-column-details', uploadPromoCode);
};

const updateColumnDetails = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const updateColumnDetailsForPromoCode = (data) => {
    emits('update:column-details', {
        column_name: data.column_name,
        value: data.value,
    });
};

const updateColumnIsMemberRequired = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });

    emits('update:selected-member-groups', {});
};

const updateColumnIsMembershipRequired = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });

    emits('update:selected-memberships', {});
};

const updateColumnOnlyForEmployee = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });

    emits('update:selected-employee-groups', {});
};

const updateColumnAllowWalkInMember = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const updateIsAvailableInPos = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const updateIsAvailableInEcommerce = (columnName, data) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });

    emits('update:selected-sale-channels', null);
};

const updateLocations = (locations) => {
    emits('update:selected-locations', {
        locations
    });
};

const updateMemberGroups = (memberGroups) => {
    emits('update:selected-member-groups', {
        memberGroups
    });
};

const updateMemberships = (memberships) => {
    emits('update:selected-memberships', {
        memberships
    });
};

const updateEmployeeGroups = (employeeGroups) => {
    emits('update:selected-employee-groups', {
        employeeGroups
    });
};

const selectAllLocations = () => {
    const locations = props.locations;
    emits('update:selected-locations', { locations });
};

const updateSaleChannels = (saleChannels) => {
    emits('update:selected-sale-channels', {
        saleChannels
    });
};

const clearAllLocations = () => {
    const locations = [];
    emits('update:selected-locations', { locations });
};

</script>
