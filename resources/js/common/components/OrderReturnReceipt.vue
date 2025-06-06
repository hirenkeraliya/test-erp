<template>
    <div
        v-if="Object.keys(orderReturn).length"
        class="receipt-container"
    >
        <div class="text-center">
            <img
                v-if="orderReturn.company.logo"
                :src="orderReturn.company.logo"
                class="img-fluid rounded"
                alt="logo"
                style="width: 115px;"
            >

            <div class="heading-part dashed-underline">
                {{ orderReturn.location.name }} <br>
                {{ orderReturn.location.address_line_1 }} <br>
                {{ orderReturn.location.address_line_2 }} <br>
                {{ orderReturn.location.city }} - {{ orderReturn.location.area_code }} <br>
                {{ orderReturn.company.name }} <br>
                CO. REG. ({{ orderReturn.company.social_security_number }}) <br>
            </div>
        </div>

        <div class="second-part dashed-underline text-center">
            DATE: {{ orderReturn.happened_at }} <br>
        </div>

        <div class="second-part dashed-underline text-center pb-5">
            INVOICE NO: {{ orderReturn.receipt_number }} <br>
            <img
                :src="'data:image/png;base64,' + orderReturn.order_return_barcode"
                width="280"
                height="50"
            >
        </div>

        <div class="second-part dashed-underline">
            STORE MANAGER: {{ orderReturn.store_manager }} <br>
            MEMBER ID: {{ orderReturn.member_id ? orderReturn.member_id : 'N/A' }} <br>
            MEMBER NAME: {{ orderReturn.member ? orderReturn.member : 'N/A' }} <br>
        </div>

        <div>
            <table id="table">
                <tbody>
                    <tr class="tabletitle">
                        <td class="item">
                            ITEM
                        </td>
                        <td class="qty">
                            QTY
                        </td>
                        <td class="unit-price">
                            UNIT PRICE ({{ currencySymbol }})
                        </td>
                        <td class="unit-price">
                            Discount ({{ currencySymbol }})
                        </td>
                        <td class="amount">
                            AMOUNT ({{ currencySymbol }})
                        </td>
                    </tr>

                    <tr
                        v-for="(orderReturnItem, index) in orderReturn.order_return_items"
                        :key="orderReturnItem.id + index"
                    >
                        <td class="item">
                            {{ orderReturnItem.product }} ({{ orderReturnItem.upc }}) 
                            <span v-if="pageProps.product_variant">
                                ({{ orderReturnItem.product_variant_values.map(item => item.value).join('-') }})
                            </span>
                            <span v-else>
                                ({{ orderReturnItem.size + '-' + orderReturnItem.color }})
                            </span>
                            ({{ orderReturnItem.order_return_reason }})
                        </td>
                        <td class="qty">
                            X {{ orderReturnItem.quantity }}
                        </td>
                        <td class="unit-price">
                            -{{ numberFormat(orderReturnItem.unit_price) }}
                        </td>
                        <td class="unit-price">
                            -{{ numberFormat(orderReturnItem.total_discount_amount) }}
                        </td>
                        <td class="amount">
                            -{{ numberFormat(orderReturnItem.quantity * orderReturnItem.unit_price) }}
                        </td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            GROSS TOTAL:
                        </td>
                        <td>{{ numberFormat(orderReturn.gross_orders) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            Discount:
                        </td>
                        <td>{{ numberFormat(orderReturn.total_discount_amount) }}</td>
                    </tr>

                    <tr
                        v-if="orderReturn.total_tax_amount"
                        class="dashed-underline"
                    >
                        <td colspan="3">
                            Tax:
                        </td>
                        <td>{{ numberFormat(orderReturn.total_tax_amount) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            ROUND-OFF:
                        </td>
                        <td>{{ numberFormat(orderReturn.round_off) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            AMOUNT:
                        </td>
                        <td>-{{ numberFormat(orderReturn.total_amount_paid) }}</td>
                    </tr>

                    <tr class="second-part dashed-underline">
                        <td colspan="2">
                            NO. OF RETURN UNITS:
                        </td>
                        <td>{{ orderReturn.units_returned }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="second-part dashed-underline">
            Remarks: {{ orderReturn.orderReturn_notes ?? 'N/A' }} <br>
        </div>

        <div class="text-center">
            <div class="bottom-part">
                {{ orderReturn.location.receipt_footer }}

                <br>
                Disclaimer: <br>
                {{ orderReturn.location.disclaimer }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { numberFormat } from '@commonServices/helper';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);
const pageProps = computed(() => usePage().props);

defineProps({
    orderReturn: {
        type: Object,
        required: true,
    },
});
</script>
