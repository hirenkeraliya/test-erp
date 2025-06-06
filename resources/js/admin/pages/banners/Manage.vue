<template>
    <PageTitle :title="banner ? 'Edit banner' : 'Add banner'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Banners
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="banner">Edit banner</span>
                        <span v-else>Add banner</span>
                    </h2>
                </div>

                <form @submit.prevent="saveBanner()">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="bannerForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="bannerForm.description"
                                    input-name="description"
                                    input-label="Description"
                                    :required="true"
                                    validation-field-name="description"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="bannerForm.action_type_id"
                                    :records="actionTypes"
                                    input-label="Action Type"
                                    validation-field-name="action_type_id"
                                    :required="true"
                                />
                            </div>

                            <div
                                v-if="bannerForm.action_type_id === actionTypesDetails.customUrl"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="bannerForm.custom_url"
                                    input-name="custom_url"
                                    input-label="Custom Url"
                                    :required="true"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-model:input-file="bannerForm.image"
                                    input-label="Image (1200px X 628px)"
                                    validation-field-name="image"
                                    :max-width="1200"
                                    :max-height="628"
                                    @update:input-file="uploadImage"
                                />

                                <div
                                    v-if="state.image_url"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <img
                                        :src="state.image_url"
                                        :alt="state.image_url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-model:is-checked="bannerForm.status"
                                    input-label="Status"
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.banners.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="banner ? 'Update' : 'Submit'"
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
import FormInput from '@commonComponents/FormInput.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import { onMounted, reactive, watch } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    banner: {
        type: Object,
        default: null,
    },
    companyId: {
        type: Number,
        default: null,
    },
    actionTypes: {
        type: Array,
        default: null,
    },
    actionTypesDetails: {
        type: Object,
        required: true,
    }
});

const bannerForm = useForm({
    _method: props.banner ? 'put' : 'post',
    name: null,
    description: null,
    custom_url: null,
    action_type_id: null,
    status: true,
    image: null,
    image_url: null,
    watchEnabled: true,
});

const state = reactive({
    image_url: null
});

const uploadImage = (selectedImage) => {
    state.image_url = URL.createObjectURL(selectedImage);
};

const saveBanner = () => {
    bannerForm.watchEnabled = false;
    removeLocalStorage('banner');

    if (props.banner) {
        bannerForm.post(route('admin.banners.update', props.banner.id));
        return;
    }
    bannerForm.post(route('admin.banners.store'));
};

onMounted(() => {
    if (props.banner) {
        removeLocalStorage('banner');
        Object.assign(bannerForm, props.banner);

        state.image_url = props.banner.image_url
            ? props.banner.image_url
            : null;
    } else {
        setLocalStorage('banner', bannerForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.banner) {
        saveLocalStorage('banner', bannerForm);
    }
};

watch(
    bannerForm,
    () => {
        if (bannerForm.watchEnabled) {
            checkSaveLocalStorage();
        }
    },
    { deep: true }
);
</script>
