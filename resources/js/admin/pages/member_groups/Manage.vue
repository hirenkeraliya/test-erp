<template>
    <PageTitle :title="memberGroup ? 'Edit Member Group' : 'Add Member Group'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Member Groups
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="memberGroup">Edit Member Group</span>
                        <span v-else>Add Member Group</span>
                    </h2>
                </div>

                <form @submit.prevent="saveMemberGroup();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="memberGroupForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="memberGroupForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="state.type_id"
                                    :records="groupTypes"
                                    input-label="Group Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                    @update:selected-record="changeTypeCleaner"
                                />
                            </div>
                        </div>

                        <div
                            v-if="state.type_id == props.manualType"
                            class="mt-4"
                        >
                            <FileUploadAndDisplayRecordsForMembers
                                :selected-members="state.members"
                                :allow-to-clear-selected-products="true"
                                file-path="/files/member-group-member-sample-file.xlsx"
                                @get-members-upload-file="getMembersUploadFile"
                                @clear-selected-members="clearSelectedMembers"
                            />
                        </div>

                        <div
                            v-if="state.showMemberAlert && ! props.smartGroupProduct.includes(state.smart_group_type_id)"
                            class="pt-5 w-100"
                        >
                            <InfoAlert
                                color="primary"
                                class="my-3"
                            >
                                <h2 class="text-lg font-medium mr-auto">
                                    Member Count:  {{ state.totalMember }}
                                </h2>
                            </InfoAlert>
                        </div>

                        <div class="grid grid-cols-12 gap-6 mt-5">
                            <div class="col-span-12 lg:col-span-12">
                                <div>
                                    <div
                                        v-if="state.type_id == props.smartType"
                                        class="grid grid-cols-12 gap-6"
                                    >
                                        <div
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <FormSelectBox
                                                :selected-record="state.smart_group_type_id"
                                                :records="smartGroupTypes"
                                                input-label="Group"
                                                validation-field-name="smart_group_type_id"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:selected-record="changeSmartGroupTypeCleaner"
                                            />
                                        </div>
                                        <div
                                            v-if="props.smartGroupCategoryItem.includes(state.smart_group_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <FormSelectBox
                                                v-model:selected-record="memberGroupForm.element_condition_type_id"
                                                :records="elementConditionTypes"
                                                input-label="Condition"
                                                validation-field-name="element_condition_type_id"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:selected-record="getMemberCount"
                                            />
                                        </div>
                                        <div
                                            v-if="props.smartGroupNumber.includes(state.smart_group_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <FormSelectBox
                                                v-model:selected-record="memberGroupForm.number_condition_type_id"
                                                :records="numberConditionTypes"
                                                input-label="Condition"
                                                validation-field-name="number_condition_type_id"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:selected-record="getMemberCount"
                                            />
                                        </div>
                                        <div
                                            v-if="props.smartGroupDate.includes(state.smart_group_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <FormSelectBox
                                                v-model:selected-record="memberGroupForm.date_condition_type_id"
                                                :records="dateConditionTypes"
                                                input-label="Condition"
                                                validation-field-name="date_condition_type_id"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:selected-record="getMemberCount"
                                            />
                                        </div>
                                        <div
                                            v-if="props.smartGroupCategory.includes(state.smart_group_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <JMultiSelect
                                                v-model:selected-records="state.categories"
                                                :records="categories"
                                                input-label="Categories"
                                                validation-field-name="category_ids"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:selected-records="getMemberCount"
                                            />
                                        </div>

                                        <div
                                            v-if="props.smartGroupProduct.includes(state.smart_group_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <FileUploadAndDisplayRecordsForProducts
                                                :selected-products="state.products"
                                                :allow-to-clear-selected-products="true"
                                                file-path="/files/reward-products-sample-file.xlsx"
                                                @get-products-upload-file="getProductsUploadFile"
                                                @clear-selected-products="clearSelectedProducts"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12 lg:col-span-12">
                                <div>
                                    <div
                                        v-if="props.smartGroupDate.includes(state.smart_group_type_id) && props.dateCondition.includes(memberGroupForm.date_condition_type_id)"
                                        class="grid grid-cols-12 gap-6"
                                    >
                                        <div
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <JDatePicker
                                                v-model:input-value="memberGroupForm.date"
                                                input-label="Date"
                                                :max-date="new Date()"
                                                :required="true"
                                                validation-field-name="date"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:input-value="getMemberCount"
                                            />
                                        </div>
                                        <div
                                            v-if="props.dateConditionBetween.includes(memberGroupForm.date_condition_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                        >
                                            <JDatePicker
                                                v-model:input-value="memberGroupForm.max_date"
                                                input-label="Max Date"
                                                :max-date="new Date()"
                                                :required="true"
                                                validation-field-name="max_date"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:input-value="getMemberCount"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="props.smartGroupNumber.includes(state.smart_group_type_id) && props.numberCondition.includes(memberGroupForm.number_condition_type_id)"
                                class="col-span-12 lg:col-span-12"
                            >
                                <div>
                                    <div class="grid grid-cols-12 gap-6">
                                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                            <FormInput
                                                v-model:input-value="state.value"
                                                input-name="value"
                                                input-label="Value"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:input-value="debouncedGetMemberCount"
                                            />
                                        </div>
                                        <div
                                            v-if="props.numberConditionBetween.includes(memberGroupForm.number_condition_type_id)"
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                        >
                                            <FormInput
                                                v-model:input-value="state.max_value"
                                                input-name="max_value"
                                                input-label="Max Value"
                                                label-class="form-label w-full flex flex-col sm:flex-row -mt-2"
                                                @update:input-value="debouncedGetMemberCount"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.member_groups.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="memberGroup ? 'Update' : 'Submit'"
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
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, reactive } from 'vue';
import InfoAlert from '@/common/components/InfoAlert.vue';
import { route } from 'ziggy';
import axios from 'axios';
import { confirmDialogBox, showSuccessNotification } from '@commonServices/notifier';
import FileUploadAndDisplayRecordsForMembers from './partials/FileUploadAndDisplayRecordsForMembers.vue';
import FileUploadAndDisplayRecordsForProducts from './partials/FileUploadAndDisplayRecordsForProducts.vue';

