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
            <div class="intro-y col-span-12 overflow-auto">
                <div class="flex mb-4 gap-2 justify-end">
                    <PrimaryButton
                        v-if="showApproveButton()"
                        text="Approve"
                        class="w-24"
                        @click="approved(parseInt(productDetails.id))"
                    />
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.draft_products.edit', parseInt(productDetails.id))"
                        @click="closeModal"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                </div>

                <div class="mb-4">
                    <div v-if="productDetails.images?.image_urls.length > 0">
                        <h1 class="mb-2 font-medium text-md">
                            Images
                        </h1>

                        <div class="flex gap-5">
                            <div
                                v-for="(imageUrl, index) in productDetails.images.image_urls"
                                :key="index"
                            >
                                <img
                                    :src="imageUrl.url"
                                    width="100"
                                    class="mt-2"
                                >
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <span class="font-medium text-md">
                            Images: N/A.
                        </span>
                    </div>

                    <div v-if="productDetails.images?.video_urls.length > 0">
                        <h1 class="mb-2 font-medium text-md mt-2">
                            Videos
                        </h1>
                        <div class="flex gap-5">
                            <div
                                v-for="(videoUrl, index) in productDetails.images.video_urls"
                                :key="index"
                            >
                                <img
                                    :src="videoUrl.url"
                                    width="100"
                                    class="mt-2"
                                >
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <span class="font-medium text-md">
                            Videos: N/A
                        </span>
                    </div>
                </div>
                <table class="table table-striped -mt-2">
                    <tbody>
                        <tr>
                            <td>
                                Name
                            </td>

                            <td>
                                {{ productDetails.name }}
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Categories
                            </td>

                            <td v-if="productDetails.categories">
                                <span v-if="productDetails.categories.length">
                                    <span
                                        v-for="(category, index) in productDetails.categories"
                                        :key="index"
                                        class="inline-block"
                                    >
                                        {{ category.name }}

                                        <ChevronRight
                                            v-if="index != productDetails.categories.length - 1"
                                            class="form-check w-4 h-4 text-slate-400 inline-block"
                                        />
                                    </span>
                                </span>
                                <span v-else>
                                    N/A
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Brand
                            </td>

                            <td v-if="productDetails.brand">
                                {{ productDetails.brand.name }}
                            </td>
                        </tr>

                        <tr v-if="pageProps.product_variant">
                            <td>
                                Attributes
                            </td>

                            <td v-if="productDetails.attributes">
                                <span
                                    v-for="(attribute, index) in productDetails.attributes"
                                    :key="index"
                                    class="inline-block flex"
                                >
                                    {{ index }} : {{ attribute }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!pageProps.product_variant">
                            <td>
                                Color
                            </td>

                            <td v-if="productDetails.color">
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.color }}
                                </span>
                                <span v-else>
                                    {{ productDetails.color.name }}
                                </span>
                            </td>

                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr v-if="!pageProps.product_variant">
                            <td>
                                Size
                            </td>

                            <td v-if="productDetails.size">
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.size }}
                                </span>
                                <span v-else>
                                    {{ productDetails.size.name }}
                                </span>
                            </td>

                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr v-if="!pageProps.product_variant">
                            <td>
                                Style
                            </td>

                            <td v-if="productDetails.style">
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.style }}
                                </span>
                                <span v-else>
                                    {{ productDetails.style.name }}
                                </span>
                            </td>

                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Unit Of Measure
                            </td>

                            <td v-if="productDetails.unitOfMeasure">
                                {{ productDetails.unitOfMeasure.name }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr v-if="!pageProps.product_variant">
                            <td>
                                Season
                            </td>

                            <td v-if=" productDetails.season">
                                <span v-if="pageProps.product_variant">
                                    {{ productDetails.season }}
                                </span>
                                <span v-else>
                                    {{ productDetails.season.name }}
                                </span>
                            </td>

                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Department
                            </td>

                            <td v-if=" productDetails.department">
                                {{ productDetails.department.name }}
                            </td>

                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                UPC
                            </td>

                            <td>
                                {{ productDetails.upc }}
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Article Number
                            </td>

                            <td v-if="productDetails.article_number">
                                {{ productDetails.article_number }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                EAN
                            </td>

                            <td v-if="productDetails.ean">
                                {{ productDetails.ean }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Custom SKU
                            </td>

                            <td v-if="productDetails.custom_sku">
                                {{ productDetails.custom_sku }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Manufacturer SKU
                            </td>

                            <td v-if="productDetails.manufacturer_sku">
                                {{ productDetails.manufacturer_sku }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Retail Price
                            </td>

                            <td v-if="productDetails.retail_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.retail_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Franchise Price 1
                            </td>

                            <td v-if="productDetails.franchise_price_1">
                                {{ displayAmountWithCurrencySymbol(productDetails.franchise_price_1) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Franchise Price 2
                            </td>

                            <td v-if="productDetails.franchise_price_2">
                                {{ displayAmountWithCurrencySymbol(productDetails.franchise_price_2) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Franchise Price 3
                            </td>

                            <td v-if="productDetails.franchise_price_3">
                                {{ displayAmountWithCurrencySymbol(productDetails.franchise_price_3) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Wholesale Price
                            </td>

                            <td v-if="productDetails.wholesale_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.wholesale_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Company or Tender Price
                            </td>

                            <td v-if="productDetails.company_or_tender_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.company_or_tender_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Branch Price
                            </td>

                            <td v-if="productDetails.branch_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.branch_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Minimum Price
                            </td>

                            <td v-if="productDetails.minimum_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.minimum_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Original Capital Price
                            </td>

                            <td v-if="productDetails.original_capital_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.original_capital_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Capital Price
                            </td>

                            <td v-if="productDetails.capital_price">
                                {{ displayAmountWithCurrencySymbol(productDetails.capital_price) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Purchase Cost
                            </td>

                            <td v-if="productDetails.purchase_cost">
                                {{ displayAmountWithCurrencySymbol(productDetails.purchase_cost) }}
                            </td>
                            <td v-else>
                                N/A
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Is Temporarily Unavailable
                            </td>

                            <td v-if="productDetails.is_temporarily_unavailable">
                                <p>Yes</p>
                            </td>
                            <td v-else>
                                <p>No</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Has Batch
                            </td>
                            <td v-if="productDetails.has_batch">
                                <p>Yes</p>
                            </td>
                            <td v-else>
                                <p>No</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Is Non-Inventory
                            </td>
                            <td v-if="productDetails.is_non_inventory">
                                <p>Yes</p>
                            </td>
                            <td v-else>
                                <p>No</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Is Non-Selling Item
                            </td>
                            <td v-if="productDetails.is_non_selling_item">
                                <p>Yes</p>
                            </td>
                            <td v-else>
                                <p>No</p>
                            </td>
                        </tr>
                        <tr>
                            <td>Created By</td>
                            <td> {{ productDetails.created_by }} </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { route } from 'ziggy';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X, ChevronRight, CheckSquare } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
const pageProps = computed(() => usePage().props);

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    productDetails: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        required: true,
    },
    creatorCanApproveDraftProduct: {
        type: Boolean,
        required: true,
    },
});
const emits = defineEmits(['close-modal', 'approve-product']);
const closeModal = () => {
    emits('close-modal');
};

const approved = (productId) => {
    emits('approve-product', productId);
};

const showApproveButton = () => {
    if (props.creatorCanApproveDraftProduct) {
        return props.creatorCanApproveDraftProduct;
    }

    return props.user.id !== props.productDetails.created_by_id && props.user.type === props.productDetails.created_by_type;
};
</script>
