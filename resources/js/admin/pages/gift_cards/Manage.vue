<template>
    <PageTitle title="Upload Gift Cards" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Upload Gift Card
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <form @submit.prevent="uploadGiftCard();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="giftCardForm.type_id"
                                    :records="giftCardTypes"
                                    input-label="Type"
                                    validation-field-name="type-id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                <div class="block sm:flex flex-col mb-3 -mx-3 sm:flex-row">
                                    <div class="w-full px-3">
                                        <JFileUpload
                                            accept=".xlsx, .xls, .ods"
                                            input-label="Upload Gift Cards"
                                            :required="state.selectedGiftCards.length ? false : true"
                                            validation-field-name="gift-cards"
                                            @update:input-file="importRecords($event)"
                                        />
                                    </div>

                                    <div class="w-full px-3 mt-4 sm:mt-0">
                                        <JFileDownload
                                            file-path="/files/gift-card-sample-file.xlsx"
                                            input-label="Download Sample File"
                                        />
                                    </div>
                                </div>

                                <div class="block sm:flex justify-between w-full my-2">
                                    <div class="w-full pr-0 sm:pr-3">
                                        <div class="flex items-center">
                                            <p class="py-3 text-2xl font-medium text-primary">
                                                {{ state.selectedGiftCards.length }}
                                            </p>
                                            <p class="ml-4 text-base text-black font-medium">
                                                Gift Cards selected
                                            </p>
                                        </div>
                                    </div>

                                    <div class="w-full pl-0 text-left sm:pl-3">
                                        <button
                                            :disabled="! state.selectedGiftCards.length"
                                            class="px-8 text-sm font-bold rounded-r-lg btn py-18 text-black-40 bg-slate-300"
                                            type="button"
                                            @click="openSelectedProductsModal"
                                        >
                                            View All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.gift_cards.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                text="Submit"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <SelectedProducts
        :modal-show="state.displaySelectedGiftCardsModal"
        :columns="state.fields"
        :records="state.selectedGiftCards"
        @close-modal="closeModal"
    />
</template>
<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { route } from 'ziggy';
import { onUpdated, reactive } from 'vue';
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import XLSX from 'xlsx';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

defineProps({
    giftCardTypes: {
        type: Array,
        required: true,
    }
});

const giftCardForm = useForm({
    type_id: null,
    gift_cards: null,
});

const state = reactive({
    fields: [
        {
            key: 'number',
        },
        {
            key: 'expiry_date',
        },
        {
            key: 'amount',
        },
    ],

    selectedGiftCards: [],
    displaySelectedGiftCardsModal: false,
});

const openSelectedProductsModal = () => {
    state.displaySelectedGiftCardsModal = true;
};

const closeModal = () => {
    state.displaySelectedGiftCardsModal = false;
};

const uploadGiftCard = () => {
    giftCardForm.gift_cards = state.selectedGiftCards;
    giftCardForm.post(route('admin.gift_cards.upload'));
    document.getElementById('gift-cards').value = '';
};

onUpdated(() => {
    if (state.records) {
        giftCardForm.gift_cards = state.selectedGiftCards;
    }
});

const importRecords = (files) => {
    state.selectedGiftCards = [];
    const fileExtension = files.name.split('.').pop().trim();

    if (fileExtension !== 'xlsx' && fileExtension !== 'xls') {
        showErrorNotification('Please select a valid excel file.');
        return;
    }

    const reader = new FileReader();

    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const records = JSON.parse(JSON.stringify(XLSX.utils.sheet_to_json(worksheet, {
            blankRows: false,
            defval: null,
        })).replace(/"\s+|\s+"/g, '"'));

        for (const key in records) {
            state.selectedGiftCards.push(records[key]);
        }
        showSuccessNotification('Gift Cards selected from the file successfully.');
    };

    reader.readAsArrayBuffer(files);
};
</script>
