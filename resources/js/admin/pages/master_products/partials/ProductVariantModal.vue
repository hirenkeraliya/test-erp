<template>
    <Modal
        size="modal-xl"
        :show="productVariantModalShow"
        @hidden="hideVariantModal"
    >
        <ModalHeader class="bg-slate-100">
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ title }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideVariantModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="bg-slate-100">
            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Information
                </div>

                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.name"
                            input-name="name"
                            input-label="Name"
                            :required="true"
                            :validation-field-name="'variants.' + variantDataIndex + '.name'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.code"
                            input-name="code"
                            input-label="Code"
                            :validation-field-name="'variants.' + variantDataIndex + '.code'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.description"
                            input-name="description"
                            input-label="Description"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <div
                            v-if="variantForm && variantForm.id"
                            class="block sm:flex items-center"
                        >
                            <div class="mt-3 w-full">
                                <label
                                    for="upc"
                                    class="form-label"
                                >UPC</label>
                                <p class="text-lg mt-1">
                                    {{ variantForm.upc }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-else
                            class="block sm:flex items-center"
                        >
                            <FormInput
                                v-model:input-value="variantForm.upc"
                                input-name="upc"
                                input-label="UPC"
                                :required="true"
                                class="w-full"
                                :validation-field-name="'variants.' + variantDataIndex + '.upc'"
                            />

                            <PrimaryButton
                                text="Generate"
                                class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                type="button"
                                @click="autoGenerateUpc()"
                            />
                        </div>
                    </div>
                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.ean"
                            input-name="ean"
                            input-label="Ean"
                            :validation-field-name="'variants.' + variantDataIndex + '.ean'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.custom_sku"
                            input-name="custom_sku"
                            input-label="Custom Sku"
                            :validation-field-name="'variants.' + variantDataIndex + '.custom_sku'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.manufacturer_sku"
                            input-name="manufacturer_sku"
                            input-label="Manufacturer Sku"
                            :validation-field-name="'variants.' + variantDataIndex + '.manufacturer_sku'"
                        />
                    </div>
                </div>
            </div>

            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Details
                </div>

                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div
                        v-for="(attribute, index) in variantAttributes"
                        :key="index"
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <DynamicAttribute
                            :attribute="attribute"
                            :field-types="fieldTypes"
                            :attribute-index="index"
                            :variant-index="variantDataIndex"
                            :attribute-selected-value="getAttributeSelectedValue(attribute)"
                            @update:custom-attribute-values="updateCustomAttributeValues($event, attribute.id, attribute.is_required)"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="variantForm.width"
                            input-name="product_variant_width"
                            input-label="Width (In CM)"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="variantForm.height"
                            input-name="product_variant_height"
                            input-label="Height (In CM)"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="variantForm.weight"
                            input-name="product_variant_weight"
                            input-label="Weight (In KG)"
                        />
                    </div>
                </div>
            </div>

            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Prices
                </div>

                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.retail_price"
                            input-name="retail_price"
                            input-label="Retail Price"
                            :input-group-prefix="currencySymbol"
                            :validation-field-name="'variants.' + variantDataIndex + '.retail_price'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.wholesale_price"
                            input-name="wholesale_price"
                            input-label="Whole Sale Price"
                            :input-group-prefix="currencySymbol"
                            :validation-field-name="'variants.' + variantDataIndex + '.wholesale_price'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.staff_price"
                            input-name="staff_price"
                            input-label="Staff Price"
                            :input-group-prefix="currencySymbol"
                            :validation-field-name="'variants.' + variantDataIndex + '.staff_price'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.minimum_price"
                            input-name="minimum_price"
                            input-label="Minimum Price"
                            :input-group-prefix="currencySymbol"
                            :validation-field-name="'variants.' + variantDataIndex + '.minimum_price'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <FormInput
                            v-model:input-value="variantForm.purchase_cost"
                            input-name="purchase_cost"
                            input-label="Purchase Cost"
                            :input-group-prefix="currencySymbol"
                            :validation-field-name="'variants.' + variantDataIndex + '.purchase_cost'"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="variantForm.online_price"
                            input-name="online_price"
                            input-label="Online Price"
                            :input-group-prefix="currencySymbol"
                            :validation-field-name="'variants.' + variantDataIndex + '.online_price'"
                        />
                    </div>
                </div>
            </div>

            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Configure Loyalty Points
                </div>

                <MembershipLoyaltyPointTiers
                    :tiers="variantForm.tiers"
                    :memberships="memberships"
                    :variant-index="variantDataIndex"
                    get-value-input-label="Loyalty Point"
                    @update:column-details="updateColumnDetails"
                    @update:tier-value-details="updateTierValueDetails"
                    @add:new-tier-details="addNewTierDetails"
                    @remove:tier-details-of="removeTierDetailsOf"
                />
            </div>

            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Box Details
                </div>

                <BoxProductTiers
                    :boxes="variantForm.boxes"
                    :variant-index="variantDataIndex"
                    :package-types="packageTypes"
                    :memberships="memberships"
                    @update:column-details="updateColumnDetails"
                    @update:tier-box-value-details="
                        updateTierBoxValueDetails
                    "
                    @add:new-tier-box-details="addNewTierBoxDetails"
                    @add:new-nested-tier-box-details="
                        addNewNestedTierBoxDetails($event)
                    "
                    @remove:tier-box-details-of="removeTierBoxDetailsOf"
                    @remove:nested-tier-box-details-of="
                        removeNestedTierBoxDetailsOf($event, $event)
                    "
                    @update:nested-tier-box-value-details="
                        nestedTierBoxValueDetails($event)
                    "
                />
            </div>

            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Media
                </div>

                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div
                        class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-3"
                    >
                        <JFileCropUpload
                            v-model:input-file="variantForm.thumbnail"
                            input-label="Thumbnail (343px X 260px)"
                            :max-width="343"
                            :max-height="260"
                            :validation-field-name="'variants.' + variantDataIndex + '.thumbnail'"
                            @update:input-file="uploadThumbnailImage"
                        />

                        <div
                            v-if="variantForm.thumbnail_url"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <Tippy
                                tag="div"
                                content="Remove this image?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="removeVariantThumbnail(variantForm.id)"
                            >
                                <X class="w-4 h-4" />
                            </Tippy>
                            <img
                                :src="variantForm.thumbnail_url"
                                :alt="variantForm.thumbnail_url"
                                width="100"
                                class="mt-2"
                            >
                        </div>
                    </div>
                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <JFileUpload
                            input-label="Images (500px X 500px)"
                            :is-multiple="true"
                            class="mt-3"
                            :validation-field-name="'variants.' + variantDataIndex + '.images'"
                            @change="uploadImage"
                        />

                        <div
                            v-for="(
                                uploadedImage, index
                            ) in variantForm.uploaded_images"
                            :key="index"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <Tippy
                                tag="div"
                                content="Remove this image?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="
                                    removeProductVariantImage(
                                        index,
                                        uploadedImage.id,
                                        variantForm.id
                                    )
                                "
                            >
                                <X class="w-4 h-4" />
                            </Tippy>
                            <img
                                :src="uploadedImage.url"
                                :alt="uploadedImage.url"
                                width="100"
                                class="mt-2"
                            >
                        </div>
                        <div
                            v-for="(
                                imageUrl, index
                            ) in state.uploadImageUrls"
                            :key="index"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <div
                                title="Remove this image?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="removeUploadItemImage(index)"
                            >
                                <X class="w-4 h-4" />
                            </div>
                            <img
                                :src="imageUrl"
                                :alt="imageUrl"
                                width="100"
                                class="mt-2"
                            >
                        </div>
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <JFileUpload
                            input-label="Videos"
                            :is-multiple="true"
                            class="mt-3"
                            :validation-field-name="'variants.' + variantDataIndex + '.videos'"
                            @change="uploadVideo"
                        />

                        <div
                            v-for="(
                                uploadedVideo, index
                            ) in variantForm.uploaded_videos"
                            :key="index"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <div
                                title="Remove this video?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="
                                    removeProductVariantVideo(
                                        index,
                                        uploadedVideo.id,
                                        variantForm.id
                                    )
                                "
                            >
                                <X class="w-4 h-4" />
                            </div>

                            <span
                                title="Video Play"
                                class="cursor-pointer flex justify-center w-12 h-12"
                                @click="
                                    openVideoPlayModal(uploadedVideo.url)
                                "
                            >
                                <PlayCircle
                                    class="text-indigo-900 w-14 h-14"
                                />
                            </span>
                        </div>

                        <div
                            v-for="(
                                videoUrl, index
                            ) in state.uploadVideoUrls"
                            :key="index"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                        >
                            <div
                                title="Remove this video?"
                                class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                @click="removeUploadProductVideo(index)"
                            >
                                <X class="w-4 h-4" />
                            </div>

                            <span
                                title="Video Play"
                                class="cursor-pointer flex justify-center w-12 h-12"
                            >
                                <PlayCircle
                                    class="text-indigo-900 w-14 h-14"
                                />
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="intro-y box p-5 mb-5">
                <div
                    class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                >
                    Configuration
                </div>

                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <JSwitch
                            v-model:is-checked="
                                variantForm.is_temporarily_unavailable
                            "
                            input-label="Temporarily Unavailable"
                            class="mt-3"
                            :validation-field-name="'variants.' + variantDataIndex + '.is_temporarily_unavailable'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <JSwitch
                            v-model:is-checked="variantForm.is_available_in_pos"
                            input-label="Available In-Store"
                            class="mt-3"
                            :validation-field-name="'variants.' + variantDataIndex + '.is_available_in_pos'"
                        />
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <JSwitch
                            :is-checked="
                                variantForm.is_available_in_ecommerce
                            "
                            input-label="Available Online"
                            class="mt-3"
                            :validation-field-name="'variants.' + variantDataIndex + '.is_available_in_ecommerce'"
                            @update:is-checked="updateIsAvailableInEcommerce('is_available_in_ecommerce', $event)"
                        />

                        <div
                            v-if="variantForm.is_available_in_ecommerce"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                        >
                            <JMultiSelect
                                v-model:selected-records="variantForm.sale_channels"
                                :records="saleChannels"
                                input-label="Sale Channels"
                                :required="true"
                                :validation-field-name="'variants.' + variantDataIndex + '.sale_channel_ids'"
                                class="w-full"
                                @update:selected-records="updateSaleChannels"
                            />
                        </div>
                    </div>

                    <div
                        class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                    >
                        <JSwitch
                            v-model:is-checked="
                                variantForm.is_sold_as_single_item
                            "
                            input-label="Is Sold As Single Item"
                            class="mt-3"
                            :validation-field-name="'variants.' + variantDataIndex + '.is_sold_as_single_item'"
                        />
                    </div>
                </div>
            </div>

            <div class="intro-y box p-5 mb-5">
                <div class="text-right">
                    <SecondaryButton
                        type="button"
                        text="Cancel"
                        class="w-24 mr-1"
                        @click="hideVariantModal"
                    />

                    <PrimaryButton
                        type="button"
                        text="Submit"
                        class="w-24"
                        @click="saveVariant"
                    />

                    <PrimaryButton
                        v-if="showApproveButton()"
                        type="button"
                        text="Approve"
                        class="w-24 ms-1"
                        @click="approveProduct()"
                    />
                </div>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import MembershipLoyaltyPointTiers from "@adminPages/master_products/MembershipLoyaltyPointTiers.vue";
