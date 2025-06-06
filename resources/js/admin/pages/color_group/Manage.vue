<template>
    <PageTitle :title="colorGroup ? 'Edit Color Group' : 'Add Color Group'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Color Groups
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="colorGroup">Edit Color Group</span>
                        <span v-else>Add Color Group</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveColorGroups();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="colorGroupForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="colorGroupForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-2">
                                <FormInput
                                    v-model:input-value="colorGroupForm.color_code"
                                    type="color"
                                    input-name="color_code"
                                    input-label="Color Code"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.color_groups.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="colorGroup ? 'Update' : 'Submit'"
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

const props = defineProps({
    colorGroup: {
        type: Object,
        default: null,
    }
});

const colorGroupForm = useForm({
    name: null,
    code: null,
    color_code: null,
    watchEnabled: true,
});

const saveColorGroups = () => {
    colorGroupForm.watchEnabled = false;
    removeLocalStorage('colorGroup');

    if (props.colorGroup) {
        colorGroupForm.put(route('admin.color_groups.update', props.colorGroup.id));
        return;
    }

    colorGroupForm.post(route('admin.color_groups.store'));
};

onMounted(() => {
    if (props.colorGroup) {
        removeLocalStorage('colorGroup');
        Object.assign(colorGroupForm, props.colorGroup);
    } else {
        setLocalStorage('colorGroup', colorGroupForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.colorGroup) {
        saveLocalStorage('colorGroup', colorGroupForm);
    }
};

const clearFormData = () => {
    colorGroupForm.reset();
};

watch(colorGroupForm, () => {
    if (colorGroupForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
