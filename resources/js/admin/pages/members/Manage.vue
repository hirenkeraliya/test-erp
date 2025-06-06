<template>
    <PageTitle :title="member ? 'Edit Member' : 'Add Member'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Members
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-12 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        {{ member ? "Edit" : "Add" }} Member
                    </h2>
                </div>

                <form @submit.prevent="saveMember()">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="memberForm.gender_id"
                                    :records="genders"
                                    :required="true"
                                    input-label="Gender"
                                    validation-field-name="gender_id"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="memberForm.title_id"
                                    :records="titles"
                                    :required="true"
                                    input-label="Title"
                                    validation-field-name="title_id"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="memberForm.race_id"
                                    :records="races"
                                    :required="true"
                                    input-label="Race"
                                    validation-field-name="race_id"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="memberForm.type_id"
                                    :records="types"
                                    :required="true"
                                    input-label="Type"
                                    validation-field-name="type_id"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                    />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="memberForm.first_name"
                                    input-name="first_name"
                                    input-label="First Name"
                                    :required="true"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="memberForm.last_name"
                                    input-name="last_name"
                                    input-label="Last Name"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="memberForm.email"
                                        :required="true"
                                        input-name="email"
                                        input-label="Email"
                                        type="email"
                                    />
                                    <Tippy
                                        v-if="member ? !member.is_email_verified && memberForm.email : memberForm.email"
                                        :content="'Your email will require verification.'"
                                    >
                                        <TriangleAlert
                                            class="text-red-400 ml-2 mt-7"
                                            :size="20"
                                        />
                                    </Tippy>
                                </div>
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="memberForm.mobile_number"
                                    input-name="mobile_number"
                                    input-label="Mobile Number"
                                    placeholder="60789456123"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-if="!memberForm.created_in_location"
                                    v-model:selected-record="memberForm.created_location_id"
                                    :records="locations"
                                    input-label="Created Location"
                                    validation-field-name="created_location_id"
                                    title="This can be configured only once"
                                    :required="true"
                                />

                                <div
                                    v-if="memberForm.created_in_location"
                                    class="mt-3"
                                >
                                    <label
                                        for="created_store"
                                        class="form-label"
                                    >
                                        Created Location
                                    </label>
                                    <div class="input-group mt-2">
                                        {{ memberForm.created_in_location.name }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="memberForm.card_number"
                                    input-name="card_number"
                                    input-label="Card Number"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JDatePicker
                                    v-model:input-value="memberForm.date_of_birth"
                                    input-label="Date of Birth"
                                    :max-date="new Date()"
                                    validation-field-name="date_of_birth"
                                />
                            </div>
                        </div>
                    </div>

                    <span v-if="corporateType === memberForm.type_id">
                        <div
                            class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                        />

                        <div class="p-5">
                            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.company_name"
                                        input-name="company_name"
                                        input-label="Company Name"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.company_registration_number"
                                        input-name="company_registration_number"
                                        input-label="Company Registration Number"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.company_tax_number"
                                        input-name="company_tax_number"
                                        input-label="Company Tax Number"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.company_address"
                                        input-name="company_address"
                                        input-label="Company Address"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.company_phone"
                                        input-name="company_phone"
                                        input-label="Company Phone"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.pic_name"
                                        input-name="pic_name"
                                        input-label="Pic Name"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="memberForm.pic_contact"
                                        input-name="pic_contact"
                                        input-label="Pic Contact"
                                    />
                                </div>
                            </div>
                        </div>
                    </span>

                    <div
                        class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                    />

                    <div>
                        <MultipleAddressTiers
                            :tiers="memberForm.member_addresses"
                            :countries="countries"
                            @update:column-details="updateColumnDetails"
                            @update:tier-value-details="updateTierValueDetails"
                            @add:new-tier-details="addNewTierDetails"
                            @remove:tier-details-of="removeTierDetailsOf"
                        />
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="memberForm.notes"
                                    input-name="notes"
                                    input-label="Notes"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-model:input-file="memberForm.photo"
                                    input-label="Photo (300px X 300px)"
                                    validation-field-name="photo"
                                    :max-width="300"
                                    :max-height="300"
                                    @update:input-file="uploadImage"
                                />
                                <div
                                    v-if="memberForm.photo_url"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <img
                                        :src="memberForm.photo_url"
                                        :alt="memberForm.photo_url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.members.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="member ? 'Update' : 'Submit'"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import MultipleAddressTiers from '@adminPages/members/MultipleAddressTiers.vue';
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    member: {
        type: Object,
        default: null,
    },
    genders: {
        type: Array,
        required: true,
    },
    races: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    corporateType: {
        type: Number,
        required: true,
    },
    titles: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
});

const memberForm = useForm({
    _method: props.member ? 'put' : 'post',
    type_id: null,
    title_id: null,
    race_id: null,
    first_name: null,
    last_name: null,
    gender_id: '',
    date_of_birth: null,
    mobile_number: null,
    email: null,
    company_name: null,
    company_registration_number: null,
    company_tax_number: null,
    company_address: null,
    company_phone: null,
    pic_name: null,
    pic_contact: null,
    created_location_id: null,
    card_number: null,
    notes: null,
    photo: null,
    photo_url: null,
    member_addresses: [
        {
            name: null,
            first_name: null,
            last_name: null,
            contact_mobile_number: null,
            contact_email: null,
            address_line_1: null,
            address_line_2: null,
            city_name: null,
            area_code: null,
            is_primary: false,
            is_disabled: false,
            country_id: null,
            state_id: null,
            city_id: null,
        }
    ],
    watchEnabled: true,
});

const saveMember = () => {
    if (props.corporateType !== memberForm.type_id) {
        memberForm.company_name = null;
        memberForm.company_registration_number = null;
        memberForm.company_tax_number = null;
        memberForm.company_address = null;
        memberForm.company_phone = null;
        memberForm.pic_name = null;
        memberForm.pic_contact = null;
    }

    if (props.member) {
        memberForm.post(route('admin.members.update', props.member.id));
        return;
    }

    router.post(route('admin.members.store'), memberForm);
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    memberForm[columnName] = details.value;
};

const updateTierValueDetails = (details) => {
    memberForm.member_addresses[details.key][details.column_name] = details.value;
};

const addNewTierDetails = () => {
    memberForm.member_addresses.push({
        name: null,
        first_name: null,
        last_name: null,
        contact_mobile_number: null,
        contact_email: null,
        address_line_1: null,
        address_line_2: null,
        $memberData: null,
        area_code: null,
        city_name: null,
        is_primary: false,
        is_disabled: false,
        country_id: null,
        state_id: null,
        city_id: null,
    });
};

const removeTierDetailsOf = (key) => {
    memberForm.member_addresses.splice(key, 1);
};

const uploadImage = (selectedImage) => {
    memberForm.photo_url = URL.createObjectURL(selectedImage);
};

onMounted(() => {
    if (props.member) {
        Object.assign(memberForm, props.member);
        memberForm.photo_url = props.member.photo_url ? props.member.photo_url : null;
    }
});
</script>
