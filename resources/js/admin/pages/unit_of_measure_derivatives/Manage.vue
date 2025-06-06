<template>
    <PageTitle :title="derivative ? 'Edit Derivative' : 'Add Derivative'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Derivatives of Unit of Measure: <span class="text-primary">{{ unitOfMeasureName }}</span>
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="derivative">Edit Derivative</span>
                        <span v-else>Add Derivative</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveDerivative();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <FormInput
                                    v-model:input-value="derivativeForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <FormInput
                                    v-model:input-value="derivativeForm.ratio"
                                    input-name="ratio"
                                    input-label="Ratio"
                                    :required="true"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link
                                :href="route('admin.unit_of_measure_derivatives.index', unitOfMeasureId)"
                            >
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="derivative ? 'Update' : 'Submit'"
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
    derivative: {
        type: Object,
        default: null,
    },
    unitOfMeasureId: {
        type: Number,
        default: null,
    },
    unitOfMeasureName: {
        type: String,
        default: null
    },
});

const derivativeForm = useForm({
    name: null,
    ratio: null,
});

const saveDerivative = () => {
    if (props.derivative) {
        derivativeForm.put(
            route('admin.unit_of_measure_derivatives.update', [props.unitOfMeasureId, props.derivative.id])
        );
        return;
    }
    derivativeForm.post(route('admin.unit_of_measure_derivatives.store', props.unitOfMeasureId));
};

onMounted(() => {
    if (props.derivative) {
        Object.assign(derivativeForm, props.derivative);
    }
});
</script>
