<template>
    <PageTitle title="Edit Promotion" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promotions
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-12 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <InfoAlert
                color="primary"
                class="mb-5"
            >
                <span class="flex">
                    Promotions will be applied after the dream price and price override have been applied.
                </span>
            </InfoAlert>
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Edit Promotion ({{ promotionName }}) </span>
                    </h2>
                </div>

                <form @submit.prevent="updatePromotion();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 intro-y">
                            <PromotionSteps
                                :steps="state.steps"
                                :current-step="state.currentStep"
                                @update:step-key="goToStep"
                            />

                            <div class="col-span-12 sm:col-span-12 md:col-span-8 lg:col-span-9 xl:col-span-9 2xl:col-span-10">
                                <div
                                    v-if="state.currentStep === state.stepBasicDetails"
                                    class="intro-y bg-slate-50"
                                >
                                    <div class="font-medium text-lg py-2 px-5 sm:p-5 border-b">
                                        Basic Details
                                    </div>

                                    <div class="p-5">
                                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-3">
                                                <FormInput
                                                    v-model:input-value="promotionForm.name"
                                                    :required="true"
                                                    input-name="name"
                                                    input-label="Promotion Name"
                                                />
                                            </div>

                                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                                                <div class="font-medium text-lg py-5 border-b">
                                                    Restrictions
                                                </div>

                                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                                    <JSwitch
                                                        input-label="Applicable When Dream Price Is Applied"
                                                        :is-checked="promotionForm.dream_price_applicable"
                                                        @update:is-checked="updateTheColumn('dream_price_applicable', $event)"
                                                    />
                                                </div>
                                            </div>

                                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-7 xl:col-span-7">
                                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-6">
                                                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JSwitch
                                                                input-label="Allow Registered Member?"
                                                                :is-checked="promotionForm.allow_registered_member"
                                                                @update:is-checked="updateTheColumn('allow_registered_member', $event)"
                                                            />
                                                        </div>

                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JMultiSelect
                                                                v-if="promotionForm.allow_registered_member"
                                                                :records="memberGroups"
                                                                input-label="Member Groups"
                                                                validation-field-name="member_groups_ids"
                                                                :selected-records="promotionForm.member_groups"
                                                                @update:selected-records="updateSelectedMemberGroups"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-6">
                                                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JSwitch
                                                                input-label="Allow Employees?"
                                                                :is-checked="promotionForm.allow_employee"
                                                                @update:is-checked="updateTheColumn('allow_employee', $event)"
                                                            />
                                                        </div>

                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JMultiSelect
                                                                v-if="promotionForm.allow_employee"
                                                                :records="employeeGroups"
                                                                input-label="Employee Groups"
                                                                validation-field-name="employee_groups_ids"
                                                                :selected-records="promotionForm.employee_groups"
                                                                @update:selected-records="updateSelectedEmployeeGroups"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-6">
                                                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JSwitch
                                                                input-label="Is Membership Required?"
                                                                :is-checked="promotionForm.is_membership_required"
                                                                @update:is-checked="updateTheColumn('is_membership_required', $event)"
                                                            />
                                                        </div>

                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JMultiSelect
                                                                v-if="promotionForm.is_membership_required"
                                                                :records="memberships"
                                                                required="true"
                                                                input-label="Memberships"
                                                                validation-field-name="membership_ids"
                                                                :selected-records="promotionForm.memberships"
                                                                @update:selected-records="updateSelectedMemberships"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                    <JSwitch
                                                        input-label="Allow Walk In Member?"
                                                        :is-checked="promotionForm.allow_walk_in_member"
                                                        @update:is-checked="updateTheColumn('allow_walk_in_member', $event)"
                                                    />
                                                </div>

                                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                    <JSwitch
                                                        input-label="Is Available In Pos?"
                                                        :is-checked="promotionForm.is_available_in_pos"
                                                        @update:is-checked="updateTheColumn('is_available_in_pos', $event)"
                                                    />
                                                </div>

                                                <div
                                                    v-if="staticDetails.cart_type_as_per_amount === promotionForm.cart_wide_promotion_type_id"
                                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-6"
                                                >
                                                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-3 items-center">
                                                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                                            <JSwitch
                                                                input-label="Is Available In Ecommerce?"
                                                                :is-checked="promotionForm.is_available_in_ecommerce"
                                                                @update:is-checked="updateTheColumn('is_available_in_ecommerce', $event)"
                                                            />
                                                        </div>

                                                        <div
                                                            v-if="promotionForm.is_available_in_ecommerce"
                                                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                                        >
                                                            <JMultiSelect
                                                                :records="saleChannels"
                                                                input-label="Sale Channels"
                                                                :required="true"
                                                                validation-field-name="sale_channel_ids"
                                                                :selected-records="promotionForm.sale_channels"
                                                                @update:selected-records="updateSelectedSaleChannels"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12 mt-5">
                                                <PromoCode
                                                    :promotion-usage-types="promotionUsageTypes"
                                                    :promotion-single-usage="promotionSingleUsage"
                                                    :promotion-form="promotionForm"
                                                    :promotion-id="promotion.data.id"
                                                    @add-new-promo-code="addNewPromoCode"
                                                    @clear-promo-codes="clearPromoCodes"
                                                    @set-promo-codes="setPromoCodes"
                                                    @update-promo-code-details="updatePromoCodeDetails"
                                                    @update:column-details="updateColumnDetails"
                                                    @update:valid-promo-codes-via-upload="updateUploadColumnDetailsForPromoCode"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="state.currentStep === state.stepPromotionDetails"
                                    class="intro-y bg-slate-50"
                                >
                                    <PromotionDetails
                                        :promotion-form="promotionForm"
                                        :categories="categories"
                                        :tags="tags"
                                        :product-collections="productCollections"
                                        :static-details="staticDetails"
                                        :static-product-upload-types="staticProductUploadTypes"
                                        :payment-types="paymentTypes"
                                        :selected-payment-types="promotionForm.payment_types"
                                        @update:column-details="updateColumnDetails"
                                        @update:tier-value-details="updateTierValueDetails"
                                        @add:new-tier-details="addNewTierDetails"
                                        @remove:tier-details-of="removeTierDetailsOf"
                                        @update:selected-payment-types="updateSelectedPaymentTypes"
                                    />
                                </div>

                                <div
                                    v-if="state.currentStep === state.stepTimeframe"
                                    class="intro-y bg-slate-50"
                                >
                                    <div class="font-medium text-lg py-2 px-5 sm:p-5 border-b">
                                        Timeframe
                                    </div>

                                    <TimeframeDetails
                                        :time-frames="timeFrames"
                                        :promotion-form="promotionForm"
                                        :static-details="staticDetails"
                                        @remove:week-day="removeWeekDay"
                                        @add:new-week-day="addNewWeekDay"
                                        @remove:month-date="removeMonthDate"
                                        @add:new-month-date="addNewMonthDate"
                                        @clear:columns="clearColumns"
                                        @update:column-details="updateColumnDetails"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.promotions.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mb-1 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                v-if="state.currentStep === state.stepTimeframe"
                                type="submit"
                                text="Save"
                                class="w-24 mb-1 ml-1"
                            />

                            <PrimaryButton
                                v-else
                                type="button"
                                text="Next"
                                class="w-24 mb-1 ml-1"
                                @click="goToNext()"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { route } from 'ziggy';
