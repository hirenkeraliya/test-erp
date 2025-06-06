<template>
    <MemberPurchaseDetails
        :member-id="memberId"
        fetch-member-sale-url="store_manager.members.fetch_member_sale_details"
        fetch-member-sale-return-url="store_manager.members.fetch_member_sale_return_details"
        fetch-member-details-url="store_manager.members.fetch_member_details"
    />
</template>

<script setup>
import MemberPurchaseDetails from '@commonPages/MemberPurchaseDetails.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { MemberDetailsHelpText } from '@commonStores/documentation';

defineProps({
    memberId: {
        type: Number,
        required: true,
    },
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(MemberDetailsHelpText());
</script>
