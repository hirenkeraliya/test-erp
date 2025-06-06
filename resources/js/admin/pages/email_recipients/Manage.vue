<template>
    <PageTitle :title="emailRecipient ? 'Edit Email Recipient' : 'Add Email Recipient'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Email Recipients
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="emailRecipient">Edit Email Recipient</span>
                        <span v-else>Add Email Recipient</span>
                    </h2>
                </div>
                <form
                    @submit.prevent="saveEmailRecipient();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="emailRecipientForm.email_type_id"
                                    :records="emailTypes"
                                    input-label="Email Type"
                                    validation-field-name="email_type_id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="emailRecipientForm.receiver_name"
                                    input-name="receiver_name"
                                    input-label="Receiver Name"
                                    :required="true"
                                    validation-field-name="receiver_name"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="emailRecipientForm.receiver_email"
                                        type="email"
                                        input-name="receiver_email"
                                        input-label="Receiver Email"
                                        :required="true"
                                        validation-field-name="receiver_email"
                                    />
                                    <Tippy
                                        v-if="emailRecipient ? !emailRecipient.is_email_verified && emailRecipientForm.receiver_email : emailRecipientForm.receiver_email"
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
                            <Link :href="route('admin.email_recipients.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="emailRecipient ? 'Update' : 'Submit'"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    emailRecipient: {
        type: Object,
        default: null,
    },
    emailTypes: {
        type: Array,
        required: true,
    },
});

const emailRecipientForm = useForm({
    email_type_id: null,
    receiver_name: null,
    receiver_email: null,
});

const saveEmailRecipient = () => {
    if (props.emailRecipient) {
        emailRecipientForm.put(route('admin.email_recipients.update', props.emailRecipient.id));
        return;
    }
    emailRecipientForm.post(route('admin.email_recipients.store'));
};

onMounted(() => {
    if (props.emailRecipient) {
        Object.assign(emailRecipientForm, props.emailRecipient);
    }
});
</script>
