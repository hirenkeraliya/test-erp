<template>
    <PageTitle :title="complimentaryItemReason ? 'Edit Complimentary Setup' : 'Add Complimentary Setup'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Complimentary Setup
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ complimentaryItemReason ? 'Edit' : 'Add' }} Complimentary Setup
                    </h2>
                </div>
                <form @submit.prevent="saveComplimentaryItemReason();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="complimentaryItemReasonForm.reason"
                                    :required="true"
                                    input-name="reason"
                                    input-label="Reason"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.complimentary_item_reasons.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="complimentaryItemReason ? 'Update' : 'Submit'"
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
    complimentaryItemReason: {
        type: Object,
        default: null,
    }
});

const complimentaryItemReasonForm = useForm({
    reason: null,
});

const saveComplimentaryItemReason = () => {
    if (props.complimentaryItemReason) {
        complimentaryItemReasonForm.put(route('admin.complimentary_item_reasons.update', props.complimentaryItemReason.id));
        return;
    }
    complimentaryItemReasonForm.post(route('admin.complimentary_item_reasons.store'));
};

onMounted(() => {
    if (props.complimentaryItemReason) {
        Object.assign(complimentaryItemReasonForm, props.complimentaryItemReason);
    }
});
</script>
