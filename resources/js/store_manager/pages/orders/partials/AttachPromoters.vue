<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Promoter Selections
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="px-5 sm:p-10">
            <div>
                <JMultiSelect
                    v-model:selected-records="state.selectedPromoters"
                    :records="promoters"
                    input-label="Promoters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please attach promoter"
                />
            </div>

            <div class="mt-3">
                <Tippy
                    tag="button"
                    type="button"
                    content="Attach To All Items"
                    class="btn btn-outline-primary mr-2 mb-2 sm:mb-0"
                    @click="attachToAllItems()"
                >
                    <Paperclip class="mr-2 w-4 h-4" />
                    Attach To All Items
                </Tippy>

                <Tippy
                    tag="button"
                    type="button"
                    content="Remove Too All Items"
                    class="btn btn-outline-primary"
                    @click="removeToAllItems()"
                >
                    <X class="mr-2 w-4 h-4" />
                    Remove To All Items
                </Tippy>
            </div>

            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="updateAttachedPromoter()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X, Paperclip } from 'lucide-vue-next';
import { onUpdated, reactive } from 'vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    promoters: {
        type: Object,
        required: true,
    },
    selectedPromoters: {
        type: Object,
        default: null,
    },
    selectedProduct: {
        type: Object,
        default: null,
    },
});

const state = reactive({
    selectedPromoters: null,
});

const emits = defineEmits([
    'close-attach-promoter-modal',
    'update-attach-promoter',
    'attach-to-all-items',
    'remove-to-all-items'
]);

const closeModal = () => {
    state.selectedPromoters = null;
    emits('close-attach-promoter-modal');
};

const updateAttachedPromoter = () => {
    emits('update-attach-promoter', state.selectedPromoters);
    closeModal();
};

onUpdated(() => {
    if (props.modalShow) {
        arePromoterAdded();
    }
});

const arePromoterAdded = () => {
    if (props.selectedProduct.promoter_ids.length === 0) {
        return;
    }

    state.selectedPromoters = props.promoters.reduce((selectedPromoters, promoter, index) => {
        if (props.selectedProduct.promoter_ids[index] === promoter.id) {
            selectedPromoters.push(promoter);
        }
        return selectedPromoters;
    }, []);
};

const attachToAllItems = () => {
    emits('attach-to-all-items', state.selectedPromoters);
};

const removeToAllItems = () => {
    emits('remove-to-all-items');
};
</script>
