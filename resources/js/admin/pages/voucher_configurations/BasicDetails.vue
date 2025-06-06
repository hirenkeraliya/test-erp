<template>
    <div class="intro-y bg-slate-50">
        <div class="font-medium text-lg p-5 border-b">
            Basic Details
        </div>

        <div class="p-5 pt-1">
            <div>
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <FormTextarea
                            :input-value="voucherConfigurationForm.handover_foot_note"
                            validation-field-name="handover_foot_note"
                            input-name="handover_foot_note"
                            input-label="Disclaimer (hand-over footnote)"
                            title="Starting some restriction for using the voucher i.e.this voucher is not applicable to xxx brand, this voucher is not redeemable from when to when etc."
                            @update:input-value="updateDetails($event, 'handover_foot_note')"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <FormTextarea
                            :input-value="voucherConfigurationForm.redemption_foot_note"
                            validation-field-name="redemption_foot_note"
                            input-name="redemption_foot_note"
                            input-label="Redemption footnote"
                            title="After redeeming the voucher, this is where the footnote appear such as Thank you note etc."
                            @update:input-value="updateDetails($event, 'redemption_foot_note')"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <FormTextarea
                            :input-value="voucherConfigurationForm.title"
                            validation-field-name="title"
                            input-name="title"
                            input-label="Title"
                            :required="true"
                            @update:input-value="updateDetails($event, 'title')"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <FormTextarea
                            :input-value="voucherConfigurationForm.description"
                            validation-field-name="description"
                            input-name="description"
                            input-label="Description"
                            @update:input-value="updateDetails($event, 'description')"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <FormTextarea
                            :input-value="voucherConfigurationForm.terms_and_conditions"
                            validation-field-name="terms_and_conditions"
                            input-name="terms_and_conditions"
                            input-label="Terms and Conditions"
                            @update:input-value="updateDetails($event, 'terms_and_conditions')"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <JFileCropUpload
                            input-label="Image (343px X 260px)"
                            validation-field-name="image"
                            :max-width="343"
                            :max-height="260"
                            @update:input-file="uploadImage"
                        />

                        <div
                            v-if="voucherConfigurationForm.image_url"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <img
                                :src="voucherConfigurationForm.image_url"
                                :alt="voucherConfigurationForm.image_url"
                                width="100"
                                class="mt-2"
                            >
                        </div>
                    </div>

                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <JFileCropUpload
                            input-label="Thumbnail (343px X 72px)"
                            validation-field-name="thumbnail"
                            :max-width="343"
                            :max-height="72"
                            @update:input-file="uploadThumbnailImage"
                        />

                        <div
                            v-if="voucherConfigurationForm.thumbnail_url"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <img
                                :src="voucherConfigurationForm.thumbnail_url"
                                :alt="voucherConfigurationForm.thumbnail_url"
                                width="100"
                                class="mt-2"
                            >
                        </div>
                    </div>
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <JSwitch
                            input-label="Is Available In Ecommerce?"
                            :is-checked="voucherConfigurationForm.is_available_in_ecommerce"
                            class="mt-3"
                            @update:is-checked="updateIsAvailableInEcommerce"
                        />

                        <div
                            v-if="voucherConfigurationForm.is_available_in_ecommerce"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                        >
                            <JMultiSelect
                                :selected-records="voucherConfigurationForm.sale_channels"
                                :records="saleChannels"
                                input-label="Sale Channels"
                                :required="true"
                                validation-field-name="sale_channel_ids"
                                class="w-full"
                                @update:selected-records="onSaleChannelsUpdate"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import FormTextarea from '@commonComponents/FormTextarea.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

defineProps({
    voucherConfigurationForm: {
        type: Object,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:column-details',
    'clear:columns',
]);

const updateDetails = (data, columnName) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const uploadImage = (selectedImage) => {
    updateDetails(selectedImage, 'image');
    updateDetails(URL.createObjectURL(selectedImage), 'image_url');
};

const uploadThumbnailImage = (selectedImage) => {
    updateDetails(selectedImage, 'thumbnail');
    updateDetails(URL.createObjectURL(selectedImage), 'thumbnail_url');
};

const updateIsAvailableInEcommerce = (data) => {
    updateDetails([], 'sale_channels');
    updateDetails(data, 'is_available_in_ecommerce');
};

const onSaleChannelsUpdate = (newSelected) => {
    updateDetails([...newSelected], 'sale_channels');
};

</script>
