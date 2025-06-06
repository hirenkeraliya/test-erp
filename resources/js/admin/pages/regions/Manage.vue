<template>
    <PageTitle :title="region ? 'Edit Region' : 'Add Region'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Regions
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="region">Edit Region</span>
                        <span v-else>Add Region</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveRegion();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="regionForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="regionForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="regionForm.manager_name"
                                    input-name="manager_name"
                                    input-label="Manager name"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="regionForm.manager_email"
                                        input-name="manager_email"
                                        input-label="Manager Email"
                                    />
                                    <Tippy
                                        v-if="region ? !region.is_email_verified && regionForm.manager_email : regionForm.manager_email"
                                        :content="'Your email will require verification.'"
                                    >
                                        <TriangleAlert
                                            class="text-red-400 ml-2 mt-7"
                                            :size="20"
                                        />
                                    </Tippy>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.regions.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="region ? 'Update' : 'Submit'"
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
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    region: {
        type: Object,
        default: null,
    }
});

const regionForm = useForm({
    name: null,
    code: null,
    manager_name: null,
    manager_email: null,
});

const saveRegion = () => {
    if (props.region) {
        regionForm.put(route('admin.regions.update', props.region.id));
        return;
    }

    regionForm.post(route('admin.regions.store'));
};

onMounted(() => {
    if (props.region) {
        Object.assign(regionForm, props.region);
    }
});
</script>
