<template>
    <PageTitle :title="cashMovementReason ? 'Edit Cash Movement Reason' : 'Add Cash Movement Reason'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cash Flow Codes
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="cashMovementReason">Edit Cash Movement Reason</span>
                        <span v-else>Add Cash Movement Reason</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveCashMovementReason();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="cashMovementReasonForm.reason"
                                    input-name="reason"
                                    input-label="Reason"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="cashMovementReasonForm.type_id"
                                    :records="cashMovementTypes"
                                    input-label="Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.cash_movement_reasons.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="cashMovementReason ? 'Update' : 'Submit'"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    cashMovementReason: {
        type: Object,
        default: null,
    },
    cashMovementTypes: {
        type: Array,
        required: true,
    },
});

const cashMovementReasonForm = useForm({
    reason: null,
    type_id: null,
});

const saveCashMovementReason = () => {
    if (props.cashMovementReason) {
        cashMovementReasonForm.put(route('admin.cash_movement_reasons.update', props.cashMovementReason.id));
        return;
    }
    cashMovementReasonForm.post(route('admin.cash_movement_reasons.store'));
};

onMounted(() => {
    if (props.cashMovementReason) {
        Object.assign(cashMovementReasonForm, props.cashMovementReason);
    }
});
</script>
