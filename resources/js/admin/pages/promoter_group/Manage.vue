<template>
    <PageTitle :title="promoterGroup ? 'Edit Promoter Group' : 'Add Promoter Group'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promoter Groups
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="promoterGroup">Edit Promoter Group</span>
                        <span v-else>Add Promoter Group</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="savePromoterGroups();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="promoterGroupForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="promoterGroupForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="promoterGroupForm.type_id"
                                    :records="types"
                                    input-label="Type Id"
                                    validation-field-name="type_id"
                                    placeholder="Please select type"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.promoter_groups.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="promoterGroup ? 'Update' : 'Submit'"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    promoterGroup: {
        type: Object,
        default: null,
    },
    types: {
        type: Object,
        required: true,
    },
    staticTypes: {
        type: Object,
        required: true,
    },
});

const promoterGroupForm = useForm({
    name: null,
    code: null,
    type_id: props.staticTypes.pos,
    watchEnabled: true,
});

const savePromoterGroups = () => {
    promoterGroupForm.watchEnabled = false;
    removeLocalStorage('promoterGroup');
    if (props.promoterGroup) {
        promoterGroupForm.put(route('admin.promoter_groups.update', props.promoterGroup.id));
        return;
    }

    promoterGroupForm.post(route('admin.promoter_groups.store'));
};

onMounted(() => {
    if (props.promoterGroup) {
        removeLocalStorage('promoterGroup');
        Object.assign(promoterGroupForm, props.promoterGroup);
    } else {
        setLocalStorage('promoterGroup', promoterGroupForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.promoterGroup) {
        saveLocalStorage('promoterGroup', promoterGroupForm);
    }
};
const clearFormData = () => {
    promoterGroupForm.reset();
};
watch(promoterGroupForm, () => {
    if (promoterGroupForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
