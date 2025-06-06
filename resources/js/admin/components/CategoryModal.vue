<template>
    <Modal
        size="modal-lg"
        :show="categoryModalShow"
        @hidden="hideCategoryModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Select Category
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideCategoryModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <div
                v-for="(productCategory, index) in state.productCategories"
                :key="index"
                class="grid grid-cols-12 gap-0 sm:gap-6"
                :style="{ 'margin-left': getMarginLeft(index)+'px' }"
            >
                <div
                    v-if="index != 0"
                    class="mr-3 py-3"
                >
                    <ChevronRight class="w-6 h-8 text-slate-400" />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                    <FormSelectBox
                        :selected-record="null"
                        :records="productCategory.items"
                        validation-field-name="category_ids"
                        :show-validation-error="false"
                        input-label="Please select category"
                        placeholder="Please select category"
                        @update:selected-record="getChildCategories($event, index)"
                    />
                </div>
            </div>

            <div class="text-left mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="updateSelectedRecord"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import '@left4code/tw-starter/dist/js/modal';
import { ChevronRight, X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

import axios from 'axios';

const props = defineProps({
    categoryModalShow: {
        type: Boolean,
        default: false,
    },
    records: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    productCategories: [],
});

const emits = defineEmits([
    'update:selected-record',
    'update:hide-category-modal',
]);

const updateSelectedRecord = () => {
    emits('update:selected-record', getPreparedCategories());
    hideCategoryModal();
};

const hideCategoryModal = () => {
    emits('update:hide-category-modal', false);
};

const getChildCategories = (categoryId, index) => {
    if (!categoryId) {
        return;
    }

    state.productCategories[index].id = categoryId;

    keepCategoriesUntil(index);

    axios.get(route('admin.categories.get_child_categories', categoryId)).then((response) => {
        if (response.data.length) {
            state.productCategories.push({
                items: response.data,
            });
        }
    });
};

const keepCategoriesUntil = (index) => {
    const categories = [];

    for (const key in state.productCategories) {
        if (key <= index) {
            categories.push(state.productCategories[key]);
        }
    }

    state.productCategories = categories;
};

const getPreparedCategories = () => {
    return state.productCategories.filter((category) => {
        return !!category.id;
    }).map((category) => {
        return {
            id: category.id,
            name: getCategoryName(category),
        };
    });
};

const getCategoryName = (category) => {
    for (const key in category.items) {
        if (category.items[key].id === parseInt(category.id)) {
            return category.items[key].name;
        }
    }

    return '';
};

const getMarginLeft = (index) => {
    let marginPixel = 0;
    const marginSize = 12;

    for (let startPoint = 0; startPoint < index; startPoint++) {
        marginPixel += marginSize;
    }

    return marginPixel;
};

onMounted(() => {
    if (props.records) {
        state.productCategories = [{
            items: props.records,
        }];
    }
});
</script>
