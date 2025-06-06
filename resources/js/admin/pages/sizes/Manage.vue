<template>
    <PageTitle :title="size ? 'Edit Size' : 'Add Size'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sizes
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="size">Edit Size</span>
                        <span v-else>Add Size</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form @submit.prevent="saveSize">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="sizeForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="sizeForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="sizeForm.group_id"
                                    :records="sizeGroups"
                                    input-label="Size Group"
                                    validation-field-name="group_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="sizeForm.sort_order"
                                    :records="sizes"
                                    :required="sizes.length ? true : false"
                                    validation-field-name="sort_order"
                                    input-label="Create After"
                                    @update:selected-record="updateSortOrder"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.sizes.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="size ? 'Update' : 'Submit'"
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
import { showErrorNotification } from '@commonServices/notifier';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    size: {
        type: Object,
        default: null,
    },

    sizes: {
        type: Array,
        required: true,
    },
    sizeGroups: {
        type: Array,
        required: true,
    },
});

const sizeForm = useForm({
    name: null,
    code: null,
    sort_order: null,
    group_id: null,
    watchEnabled: true,
});

const saveSize = () => {
    sizeForm.watchEnabled = false;
    removeLocalStorage('size');

    if (props.size) {
        if (sizeForm.sort_order === props.sizes.id) {
            showErrorNotification('Same size is not allowed in sort');
            return;
        }
        sizeForm.put(route('admin.sizes.update', props.size.id));
        return;
    }

    if (props.sizes.length === 0) {
        sizeForm.sort_order = 0;
    }

    sizeForm.post(route('admin.sizes.store'));
};

const updateSortOrder = (sizeSortOrder) => {
    sizeForm.sort_order = sizeSortOrder;
};

onMounted(() => {
    if (props.size) {
        removeLocalStorage('size');
        Object.assign(sizeForm, props.size);
    } else {
        setLocalStorage('size', sizeForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.size) {
        saveLocalStorage('size', sizeForm);
    }
};

const clearFormData = () => {
    sizeForm.reset();
};

watch(sizeForm, () => {
    if (sizeForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
