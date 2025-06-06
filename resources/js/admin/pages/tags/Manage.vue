<template>
    <PageTitle :title="tag ? 'Edit Tag' : 'Add Tag'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Tags
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="tag">Edit Tag</span>
                        <span v-else>Add Tag</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveTag();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="tagForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                    validation-field-name="name"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.tags.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="tag ? 'Update' : 'Submit'"
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
import { useForm, router } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';

const props = defineProps({
    tag: {
        type: Object,
        default: null,
    }
});

const tagForm = useForm({
    name: null,
});

const saveTag = () => {
    if (props.tag) {
        tagForm.put(route('admin.tags.update', props.tag.id));
        return;
    }
    axios.post(route('admin.tags.store'), tagForm).then(() => {
        router.get(route('admin.tags.index'));
    });
};

onMounted(() => {
    if (props.tag) {
        Object.assign(tagForm, props.tag);
    }
});
</script>
