<template>
    <PageTitle title="Member Details" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-5">
        <h2 class="text-lg font-medium mr-auto">
            Member Details
        </h2>
    </div>

    <div class="relative z-0 grid grid-cols-5 mb-2 mt-5">
        <div class="col-span-12">
            <div class="grid grid-cols-12 gap-6 auto-rows-fr">
                <div
                    class="col-span-3 2xl:col-span-3 xl:col-span-3 lg:col-span-3 md:col-span-3 sm:col-span-6 intro-y h-full"
                >
                    <div
                        :class="[
                            'relative zoom-in',
                            'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0 h-full',
                        ]"
                    >
                        <div
                            v-if="! state.isDataFetched"
                            class="px-5 py-3 box h-full"
                        >
                            <div class="mb-3 mt-2">
                                <h3
                                    class="font-medium text-base mr-auto pr-8 border-b border-dashed mb-2"
                                >
                                    Profile Details
                                </h3>

                                <div>
                                    <p>
                                        Name: <span class="font-bold">{{ state.member.first_name }}</span>
                                    </p>
                                    <p>
                                        Since:
                                        <span class="font-bold">{{ state.member.created_at }}</span>
                                    </p>
                                    <p>
                                        Location:
                                        <span class="font-bold">{{ state.member.location }}</span>
                                    </p>
                                    <p>
                                        Phone: <span class="font-bold">{{ state.member.mobile_number }}</span>
                                    </p>
                                    <p>
                                        Address:
                                        <span class="font-bold">{{ state.member.address_line_1 }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3">
                                <h2
                                    class="font-medium text-base mr-auto pr-8 border-b border-dashed mb-2"
                                >
                                    Membership Details
                                </h2>
                                <div>
                                    <p>
                                        Name:
                                        <span class="font-bold">{{ state.member.membership_name }}</span>
                                    </p>
                                    <p>
                                        Since:
                                        <span class="font-bold">{{ state.member.membership_date }}</span>
                                    </p>
                                    <p>
                                        Loyalty Points:
                                        <span class="font-bold">{{ state.member.loyalty_points }}</span>
                                    </p>
                                    <p>
                                        Redeemed Points:
                                        <span class="font-bold">{{ state.member.redeemed_points }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="px-5 py-3 box h-full"
                        >
                            <div class="cp">
                                <div
                                    class="animated-background !h-[120px] !rounded-xl"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-3 2xl:col-span-3 xl:col-span-3 lg:col-span-3 md:col-span-3 sm:col-span-6 intro-y h-full"
                >
                    <div
                        :class="[
                            'relative zoom-in',
                            'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0 h-full',
                        ]"
                    >
                        <div
                            v-if="! state.isDataFetched"
                            class="px-5 py-3 box h-full"
                        >
                            <div class="mb-3">
                                <h2
                                    class="font-medium text-base mr-auto pr-8 border-b border-dashed mb-2"
                                >
                                    Preferences
                                </h2>
                                <div>
                                    <p>Channel: <span class="font-bold">{{ state.member.channel }} </span></p>

                                    <p>
                                        Color:
                                        <span
                                            v-if="state.preferencesColor"
                                            class="font-bold"
                                        >
                                            {{ state.preferencesColor.name }}
                                            ({{
                                                state.preferencesColor.quantity
                                            }})
                                        </span>
                                        <span v-else> N/A </span>
                                    </p>

                                    <p>
                                        Size:
                                        <span
                                            v-if="state.preferencesSize"
                                            class="font-bold"
                                        >
                                            {{ state.preferencesSize.name }} ({{
                                                state.preferencesSize.quantity
                                            }})
                                        </span>
                                        <span v-else> N/A </span>
                                    </p>

                                    <p>
                                        Category:
                                        <span
                                            v-if="state.preferencesCategory"
                                            class="font-bold"
                                        >
                                            {{ state.preferencesCategory.name }}
                                            ({{
                                                state.preferencesCategory
                                                    .quantity
                                            }})
                                        </span>
                                        <span v-else> N/A </span>
                                    </p>

                                    <p>
                                        Date:
                                        <span
                                            v-if="state.preferredDate"
                                            class="font-bold"
                                        >
                                            {{ state.preferredDate.date }} ({{
                                                state.preferredDate.quantity
                                            }})
                                        </span>
                                        <span v-else> N/A </span>
                                    </p>

                                    <p>
                                        Day:
                                        <span
                                            v-if="state.preferredDay"
                                            class="font-bold"
                                        >
                                            {{ state.preferredDay.day }} ({{
                                                state.preferredDay.quantity
                                            }})
                                        </span>
                                        <span v-else> N/A </span>
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3">
                                <h2
                                    class="font-medium text-base mr-auto pr-8 border-b border-dashed mb-2"
                                >
                                    Others
                                </h2>
                                <div>
                                    <p>
                                        Life Time Value:
                                        {{ state.member.spent_till_now }}
                                    </p>
                                    <p>
                                        Discount:
                                        {{
                                            displayAmountWithCurrencySymbol(
                                                state.member
                                                    .total_discount_amount
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="px-5 py-3 box h-full"
                        >
                            <div class="cp">
                                <div
                                    class="animated-background !h-[120px] !rounded-xl"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-6 2xl:col-span-6 xl:col-span-6 lg:col-span-6 md:col-span-6 sm:col-span-12 intro-y h-full"
                >
                    <div
                        :class="[
                            'relative zoom-in',
                            'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0 h-full',
                        ]"
                    >
                        <div
                            v-if="! state.isDataFetched"
                            class="px-5 py-3 box h-full"
                        >
                            <h3 class="font-medium text-base mb-2">
                                Top 5 Items
                            </h3>
                            <table
                                v-if="state.preferencesProduct.length"
                                class="table w-full text-center border border-gray-200"
                            >
                                <tr>
                                    <th class="text-left">
                                        SKU
                                    </th>
                                    <th class="text-left">
                                        Item Name
                                    </th>
                                    <th class="text-center">
                                        Units purchased
                                    </th>
                                </tr>
                                <tr
                                    v-for="(
                                        product, index
                                    ) in state.preferencesProduct"
                                    :key="index"
                                >
                                    <td class="text-left">
                                        {{ product.sku }}
                                    </td>
                                    <td class="text-left">
                                        {{ product.name }}
                                    </td>
                                    <td class="text-center">
                                        {{ product.quantity }}
                                    </td>
                                </tr>
                                <tr
                                    v-if="state.preferencesProduct.length === 0"
                                >
                                    <td colspan="3">
                                        Not Available
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div
                            v-else
                            class="px-5 py-3 box h-full"
                        >
                            <div class="cp">
                                <div
                                    class="animated-background !h-[120px] !rounded-xl"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-10">
        <TabGroup>
            <div
                class="w-1/2 bg-white rounded-xl px-3 py-5 md:mb-6 float-right"
            >
                <TabListMemberSaleDetails />
            </div>

            <TabPanels class="rounded-xl px-3 clear-right w-full">
                <TabPanel class="active">
                    <JTable
                        :fetch-url="
                            route(fetchMemberSaleUrl, { member_id: memberId })
                        "
                        :columns="state.columns"
                        :refresh-table-data="state.refreshTableData"
                        search-title="Search by receipt id"
                    >
                        <template #gross_sales="data">
                            {{
                                displayAmountWithCurrencySymbol(
                                    data.item.gross_sales
                                )
                            }}
                        </template>

                        <template #total_discount_amount="data">
                            -{{
                                displayAmountWithCurrencySymbol(
                                    data.item.total_discount_amount
                                )
                            }}
                        </template>

                        <template #total_tax_amount="data">
                            {{
                                displayAmountWithCurrencySymbol(
                                    data.item.total_tax_amount
                                )
                            }}
                        </template>

                        <template #total_amount_paid="data">
                            {{
                                displayAmountWithCurrencySymbol(
                                    data.item.total_amount_paid
                                )
                            }}
                        </template>
                    </JTable>
                </TabPanel>

                <TabPanel>
                    <JTable
                        :fetch-url="
                            route(fetchMemberSaleReturnUrl, {
                                member_id: memberId,
                            })
                        "
                        :columns="state.columnsForSaleReturn"
                        :refresh-table-data="state.refreshTableData"
                        search-title="Search by receipt id"
                    >
                        <template #location="data">
                            {{ data.item.location.name }}
                        </template>
                        <template #gross_returns="data">
                            {{
                                displayAmountWithCurrencySymbol(
                                    data.item.gross_returns
                                )
                            }}
                        </template>
                        <template #return_amount="data">
                            {{
                                displayAmountWithCurrencySymbol(
                                    data.item.return_amount
                                )
                            }}
                        </template>
                        <template #total_discount_amount="data">
                            -{{
                                displayAmountWithCurrencySymbol(
                                    data.item.total_discount_amount
                                )
                            }}
                        </template>
                        <template #total_tax_amount="data">
                            {{
                                displayAmountWithCurrencySymbol(
                                    data.item.total_tax_amount
                                )
                            }}
                        </template>
                    </JTable>
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import JTable from '@commonComponents/JTable.vue';
import { route } from 'ziggy';
import { onMounted, reactive } from 'vue';
import TabListMemberSaleDetails from '@commonComponents/TabListMemberSaleDetails.vue';
import { TabPanel, TabGroup, TabPanels } from '@commonVendor/tab';
import axios from 'axios';

const props = defineProps({
    memberId: {
        type: Number,
        required: true,
    },
    fetchMemberSaleUrl: {
        type: String,
        required: true,
    },
    fetchMemberSaleReturnUrl: {
        type: String,
        required: true,
    },
    fetchMemberDetailsUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    isDataFetched: true,
    columns: [
        {
            key: 'offline_sale_id',
            label: 'Receipt Id',
        },
        {
            key: 'bill_reference_number',
            label: '# Reference'
        },
        {
            key: 'location',
        },
        {
            key: 'counter',
        },
        {
            key: 'cashier',
        },
        {
            key: 'happened_at',
            label: 'Date & Time',
        },
        {
            key: 'gross_sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'total_discount_amount',
            label: 'Discount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'units_sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'units_returned',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'total_tax_amount',
            label: 'Tax',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'total_amount_paid',
            label: 'Paid',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'notes',
            label: 'Remarks',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
    ],
    columnsForSaleReturn: [
        {
            key: 'offline_sale_return_id',
            label: 'Receipt Id',
            isDisplay: true,
        },
        {
            key: 'bill_reference_number',
            label: '# Reference',
            isDisplay: true,
        },
        {
            key: 'location',
            isDisplay: true,
        },
        {
            key: 'counter',
            isDisplay: true,
        },
        {
            key: 'cashier',
            isDisplay: true,
        },
        {
            key: 'happened_at',
            label: 'Date & Time',
            isDisplay: true,
        },
        {
            key: 'gross_returns',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        },
        {
            key: 'total_discount_amount',
            label: 'Discount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        },
        {
            key: 'units_returned',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'total_tax_amount',
            label: 'Tax',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        },
        {
            key: 'return_amount',
            label: 'Return',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        },
    ],
    refreshTableData: Math.random(),
    member: [],
    preferencesColor: null,
    preferencesSize: null,
    preferencesCategory: null,
    preferredDate: null,
    preferredDay: null,
    preferencesProduct: [],
});

onMounted(() => {
    axios
        .get(route(props.fetchMemberDetailsUrl, props.memberId))
        .then((response) => {
            state.member = response.data.member;
            state.preferencesColor = response.data.preferencesColor;
            state.preferencesSize = response.data.preferencesSize;
            state.preferencesCategory = response.data.preferencesCategory;
            state.preferredDate = response.data.preferredDate;
            state.preferredDay = response.data.preferredDay;
            state.preferencesProduct = response.data.preferencesProduct;
            state.isDataFetched = false;
        });
});
</script>