import DynamicAttribute from "@adminPages/master_products/partials/DynamicAttribute.vue";
import BoxProductTiers from "@adminPages/master_products/BoxProductTiers.vue";
import FormInput from "@commonComponents/FormInput.vue";
import JFileCropUpload from "@commonComponents/JFileCropUpload.vue";
import JFileUpload from "@commonComponents/JFileUpload.vue";
import JSwitch from "@commonComponents/JSwitch.vue";
import PrimaryButton from "@commonComponents/PrimaryButton.vue";
import SecondaryButton from "@commonComponents/SecondaryButton.vue";
import { Modal, ModalBody, ModalHeader } from "@commonVendor/model";
import { useForm, usePage } from "@inertiajs/vue3";
import "@left4code/tw-starter/dist/js/modal";
import axios from "axios";
import { PlayCircle, X } from "lucide-vue-next";
import { computed, onMounted, reactive } from "vue";
import { route } from "ziggy";
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    productVariantModalShow: {
        type: Boolean,
        default: false,
    },
    variantDataIndex: {
        type: Number,
        default: 0,
    },
    variantAttributes: {
        type: [Array, Object],
        default: () => {},
    },
    title: {
        type: String,
        default: "Add Variant",
    },
    templates: {
        type: Array,
        required: true,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
    memberships: {
        type: Object,
        required: true,
    },
    packageTypes: {
        type: Object,
        required: true,
    },
    variantData: {
        type: [Object, Array],
        default: () => {},
    },
    showApproveButton: {
        type: Boolean,
        default: false,
    },
    productId: {
        type: Number,
        default: 0
    },
    saleChannels: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    images: [],
    uploadImageUrls: [],
    videos: [],
    uploadVideoUrls: [],
    videoUrl: null,
});

