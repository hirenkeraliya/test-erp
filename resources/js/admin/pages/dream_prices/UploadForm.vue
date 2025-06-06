<template>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        Upload Products
                    </h2>
                </div>

                <div class="px-5">
                    <InfoAlert
                        color="primary"
                        class="mb-3 mt-5"
                    >
                        Please only upload regular products. Uploading other types of products may result in loss of data.
                    </InfoAlert>
                </div>

                <form @submit.prevent="saveDreamPriceProducts();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileUpload
                                    v-model:input-file="dreamPriceProductsForm.dream_price_products"
                                    accept=".xlsx, .xls, .ods"
                                    input-label="Dream Price Products"
                                    validation-field-name="dream-price-products"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileDownload
                                    file-path="/files/dream-price-products-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.dream_prices.index')">
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
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JFileDownload from '@commonComponents/JFileDownload.vue';
import { route } from 'ziggy';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';

const props = defineProps({
    dreamPriceId: {
        type: Number,
        default: null,
    },
});

const dreamPriceProductsForm = useForm({
    dream_price_products: null,
});

const saveDreamPriceProducts = () => {
    dreamPriceProductsForm.post(route('admin.dream_prices.upload_products', props.dreamPriceId));
};

</script>
