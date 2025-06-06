<template>
    <PageTitle :title="posAdvertisement ? 'Edit Pos Advertisement' : 'Add Pos Advertisement'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Pos Advertisement
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="posAdvertisement">Edit Pos Advertisement</span>
                        <span v-else>Add Pos Advertisement</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="savePosAdvertisement();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="posAdvertisementForm.type_id"
                                    :records="posAdvertisementTypes"
                                    input-label="Advertisement Type"
                                    :required="true"
                                    validation-field-name="type_id"
                                />
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JFileCropUpload
                                        v-if="
                                            posAdvertisementForm.type_id === advertisementTypeImage
                                        "
                                        v-model:input-file="posAdvertisementForm.photo"
                                        input-label="Photo (1920px X 1280px)"
                                        validation-field-name="photo"
                                        :max-width="1920"
                                        :max-height="1280"
                                        @update:input-file="uploadImage"
                                    />
                                    <JFileUpload
                                        v-if="posAdvertisementForm.type_id === advertisementTypeVideo"
                                        v-model:input-file="posAdvertisementForm.video"
                                        input-label="Video"
                                        validation-field-name="video"
                                    />
                                </div>

                                <div
                                    v-if="posAdvertisementForm.photo_url && posAdvertisementForm.type_id === advertisementTypeImage"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <img
                                        :src="posAdvertisementForm.photo_url"
                                        :alt="posAdvertisementForm.photo_url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="posAdvertisementForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="posAdvertisementForm.locations"
                                    :records="locations"
                                    input-label="Locations"
                                    :required="true"
                                    validation-field-name="location_ids"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="posAdvertisementForm.status"
                                    input-label="Status"
                                    validation-field-name="Status"
                                    :required="true"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.pos_advertisements.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="posAdvertisement ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';

const props = defineProps({
    posAdvertisementTypes: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    posAdvertisement: {
        type: Object,
        default: null,
    },
    advertisementTypeVideo: {
        type: Number,
        default: null,
    },
    advertisementTypeImage: {
        type: Number,
        default: null,
    }
});

const posAdvertisementForm = useForm({
    _method: props.posAdvertisement ? 'put' : 'post',
    type_id: null,
    name: null,
    status: true,
    location_ids: [],
    locations: [],
    photo: null,
    video: null,
    photo_url: null,
    video_url: null,
});

const savePosAdvertisement = () => {
    preparePosAdvertisementDetails();
    if (props.posAdvertisement) {
        posAdvertisementForm.post(route('admin.pos_advertisements.update', props.posAdvertisement.id));
        return;
    }
    posAdvertisementForm.post(route('admin.pos_advertisements.store'));
};

const preparePosAdvertisementDetails = () => {
    posAdvertisementForm.location_ids = posAdvertisementForm.locations.map((location) => {
        return location.id;
    });
};

onMounted(() => {
    if (props.posAdvertisement) {
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(posAdvertisementForm, JSON.parse(JSON.stringify(props.posAdvertisement)));
    }
});

const uploadImage = (selectedImage) => {
    posAdvertisementForm.photo_url = URL.createObjectURL(selectedImage);
};
</script>
