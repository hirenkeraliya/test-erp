<template>
    <Modal
        size="modal-lg"
        :show="props.modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Upload Promo Codes
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
            <div v-if="state.displayInvalidPromoCodeModal">
                <div>
                    <InfoAlert
                        color="danger"
                    >
                        <span class="flex">
                            The following promo codes contain invalid characters. Please fix and re-upload the file.
                        </span>
                    </InfoAlert>
                </div>
                <div
                    v-if="state.invalidPromoCodes"
                    class="grid grid-cols-12 gap-0 sm:gap-6 py-5"
                >
                    <div
                        v-for="(invalidPromoCode, index) in state.invalidPromoCodes"
                        :key="index"
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                    >
                        <span> {{ invalidPromoCode }}</span>
                    </div>
                </div>

                <PrimaryButton
                    type="button"
                    text="Cancel"
                    class="w-24 mr-1"
                    @click="closeModal"
                />
            </div>
            <div v-else>
                <div>
                    <InfoAlert
                        color="primary"
                    >
                        <span class="flex">
                            Promo codes cannot contain any of the following characters: 'space(a b)', '>', '&lt;', '|', '^', '`(grave accent)', '/' or '\' characters.
                        </span>
                    </InfoAlert>
                </div>

                <div class="grid grid-cols-12 gap-0 sm:gap-6 py-5">
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                        <JFileUpload
                            accept=".xlsx, .xls, .ods"
                            input-label="Upload File Records"
                            validation-field-name="import-promo-codes"
                            :required="true"
                            @update:input-file="importPromoCodes($event)"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                    >
                        <JFileDownload
                            file-path="/files/import-promo-codes-sample-file.xlsx"
                            input-label="Download Sample File"
                        />
                    </div>
                </div>

                <PrimaryButton
                    type="button"
                    text="Cancel"
                    class="w-24 mr-1"
                    @click="closeModal"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import XLSX from 'xlsx';
import { reactive } from 'vue';
import { showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import axios from 'axios';
import { route } from 'ziggy';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    promotionForm: {
        type: Object,
        required: true,
    },
    promotionId: {
        type: Number,
        default: null,
    }
});

const state = reactive({
    invalidPromoCodes: [],
    displayInvalidPromoCodeModal: false,
});

const emits = defineEmits([
    'close-modal',
    'update:valid-promo-codes-via-upload'
]);

const closeModal = () => {
    emits('close-modal');
};

const importPromoCodes = (files) => {
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

        const validPromoCodes = [];

        records.forEach(record => {
            if (record.promo_code.toString().includes('/') ||
                record.promo_code.toString().includes('\\') ||
                record.promo_code.toString().includes('>') ||
                record.promo_code.toString().includes('<') ||
                record.promo_code.toString().includes('|') ||
                record.promo_code.toString().includes('^') ||
                record.promo_code.toString().includes('`') ||
                record.promo_code.toString().includes(' ')
            ) {
                state.invalidPromoCodes.push(record.promo_code);
            } else {
                validPromoCodes.push(record.promo_code.toString());
            }
        });

        if (state.invalidPromoCodes.length) {
            state.displayInvalidPromoCodeModal = true;
        }

        let url = route('admin.promotions.exists_promo_codes');

        if (props.promotionId) {
            url = route('admin.promotions.exists_promo_codes', {
                promotionId: props.promotionId
            });
        }

        axios.post(url, { promoCodes: validPromoCodes })
            .then((response) => {
                const existsPromoCodes = response.data.exists_promo_codes;
                if (existsPromoCodes) {
                    showErrorNotification('The Promo code is already present in our records.');
                    return;
                }

                showSuccessNotification('Promo codes selected successfully.');

                emits('update:valid-promo-codes-via-upload', validPromoCodes);
            });

        document.getElementById('import-promo-codes').value = '';

        if (props.promotionForm.promo_codes.length !== validPromoCodes.length) {
            showErrorNotification(props.promotionForm.promo_codes.length + ' promo codes are expected from the uploaded file. We found only ' + validPromoCodes.length + ' promo codes. Please update and try again.');
            return;
        }

        emits('update:valid-promo-codes-via-upload', validPromoCodes);

        closeModal();
    };

    reader.readAsArrayBuffer(files);
};
</script>