const props = defineProps({
    memberGroup: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        required: true,
    },
    groupTypes: {
        type: Array,
        required: true,
    },
    smartGroupTypes: {
        type: Array,
        required: true,
    },
    dateConditionTypes: {
        type: Array,
        required: true,
    },
    elementConditionTypes: {
        type: Array,
        required: true,
    },
    numberConditionTypes: {
        type: Array,
        required: true,
    },
    smartGroupDate: {
        type: Array,
        required: true,
    },
    smartGroupCategoryItem: {
        type: Array,
        required: true,
    },
    smartGroupNumber: {
        type: Array,
        required: true,
    },
    smartGroupCategory: {
        type: Array,
        required: true
    },
    smartGroupProduct: {
        type: Array,
        required: true
    },
    groupManualSmart: {
        type: Array,
        required: true
    },
    dateCondition: {
        type: Array,
        required: true
    },
    dateConditionBetween: {
        type: Array,
        required: true,
    },
    numberCondition: {
        type: Array,
        required: true
    },
    numberConditionBetween: {
        type: Array,
        required: true
    },
    manualType: {
        type: Number,
        required: true
    },
    smartType: {
        type: Number,
        required: true
    }
});

const memberGroupForm = useForm({
    name: null,
    code: null,
    type_id: null,
    smart_group_type_id: null,
    date_condition_type_id: null,
    element_condition_type_id: null,
    number_condition_type_id: null,
    date: null,
    max_date: null,
    value: null,
    max_value: null,
    members_count: null,
    category_ids: [],
    member_file: null,
    product_file: null,
});

const state = reactive({
    members: [],
    categories: [],
    products: [],
    type_id: null,
    smart_group_type_id: null,
    totalMember: 0,
    showMemberAlert: false,
    timeout: null,
    value: null,
    max_value: null,
});

const saveMemberGroup = () => {

    prepareMemberGroupDetails();

    if (props.memberGroup) {
        router.post(route('admin.member_groups.update', props.memberGroup.id), memberGroupForm);
        return;
    }

    router.post(route('admin.member_groups.store'), memberGroupForm);
};

