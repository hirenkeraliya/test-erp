<template>
    <Modal
        size="modal-lg"
        :show="showHierarchyModal"
        @hidden="hideHierarchyModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Select Merchandise Hierarchy
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideHierarchyModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <div
                v-for="(productHierarchy, index) in state.productHierarchies"
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
                        :records="productHierarchy.items"
                        validation-field-name="hierarchy_ids"
                        :show-validation-error="false"
                        input-label="Please select hierarchy"
                        placeholder="Please select hierarchy"
                        @update:selected-record="getChildHierarchies($event, index)"
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
    showHierarchyModal: {
        type: Boolean,
        default: false,
    },
    records: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    productHierarchies: [],
});

const emits = defineEmits([
    'update:selected-record',
    'update:hide-hierarchy-modal',
]);

const updateSelectedRecord = () => {
    emits('update:selected-record', getPreparedHierarchies());
    hideHierarchyModal();
};

const hideHierarchyModal = () => {
    emits('update:hide-hierarchy-modal', true);
};

const getChildHierarchies = (retailPlanningHierarchyId, index) => {
    if (!retailPlanningHierarchyId) {
        return;
    }

    state.productHierarchies[index].id = retailPlanningHierarchyId;

    limitHierarchiesToSpecifiedIndex(index);

    axios.get(route('admin.retail_planning_hierarchies.get_child_hierarchies', retailPlanningHierarchyId)).then((response) => {
        if (response.data.length) {
            state.productHierarchies.push({
                items: response.data,
            });
        }
    });
};

const getPreparedHierarchies = () => {
    const hierarchyPath = state.productHierarchies.filter((hierarchy) => {
        return !!hierarchy.id;
    }).map((hierarchy) => {
        return {
            id: hierarchy.id,
            name: getHierarchyName(hierarchy),
        };
    });

    const selectedHierarchyId = hierarchyPath.length > 0 ? hierarchyPath[hierarchyPath.length - 1].id : null;

    return {
        hierarchyPath,
        selectedHierarchyId
    };
};

const limitHierarchiesToSpecifiedIndex = (index) => {
    state.productHierarchies = state.productHierarchies.slice(0, index + 1);
};

const getHierarchyName = (hierarchy) => {
    for (const key in hierarchy.items) {
        if (hierarchy.items[key].id === parseInt(hierarchy.id)) {
            return hierarchy.items[key].name;
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
        state.productHierarchies = [{
            items: props.records,
        }];
    }
});
</script>
