<template>
    <PageTitle :title="designation ? 'Edit Designation' : 'Add Designation'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Designations
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="designation">Edit Designation</span>
                        <span v-else>Add Designation</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveDesignation();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="designationForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="designationForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="designationForm.company_id"
                                    :records="companies"
                                    input-label="Company"
                                    validation-field-name="company_id"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('super_admin.designations.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="designation ? 'Update' : 'Submit'"
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
import { onMounted } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    designation: {
        type: Object,
        default: null,
    },
    companies: {
        type: Array,
        default: () => [],
    }
});

const designationForm = useForm({
    company_id: null,
    name: null,
    code: null,
});

const saveDesignation = () => {
    if (props.designation) {
        designationForm.put(route('super_admin.designations.update', props.designation.id));
        return;
    }
    designationForm.post(route('super_admin.designations.store'));
};

onMounted(() => {
    if (props.designation) {
        Object.assign(designationForm, props.designation);
    }
});
</script>