const prepareMemberGroupDetails = () => {
    memberGroupForm.category_ids = state.categories.map((category) => {
        return category.id;
    });

    memberGroupForm.type_id = state.type_id;
    memberGroupForm.smart_group_type_id = state.smart_group_type_id;
    if (props.smartGroupDate.includes(state.smart_group_type_id)) {
        memberGroupForm.element_condition_type_id = null;
        memberGroupForm.number_condition_type_id = null;
    }
    if (props.smartGroupCategoryItem.includes(state.smart_group_type_id)) {
        memberGroupForm.date_condition_type_id = null;
        memberGroupForm.number_condition_type_id = null;
    }
    if (props.smartGroupNumber.includes(state.smart_group_type_id)) {
        memberGroupForm.date_condition_type_id = null;
        memberGroupForm.element_condition_type_id = null;
    }
    if (state.value) {
        memberGroupForm.value = state.value;
    }
    if (state.max_value) {
        memberGroupForm.max_value = state.max_value;
    }
};

const changeTypeCleaner = (value) => {
    state.type_id = value;
    state.showMemberAlert = false;
    memberGroupForm.member_file = null;
    if (value == 1) state.smart_group_type_id = null;
    state.value = null;
    state.max_value = null;
};

const changeSmartGroupTypeCleaner = (value) => {
    state.smart_group_type_id = value;
    getMemberCount();
};

const getMemberInterval = 2000;

const debouncedGetMemberCount = () => {
    clearTimeout(state.timeout);
    state.timeout = setTimeout(() => {
        getMemberCount();
    }, getMemberInterval);
};

const getMemberCount = () => {
    if (state.type_id == props.smartType) {
        axios.post(route('admin.member_groups.get_group_member_count'), {
            groupType: state.type_id,
            smartGroupType: state.smart_group_type_id,
            dateConditionTypeId: memberGroupForm.date_condition_type_id,
            elementConditionTypeId: memberGroupForm.element_condition_type_id,
            numberConditionTypeId: memberGroupForm.number_condition_type_id,
            value: state.value,
            max_value: state.max_value,
            date: memberGroupForm.date,
            max_date: memberGroupForm.max_date,
            productIds: state.products.map((product) => {
                return product.id;
            }),
            categoryIds: state.categories.map((category) => {
                return category.id;
            }),
            memberIds: state.members.map((member) => {
                return member.id;
            })
        }).then((response) => {
            state.showMemberAlert = true;
            state.totalMember = response.data;
        }).catch(() => {
        });
    }
    if (state.type_id == props.manualType) {
        state.showMemberAlert = true;
        state.totalMember = state.members.length;
    }
};

const getMembersUploadFile = (file) => {
    memberGroupForm.member_file = file;
};

const getProductsUploadFile = (file) => {
    memberGroupForm.product_file = file;
};

const clearSelectedMembers = () => {
    confirmDialogBox('Do you want to clear the selected members?', () => {
        if (props.memberGroup) {
            router.post(route('admin.member_groups.remove_selected_members', props.memberGroup.id), {}, {
                onSuccess: () => {
                    showSuccessNotification('The selected members have been removed successfully.');
                    window.location.reload();
                }
            });
        } else {
            window.location.reload();
        }
    });
};

const clearSelectedProducts = () => {
    confirmDialogBox('Do you want to clear the selected products?', () => {
        if (props.memberGroup) {
            router.post(route('admin.member_groups.remove_selected_products', props.memberGroup.id), {}, {
                onSuccess: () => {
                    showSuccessNotification('The selected products have been removed successfully.');
                    window.location.reload();
                }
            });
        } else {
            window.location.reload();
        }
    });
};

onMounted(() => {
    if (props.memberGroup) {
        Object.assign(memberGroupForm, props.memberGroup);
        state.members = props.memberGroup.members;
        state.categories = props.memberGroup.categories;
        state.products = props.memberGroup.products;
        state.type_id = props.memberGroup.type_id;
        state.smart_group_type_id = props.memberGroup.smart_group_type_id;
        state.value = memberGroupForm.value;
        state.max_value = memberGroupForm.max_value;
        getMemberCount();
    }
});
</script>
