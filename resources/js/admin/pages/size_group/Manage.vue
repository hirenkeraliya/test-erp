<template>
    <PageTitle :title="sizeGroup ? 'Edit Size Group' : 'Add Size Group'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Size Groups
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="sizeGroup">Edit Size Group</span>
                        <span v-else>Add Size Group</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveSizeGroups();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="sizeGroupForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="sizeGroupForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.size_groups.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="sizeGroup ? 'Update' : 'Submit'"
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
    sizeGroup: {
        type: Object,
        default: null,
    }
});

const sizeGroupForm = useForm({
    name: null,
    code: null,
    watchEnabled: true,
});

const saveSizeGroups = () => {
    sizeGroupForm.watchEnabled = false;
    removeLocalStorage('sizeGroup');

    if (props.sizeGroup) {
        sizeGroupForm.put(route('admin.size_groups.update', props.sizeGroup.id));
        return;
    }

    sizeGroupForm.post(route('admin.size_groups.store'));
};

onMounted(() => {
    if (props.sizeGroup) {
        removeLocalStorage('sizeGroup');
        Object.assign(sizeGroupForm, props.sizeGroup);
    } else {
        setLocalStorage('sizeGroup', sizeGroupForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.sizeGroup) {
        saveLocalStorage('sizeGroup', sizeGroupForm);
    }
};

const clearFormData = () => {
    sizeGroupForm.reset();
};

watch(sizeGroupForm, () => {
    if (sizeGroupForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
