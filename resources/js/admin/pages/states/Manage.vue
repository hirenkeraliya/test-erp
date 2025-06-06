<template>
    <PageTitle :title="state ? 'Edit State' : 'Add State'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            States
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ state ? 'Edit' : 'Add' }} State
                    </h2>
                </div>
                <form @submit.prevent="saveState();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="stateForm.country_id"
                                    :records="countries"
                                    input-label="Country"
                                    :required="true"
                                    validation-field-name="country_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="stateForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div
                                    v-if="state"
                                    class="mt-4"
                                >
                                    <div class="input-group">
                                        <label>
                                            Country Code:
                                        </label>
                                    </div>
                                    <div class="font-medium">
                                        {{ state.country_code }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.states.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="state ? 'Update' : 'Submit'"
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
import { onMounted } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

const props = defineProps({
    state: {
        type: Object,
        default: null,
    },
    countries: {
        type: Array,
        required: true,
    },
});

const stateForm = useForm({
    name: null,
    country_id: null,
});

const saveState = () => {
    if (props.state) {
        stateForm.put(route('admin.states.update', props.state.id));
        return;
    }
    stateForm.post(route('admin.states.store'));
};

onMounted(() => {
    if (props.state) {
        Object.assign(stateForm, props.state);
    }
});
</script>
