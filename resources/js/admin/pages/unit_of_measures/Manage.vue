<template>
    <PageTitle :title="unitOfMeasure ? 'Edit Unit of Measure' : 'Add Unit of Measure'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Unit of Measures (UOM)
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="unitOfMeasure">Edit Unit of Measure</span>
                        <span v-else>Add Unit of Measure</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form
                    @submit.prevent="saveUnitOfMeasure();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="unitOfMeasureForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <JSwitch
                                v-model:is-checked="unitOfMeasureForm.allow_decimal_qty"
                                input-label="Allow Decimal Quantity?"
                                class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                            />
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.unit_of_measures.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="unitOfMeasure ? 'Update' : 'Submit'"
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
import { onMounted, watch } from 'vue';
import { route } from 'ziggy';
import JSwitch from '@commonComponents/JSwitch.vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    unitOfMeasure: {
        type: Object,
        default: null,
    },
});

const unitOfMeasureForm = useForm({
    name: null,
    allow_decimal_qty: false,
    watchEnabled: true,
});

const saveUnitOfMeasure = () => {
    unitOfMeasureForm.watchEnabled = false;
    removeLocalStorage('unitOfMeasure');

    if (props.unitOfMeasure) {
        unitOfMeasureForm.put(route('admin.unit_of_measures.update', props.unitOfMeasure.id));
        return;
    }
    unitOfMeasureForm.post(route('admin.unit_of_measures.store'));
};

onMounted(() => {
    if (props.unitOfMeasure) {
        removeLocalStorage('unitOfMeasure');
        unitOfMeasureForm.name = props.unitOfMeasure.name;
        unitOfMeasureForm.allow_decimal_qty = props.unitOfMeasure.allow_decimal_qty;
    } else {
        setLocalStorage('unitOfMeasure', unitOfMeasureForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.unitOfMeasure) {
        saveLocalStorage('unitOfMeasure', unitOfMeasureForm);
    }
};

const clearFormData = () => {
    unitOfMeasureForm.reset();
};

watch(unitOfMeasureForm, () => {
    if (unitOfMeasureForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
