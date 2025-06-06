<template>
    <div class="intro-y bg-slate-50">
        <div class="font-medium text-lg p-5 border-b">
            Type
        </div>

        <div v-if="birthdayVoucherId">
            <InfoAlert
                color="primary"
                class="m-5 mb-0"
            >
                <span class="flex">
                    Configuration for birthday vouchers has already been added. Please
                    <Link
                        class="mx-1 underline decoration-dotted"
                        :href="route('admin.vouchers_configuration.edit', birthdayVoucherId)"
                    >
                        click here
                    </Link>
                    to manage it.
                </span>
            </InfoAlert>
        </div>

        <div v-if="welcomeMemberVoucherId">
            <InfoAlert
                color="primary"
                class="m-5 mb-0"
            >
                <span class="flex">
                    Configuration for welcome member vouchers has already been added. Please
                    <Link
                        class="mx-1 underline decoration-dotted"
                        :href="route('admin.vouchers_configuration.edit', welcomeMemberVoucherId)"
                    >
                        click here
                    </Link>
                    to manage it.
                </span>
            </InfoAlert>
        </div>

        <div class="p-5">
            <div class="pb-5">
                <OutlinePrimaryButton
                    v-for="(voucherType, index) in voucherTypes"
                    :key="'voucher-type-'+index"
                    :text="voucherType.name"
                    class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                    :class="voucherConfigurationForm.voucher_type === voucherType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                    :disabled="voucherTypeEditable(voucherType)"
                    @click="selectVoucherType(voucherType)"
                />
            </div>

            <div
                v-if="voucherConfigurationForm.voucher_type"
                class="pt-5"
            >
                <div class="font-medium text-base border-b pb-2">
                    Restricted By Types
                </div>

                <div class="mt-4">
                    <Tippy
                        v-for="(restrictedByType, index) in restrictedByTypes"
                        :key="'restricted-by-type-'+index"
                        :content="selectTitle(restrictedByType.id,staticDetails)"
                    >
                        <OutlinePrimaryButton
                            :text="restrictedByType.name"
                            class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                            :class="voucherConfigurationForm.restricted_by_type === restrictedByType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                            :disabled="voucherConfigurationForm.voucher_type ===
                                staticDetails.birthday_voucher || voucherConfigurationForm.voucher_type ===
                                    staticDetails.welcome_member || voucherConfigurationForm.voucher_type ===
                                    staticDetails.loyalty_point "
                            @click="selectRestrictedByType(restrictedByType)"
                        />
                    </Tippy>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import InfoAlert from '@commonComponents/InfoAlert.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { route } from 'ziggy';

const props = defineProps({
    voucherTypes: {
        type: Array,
        required: true,
    },
    voucherConfigurationForm: {
        type: Object,
        required: true,
    },
    voucherConfiguration: {
        type: Object,
        default: null,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    restrictedByTypes: {
        type: Array,
        default: () => [],
    },
    birthdayVoucherId: {
        type: Number,
        default: null,
    },
    welcomeMemberVoucherId: {
        type: Number,
        default: null,
    },
});

const emits = defineEmits([
    'update:column-details',
    'clear:columns',
    'click:go-to-next',
    'add:new-tier-details'
]);

const selectVoucherType = (voucherType) => {
    emits('update:column-details', {
        column_name: 'tiers',
        value: [],
    });

    if (voucherType.id === props.staticDetails.birthday_voucher || voucherType.id === props.staticDetails.welcome_member || voucherType.id === props.staticDetails.loyalty_point) {
        emits('update:column-details', {
            column_name: 'restricted_by_type',
            value: props.staticDetails.restricted_by_member,
        });
    } else {
        emits('clear:columns', {
            restricted_by_type: null,
            discount_type: null,
        });
    }

    if (voucherType.id === props.staticDetails.tier_voucher) {
        emits('update:column-details', {
            column_name: 'discount_type',
            value: props.staticDetails.percentage_discount,
        });

        emits('add:new-tier-details');
    }

    if (voucherType.id === props.staticDetails.loyalty_point) {
        emits('update:column-details', {
            column_name: 'discount_type',
            value: props.staticDetails.percentage_discount,
        });

        emits('add:new-tier-details');
    }

    emits('update:column-details', {
        column_name: 'voucher_type',
        value: voucherType.id,
    });

    emits('clear:columns', {
        exclude_by_type_id: null,
        use_minimum_spend_amount: null,
        validity_days: 0,
        get_value: null,
    });
};

const selectRestrictedByType = (restrictedByType) => {
    emits('update:column-details', {
        column_name: 'restricted_by_type',
        value: restrictedByType.id,
    });

    goToNext();
};

const selectTitle = (restrictedById, staticDetails) => {
    if (restrictedById === staticDetails.restricted_by_member) {
        return 'Vouchers will be issued and generated only when a member is associated with the sale, and they will be redeemable.';
    }

    if (restrictedById === staticDetails.restricted_by_non_member) {
        return 'Vouchers will be redeemable and issued/generated only when there is no member attached to the sale.';
    }
};

const voucherTypeEditable = (voucherType) => {
    if (props.voucherConfiguration) {
        return true;
    }

    if (props.birthdayVoucherId && voucherType.id === props.staticDetails.birthday_voucher) {
        return true;
    }

    if (props.welcomeMemberVoucherId && voucherType.id === props.staticDetails.welcome_member) {
        return true;
    }

    return false;
};

const goToNext = () => {
    emits('click:go-to-next');
};
</script>
