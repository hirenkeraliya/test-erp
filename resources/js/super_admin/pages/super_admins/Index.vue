<template>
    <PageTitle title="SuperAdmins" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Super Admins
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.super_admins.create')">
                <PrimaryButton
                    text="Add New SuperAdmin"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('super_admin.super_admins.fetch')"
        :columns="state.columns"
    >
        <template #email="data">
            {{ data.item.email }}
            <Tippy
                v-if="!data.item.is_email_verified && data.item.email"
                :content="'Updating your email will require re-verification.'"
            >
                <TriangleAlert
                    class="text-red-400 ml-2"
                    :size="15"
                />
            </Tippy>
        </template>
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.super_admins.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.super_admins.change_password', data.item.id)"
                >
                    <Unlock class="w-4 h-4 mr-1" />
                    Change Password
                </Link>
                <Link
                    v-if="!data.item.is_email_verified && data.item.email"
                    class="flex items-center"
                    :href="route('super_admin.super_admins.resend_verification_email', data.item.id)"
                >
                    <Tippy
                        :content="'Resend mail'"
                    >
                        <Mail class="w-4 h-5 mr-2" />
                    </Tippy>
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Mail, TriangleAlert, Unlock } from 'lucide-vue-next';

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'name',
        }, {
            key: 'username',
            sortable: true
        }, {
            key: 'email',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        },
    ]
});
</script>
