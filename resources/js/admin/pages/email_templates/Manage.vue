<template>
    <PageTitle :title="emailTemplate ? 'Edit Email Template' : 'Add Email Template'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Email Template
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="emailTemplate">Edit Email Template</span>
                        <span v-else>Add Email Template</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveEmailTemplate();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <FormInput
                                    v-model:input-value="emailTemplateForm.name"
                                    :required="true"
                                    input-label="Name"
                                    input-name="name"
                                    validation-field-name="name"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-6 py-4">
                            <EmailEditor 
                                ref="emailEditorRef" 
                                @load="onEditorReady" 
                            />
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.email_templates.index')">
                                <SecondaryButton
                                    class="w-24 mr-1"
                                    text="Cancel"
                                    type="button"
                                />
                            </Link>

                            <PrimaryButton
                                :text="emailTemplate ? 'Update' : 'Submit'"
                                class="w-24"
                                type="submit"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import FormInput from "@/common/components/FormInput.vue";
import PrimaryButton from "@/common/components/PrimaryButton.vue";
import SecondaryButton from "@/common/components/SecondaryButton.vue";
import { useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { EmailEditor } from 'vue-email-editor';
import { route } from 'ziggy';

const props = defineProps({
    emailTemplate: {
        type: Object,
        default: null,
    },
});

const emailEditorRef = ref(null);

const emailTemplateForm = useForm({
    name: null,
    template_json: null,
    html: null
});

const onEditorReady = () => {
    loadEmailTemplate();
};

const loadEmailTemplate = () => {
    if (props.emailTemplate && props.emailTemplate.template_json) {
        const templateJson = JSON.parse(props.emailTemplate.template_json);
        emailEditorRef.value?.editor?.loadDesign(templateJson);
    }
};

const prepareEmailTemplateFromDetails = async () => {
    if(props.emailTemplate) emailTemplateForm.template_json = JSON.parse(props.emailTemplate.template_json);
    await new Promise((resolve) => {
        emailEditorRef.value?.editor.saveDesign((design) => {
            emailTemplateForm.template_json = design;
            resolve();
        });
    });

    await new Promise((resolve) => {
        emailEditorRef.value?.editor.exportHtml((data) => {
            emailTemplateForm.html = data?.html;
            resolve();
        });
    });
};

const saveEmailTemplate = async () => {
    await prepareEmailTemplateFromDetails();

    if (props.emailTemplate) {
        emailTemplateForm.post(route("admin.email_templates.update", props.emailTemplate.id));
    } else {
        emailTemplateForm.post(route("admin.email_templates.store"));
    }
};

onMounted(() => {
    if (props.emailTemplate) {
        Object.assign(emailTemplateForm, props.emailTemplate);
        emailEditorRef.value?.editor?.loadDesign(JSON.parse(props.emailTemplate.template_json.toString()));
    }
});
</script>

