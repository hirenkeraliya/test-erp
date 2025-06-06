<template>
    <PageTitle :title="dreamPrice ? 'Edit Price Markdown' : 'Add Price Markdown'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Price Markdown
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ dreamPrice ? 'Edit' : 'Add' }} Price Markdown
                    </h2>
                </div>
                <form @submit.prevent="saveDreamPrice();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="dreamPriceForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="dreamPriceForm.start_date"
                                    input-label="Start Date"
                                    validation-field-name="start_date"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="dreamPriceForm.end_date"
                                    input-label="End Date"
                                    validation-field-name="end_date"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                <div class="block md:flex flex-col sm:flex-row -mx-3">
                                    <div class="w-full md:w-1/2 px-3">
                                        <JMultiSelect
                                            v-model:selected-records="dreamPriceForm.locations"
                                            :records="locations"
                                            input-label="Locations"
                                            :required="true"
                                            validation-field-name="location_ids"
                                            title="This dream price (Promo price) will be eligible for selected locations only."
                                            class="w-full"
                                        />
                                    </div>

                                    <div class="w-full md:w-1/2 px-3 mt-2 sm:mt-2 md:mt-8">
                                        <PrimaryButton
                                            type="button"
                                            text="Select all"
                                            class="w-24 md:w-1/1"
                                            @click="selectAllLocations"
                                        />

                                        <PrimaryButton
                                            v-if="dreamPriceForm.locations.length > 0"
                                            type="button"
                                            text="Clear All"
                                            class="w-24 md:w-1/1 ml-2"
                                            @click="clearAllLocations"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3">
                                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                        <JSwitch
                                            input-label="Allow Registered Member?"
                                            :is-checked="dreamPriceForm.allow_registered_member"
                                            class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                            @update:is-checked="updateColumnIsMemberRequired('allow_registered_member', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                        <JMultiSelect
                                            v-if="dreamPriceForm.allow_registered_member"
                                            :records="memberGroups"
                                            input-label="Member Groups"
                                            validation-field-name="member_groups_ids"
                                            :selected-records="dreamPriceForm.member_groups"
                                            @update:selected-records="updateSelectedMemberGroups"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3">
                                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                        <JSwitch
                                            input-label="Allow Employees?"
                                            :is-checked="dreamPriceForm.allow_employee"
                                            class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                            @update:is-checked="updateColumnOnlyForEmployee('allow_employee', $event)"
                                        />
                                    </div>
                                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                        <JMultiSelect
                                            v-if="dreamPriceForm.allow_employee"
                                            :records="employeeGroups"
                                            input-label="Employee Groups"
                                            validation-field-name="employee_groups_ids"
                                            :selected-records="dreamPriceForm.employee_groups"
                                            @update:selected-records="updateSelectedEmployeeGroups"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                <JSwitch
                                    input-label="Allow Walk In Member?"
                                    :is-checked="dreamPriceForm.allow_walk_in_member"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                    @update:is-checked="updateColumnAllowWalkInMember('allow_walk_in_member', $event)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                <JSwitch
                                    input-label="Is Available In Pos?"
                                    :is-checked="dreamPriceForm.is_available_in_pos"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                    @update:is-checked="updateIsAvailableInPos('is_available_in_pos', $event)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3">
                                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                        <JSwitch
                                            input-label="Is Available In Ecommerce?"
                                            :is-checked="dreamPriceForm.is_available_in_ecommerce"
                                            class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                            @update:is-checked="updateIsAvailableInEcommerce('is_available_in_ecommerce', $event)"
                                        />
                                    </div>

                                    <div
                                        v-if="dreamPriceForm.is_available_in_ecommerce"
                                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                    >
                                        <JMultiSelect
                                            v-model:selected-records="dreamPriceForm.sale_channels"
                                            :records="saleChannels"
                                            input-label="Sale Channels"
                                            :required="true"
                                            validation-field-name="sale_channel_ids"
                                            class="w-full"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.dream_prices.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="dreamPrice ? 'Update' : 'Submit'"
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
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    dreamPrice: {
        type: Object,
        default: null,
    },
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
    saleChannels: {
        type: Array,
        required: true,
    },
});

const dreamPriceForm = useForm({
    name: null,
    allow_registered_member: false,
    allow_employee: false,
    allow_walk_in_member: false,
    is_available_in_pos: true,
    is_available_in_ecommerce: false,
    location_ids: [],
    locations: [],
    member_groups: [],
    member_group_ids: [],
    employee_groups: [],
    employee_group_ids: [],
    start_date: null,
    end_date: null,
    sale_channels: [],
    sale_channel_ids: [],
});

const saveDreamPrice = () => {
    prepareDreamPriceFormDetails();

    if (props.dreamPrice) {
        dreamPriceForm.post(route('admin.dream_prices.update', props.dreamPrice.id));
        return;
    }
    dreamPriceForm.post(route('admin.dream_prices.store'));
};

const updateSelectedMemberGroups = (memberGroups) => {
    dreamPriceForm.member_groups = memberGroups;
};

const updateSelectedEmployeeGroups = (employeeGroups) => {
    dreamPriceForm.employee_groups = employeeGroups;
};

const updateColumnIsMemberRequired = (columnName, data) => {
    dreamPriceForm[columnName] = data;
};

const updateColumnAllowWalkInMember = (columnName, data) => {
    dreamPriceForm[columnName] = data;
};

const updateColumnOnlyForEmployee = (columnName, data) => {
    dreamPriceForm[columnName] = data;
};

const updateIsAvailableInPos = (columnName, data) => {
    dreamPriceForm[columnName] = data;
};

const updateIsAvailableInEcommerce = (columnName, data) => {
    dreamPriceForm.sale_channels = [];
    dreamPriceForm[columnName] = data;
};

const prepareDreamPriceFormDetails = () => {
    dreamPriceForm.location_ids = dreamPriceForm.locations.map((location) => {
        return location.id;
    });

    dreamPriceForm.member_group_ids = dreamPriceForm.member_groups.map((memberGroup) => {
        return memberGroup.id;
    });

    dreamPriceForm.employee_group_ids = dreamPriceForm.employee_groups.map((employeeGroup) => {
        return employeeGroup.id;
    });

    if (dreamPriceForm.sale_channels) {
        dreamPriceForm.sale_channel_ids = dreamPriceForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }
};

onMounted(() => {
    if (props.dreamPrice) {
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(dreamPriceForm, JSON.parse(JSON.stringify(props.dreamPrice)));
    }
});

const selectAllLocations = () => {
    dreamPriceForm.locations = props.locations;
    dreamPriceForm.location_ids = props.locations.map((location) => {
        return location.id;
    });
};

const clearAllLocations = () => {
    dreamPriceForm.locations = [];
    dreamPriceForm.location_ids = [];
};
</script>
