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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div>
                    <FormInput
                        v-model:input-value="state.article_number"
                        type="text"
                        input-label="Article Number"
                        input-name="article_number"
                        validation-field-name="article_number"
                        :required="true"
                    />
                </div>

                <div>
                    <OutlinePrimaryButton
                        text="Search"
                        class="shadow-md text-sm mx-1 mt-0 md:mt-8"
                        @click="searchProductArticleNumber()"
                    />
                </div>
            </div>

            <h4 class="mt-4">
                <div class="block md:flex justify-between">
                    <p v-if="Object.keys(state.products).length > 0">
                        <Tippy
                            :content="state.products[0].name"
                        >
                            <JBadge
                                :label="state.products[0].name"
                                type="success"
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
                        <OutlinePrimaryButton
                            text="Apply To All"
                            class="shadow-md text-sm mt-8"
                            :disabled="state.quantity <= 0 || state.quantity === ''"
                            @click="quantityApply()"
                        />
                    </div>
                </div>
            </h4>

            <div
                v-if="state.products.length && (Object.keys(state.xNames).length || Object.keys(state.yNames).length)"
                class="mt-5"
            >
                <div class="overflow-x-auto">
                    <table class="w-full table-auto rounded">
                        <thead>
                            <tr class="bg-zinc-100">
                                <th class="font-medium px-5 py-3 border-b-2 border-l border-r border-t text-center">
                                    #
                                </th>
                                <template v-if="Object.keys(state.xNames).length > 0">
                                    <th
                                        v-for="(xName, index) in state.xNames"
                                        :key="index"
                                        class="font-medium px-5 py-3 border-b-2 border-l border-r border-t text-center"
                                    >
                                        {{ xName }}
                                    </th>
                                </template>
                                <th
                                    v-else
                                    class="font-medium px-5 py-3 border-b-2 border-l border-r border-t text-center"
                                />
                            </tr>
                        </thead>

                        <tbody>
                            <template
                                v-if="Object.keys(state.yNames).length > 0"
                            >
                                <tr
                                    v-for="(yName, yIndex) in state.yNames"
                                    :key="yIndex"
                                >
                                    <td
                                        class="font-medium px-5 py-3 border-b-2 border-l border-r border-t"
                                    >
                                        {{ yName }}
                                    </td>
                                    <template
                                        v-if="Object.keys(state.xNames).length > 0"
                                    >
                                        <td
                                            v-for="(xName, xIndex) in state.xNames"
                                            :key="xIndex"
                                            class="font-medium px-5 py-3 border-b-2 border-l border-r border-t"
                                        >
                                            <div
                                                v-if="!getProducts(xName,yName).length"
                                                class="text-red-500"
                                            >
                                                <StopSvg label-class="mx-auto" />
                                            </div>

                                            <div
                                                v-for="(product, productIndex) in getProducts(xName,yName)"
                                                v-else
                                                :key="productIndex"
                                            >
                                                <FormInput
                                                    :input-value="product.stock"
                                                    input-name="stock"
                                                    placeholder="Print Quantity"
                                                    label-class="mt-0"
                                                    type="number"
                                                    class="w-[200px] lg:w-full"
                                                    @update:input-value="updateTheStock(product, $event)"
                                                />

                                                <span class="pl-1 flex justify-between mt-1">
                                                    Print Quantity: {{ product.stock }} <br>
                                                </span>
                                            </div>
                                        </td>
                                    </template>
                                    <td
                                        v-else
                                        class="font-medium px-5 py-3 border-b-2 border-l border-r border-t"
                                    >
                                        <div
                                            v-if="!getProducts('',yName).length"
                                            class="text-red-500"
                                        >
                                            <StopSvg label-class="mx-auto" />
                                        </div>

                                        <div
                                            v-for="(product, productIndex) in getProducts('', yName)"
                                            v-else
                                            :key="productIndex"
                                        >
                                            <FormInput
                                                :input-value="product.stock"
                                                input-name="stock"
                                                placeholder="Print Quantity"
                                                label-class="mt-0"
                                                type="number"
                                                class="w-[200px] lg:w-full"
                                                @update:input-value="updateTheStock(product, $event)"
                                            />

                                            <span
                                                class="pl-1 flex justify-between mt-1"
                                            >
                                                Print Quantity: {{ product.stock }} <br>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template
                                v-else-if="Object.keys(state.xNames).length > 0"
                            >
                                <tr>
                                    <td
                                        class="font-medium px-5 py-3 border-b-2 border-l border-r border-t"
                                    />
                                    <template
                                        v-if="Object.keys(state.xNames).length > 0"
                                    >
                                        <td
                                            v-for="(xName, xIndex) in state.xNames"
                                            :key="xIndex"
                                            class="font-medium px-5 py-3 border-b-2 border-l border-r border-t"
                                        >
                                            <div
                                                v-if="!getProducts(xName,'').length"
                                                class="text-red-500"
                                            >
                                                <StopSvg label-class="mx-auto" />
                                            </div>

                                            <div
                                                v-for="(product, productIndex) in getProducts(xName,'')"
                                                v-else
                                                :key="productIndex"
                                            >
                                                <FormInput
                                                    :input-value="product.stock"
                                                    input-name="stock"
                                                    placeholder="Print Quantity"
                                                    label-class="mt-0"
                                                    type="number"
                                                    class="w-[200px] lg:w-full"
                                                    @update:input-value="updateTheStock(product, $event)"
                                                />

                                                <span class="pl-1 flex justify-between mt-1">
                                                    Print Quantity: {{ product.stock }} <br>
                                                </span>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </template>
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
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import axios from 'axios';
import { reactive } from 'vue';
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
    stockTransferForm: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    products: [],
    data: [],
    attributeNames: [],
    xNames: [],
    yNames: [],
    article_number: null,
    quantity: null,
});

const emits = defineEmits(['close-modal', 'update:filter-advance-product-selection']);

const closeModal = () => {
    emits('close-modal');
};

const searchProductArticleNumber = () => {
    if (state.article_number === null) {
        showErrorNotification('Please enter article number');
        return;
    }

    axios.post(route(props.productArticleSearchUrl), {
        article_number: state.article_number,
    }).then((response) => {
        state.products = response.data.products;
        state.attributeNames = response.data.attributeNames;
        state.xNames = response.data.xNames;
        state.yNames = response.data.yNames;

        if (!Object.keys(state.xNames).length && !Object.keys(state.yNames).length) {
            showErrorNotification('No products found for this article number');
        }
    }).catch((error) => {
        showErrorNotification(error.response.data.message);
    });
};

const updateTheStock = (product, quantity) => {
    if (isNaN(quantity)) {
        return;
    }

    product.print_quantity = quantity;
    product.article_number = state.article_number;
};

const getProducts = (xName, yName) => {
    if(!xName){
        xName = '';
    }

    if(!yName){
        yName = '';
    }

    xName = xName.replace(/\|/g, ' ').trim();
    yName = yName.replace(/\|/g, ' ').trim();

    return state.products.filter((product) => {
        return product.combination == xName + ' ' + yName;
    });
};

const saveProducts = () => {
    for (const key in state.products) {
        if (state.products[key].print_quantity > 0) {
            emits('update:filter-advance-product-selection', state.products[key]);
        }
    }

    closeModal();
};

const quantityApply = () => {
    for (const key in state.products) {
        state.products[key].stock = state.quantity;
        state.products[key].print_quantity = state.quantity;
        state.products[key].article_number = state.article_number;
    }
    saveProducts();
};
</script>