const variantForm = useForm({
    id: null,
    name: null,
    code: null,
    description: null,
    upc: null,
    ean: null,
    custom_sku: null,
    manufacturer_sku: null,
    width: 0,
    height: 0,
    weight: 0,
    retail_price: 0,
    wholesale_price: 0,
    staff_price: 0,
    minimum_price: 0,
    purchase_cost: 0,
    online_price: 0,
    is_temporarily_unavailable: false,
    is_available_in_pos: true,
    is_available_in_ecommerce: false,
    is_sold_as_single_item: true,
    thumbnail: null,
    thumbnail_url: null,
    images: [],
    uploaded_images: [],
    videos: [],
    uploaded_videos: [],
    tiers: [
        {
            membership_id: null,
            points: null,
        },
    ],
    boxes: [],
    product_variant_values: [],
    sale_channels: [],
    sale_channel_ids: [],
});

const saveVariant = () => {
    emits("new:record", variantForm.data());
    hideVariantModal();
};

const emits = defineEmits(["update:hide-product-variant-modal", "new:record", "approve-product"]);

const hideVariantModal = () => {
    emits("update:hide-product-variant-modal", false);
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    variantForm[columnName] = details.value;
};

const updateTierValueDetails = (details) => {
    variantForm.tiers[details.key][details.column_name] = details.value;
};

