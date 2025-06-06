<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <FormInput
                        v-model:input-value="state.article_number"
                        type="text"
                        input-label="Article Number"
                        input-name="article_number"
                        validation-field-name="article_number"
                        :required="true"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <OutlinePrimaryRequestButton
                        text="Search"
                        class="shadow-md text-sm mt-8"
                        :disabled="state.is_button_disabled"
                        @click="searchProductArticleNumber()"
                    />
                </div>
            </div>

            <h4 class="mt-4">
                <div
                    v-if="Object.keys(state.products).length > 0"
                    class="block md:flex justify-between"
                >
                    <p>
                        <Tippy
                            :content="state.products[0].name"
                        >
                            <JBadge
                                :label="state.products[0].name"
                                type="success"
                                class="mb-2 md:mb-0"
                            />
                        </Tippy>
                    </p>

                    <p>
                        <Tippy
                            content="Stock By Article Number"
                        >
                            <JBadge
                                :label="`Stock By Article Number: ${getTotalStock}`"
                                type="primary"
                                class="mb-2 md:mb-0"
                            />
                        </Tippy>
                    </p>

                    <p>
                        <Tippy
                            content="Transfer Quantity"
                        >
                            <JBadge
                                :label="`Transfer Quantity: ${getTransferQuantity}`"
                                :type="getTransferQuantity >= 1 ? 'success' : 'danger'"
                            />
                        </Tippy>
                    </p>
                </div>

                <div
                    v-if="Object.keys(state.products).length > 0"
                    class="grid grid-cols-12 gap-0 sm:gap-6 mt-2"
                >
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="state.quantity"
                            type="number"
                            input-label="Same Quantity"
                            input-name="quantity"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <OutlinePrimaryRequestButton
                            text="Apply To All"
                            class="shadow-md text-sm mt-8"
                            :disabled="state.quantity <= 0 || state.quantity === ''"
                            @click="quantityApply()"
                        />
                    </div>
                </div>
            </h4>

            <div
                v-if="state.products.length && Object.keys(state.colors).length && Object.keys(state.sizes).length"
                class="mt-5"
            >
                <div class="overflow-x-auto">
                    <table class="w-full table-auto rounded">
                        <thead>
                            <tr class="bg-zinc-100">
                                <th class="font-medium px-5 py-3 border-b-2 border-l border-r border-t text-center">
                                    #
                                </th>
                                <th
                                    v-for="(size, index) in state.sizes"
                                    :key="index"
                                    class="font-medium px-5 py-3 border-b-2 border-l border-r border-t text-center"
                                >
                                    {{ size }}
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(color, colorIndex) in state.colors"
                                :key="colorIndex"
                            >
                                <td class="font-medium px-5 py-3 border-b-2 border-l border-r border-t">
                                    {{ color }}
                                </td>
                                <td
                                    v-for="(size, sizeIndex) in state.sizes"
                                    :key="sizeIndex"
                                    class="font-medium px-5 py-3 border-b-2 border-l border-r border-t"
                                >
                                    <div
                                        v-if="!getProducts(color, size).length"
                                        class="text-red-500"
                                    >
                                        <StopSvg label-class="mx-auto" />
                                    </div>

                                    <div
                                        v-for="(product, productIndex) in getProducts(color, size)"
                                        v-else
                                        :key="productIndex"
                                    >
                                        <FormInput
                                            :input-value="product.quantity"
                                            input-name="stock"
                                            placeholder="Transfer Stock"
                                            label-class="mt-0"
                                            type="number"
                                            class="w-[200px] lg:w-full"
                                            @update:input-value="updateTheStock(product, $event)"
                                        />

                                        <span
                                            v-if="product.stock <= 0"
                                            class="pl-1 flex justify-between mt-1 text-danger font-extrabold"
                                        >

                                            Stock: {{ product.stock }} <br>
                                        </span>

                                        <span
                                            v-else
                                            class="pl-1 flex justify-between mt-1"
                                        >
                                            Stock: {{ product.stock }} <br>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <PrimaryButton
                    text="Select Products"
                    class="shadow-md text-sm mx-1 mt-4"
                    @click="saveProducts"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import OutlinePrimaryRequestButton from '@commonComponents/OutlinePrimaryRequestButton.vue';
import axios from 'axios';
import { reactive, computed } from 'vue';
import { route } from 'ziggy';
import StopSvg from '@svg/StopSvg.vue';
import { showErrorNotification } from '@commonServices/notifier';
import JBadge from '@commonComponents/JBadge.vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Advance Product Selection'
    },
    productArticleSearchUrl: {
        type: String,
        required: true,
    },
    purchasePlanForm: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    defaultOrderType: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    products: [],
    data: [],
    sizes: [],
    colors: [],
    article_number: null,
    is_button_disabled: false,
    quantity: null,
});

const emits = defineEmits(['close-modal', 'update:filter-advance-products-selection']);

const closeModal = () => {
    emits('close-modal');
};

const searchProductArticleNumber = () => {
    state.is_button_disabled = true;
    axios.post(route(props.productArticleSearchUrl), {
        article_number: state.article_number,
        location_id: props.purchasePlanForm.location_id,
    }).then((response) => {
        state.products = response.data.products;
        state.sizes = response.data.sizes;
        state.colors = response.data.colors;
        if (!Object.keys(state.colors).length || !Object.keys(state.sizes).length) {
            showErrorNotification('No color or size options are available for products with this article number');
        }
    }).catch((error) => {
        showErrorNotification(error.response.data.message);
    })
        .finally(() => {
            state.is_button_disabled = false;
        });
};

const updateTheStock = (product, transferStock) => {
    if (isNaN(transferStock)) {
        return;
    }
    product.quantity = transferStock;
};

const getTransferQuantity = computed(() => {
    return state.products.reduce((transferQuantity, product) => parseFloat(transferQuantity) + parseFloat(product.quantity), 0);
});

const getTotalStock = computed(() => {
    return state.products.reduce((transferQuantity, product) => parseFloat(transferQuantity) + parseFloat(product.stock), 0);
});

const getProducts = (color, size) => {
    return state.products.filter((product) => {
        return product.combination === color + ' ' + size;
    });
};

const saveProducts = () => {
    const products = [];
    for (const key in state.products) {
        if (state.products[key].quantity) {
            products.push(state.products[key]);
        }
    }

    emits('update:filter-advance-products-selection', products);

    closeModal();
};

const quantityApply = () => {
    for (const key in state.products) {
        if (state.products[key].stock > 0) {
            state.products[key].quantity = state.products[key].stock;
            if (state.products[key].stock >= state.quantity || props.defaultOrderType !== props.staticDetails.transfer_request) {
                state.products[key].quantity = state.quantity;
            }
        }
    }
    saveProducts();
};
</script>
