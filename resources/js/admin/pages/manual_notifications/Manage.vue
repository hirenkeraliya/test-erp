<template>
    <PageTitle title="Add Manual Notifications" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Manual Notifications
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Add Manual Notifications</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveNotification();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="notificationForm.notification_type"
                                    :records="manualNotificationTypes"
                                    input-label="Notification Type"
                                    validation-field-name="notification_type"
                                    :required="true"
                                    @update:selected-record="updateNotificationType"
                                />
                            </div>

                            <div
                                v-if="staticManualNotificationTypes.promoters === notificationForm.notification_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div>
                                    <FormSelectBox
                                        :selected-record="notificationForm.promoter_filter_type"
                                        :records="promoterFilterTypes"
                                        input-label="Filter Type"
                                        validation-field-name="promoter_filter_type"
                                        :required="true"
                                        @update:selected-record="updatePromotersFilterType"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="staticManualNotificationTypes.promoters === notificationForm.notification_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div>
                                    <div
                                        v-if="staticPromoterFilterTypes.locations ===
                                            notificationForm.promoter_filter_type"
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JMultiSelect
                                            :records="locations"
                                            input-label="Locations"
                                            :required="true"
                                            validation-field-name="location_ids"
                                            :selected-records="state.locations"
                                            @update:selected-records="updateLocationId"
                                        />
                                    </div>
                                    <div
                                        v-if="staticPromoterFilterTypes.groups === notificationForm.promoter_filter_type"
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JMultiSelect
                                            :selected-records="state.promoter_groups"
                                            :records="promoterGroups"
                                            input-label="Promoter Groups"
                                            :required="true"
                                            validation-field-name="promoter_group_ids"
                                            @update:selected-records="updatePromoterGroupId"
                                        />
                                    </div>
                                    <div
                                        v-if="staticPromoterFilterTypes.promoters === notificationForm.promoter_filter_type"
                                    >
                                        <JMultiSelect
                                            :selected-records="state.promoters"
                                            :records="promoters"
                                            input-label="Promoters"
                                            :required="true"
                                            validation-field-name="promoter_ids"
                                            @update:selected-records="updatePromoterId"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="staticManualNotificationTypes.members === notificationForm.notification_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div>
                                    <FormSelectBox
                                        :selected-record="notificationForm.member_filter_type"
                                        :records="memberFilterTypes"
                                        input-label="Filter Type"
                                        validation-field-name="member_filter_type"
                                        :required="true"
                                        @update:selected-record="updateMembersFilterType"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="staticManualNotificationTypes.members === notificationForm.notification_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div>
                                    <div
                                        v-if="staticMemberFilterTypes.locations ===
                                            notificationForm.member_filter_type"
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JMultiSelect
                                            :records="locations"
                                            input-label="locations"
                                            :required="true"
                                            validation-field-name="location_ids"
                                            :selected-records="state.locations"
                                            @update:selected-records="updateLocationId"
                                        />
                                    </div>
                                    <div
                                        v-if="staticMemberFilterTypes.groups===
                                            notificationForm.member_filter_type"
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JMultiSelect
                                            :selected-records="state.member_groups"
                                            :records="memberGroups"
                                            input-label="Member Groups"
                                            :required="true"
                                            validation-field-name="member_group_ids"
                                            @update:selected-records="updateMemberGroupId"
                                        />
                                    </div>
                                    <div
                                        v-if="staticMemberFilterTypes.types===
                                            notificationForm.member_filter_type"
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                    >
                                        <JMultiSelect
                                            :selected-records="state.member_types"
                                            :records="memberTypes"
                                            input-label="Member Types"
                                            :required="true"
                                            validation-field-name="member_type_ids"
                                            @update:selected-records="updateMemberTypeId"
                                        />
                                    </div>
                                    <div
                                        v-if="staticMemberFilterTypes.members===
                                            notificationForm.member_filter_type"
                                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 -mt-3"
                                    >
                                        <FormAjaxSelect
                                            :selected-record="state.members"
                                            :search-records="searchMembers"
                                            placeholder="Member Name to search..."
                                            :required="true"
                                            input-label="Member"
                                            :multi-select="true"
                                            validation-field-name="member_ids"
                                            @update:selected-record="updateMember"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="notificationForm.notification_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6"
                            >
                                <span
                                    v-if="staticManualNotificationTypes.promoters === notificationForm.notification_type || staticMemberFilterTypes.members!==
                                        notificationForm.member_filter_type"
                                >
                                    <PrimaryButton
                                        type="button"
                                        text="Select all"
                                        class="w-auto sm:w-24 md:w-1/1"
                                        @click="selectAll"
                                    />
                                </span>

                                <OutlinePrimaryButton
                                    type="button"
                                    text="Clear All"
                                    class="w-auto sm:w-24 md:w-1/1 mt-2 ml-2"
                                    @click="clearAll"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-8">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="notificationForm.title"
                                    input-name="title"
                                    :required="true"
                                    input-label="Title"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="notificationForm.message"
                                    input-name="message"
                                    :required="true"
                                    input-label="Message"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.manual_notifications.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                text="Submit"
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
import { route } from 'ziggy';
import axios from 'axios';
import { reactive } from 'vue';
import FormInput from '@commonComponents/FormInput.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    promoters: {
        type: Array,
        required: true,
    },
    promoterGroups: {
        type: Array,
        required: true,
    },
    memberGroups: {
        type: Array,
        required: true,
    },
    memberTypes: {
        type: Array,
        required: true,
    },
    promoterFilterTypes: {
        type: Array,
        required: true,
    },
    memberFilterTypes: {
        type: Array,
        required: true,
    },
    manualNotificationTypes: {
        type: Array,
        required: true,
    },
    staticManualNotificationTypes: {
        type: Object,
        required: true,
    },
    staticPromoterFilterTypes: {
        type: Object,
        required: true,
    },
    staticMemberFilterTypes: {
        type: Object,
        required: true,
    },
});

