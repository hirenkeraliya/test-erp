<template>
    <PageTitle :title="externalConnection ? 'Edit External Connection' : 'Add External Connection'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            External Connections
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="externalConnection">Edit External Connection</span>
                        <span v-else>Add External Connection</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveExternalConnection();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalConnectionForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="! externalConnection"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="externalConnectionForm.url"
                                    input-name="url"
                                    input-label="Url"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('super_admin.external_connections.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="externalConnection ? 'Update' : 'Submit'"
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
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    externalConnection: {
        type: Object,
        default: null,
    },
});

const externalConnectionForm = useForm({
    name: null,
    url: null,
});

const saveExternalConnection = () => {
    if (props.externalConnection) {
        router.post(route('super_admin.external_connections.update', props.externalConnection.id), externalConnectionForm);
        return;
    }

    router.post(route('super_admin.external_connections.store'), externalConnectionForm);
};

onMounted(() => {
    if (props.externalConnection) {
        Object.assign(externalConnectionForm, props.externalConnection);
    }
});
</script>
