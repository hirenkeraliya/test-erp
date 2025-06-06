<template>
    <div
        v-if="Object.keys(saleReturn).length"
        class="receipt-container"
    >
        <div class="text-center">
            <img
                v-if="saleReturn.company.logo"
                :src="saleReturn.company.logo"
                class="img-fluid rounded"
                alt="logo"
                style="width: 115px;"
            >

            <div class="heading-part dashed-underline">
                {{ saleReturn.location.name }} <br>
                {{ saleReturn.location.address_line_1 }} <br>
                {{ saleReturn.location.address_line_2 }} <br>
                {{ saleReturn.location.city }} - {{ saleReturn.location.area_code }} <br>
                {{ saleReturn.company.name }} <br>
                CO. REG. ({{ saleReturn.company.social_security_number }}) <br>
                Reprint
            </div>
        </div>

        <div class="second-part dashed-underline">
            RETURN NO: {{ saleReturn.id }} <br>
            INVOICE NO: {{ saleReturn.original_sale_id }} <br>
            DATE: {{ saleReturn.happened_at }} <br>
            COUNTER: {{ saleReturn.counter }} <br>
            MEMBER ID: {{ saleReturn.member_id ? saleReturn.member_id : 'N/A' }} <br>
            MEMBER NAME: {{ saleReturn.member ? saleReturn.member : 'N/A' }} <br>
            SALESPERSON NAME: {{ saleReturn.cashier }} <br>
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
                            UNIT PRICE
                        </td>
                        <td class="amount">
                            AMOUNT
                        </td>
                    </tr>

                    <tr
                        v-for="(returnProduct, index) in saleReturn.sale_return_items"
                        :key="returnProduct.id + index"
                    >
                        <td class="item">
                            {{ returnProduct.product }}
                            ({{ returnProduct.sale_return_reason }})
                        </td>
                        <td class="qty">
                            -{{ Math.abs(returnProduct.quantity) }}
                        </td>
                        <td class="unit-price">
                            -{{ numberFormat(returnProduct.unit_price) }}
                        </td>
                        <td class="amount">
                            -{{ numberFormat(returnProduct.quantity * returnProduct.unit_price) }}
                        </td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="4">
&nbsp;
                        </td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            Discount:
                        </td>
                        <td>{{ numberFormat(saleReturn.total_discount_amount) }}</td>
                    </tr>

                    <tr
                        v-if="saleReturn.total_tax_amount"
                        class="dashed-underline"
                    >
                        <td colspan="3">
                            Tax:
                        </td>
                        <td>{{ numberFormat(saleReturn.total_tax_amount) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            ROUND-OFF:
                        </td>
                        <td>{{ numberFormat(saleReturn.round_off) }}</td>
                    </tr>

                    <tr class="dashed-underline">
                        <td colspan="3">
                            Net Total:
                        </td>
                        <td>-{{ numberFormat(saleReturn.return_amount) }}</td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            RETURN UNITS:
                        </td>
                        <td>{{ saleReturn.units_returned }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <div class="bottom-part">
                {{ saleReturn.location.receipt_footer }}

                <br>
                Disclaimer: <br>
                {{ saleReturn.location.disclaimer }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { numberFormat } from '@commonServices/helper';

defineProps({
    saleReturn: {
        type: Object,
        required: true,
    },
});
</script>
