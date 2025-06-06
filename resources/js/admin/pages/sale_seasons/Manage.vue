<template>
    <PageTitle :title="saleSeason ? 'Edit Sale Season' : 'Add Sale Season'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Season
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="saleSeason">Edit Sale Season</span>
                        <span v-else>Add Sale Season</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveSaleSeason();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleSeasonForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="saleSeasonForm.start_date"
                                    input-label="Start Date"
                                    validation-field-name="start_date"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="saleSeasonForm.end_date"
                                    input-label="End Date"
                                    validation-field-name="end_date"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.sale_seasons.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="saleSeason ? 'Update' : 'Submit'"
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
import JDatePicker from '@commonComponents/JDatePicker.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    saleSeason: {
        type: Object,
        default: null,
    },
});

const saleSeasonForm = useForm({
    name: null,
    start_date: null,
    end_date: null,
    watchEnabled: true,
});

const saveSaleSeason = () => {
    saleSeasonForm.watchEnabled = false;
    removeLocalStorage('saleSeason');
    if (props.saleSeason) {
        saleSeasonForm.put(route('admin.sale_seasons.update', props.saleSeason.id));
        return;
    }
    saleSeasonForm.post(route('admin.sale_seasons.store'));
};

const checkSaveLocalStorage = () => {
    if (!props.saleSeason) {
        saveLocalStorage('saleSeason', saleSeasonForm);
    }
};

const clearFormData = () => {
    saleSeasonForm.reset();
};

watch(saleSeasonForm, () => {
    if (saleSeasonForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

onMounted(() => {
    if (props.saleSeason) {
        removeLocalStorage('saleSeason');
        Object.assign(saleSeasonForm, props.saleSeason);
    } else {
        setLocalStorage('saleSeason', saleSeasonForm);
    }
});
</script>
