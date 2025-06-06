<template>
    <PageTitle :title="voidSaleReason ? 'Edit Void Sale Reason' : 'Add Void Sale Reason'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Void Sale Reasons
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="voidSaleReason">Edit Void Sale Reason</span>
                        <span v-else>Add Void Sale Reason</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveVoidSaleReason();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="voidSaleReasonForm.reason"
                                    input-name="reason"
                                    input-label="Reason"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="state.types"
                                    :records="types"
                                    input-label="Types"
                                    :required="true"
                                    validation-field-name="type_ids"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.void_sale_reasons.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="voidSaleReason ? 'Update' : 'Submit'"
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
import { onMounted, reactive } from 'vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { route } from 'ziggy';

const props = defineProps({
    voidSaleReason: {
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

const voidSaleReasonForm = useForm({
    reason: null,
    type_ids: [],
});

const state = reactive({
    types: [],
});

const saveVoidSaleReason = () => {
    prepareVoidSaleReasonFormDetails();
    if (props.voidSaleReason) {
        voidSaleReasonForm.put(route('admin.void_sale_reasons.update', props.voidSaleReason.id));
        return;
    }
    voidSaleReasonForm.post(route('admin.void_sale_reasons.store'));
};

const prepareVoidSaleReasonFormDetails = () => {
    voidSaleReasonForm.type_ids = state.types.map((type) => {
        return type.id;
    });
};

onMounted(() => {
    if (props.voidSaleReason) {
        Object.assign(voidSaleReasonForm, props.voidSaleReason);
        state.types = props.voidSaleReason.types;
    }
});
</script>