const addNewTierDetails = () => {
    variantForm.tiers.push({ membership_id: null, points: null });
};

const addNewTierBoxDetails = () => {
    variantForm.boxes.push({
        package_type_id: null,
        units: null,
        retail_price: 0,
        minimum_price: 0,
        staff_price: 0,
        purchase_cost: 0,
        wholesale_price: 0,
        box_product_loyalty_points: [{ membership_id: null, points: null }],
    });
};

const addNewNestedTierBoxDetails = (index) => {
    variantForm.boxes[index].box_product_loyalty_points.push({
        membership_id: null,
        points: null,
    });
};

const updateTierBoxValueDetails = (details) => {
    variantForm.boxes[details.key][details.column_name] = details.value;
};

const removeTierBoxDetailsOf = (key) => {
    variantForm.boxes.splice(key, 1);
};

const removeNestedTierBoxDetailsOf = (data) => {
    variantForm.boxes[data.mainIndex].box_product_loyalty_points.splice(
        data.key,
        1
    );
};

const nestedTierBoxValueDetails = (details) => {
    variantForm.boxes[details.main_index].box_product_loyalty_points[
        details.key
    ][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    variantForm.tiers.splice(key, 1);
};


const uploadThumbnailImage = (selectedImage) => {
    variantForm.thumbnail_url = URL.createObjectURL(selectedImage);
};

const removeVariantThumbnail = (variantId) => {
    variantForm.thumbnail = null;
    variantForm.thumbnail_url = null;

    if (variantId) {
        axios.get(route("admin.master_products.remove_product_variant_thumbnail", variantId));
    }
};

const autoGenerateUpc = () => {
    const alphabetBase = 36;
    const minNumber = 1111;
    const maxNumber = 9999;
    const stringStartIndex = 2;
    const stringEndIndex = 6;
    const randomLetters = Math.random()
        .toString(alphabetBase)
        .substring(stringStartIndex, stringEndIndex)
        .toUpperCase();
    const randomNumber = Math.floor(Math.random() * (maxNumber - minNumber + 1)) + minNumber;
    const generatedUPC =
            randomLetters +
            randomNumber +
            Math.random().toString(alphabetBase).substring(stringStartIndex, stringEndIndex).toUpperCase();

    axios
        .get(route("admin.master_products.exists_master_product_upc", generatedUPC))
        .then((response) => {
            if (response.data.status === true) {
                autoGenerateUpc();
            }
            variantForm.upc = generatedUPC;
        });
};

onMounted(() => {
    if (props.variantData !== null) {
        Object.assign(
            variantForm,
            JSON.parse(JSON.stringify(props.variantData))
        );

        if (variantForm.product_variant_values.length <= 0) {
            props.variantAttributes.forEach(variantAttribute => {
                variantForm.product_variant_values.push({
                    id: variantAttribute.id,
                    selected_value: variantAttribute.selected_value,
                    is_required: variantAttribute.is_required,
                });
            });
        }

        if(variantForm.sale_channels && variantForm.sale_channels.length > 0) {
            variantForm.sale_channel_ids = variantForm.sale_channels.map(channel => channel.id);
        }
    }
});

const removeProductVariantImage = (index, mediaId, varianId) => {
    variantForm.uploaded_images.splice(index, 1);

    if (varianId) {
        axios.get(route("admin.master_products.remove_product_variant_image", [varianId, mediaId]));
    }
};

const removeProductVariantVideo = (index, mediaId, variantId) => {
    variantForm.uploaded_videos.splice(index, 1);

    if (variantId) {
        axios.get(route("admin.master_products.remove_product_variant_video", [variantId, mediaId]));
    }
};

const removeUploadItemImage = (index) => {
    state.uploadImageUrls.splice(index, 1);
    state.images.splice(index, 1);
    variantForm.images = state.images;
};

const removeUploadProductVideo = (index) => {
    state.uploadVideoUrls.splice(index, 1);
    state.videos.splice(index, 1);
    variantForm.videos = state.videos;
};

const uploadImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.images.push(selectedImage.target.files[index]);
        variantForm.images = state.images;
        state.uploadImageUrls.push(
            URL.createObjectURL(selectedImage.target.files[index])
        );
    }
};

