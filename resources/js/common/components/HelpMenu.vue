<template>
    <div>
        <HelpCircle
            v-if="helpStore.getHelpData()"
            class="intro-x mr-4 text-white cursor-pointer"
            @click="state.isModalVisible = true"
        />
    </div>

    <Modal
        :slide-over="true"
        :show="state.isModalVisible"
        @hidden="state.isModalVisible = false"
    >
        <a
            class="absolute right-0 top-0 mr-3 mt-4"
            @click="state.isModalVisible = false"
        >
            <X class="w-8 h-8 text-slate-400" />
        </a>

        <ModalHeader
            class="p-5"
        >
            <h2 class="font-medium text-base mr-auto">
                Help
            </h2>
        </ModalHeader>
        <ModalBody>
            <div
                v-if="helpStore.getHelpData()"
            >
                <div
                    class="w-full"
                    v-html="helpStore.getHelpData()"
                />
            </div>
            <div v-else>
                <p class="text-sm text-center text-slate-700 mb-3">
                    Unfortunately, this page does not offer any helpful information.
                </p>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { X, HelpCircle } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { reactive } from 'vue';

const helpStore = useHelpCenterStore();

const state = reactive({
    isModalVisible: false,
});
</script>
