<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Create Member
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="px-5 sm:p-10">
            <div>
                <FormInput
                    v-model:input-value="state.memberDetails.first_name"
                    input-label="Member Name"
                    input-name="member_name"
                    label-class="mt-0"
                    :required="true"
                />

                <FormInput
                    v-model:input-value="state.memberDetails.mobile_number"
                    input-label="Member Mobile Number"
                    input-name="member_mobile_number"
                    label-class="mt-0"
                    :required="true"
                />

                <FormInput
                    v-model:input-value="state.memberDetails.card_number"
                    input-label="Member Card Number"
                    input-name="member_card_number"
                    label-class="mt-0"
                />

                <FormInput
                    v-model:input-value="state.memberDetails.company_name"
                    input-label="Member Company Name"
                    input-name="member_company_name"
                    label-class="mt-0"
                />

                <FormInput
                    v-model:input-value="state.memberDetails.company_address"
                    input-label="Member Company Address"
                    input-name="member_company_address"
                    label-class="mt-0"
                />

                <FormInput
                    v-model:input-value="state.memberDetails.pic_name"
                    input-label="Member Pic Name"
                    input-name="member_pic_name"
                    label-class="mt-0"
                />

                <FormInput
                    v-model:input-value="state.memberDetails.pic_contact"
                    input-label="Member Pic Contact"
                    input-name="member_pic_contact"
                    label-class="mt-0"
                />
            </div>
            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="createMember()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { route } from 'ziggy';
import axios from 'axios';
import { showErrorNotification } from '@commonServices/notifier';
import { reactive } from 'vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    locationId: {
        type: Number,
        required: true
    },
    memberTypeCorporate: {
        type: Number,
        required: true
    },
});

const state = reactive({
    memberDetails: {
        type_id: props.memberTypeCorporate,
        first_name: null,
        mobile_number: null,
        card_number: null,
        company_name: null,
        company_address: null,
        pic_name: null,
        pic_contact: null,
        created_location_id: props.locationId,
    },
});

const emits = defineEmits(['close-member-modal', 'update-member']);

const closeModal = () => {
    clearMemberDetails();
    emits('close-member-modal');
};

const clearMemberDetails = () => {
    state.memberDetails.type_id = null;
    state.memberDetails.first_name = null;
    state.memberDetails.mobile_number = null;
    state.memberDetails.card_number = null;
    state.memberDetails.company_name = null;
    state.memberDetails.company_address = null;
    state.memberDetails.pic_name = null;
    state.memberDetails.pic_contact = null;
};

const createMember = () => {
    if (state.memberDetails.first_name === null || state.memberDetails.mobile_number === null) {
        showErrorNotification('Please enter all the details.');
        return;
    }

    axios.post(route('store_manager.members.add_new_member_for_order', state.memberDetails))
        .then((response) => {
            emits('update-member', response.data.member);
            closeModal();
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};
</script>
