<template>
    <PageTitle :title="counter ? 'Edit Counter' : 'Add Counter'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Counters
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ counter ? 'Edit' : 'Add' }} Counter
                    </h2>
                </div>
                <form @submit.prevent="saveCounter();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="counterForm.name"
                                    :required="true"
                                    input-name="name"
                                    input-label="Name"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="counterForm.location_id"
                                    :records="locations"
                                    input-label="Location"
                                    validation-field-name="location_id"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="counterForm.is_locked"
                                    input-label="Is Locked?"
                                    title="Locked counters cannot be opened by cashiers."
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="counterForm.is_self_checkout"
                                    input-label="Is Self Checkout?"
                                    title="Enable this if the counter is for self checkout."
                                    class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.counters.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="counter ? 'Update' : 'Submit'"
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
import JSwitch from '@commonComponents/JSwitch.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    counter: {
        type: Object,
        default: null,
    },
    locations: {
        type: Array,
        required: true,
    },
});

const counterForm = useForm({
    name: null,
    location_id: null,
    is_locked: false,
    is_self_checkout: false,
});

const saveCounter = () => {
    if (props.counter) {
        counterForm.put(route('admin.counters.update', props.counter.id));
        return;
    }
    counterForm.post(route('admin.counters.store'));
};

onMounted(() => {
    if (props.counter) {
        Object.assign(counterForm, props.counter);
    }
});
</script>