const uploadVideo = (selectedVideo) => {
    for (let index = 0; index < selectedVideo.target.files.length; index++) {
        state.videos.push(selectedVideo.target.files[index]);
        variantForm.videos = state.videos;
        state.uploadVideoUrls.push(
            URL.createObjectURL(selectedVideo.target.files[index])
        );
    }
};

const itemNotFound = -1;

const updateCustomAttributeValues = (value, attributeId, isRequired) => {
    const index = variantForm.product_variant_values.findIndex(item => item.id === attributeId);
    if (index !== itemNotFound) {
        variantForm.product_variant_values[index].selected_value = value;
    } else {
        variantForm.product_variant_values.push(
            {
                id: attributeId,
                selected_value: value,
                is_required: isRequired
            },
        );
    }
};

const getAttributeSelectedValue = (attribute) => {
    const index = variantForm.product_variant_values.findIndex(item => item.id === attribute.id);
    if (index !== itemNotFound) {
        return variantForm.product_variant_values[index].selected_value;
    }

    return null;
};

const showApproveButton = () => {
    if (props.productId !== variantForm.id) {
        return false;
    }

    return props.showApproveButton;
};

const approveProduct = () => {
    hideVariantModal();
    emits("approve-product");
};

const updateIsAvailableInEcommerce = (columnName, data) => {
    variantForm.sale_channels = [];
    variantForm.sale_channel_ids = [];
    variantForm[columnName] = data;
};

const updateSaleChannels = (values) => {

    if (values) {
        variantForm.sale_channel_ids = values.map((saleChannel) => {
            return saleChannel.id;
        });
    }
};
</script>