import { onMounted, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import FormInput from '@commonComponents/FormInput.vue';
import PromotionDetails from '@adminPages/promotions/partials/PromotionDetails.vue';
import TimeframeDetails from '@adminPages/promotions/partials/TimeframeDetails.vue';
import PromotionSteps from '@adminPages/promotions/partials/PromotionSteps.vue';
import { showErrorNotification } from '@commonServices/notifier';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PromoCode from '@adminPages/promotions/partials/PromoCode.vue';

const props = defineProps({
    promotion: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    timeFrames: {
        type: Array,
        required: true
    },
    promotionName: {
        type: String,
        required: true
    },
    staticDetails: {
        type: Object,
        required: true
    },
    staticProductUploadTypes: {
        type: Object,
        required: true
    },
    memberGroups: {
        type: Array,
        required: true,
    },
    tags: {
        type: Array,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    employeeGroups: {
        type: Array,
        required: true,
    },
    promotionUsageTypes: {
        type: Array,
        required: true
    },
    promotionSingleUsage: {
        type: Number,
        required: true
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    paymentTypes: {
        type: Array,
        required: true
    },
    memberships: {
        type: Array,
        required: true
    },
});

const promotionForm = useForm({
    name: null,
    allow_registered_member: false,
    allow_employee: false,
    allow_walk_in_member: false,
    dream_price_applicable: true,
    regular_products: [],
    regular_product_ids: [],
    buy_products: [],
    buy_product_ids: [],
    get_products: [],
    get_product_ids: [],
    category_ids: [],
    categories: [],
    brand_ids: [],
    brands: [],
    tag_ids: [],
    tags: [],
    product_collection_ids: [],
    productCollections: [],
    member_groups: [],
    member_group_ids: [],
    employee_groups: [],
    employee_group_ids: [],
    start_date: null,
    end_date: null,
    week_days: [],
    month_dates: [],
    start_time: null,
    end_time: null,
    date: null,
    promotion_applicable_type_id: null,
    discount_type_id: null,
    cart_wide_promotion_type_id: null,
    item_wise_promotion_type_id: null,
    timeframe_type_id: null,
    is_automatic: true,
    usage_type: props.promotionSingleUsage,
    promo_codes: [''],
    percentage: null,
    flat_amount: null,
    is_available_in_pos: true,
    is_available_in_ecommerce: false,
    is_membership_required: false,
    tiers: [
        {
            buy_value: null,
            get_value: null,
        }
    ],
    sale_channels: [],
    sale_channel_ids: [],
    payment_types: [],
    payment_type_ids: [],
    memberships: [],
    membership_ids: []
});

const state = reactive({
    steps: [
        {
            key: 'promotion-details',
            label: 'Promotion Details',
        }, {
            key: 'basic-details',
            label: 'Basic Details',
        }, {
            key: 'timeframe',
            label: 'Timeframe',
        },
    ],

    currentStep: 'promotion-details',
    stepPromotionDetails: 'promotion-details',
    stepBasicDetails: 'basic-details',
    stepTimeframe: 'timeframe',
});

const goToStep = (stepKey) => {
    state.currentStep = stepKey;
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    promotionForm[columnName] = details.value;
};

const preparePromotionFormDetails = () => {
    promotionForm.category_ids = promotionForm.categories.map((category) => {
        return category.id;
    });

    if (promotionForm.member_groups) {
        promotionForm.member_group_ids = promotionForm.member_groups.map((memberGroup) => {
            return memberGroup.id;
        });
    }

    if (promotionForm.employee_groups) {
        promotionForm.employee_group_ids = promotionForm.employee_groups.map((employeeGroup) => {
            return employeeGroup.id;
        });
    }

    promotionForm.brand_ids = promotionForm.brands.map((brand) => {
        return brand.id;
    });

    promotionForm.tag_ids = promotionForm.tags.map((tag) => {
        return tag.id;
    });

    promotionForm.product_collection_ids = promotionForm.productCollections.map((productCollection) => {
        return productCollection.id;
    });

    promotionForm.regular_product_ids = promotionForm.regular_products.map((product) => {
        return product.id;
    });

    promotionForm.buy_product_ids = promotionForm.buy_products.map((product) => {
        return product.id;
    });

    promotionForm.get_product_ids = promotionForm.get_products.map((product) => {
        return product.id;
    });

    if (promotionForm.sale_channels) {
        promotionForm.sale_channel_ids = promotionForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }

    if (promotionForm.payment_types) {
        promotionForm.payment_type_ids = promotionForm.payment_types.map((types) => {
            return types.id;
        });
    }

    if (promotionForm.memberships) {
        promotionForm.membership_ids = promotionForm.memberships.map((membership) => {
            return membership.id;
        });
    }
};

const removeWeekDay = (weekDayKey) => {
    promotionForm.week_days.splice(weekDayKey, 1);
};

const addNewWeekDay = (weekDay) => {
    promotionForm.week_days.push(weekDay);
};

const addNewMonthDate = (monthDate) => {
    promotionForm.month_dates.push(monthDate);
};

const addNewPromoCode = () => {
    promotionForm.promo_codes.push('');
};

const clearPromoCodes = () => {
    promotionForm.promo_codes = [''];
};

const setPromoCodes = (promoCodes) => {
    promotionForm.promo_codes = promoCodes;
};

const updatePromoCodeDetails = (promoCodeDetails) => {
    promotionForm.promo_codes[promoCodeDetails.key] = promoCodeDetails.value;
};

const updateUploadColumnDetailsForPromoCode = (promoCodeDetails) => {
    for (const key in promoCodeDetails) {
        promotionForm.promo_codes[key] = promoCodeDetails[key];
    }
};

const removeMonthDate = (monthDateKey) => {
    promotionForm.month_dates.splice(monthDateKey, 1);
};

const clearColumns = (columnDetails) => {
    for (const key in columnDetails) {
        promotionForm[key] = columnDetails[key];
    }
};

const updatePromotion = () => {
    preparePromotionFormDetails();

    promotionForm.put(route('admin.promotions.update', promotionForm.id), {
        onError: () => showErrorNotification('There are input errors. Please fill out the required form fields on all tabs and try again.'),
    });
};

const addNewTierDetails = () => {
    promotionForm.tiers.push({ buy_value: null, get_value: null });
};

const updateTierValueDetails = (details) => {
    promotionForm.tiers[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    promotionForm.tiers.splice(key, 1);
};

const goToNext = () => {
    for (const key in state.steps) {
        if (state.steps[key].key === state.currentStep) {
            state.currentStep = state.steps[parseInt(key) + 1].key;
            return;
        }
    }
};

const updateTheColumn = (columnName, data) => {
    if (columnName === 'is_available_in_ecommerce' &&  ! data) {
        promotionForm.sale_channels = [];
        promotionForm.sale_channel_ids = [];
    }
    promotionForm[columnName] = data;
};

const updateSelectedPaymentTypes = (details) => {
    promotionForm.payment_types = details;
};

const updateSelectedMemberGroups = (memberGroups) => {
    promotionForm.member_groups = memberGroups;
};

const updateSelectedMemberships = (memberships) => {
    promotionForm.memberships = memberships;
};

const updateSelectedEmployeeGroups = (employeeGroups) => {
    promotionForm.employee_groups = employeeGroups;
};

const updateSelectedSaleChannels = (saleChannels) => {
    promotionForm.sale_channels = saleChannels;
};

onMounted(() => {
    if (props.promotion) {
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(promotionForm, JSON.parse(JSON.stringify(props.promotion.data)));
    }
});
</script>
