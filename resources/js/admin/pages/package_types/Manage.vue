<template>
    <PageTitle :title="packageType ? 'Edit Package Type' : 'Add Package Type'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Package Type
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="packageType">Edit Package Type</span>
                        <span v-else>Add Package Type</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>
                <form
                    @submit.prevent="savePackageType();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="packageTypeForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.package_types.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="packageType ? 'Update' : 'Submit'"
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
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';

const props = defineProps({
    packageType: {
        type: Object,
        default: null,
    },
});

const packageTypeForm = useForm({
    name: null,
    watchEnabled: true,
});

const savePackageType = () => {
    packageTypeForm.watchEnabled = false;
    removeLocalStorage('PackageType');

    if (props.packageType) {
        packageTypeForm.put(route('admin.package_types.update', props.packageType.id));
        return;
    }
    packageTypeForm.post(route('admin.package_types.store'));
};

onMounted(() => {
    if (props.packageType) {
        removeLocalStorage('PackageType');
        packageTypeForm.name = props.packageType.name;
    } else {
        setLocalStorage('PackageType', packageTypeForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.packageType) {
        saveLocalStorage('PackageType', packageTypeForm);
    }
};

const clearFormData = () => {
    packageTypeForm.reset();
};

watch(packageTypeForm, () => {
    if (packageTypeForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
