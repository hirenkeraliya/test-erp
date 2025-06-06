<template>
    <PageTitle :title="template ? 'Edit Template' : 'Add Template'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Templates
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="template">Edit Template</span>
                        <span v-else>Add Template</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveTemplate();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="templateForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="templateForm.description"
                                    input-name="description"
                                    input-label="Description"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6">
                                <JSwitch
                                    v-model:is-checked="templateForm.is_variant"
                                    input-label="Is Variant?"
                                    class="mt-3"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.templates.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="template ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import JSwitch from '@commonComponents/JSwitch.vue';

const props = defineProps({
    template: {
        type: Object,
        default: null,
    }
});

const templateForm = useForm({
    name: null,
    description: null,
    is_variant: false,
    watchEnabled: true,
});

const saveTemplate = () => {
    templateForm.watchEnabled = false;
    removeLocalStorage('template');

    if (props.template) {
        templateForm.put(route('admin.templates.update', props.template.id));
        return;
    }

    templateForm.post(route('admin.templates.store'));
};

onMounted(() => {
    if (props.template) {
        removeLocalStorage('template');
        Object.assign(templateForm, props.template);
    } else {
        setLocalStorage('template', templateForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.template) {
        saveLocalStorage('template', templateForm);
    }
};

const clearFormData = () => {
    templateForm.reset();
};

watch(templateForm, () => {
    if (templateForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
