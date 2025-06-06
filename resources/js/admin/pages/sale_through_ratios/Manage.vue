<template>
    <PageTitle :title="saleThroughRatio ? 'Edit Sale Through Ratios' : 'Add Sale Through Ratios'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Through Ratios
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ saleThroughRatio ? 'Edit' : 'Add' }} Sale Through Ratio
                    </h2>
                </div>
                <form @submit.prevent="saveSaleThroughRatio();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleThroughRatioForm.name"
                                    :required="true"
                                    input-name="name"
                                    input-label="Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleThroughRatioForm.percentage"
                                    :required="true"
                                    input-name="percentage"
                                    input-label="Percentage"
                                    input-group-suffix="%"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleThroughRatioForm.description"
                                    :required="true"
                                    input-name="description"
                                    input-label="Description"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.sale_through_ratios.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="saleThroughRatio ? 'Update' : 'Submit'"
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

const props = defineProps({
    saleThroughRatio: {
        type: Object,
        default: null,
    }
});

const saleThroughRatioForm = useForm({
    name: null,
    percentage: null,
    description: null,
});

const saveSaleThroughRatio = () => {
    if (props.saleThroughRatio) {
        saleThroughRatioForm.put(route('admin.sale_through_ratios.update', props.saleThroughRatio.id));
        return;
    }
    saleThroughRatioForm.post(route('admin.sale_through_ratios.store'));
};

onMounted(() => {
    if (props.saleThroughRatio) {
        Object.assign(saleThroughRatioForm, props.saleThroughRatio);
    }
});
</script>
