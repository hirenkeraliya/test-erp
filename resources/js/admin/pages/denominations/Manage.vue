<template>
    <PageTitle :title="denomination ? 'Edit Denomination' : 'Add Denomination'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Denominations
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="denomination">Edit Denominations</span>
                        <span v-else>Add Denominations</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveDenomination();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="denominationForm.denomination"
                                    input-name="denomination"
                                    input-label="Denomination"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.denominations.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="denomination ? 'Update' : 'Submit'"
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
    denomination: {
        type: Object,
        default: null,
    },
});

const denominationForm = useForm({
    denomination: null,
});

const saveDenomination = () => {
    if (props.denomination) {
        denominationForm.put(route('admin.denominations.update', props.denomination.id));
        return;
    }
    denominationForm.post(route('admin.denominations.store'));
};

onMounted(() => {
    if (props.denomination) {
        Object.assign(denominationForm, props.denomination);
    }
});
</script>
