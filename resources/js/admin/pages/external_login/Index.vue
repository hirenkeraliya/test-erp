<template>
    <PageTitle title="External Companies" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2
            v-if="externalCompanies.length"
            class="text-lg font-medium mr-auto"
        >
            External Companies Login
        </h2>
        <h2
            v-else
            class="text-lg font-medium mr-auto"
        >
            No External Companies configured. Please contact the super admin.
        </h2>
    </div>

    <div
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-0 sm:gap-5 mt-10"
    >
        <div
            v-for="(externalCompany, index) in externalCompanies"
            :key="index"
            class="border p-4 rounded-lg hover:bg-slate-100 cursor-pointer space-y-2 mb-4 sm:mb-0"
            @click="loginExternalAdmin(externalCompany.id)"
        >
            <div class="flex">
                <ArrowUpRightFromSquareIcon class="w-5 h-5 mr-4" />
                <div
                    class="space-x-3"
                >
                    <span class="text-sm font-bold">Name :</span>
                    <span class="text-sm text-gray-500 dark:text-white/70">{{ externalCompany.name }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { route } from 'ziggy';
import { showErrorNotification } from '@commonServices/notifier';
import { ArrowUpRightFromSquareIcon } from 'lucide-vue-next';

defineProps({
    externalCompanies: {
        type: Array,
        required: true,
    },
});

const loginExternalAdmin = (externalCompanyId) => {
    axios.get(route('admin.external_logins.get_external_login_details', externalCompanyId))
        .then((response) => {
            window.open(response.data.url, '_blank');
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

</script>
