<template>
    <PageTitle :title="color ? 'Edit Color' : 'Add Color'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Colors
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="color">Edit Color</span>
                        <span v-else>Add Color</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveColor();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="colorForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="colorForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="colorForm.group_id"
                                    :records="colorGroups"
                                    input-label="Color Group"
                                    validation-field-name="group_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-2">
                                <FormInput
                                    v-model:input-value="colorForm.color_code"
                                    type="color"
                                    input-name="color_code"
                                    input-label="Color Code"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.colors.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="color ? 'Update' : 'Submit'"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    color: {
        type: Object,
        default: null,
    },
    colorGroups: {
        type: Array,
        required: true,
    },
});

const colorForm = useForm({
    name: null,
    code: null,
    group_id: null,
    color_code: null,
    watchEnabled: true,
});

const saveColor = () => {
    colorForm.watchEnabled = false;
    removeLocalStorage('color');

    if (props.color) {
        colorForm.put(route('admin.colors.update', props.color.id));
        return;
    }

    colorForm.post(route('admin.colors.store'));
};

onMounted(() => {
    if (props.color) {
        removeLocalStorage('color');
        Object.assign(colorForm, props.color);
    } else {
        setLocalStorage('color', colorForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.color) {
        saveLocalStorage('color', colorForm);
    }
};

const clearFormData = () => {
    colorForm.reset();
};

watch(colorForm, () => {
    if (colorForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
