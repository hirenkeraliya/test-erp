<template>
    <div
        v-if="Object.keys(order).length"
        class="receipt-container"
    >
        <div class="text-center">
            <img
                v-if="order.company.logo"
                :src="order.company.logo"
                class="img-fluid rounded"
                alt="logo"
                style="width: 115px;"
            >

            <div class="heading-part dashed-underline">
                {{ order.location.name }} <br>
                {{ order.location.address_line_1 }} <br>
                {{ order.location.address_line_2 }} <br>
                {{ order.location.city }} - {{ order.location.area_code }} <br>
                {{ order.company.name }} <br>
                CO. REG. ({{ order.company.social_security_number }}) <br>
            </div>
        </div>

        <div class="second-part dashed-underline text-center">
            DATE: {{ order.happened_at }} <br>
        </div>

        <div class="second-part dashed-underline text-center pb-5">
            INVOICE NO: {{ order.receipt_number }} <br>
            <img
                :src="'data:image/png;base64,' + order.order_barcode"
                width="280"
                height="50"
            >
        </div>

        <div class="second-part dashed-underline">
            STORE MANAGER: {{ order.store_manager }} <br>
            MEMBER ID: {{ order.member_id ? order.member_id : 'N/A' }} <br>
            MEMBER NAME: {{ order.member ? order.member : 'N/A' }} <br>
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
                        v-for="(orderItem, index) in order.order_items"
                        :key="orderItem.id + index"
                    >
                        <td class="item">
                            {{ orderItem.product }}
                            ({{ orderItem.upc }})
                            <span v-if="pageProps.product_variant">
                                ({{ orderItem.product_variant_values.map(item => item.value).join('-') }})
                            </span>
                            <span v-else>
                                ({{ orderItem.size + '-' + orderItem.color }})
                            </span>
                            <span v-if="orderItem.bundle">
                                ({{ orderItem.bundle.package_type_name }})
                            </span>
                        </td>
                        <td class="qty">
                            X {{ orderItem.quantity }}
                        </td>
                        <td class="unit-price">
                            {{ numberFormat(orderItem.original_price_per_unit) }}
                        </td>
                        <td class="unit-price">
                            {{ numberFormat(orderItem.total_discount_amount) }}
                        </td>
                        <td class="amount">
                            {{ numberFormat(orderItem.quantity * orderItem.original_price_per_unit) }}
                        </td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            GROSS TOTAL:
                        </td>
                        <td>{{ numberFormat(order.gross_orders) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            Discount:
                        </td>
                        <td>{{ numberFormat(order.total_discount_amount) }}</td>
                    </tr>

                    <tr
                        v-if="order.total_tax_amount"
                        class="dashed-underline"
                    >
                        <td colspan="3">
                            Tax:
                        </td>
                        <td>{{ numberFormat(order.total_tax_amount) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            Net Total:
                        </td>
                        <td>{{ numberFormat(order.net_total) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            ROUND-OFF:
                        </td>
                        <td>{{ numberFormat(order.round_off) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            AMOUNT:
                        </td>
                        <td>{{ numberFormat(order.total_amount_paid) }}</td>
                    </tr>

                    <tr
                        v-if="order.layaway_pending_amount"
                        class="dashed-underline"
                    >
                        <td colspan="3">
                            LAYAWAY PENDING:
                        </td>
                        <td>{{ numberFormat(order.layaway_pending_amount) }}</td>
                    </tr>

                    <tr
                        v-if="order.credit_pending_amount"
                        class="dashed-underline"
                    >
                        <td colspan="3">
                            CREDIT PENDING:
                        </td>
                        <td>{{ numberFormat(order.credit_pending_amount) }}</td>
                    </tr>

                    <tr
                        v-for="(payment, index) in order.payments"
                        :key="index"
                        :class="(index == order.payments.length -1) ? 'dashed-underline' : ''"
                    >
                        <td colspan="3">
                            {{ payment.payment_type.name }}
                            ({{ currencySymbol }})
                        </td>

                        <td>{{ numberFormat(payment.amount) }}</td>
                    </tr>

                    <tr class="second-part dashed-underline">
                        <td colspan="2">
                            NO. OF UNITS:
                        </td>
                        <td>{{ order.units_sold }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="second-part dashed-underline">
            Remarks: {{ order.order_notes ?? 'N/A' }} <br>
            # Reference: {{ order.bill_reference_number ?? 'N/A' }} <br>
        </div>

        <div class="text-center">
            <div class="bottom-part">
                {{ order.location.receipt_footer }}

                <br>
                Disclaimer: <br>
                {{ order.location.disclaimer }}
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
    order: {
        type: Object,
        required: true,
    },
});
</script>
