<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ ecommerceLocation ? ' Update Ecommerce Location Setup' : 'Add Ecommerce Location Setup' }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <form
                @submit.prevent="saveEcommerceLocationSetup();"
            >
                <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                    <div class="input-form col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-6 xl:col-span-4">
                        <FormInput
                            v-model:input-value="ecommerceLocationForm.url"
                            input-name="url"
                            input-label="URL"
                            :required="true"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-6 xl:col-span-4">
                        <FormInput
                            v-model:input-value="ecommerceLocationForm.client_secret"
                            input-name="client_secret"
                            input-label="Client Secret"
                            :required="true"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4">
                        <FormSelectBox
                            v-model:selected-record="ecommerceLocationForm.inventory_deduct_order_status"
                            :records="orderStatuses"
                            input-label="Inventory Deduct Order Status"
                            validation-field-name="inventory_deduct_order_status"
                            :required="true"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4">
                        <JMultiSelect
                            v-model:selected-records="ecommerceLocationForm.order_statuses"
                            :records="orderStatuses"
                            input-label="Inventory rollback order statuses "
                            :required="true"
                            validation-field-name="inventory_rollback_order_status"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <SecondaryButton
                        type="button"
                        text="Cancel"
                        class="w-24 mr-1"
                        @click="closeModal"
                    />

                    <PrimaryButton
                        type="submit"
                        :text="ecommerceLocation ? 'Update' : 'Submit'"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { showSuccessNotification } from '@commonServices/notifier';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { onMounted } from 'vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    locationId: {
        type: Number,
        required: true,
    },
    ecommerceLocation: {
        type: Object,
        default: () => {},
    },
    orderStatuses: {
        type: Array,
        required: true,
    },
});

const ecommerceLocationForm = useForm({
    url: null,
    client_secret: null,
    inventory_deduct_order_status: null,
    order_statuses: [],
    inventory_rollback_order_status: [],
});

const saveEcommerceLocationSetup = () => {
    prepareEcommerceLocationFormDetails();
    if (props.ecommerceLocation) {
        ecommerceLocationForm.post(route('admin.locations.update_ecommerce_location_setup', parseInt(props.ecommerceLocation.id)), {
            onSuccess: (page) => {
                if (page.props.flash.error) {
                    return;
                }

                showSuccessNotification('Ecommerce location setup update successfully.');
                closeModal();
            },
        });
        return;
    }

    ecommerceLocationForm.post(route('admin.locations.setup_ecommerce_location', parseInt(props.locationId)), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            showSuccessNotification('Ecommerce location setup add successfully.');
            closeModal(true);
        },
    });
};

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal', true);
};

const prepareEcommerceLocationFormDetails = () => {
    ecommerceLocationForm.inventory_rollback_order_status = ecommerceLocationForm.order_statuses.map((status) => {
        return status.id;
    });
};

onMounted(() => {
    if (props.ecommerceLocation) {
        Object.assign(ecommerceLocationForm, props.ecommerceLocation);
    }
});
</script>