const notificationForm = useForm({
    title: null,
    message: null,
    location_ids: [],
    promoter_ids: [],
    promoter_group_ids: [],
    member_group_ids: [],
    member_type_ids: [],
    member_ids: [],
    notification_type: null,
    promoter_filter_type: null,
    member_filter_type: null,
});

const state = reactive({
    locations: [],
    promoters: [],
    promoter_groups: [],
    member_groups: [],
    member_types: [],
    members: [],
});

const saveNotification = () => {
    notificationForm.post(route('admin.manual_notifications.store'));
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
    state.members = selectMember;
    notificationForm.member_ids = state.members.map((member) => {
        return member.id;
    });
};

const updateLocationId = (locationIds) => {
    state.locations = locationIds;
    notificationForm.location_ids = state.locations.map((location) => {
        return location.id;
    });
};

const updatePromoterId = (promoterIds) => {
    state.promoters = promoterIds;
    notificationForm.promoter_ids = state.promoters.map((promoter) => {
        return promoter.id;
    });
};

const updatePromoterGroupId = (promoterGroupIds) => {
    state.promoter_groups = promoterGroupIds;
    notificationForm.promoter_group_ids = state.promoter_groups.map((promoterGroup) => {
        return promoterGroup.id;
    });
};

const updateMemberGroupId = (memberGroupIds) => {
    state.member_groups = memberGroupIds;
    notificationForm.member_group_ids = state.member_groups.map((memberGroup) => {
        return memberGroup.id;
    });
};

const updateMemberTypeId = (memberTypeIds) => {
    state.member_types = memberTypeIds;
    notificationForm.member_type_ids = state.member_types.map((memberType) => {
        return memberType.id;
    });
};

const selectAll = () => {
    if (props.staticManualNotificationTypes.promoters === notificationForm.notification_type) {
        if (props.staticPromoterFilterTypes.promoters === notificationForm.promoter_filter_type) {
            updatePromoterId(props.promoters);
        } else if (props.staticPromoterFilterTypes.locations === notificationForm.promoter_filter_type) {
            updateLocationId(props.locations);
        } else if (props.staticPromoterFilterTypes.groups === notificationForm.promoter_filter_type) {
            updatePromoterGroupId(props.promoterGroups);
        }
    } else if (props.staticManualNotificationTypes.members === notificationForm.notification_type) {
        if (props.staticMemberFilterTypes.locations === notificationForm.member_filter_type) {
            updateLocationId(props.locations);
        } else if (props.staticMemberFilterTypes.groups === notificationForm.member_filter_type) {
            updateMemberGroupId(props.memberGroups);
        } else if (props.staticMemberFilterTypes.types === notificationForm.member_filter_type) {
            updateMemberTypeId(props.memberTypes);
        }
    }
};

const clearAll = () => {
    notificationForm.member_filter_type = null;
    notificationForm.promoter_filter_type = null;
    notificationForm.promoter_ids = [];
    notificationForm.location_ids = [];
    notificationForm.promoter_group_ids = [];
    notificationForm.member_group_ids = [];
    notificationForm.member_type_ids = [];
    notificationForm.member_ids = [];
    state.promoters = [];
    state.locations = [];
    state.promoter_groups = [];
    state.member_groups = [];
    state.member_types = [];
    state.members = [];
};

const clearMemberDetails = (filterType) => {
    if (filterType === null) {
        state.promoter_groups = [];
        notificationForm.promoter_group_ids = [];
        state.locations = [];
        notificationForm.location_ids = [];
        state.promoters = [];
        notificationForm.promoter_ids = [];
    }
    notificationForm.member_filter_type = null;
    notificationForm.member_group_ids = [];
    notificationForm.member_type_ids = [];
    notificationForm.member_ids = [];
    state.member_groups = [];
    state.member_types = [];
    state.members = [];
};

const clearPromoterDetails = (filterType) => {
    if (filterType === null) {
        state.member_groups = [];
        notificationForm.member_group_ids = [];
        state.member_types = [];
        notificationForm.member_type_ids = [];
        state.members = [];
        notificationForm.member_ids = [];
        state.locations = [];
        notificationForm.location_ids = [];
    }
    notificationForm.promoter_filter_type = null;
    notificationForm.promoter_ids = [];
    notificationForm.promoter_group_ids = [];
    state.promoters = [];
    state.promoter_groups = [];
};

const updatePromotersFilterType = (filterType) => {
    notificationForm.promoter_filter_type = filterType;
    clearMemberDetails(filterType);

    state.promoter_groups = [];
    state.locations = [];
    state.promoters = [];
    notificationForm.promoter_group_ids = [];
    notificationForm.promoter_ids = [];
    notificationForm.location_ids = [];
};

const updateMembersFilterType = (filterType) => {
    notificationForm.member_filter_type = filterType;
    clearPromoterDetails(filterType);

    state.member_groups = [];
    state.member_types = [];
    state.locations = [];
    state.members = [];
    notificationForm.member_group_ids = [];
    notificationForm.member_type_ids = [];
    notificationForm.location_ids = [];
};

const updateNotificationType = (notificationType) => {
    clearAll();
    notificationForm.notification_type = notificationType;
    if (notificationType === props.staticManualNotificationTypes.promoters) {
        notificationForm.promoter_filter_type = props.staticPromoterFilterTypes.promoters;
    } else if (notificationType === props.staticManualNotificationTypes.members) {
        notificationForm.member_filter_type = props.staticMemberFilterTypes.members;
    }
};
</script>
