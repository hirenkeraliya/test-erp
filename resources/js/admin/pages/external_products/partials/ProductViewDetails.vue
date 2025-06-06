<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Product Details
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
            <InfoAlert
                color="primary"
                class="mb-3"
            >
                <b>Note :</b> Here are the below basic details will be storing while creating the product and may require other details update manually.
            </InfoAlert>
            <div class="col-span-12 overflow-auto intro-y">
                <table class="table table-striped -mt-2 w-full">
                    <tbody>
                        <tr>
                            <td class="font-semibold">
                                Name
                            </td>

                            <td>
                                {{ productDetails.name }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Upc
                            </td>

                            <td>
                                {{ productDetails.upc ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Ean
                            </td>

                            <td>
                                {{ productDetails.ean ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Custom Sku
                            </td>

                            <td>
                                {{ productDetails.custom_sku ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Manufacturer Sku
                            </td>

                            <td>
                                {{ productDetails.manufacturer_sku ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Code
                            </td>

                            <td>
                                {{ productDetails.code ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr
                            v-if="pageProps.product_variant"
                        >
                            <td class="font-semibold">
                                Attributes
                            </td>

                            <td>
                                <span
                                    v-for="(product_variant_value,index) in productDetails.product_variant_values"
                                    :key="index"
                                >
                                    <p>
                                        {{ product_variant_value.attribute.name }} : {{ product_variant_value.value }}
                                    </p>
                                </span>                                
                            </td>
                        </tr>
                        <tr
                            v-if="! pageProps.product_variant"
                        >
                            <td class="font-semibold">
                                Size
                            </td>

                            <td>
                                <span>
                                    {{ productDetails.size ? productDetails.size.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Tags
                            </td>
                            <td v-if="pageProps.product_variant">
                                <span
                                    v-for="(tag,index) in productDetails.master_product?.tags"
                                    :key="index"
                                >
                                    {{ tag.name }}

                                    <strong
                                        v-if="index != productDetails.master_product?.tags.length - 1"
                                        class="text-dark"
                                    >
                                        ,
                                    </strong>
                                </span>
                            </td>
                            <td v-else>
                                <span
                                    v-for="(tag,index) in productDetails.tags"
                                    :key="index"
                                >
                                    {{ tag.name }}

                                    <strong
                                        v-if="index != productDetails.tags.length - 1"
                                        class="text-dark"
                                    >
                                        ,
                                    </strong>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Brand
                            </td>
                            <td>
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.master_product?.brand.name ?? 'N/A' }}
                                </span>
                                <span v-else>
                                    {{ productDetails.brand ? productDetails.brand.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr
                            v-if="! pageProps.product_variant"
                        >
                            <td class="font-semibold">
                                Color
                            </td>
                            <td>
                                <span>
                                    {{ productDetails.color ? productDetails.color.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr
                            v-if="! pageProps.product_variant"
                        >
                            <td class="font-semibold">
                                Style
                            </td>                           
                            <td>
                                <span>
                                    {{ productDetails.style ? productDetails.style.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr
                            v-if="! pageProps.product_variant"
                        >
                            <td class="font-semibold">
                                Season
                            </td>                            
                            <td>
                                <span>
                                    {{ productDetails.season ? productDetails.season.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Categories
                            </td>
                            <td v-if="pageProps.product_variant">
                                <span
                                    v-for="(category,index) in productDetails.master_product?.categories"
                                    :key="index"
                                >
                                    {{ category.name }}

                                    <strong
                                        v-if="index != productDetails.master_product?.categories.length - 1"
                                        class="text-dark"
                                    >
                                        >
                                    </strong>
                                </span>
                            </td>
                            <td v-else>
                                <span
                                    v-for="(category,index) in productDetails.categories"
                                    :key="index"
                                >
                                    {{ category.name }}

                                    <strong
                                        v-if="index != productDetails.categories.length - 1"
                                        class="text-dark"
                                    >
                                        >
                                    </strong>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Department
                            </td>
                            <td>
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.master_product?.department ? productDetails.master_product.department.name : 'N/A' }}
                                </span>
                                <span v-else>
                                    {{ productDetails.department ? productDetails.department.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Unit Of Measure
                            </td>
                            <td>
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.master_product?.unit_of_measure ? productDetails.master_product.unit_of_measure.name : 'N/A' }}
                                </span>
                                <span v-else>
                                    {{ productDetails.unit_of_measure ? productDetails.unit_of_measure.name : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Article Number
                            </td>
                            <td>
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.master_product?.article_number ?? 'N/A' }}
                                </span>
                                <span v-else>
                                    {{ productDetails.article_number ?? 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Staff Price
                            </td>
                            <td>
                                {{ productDetails.staff_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Branch Price
                            </td>
                            <td>
                                {{ productDetails.branch_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Online Price
                            </td>
                            <td>
                                {{ productDetails.online_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Retail Price
                            </td>
                            <td>
                                {{ productDetails.retail_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Capital Price
                            </td>
                            <td>
                                {{ productDetails.capital_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Minimum Price
                            </td>
                            <td>
                                {{ productDetails.minimum_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Purchase Cost
                            </td>
                            <td>
                                {{ productDetails.purchase_cost ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Wholesale Price
                            </td>
                            <td>
                                {{ productDetails.wholesale_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Franchise Price 1
                            </td>
                            <td>
                                {{ productDetails.franchise_price_1 ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Franchise Price 2
                            </td>
                            <td>
                                {{ productDetails.franchise_price_2 ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Franchise Price 3
                            </td>
                            <td>
                                {{ productDetails.franchise_price_3 ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Original Capital Price
                            </td>
                            <td>
                                {{ productDetails.original_capital_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Company Or Tender Price
                            </td>
                            <td>
                                {{ productDetails.company_or_tender_price ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Is Non Inventory
                            </td>
                            <td>
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.master_product?.is_non_inventory }}
                                </span>
                                <span v-else>
                                    {{ productDetails.is_non_inventory }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Is Available In Pos
                            </td>
                            <td>
                                {{ productDetails.is_available_in_pos }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Is Non Selling Item
                            </td>
                            <td>
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.master_product?.is_non_selling_item }}
                                </span>
                                <span v-else>
                                    {{ productDetails.is_non_selling_item }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Is Available In Ecommerce
                            </td>
                            <td>
                                {{ productDetails.is_available_in_ecommerce }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">
                                Is Temporarily Unavailable
                            </td>
                            <td>
                                {{ productDetails.is_temporarily_unavailable }}
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
import { X } from 'lucide-vue-next';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    productDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};

</script>
