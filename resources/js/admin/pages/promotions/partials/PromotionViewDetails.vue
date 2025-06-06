<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Promotion Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="col-span-12 overflow-auto intro-y">
                <table class="table table-striped -mt-2 w-full">
                    <tbody>
                        <tr>
                            <td>
                                Id
                            </td>

                            <td>
                                {{ promotionDetails.id }}
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Name
                            </td>

                            <td>
                                {{ promotionDetails.name }}
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Locations
                            </td>

                            <td>
                                {{ prepareImplodedNames(promotionDetails.locations) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Promotion Applicable Type
                            </td>

                            <td>
                                {{ promotionDetails.promotion_type }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Type
                            </td>

                            <td>
                                {{ promotionDetails.type }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Time Frame Type
                            </td>

                            <td>
                                {{ promotionDetails.timeframe_type }}
                            </td>
                        </tr>

                        <tr v-if="promotionDetails.promotion_tiers">
                            <td>
                                Promotion Details
                            </td>
                            <td v-if="promotionDetails.promotion_tiers.length > 0">
                                <tr
                                    v-for="(promotion, index) in promotionDetails.promotion_tiers"
                                    :key="index"
                                >
                                    <div
                                        v-if="promotionDetails.cart_wide_promotion_type === 'As Per Amount' || promotionDetails.cart_wide_promotion_type === 'As Per Payment Type' ||promotionDetails.item_wise_promotion_type === 'As Per Amount Limited To Brands' || promotionDetails.item_wise_promotion_type === 'As Per Amount Get Off On Others'"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Minimum Spend
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.minimum_spend_amount) }}
                                        </div>

                                        <div
                                            v-if="promotionDetails.item_wise_promotion_type === 'As Per Amount Get Off On Others'"
                                        >
                                            <h1 v-if="index === 0">
                                                Maximum Spend
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.maximum_spend_amount) }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                {{ promotionDetails.type }}
                                            </h1>
                                            {{ promotionDetails.type=='Flat'? displayAmountWithCurrencySymbol(promotion.flat_amount): displayAmountWithPercentageSymbol(promotion.percentage) }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="promotionDetails.item_wise_promotion_type === 'As Per Amount Limited To Price'"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Minimum Spend
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.minimum_spend_amount) }}
                                        </div>
                                        <div>
                                            <h1 v-if="index === 0">
                                                Maximum Spend
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.maximum_spend_amount) }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                {{ promotionDetails.type }}
                                            </h1>
                                            {{ promotionDetails.type=='Flat'? displayAmountWithCurrencySymbol(promotion.flat_amount): displayAmountWithPercentageSymbol(promotion.percentage) }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="(promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.buy_2_get_50_off_on_others || promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.buy_any_3_or_more_and_get_30_off || promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.percentage_discount_for_next_item)"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Buy Quantity
                                            </h1>
                                            {{ promotion.buy_quantity }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Percentage
                                            </h1>
                                            {{ displayAmountWithPercentageSymbol(promotion.percentage) }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="(promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.buy_2_get_rm_50_off_on_others || promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.buy_any_3_or_more_and_get_rm_30_off || promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.flat_discount_for_next_item)"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Buy Quantity
                                            </h1>
                                            {{ promotion.buy_quantity }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Flat
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.flat_amount) }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="(promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.buy_3_get_1 || promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.cheapest_free)"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Buy Quantity
                                            </h1>
                                            {{ promotion.buy_quantity }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Get Quantity
                                            </h1>
                                            <p> {{ promotion.get_quantity }} </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="(promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.gift_with_purchase)"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Minimum Spend
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.minimum_spend_amount) }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Free Quantity
                                            </h1>
                                            {{ promotion.free_quantity }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="(promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.bundle_buy)"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Buy Quantity
                                            </h1>
                                            {{ promotion.buy_quantity }}
                                        </div>

                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Amount
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.amount) }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="(promotionDetails.item_wise_promotion_type_id === itemWisePromotionType.buy_2_and_get_1_quantity_at_rm1 )"
                                        class="grid grid-cols-3 gap-24 sm:gap-4"
                                    >
                                        <div>
                                            <h1 v-if="index === 0">
                                                Buy Quantity
                                            </h1>
                                            {{ promotion.buy_quantity }}
                                        </div>
                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Get Quantity
                                            </h1>
                                            {{ promotion.get_quantity }}
                                        </div>
                                        <div class="ml-8">
                                            <h1 v-if="index === 0">
                                                Amount
                                            </h1>
                                            {{ displayAmountWithCurrencySymbol(promotion.amount) }}
                                        </div>
                                    </div>
                                </tr>
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Percentage
                            </td>
                            <td>
                                {{ promotionDetails.percentage ? displayAmountWithPercentageSymbol(promotionDetails.percentage) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Flat
                            </td>

                            <td>
                                {{ promotionDetails.flat_amount ? displayAmountWithCurrencySymbol(promotionDetails.flat_amount) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Promotion Apply
                            </td>

                            <td>
                                <div v-if="promotionDetails.allow_employee">
                                    <p class="font-bold">
                                        Available For The Employees
                                    </p>

                                    <p
                                        v-if="promotionDetails.employee_groups.length > 0"
                                        class="ml-5"
                                    >
                                        Employee Groups:
                                        <span
                                            v-for="(employeeGroup, index) in promotionDetails.employee_groups"
                                            :key="index"
                                        >
                                            {{ employeeGroup.name }},
                                        </span>
                                    </p>
                                </div>

                                <div v-if="promotionDetails.allow_registered_member">
                                    <p class="font-bold">
                                        Available when a member is attached to the sale
                                    </p>
                                    <p
                                        v-if="promotionDetails.member_groups.length > 0"
                                        class="ml-5"
                                    >
                                        Member Groups:
                                        <span
                                            v-for="(memberGroup, index) in promotionDetails.member_groups"
                                            :key="index"
                                        >
                                            {{ memberGroup.name }},
                                        </span>
                                    </p>
                                </div>

                                <div v-if="promotionDetails.allow_walk_in_member">
                                    <p>
                                        <b>
                                            Available for the walk in member
                                        </b>
                                    </p>
                                </div>

                                <div v-if="promotionDetails.is_membership_required">
                                    <p class="font-bold">
                                        Membership Required
                                    </p>

                                    <p
                                        v-if="promotionDetails.memberships.length > 0"
                                        class="ml-5"
                                    >
                                        Memberships:
                                        <span
                                            v-for="(membership, index) in promotionDetails.memberships"
                                            :key="index"
                                        >
                                            {{ membership.name }},
                                        </span>
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                PromoCodes
                            </td>
                            <td v-if="promotionDetails.promo_codes">
                                <div v-if="promotionDetails.promo_codes.length > 0 ">
                                    <span
                                        v-for="(promoCode, index) in promotionDetails.promo_codes"
                                        :key="index"
                                    >
                                        {{ promoCode.name }},
                                    </span>
                                </div>
                                <div v-else>
                                    N/A
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithCurrencySymbol, prepareImplodedNames, displayAmountWithPercentageSymbol } from '@commonServices/helper';
import { X } from 'lucide-vue-next';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    promotionDetails: {
        type: Object,
        required: true,
    },
    itemWisePromotionType: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};

</script>
