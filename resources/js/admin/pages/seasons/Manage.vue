<template>
    <PageTitle :title="season ? 'Edit Season' : 'Add Season'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Seasons
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="season">Edit Season</span>
                        <span v-else>Add Season</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveSeason();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="seasonForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="seasonForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.seasons.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="season ? 'Update' : 'Submit'"
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
    season: {
        type: Object,
        default: null,
    }
});

const seasonForm = useForm({
    name: null,
    code: null,
    watchEnabled: true,
});

const saveSeason = () => {
    seasonForm.watchEnabled = false;
    removeLocalStorage('season');

    if (props.season) {
        seasonForm.put(route('admin.seasons.update', props.season.id));
        return;
    }

    seasonForm.post(route('admin.seasons.store'));
};

onMounted(() => {
    if (props.season) {
        removeLocalStorage('season');
        Object.assign(seasonForm, props.season);
    } else {
        setLocalStorage('season', seasonForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.season) {
        saveLocalStorage('season', seasonForm);
    }
};

const clearFormData = () => {
    seasonForm.reset();
};

watch(seasonForm, () => {
    if (seasonForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
