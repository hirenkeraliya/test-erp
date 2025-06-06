<template>
    <PageTitle :title="masterProduct ? 'Edit Master Product' : 'Add Master Product'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2
            v-if="masterProduct"
            class="text-lg font-medium mr-auto"
        >
            Edit Master Product
        </h2>

        <h2
            v-else
            class="text-lg font-medium mr-auto"
        >
            Add Master Product
        </h2>

        <SecondaryButton
            type="button"
            text="Clear"
            class="w-24"
            @click="clearFormData"
        />
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <form @submit.prevent="saveMasterProduct()">
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
                                v-model:input-value="masterProductForm.name"
                                input-name="name"
                                input-label="Name"
                                :required="true"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormInput
                                v-model:input-value="masterProductForm.code"
                                input-name="code"
                                input-label="Code"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormInput
                                v-model:input-value="masterProductForm.article_number"
                                input-name="article_number"
                                input-label="Article Number"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <JDateTimePicker
                                v-model:input-value="masterProductForm.original_created_at"
                                input-label="Original Created At"
                                validation-field-name="original_created_at"
                            />
                        </div>

                        <div class="input-form col-span-12">
                            <label class="form-label mt-3"> Description </label>

                            <ckeditor
                                v-model="masterProductForm.description"
                                :editor="ClassicEditor"
                                :config="state.editorConfig"
                                tag-name="textarea"
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
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormSelectBox
                                v-if="!masterProduct"
                                :selected-record="masterProductForm.type_id"
                                :records="types"
                                input-label="Product Type"
                                validation-field-name="type_id"
                                :title="
                                    masterProductForm.type_id !==
                                        defaultTypeStatic.regularProduct
                                        ? 'Dream Price, Price Override, and complimentary are eligible for regular, box and assembly products only.'
                                        : null
                                "
                                @update:selected-record="updateProductType"
                            />

                            <div
                                v-else
                                class="mt-3"
                            >
                                <div class="input-group">
                                    <label> Product Type: </label>
                                </div>
                                <div class="font-medium">
                                    {{ masterProduct.type_name }}
                                </div>
                            </div>
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <div
                                v-if="state.unitOfMeasureName"
                                class="block sm:flex items-center"
                            >
                                <div v-if="updateUnitOfMeasure">
                                    <FormSelectBox
                                        v-model:selected-record="masterProductForm.unit_of_measure_id"
                                        :records="unitOfMeasures"
                                        input-label="Unit Of Measure"
                                        validation-field-name="unit_of_measure_id"
                                    />
                                </div>

                                <div
                                    v-else
                                    class="mt-3 w-full"
                                >
                                    <label
                                        for="uom"
                                        class="form-label"
                                    >Unit Of Measure</label>
                                    <p class="text-lg mt-1">
                                        {{ state.unitOfMeasureName }}
                                    </p>
                                </div>
                            </div>

                            <FormSelectBox
                                v-else
                                v-model:selected-record="masterProductForm.unit_of_measure_id"
                                :records="unitOfMeasures"
                                input-label="Unit Of Measure"
                                validation-field-name="unit_of_measure_id"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormSelectBox
                                v-model:selected-record="masterProductForm.brand_id"
                                :records="brands"
                                input-label="Brand"
                                validation-field-name="brand_id"
                                :required="true"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormSelectBox
                                v-model:selected-record="masterProductForm.vendor_id"
                                :records="vendors"
                                input-label="Vendor"
                                validation-field-name="vendor_id"
                                class="w-full"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormSelectBox
                                v-model:selected-record="masterProductForm.department_id"
                                :records="departments"
                                input-label="Department"
                                validation-field-name="department_id"
                                class="w-full"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <JMultiSelect
                                v-model:selected-records="masterProductForm.tags"
                                :records="tags"
                                input-label="Tags (Type & Enter to create new tag)"
                                :option-create="saveNewTag"
                                :taggable="true"
                                validation-field-name="tag_ids"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6"
                        >
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6 mt-3"
                            >
                                <label>
                                    Category
                                    <span class="text-danger">*</span>
                                </label>

                                <div
                                    v-if="masterProductForm.categories.length === 0"
                                    class="flex flex-col sm:flex-row"
                                >
                                    <div class="form-check mr-2">
                                        <OutlinePrimaryButton
                                            text="Select Category"
                                            class="inline-block mr-1 mb-2"
                                            @click="state.categoryModalShow = true"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-col sm:flex-row"
                                >
                                    <div
                                        v-for="(
                                            category, index
                                        ) in masterProductForm.categories"
                                        :key="index"
                                        class="form-check mr-2 mt-2 sm:mt-0"
                                    >
                                        <ChevronRight
                                            v-if="index != 0"
                                            class="w-4 h-4 text-slate-400"
                                        />
                                        <label
                                            class="form-check-label"
                                            for="checkbox-switch-5"
                                        >
                                            {{ category.name }}
                                        </label>
                                    </div>

                                    <div class="form-check mr-2 mt-2 sm:mt-0">
                                        <label
                                            class="form-check-label"
                                            for="checkbox-switch-5"
                                        >
                                            <Edit2
                                                class="w-4 h-5 text-slate-400"
                                                @click="
                                                    state.categoryModalShow = true
                                                "
                                            />
                                        </label>

                                        <label
                                            class="form-check-label"
                                            for="checkbox-switch-5"
                                        >
                                            <X
                                                class="w-6 h-6 text-slate-400"
                                                @click="masterProductForm.categories = []"
                                            />
                                        </label>
                                    </div>
                                </div>
                                <ValidationError
                                    :validation-field-name="'category_ids'"
                                />
                            </div>

                            <CategoryModal
                                v-if="state.categoryModalShow"
                                :records="categories"
                                :category-modal-show="state.categoryModalShow"
                                @update:selected-record="updateCategories"
                                @update:hide-category-modal="hideCategoryModal"
                            />
                        </div>
                    </div>
                </div>

                <div class="intro-y box p-5 mb-5">
                    <div
                        class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                    >
                        Custom Attributes
                    </div>

                    <CustomFieldValues
                        :custom-field-values="masterProduct?.custom_field_values"
                        :templates="props.templates"
                        :field-types="props.fieldTypes"
                        @update:custom-field-values="
                            updateCustomFieldValues($event)
                        "
                    />
                </div>

                <div
                    v-if="assemblyProductTypeStatic.assemblyProduct !== masterProductForm.type_id"
                    class="intro-y box p-5 mb-5"
                >
                    <div
                        class="flex pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                    >
                        <div class="mr-auto">
                            Variants
                        </div>

                        <div class="ml-auto pr-10">
                            <FormSelectBox
                                :selected-record="masterProductForm.variant_template_id"
                                :records="props.variantTemplates"
                                input-label="Variant Template"
                                validation-field-name="variant_template_id"
                                :required="true"
                                @update:selected-record="variantTemplateSelected"
                            />
                        </div>
                        <div class="mt-8">
                            <OutlinePrimaryButton
                                text="Add Variant"
                                type="button"
                                :disabled="masterProductForm.variant_template_id === null"
                                @click="addVariant"
                            />
                        </div>
                    </div>

                    <div>
                        <ProductVariantDetails
                            :item-variants="masterProductForm.variants"
                            :item-form="masterProductForm"
                            :variant-attributes="state.variantAttributes"
                            :is-draft-product="isDraftProduct"
                            :product-id="productId"
                            @edit:variant-data="getVariantDetails"
                            @remove:variant-data="removeVariant"
                        />
                    </div>
                </div>

                <div
                    v-else
                    class="intro-y box p-5 mb-5"
                >
                    <div
                        class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                    >
                        Child Items
                    </div>

                    <AssemblyChildMasterProductTiers
                        :assembly-child-master-products="masterProductForm.assembly_child_master_products"
                        @update:column-details="updateColumnDetails"
                        @update:tier-assembly-item-value-details="
                            updateTierAssemblyItemValueDetails
                        "
                        @add:new-tier-assembly-item-details="
                            addNewTierAssemblyItemDetails
                        "
                        @remove:tier-assembly-item-details-of="
                            removeTierAssemblyItemDetailsOf
                        "
                    />
                </div>

                <div class="intro-y box p-5 mb-5">
                    <div
                        class="flex pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                    >
                        <div class="mr-auto">
                            Media Management
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-0 sm:gap-6">
                        <div
                            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-3"
                        >
                            <JFileCropUpload
                                v-model:input-file="masterProductForm.thumbnail"
                                input-label="Thumbnail (343px X 260px)"
                                validation-field-name="thumbnail"
                                :max-width="343"
                                :max-height="260"
                                @update:input-file="uploadThumbnailImage"
                            />

                            <div
                                v-if="masterProductForm.thumbnail_url"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <Tippy
                                    tag="div"
                                    content="Remove this image?"
                                    class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                    @click="removeMasterProductThumbnail(masterProduct?.id)"
                                >
                                    <X class="w-4 h-4" />
                                </Tippy>
                                <img
                                    :src="masterProductForm.thumbnail_url"
                                    :alt="masterProductForm.thumbnail_url"
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
                                validation-field-name="images"
                                :is-multiple="true"
                                class="mt-3"
                                @change="uploadImage"
                            />

                            <div
                                v-for="(
                                    uploadedImage, index
                                ) in masterProductForm.uploaded_images"
                                :key="index"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <Tippy
                                    tag="div"
                                    content="Remove this image?"
                                    class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                    @click="
                                        removeMasterProductImage(
                                            index,
                                            uploadedImage.id,
                                            masterProduct?.id
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
                                    @click="removeUploadMasterProductImage(index)"
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
                                validation-field-name="videos"
                                :is-multiple="true"
                                class="mt-3"
                                @change="uploadVideo"
                            />

                            <div
                                v-for="(
                                    uploadedVideo, index
                                ) in masterProductForm.uploaded_videos"
                                :key="index"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div
                                    title="Remove this video?"
                                    class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                    @click="
                                        removeMasterProductVideo(
                                            index,
                                            uploadedVideo.id,
                                            masterProduct?.id
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
                                    @click="removeUploadMasterProductVideo(index)"
                                >
                                    <X class="w-4 h-4" />
                                </div>

                                <span
                                    title="Video Play"
                                    class="cursor-pointer flex justify-center w-12 h-12"
                                    @click="openVideoPlayModal(videoUrl)"
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
                        class="flex pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                    >
                        <div class="mr-auto">
                            Configuration
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-0 sm:gap-6">
                        <div
                            v-if="
                                assemblyProductTypeStatic.assemblyProduct !==
                                    masterProductForm.type_id
                            "
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <JSwitch
                                v-model:is-checked="masterProductForm.is_non_inventory"
                                input-label="Non-Inventory"
                                class="mt-3"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <JSwitch
                                v-model:is-checked="masterProductForm.has_batch"
                                input-label="Batch Product"
                                class="mt-3"
                                title="Providing Batch number and expiry date is compulsory for the products that have batch numbers in the inventory related modules like GRN, transfers, adjustments, etc."
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <JSwitch
                                v-model:is-checked="
                                    masterProductForm.is_non_selling_item
                                "
                                input-label="Non-Selling"
                                class="mt-3"
                            />
                        </div>
                    </div>
                </div>

                <div class="intro-y box p-5 mb-5">
                    <div class="flex justify-end">
                        <SecondaryButton
                            type="button"
                            text="Clear"
                            class="w-24 mr-1"
                            @click="clearFormData"
                        />

                        <Link
                            :href="isDraftProduct === true
                                ? route('admin.draft_products.index')
                                : route('admin.master_products.index')
                            "
                        >
                            <SecondaryButton
                                type="button"
                                text="Cancel"
                                class="w-24 mr-1"
                            />
                        </Link>

                        <PrimaryButton
                            type="submit"
                            :text="masterProduct ? 'Update' : 'Submit'"
                            class="w-24"
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
            </form>
        </div>
    </div>

    <VideoPlay
        v-if="state.displayVideoPlayModal"
        :modal-show="state.displayVideoPlayModal"
        :video-url="state.videoUrl"
        @close-modal="closeModal"
    />

    <ProductVariantModal
        v-if="state.productVariantModalShow"
        :product-variant-modal-show="state.productVariantModalShow"
        :variant-attributes="state.variantAttributes"
        :variant-data-index="state.variantDataIndex"
        :templates="templates"
        :field-types="fieldTypes"
        :package-types="packageTypes"
        :memberships="memberships"
        :variant-data="state.variantData"
        :show-approve-button="showApproveButton()"
        :product-id="productId"
        :sale-channels="saleChannels"
        @update:hide-product-variant-modal="hideProductVariantModal"
        @new:record="newItemVariant"
        @approve-product="approveProduct()"
    />
</template>

<script setup>
import CategoryModal from "@adminComponents/CategoryModal.vue";
import VideoPlay from "@adminPages/pos_advertisement/partials/VideoPlay.vue";
import { component as ckeditor } from "@ckeditor/ckeditor5-vue";
import FormInput from "@commonComponents/FormInput.vue";
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import JFileCropUpload from "@commonComponents/JFileCropUpload.vue";
import JFileUpload from "@commonComponents/JFileUpload.vue";
import JSwitch from "@commonComponents/JSwitch.vue";
import OutlinePrimaryButton from "@commonComponents/OutlinePrimaryButton.vue";
import PrimaryButton from "@commonComponents/PrimaryButton.vue";
import SecondaryButton from "@commonComponents/SecondaryButton.vue";
import ValidationError from "@commonComponents/ValidationError.vue";
import {
    removeLocalStorage,
    saveLocalStorage,
    setLocalStorage,
} from "@commonServices/helper";
import { confirmDialogBox, showSuccessNotification, showErrorNotification } from "@commonServices/notifier";
import { useForm,router } from "@inertiajs/vue3";
import axios from "axios";
import { ChevronRight, Edit2, PlayCircle, X } from "lucide-vue-next";
import { onMounted, reactive, watch } from "vue";
import { route } from "ziggy";
import CustomFieldValues from "./CustomFieldValues.vue";
import JDateTimePicker from "@commonComponents/JDateTimePicker.vue";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import ProductVariantModal from "@adminPages/master_products/partials/ProductVariantModal.vue";
import ProductVariantDetails from "@adminPages/master_products/ProductVariantDetails.vue";
import AssemblyChildMasterProductTiers from "@adminPages/master_products/AssemblyChildMasterProductTiers.vue";
import JMultiSelect from "@commonComponents/JMultiSelect.vue";

const props = defineProps({
    masterProduct: {
        type: Object,
        default: null,
    },
    unitOfMeasures: {
        type: Object,
        default: () => {},
    },
    brands: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        required: true,
    },
    vendors: {
        type: Object,
        required: true,
    },
    departments: {
        type: Object,
        required: true,
    },
    types: {
        type: Object,
        required: true,
    },
    memberships: {
        type: Object,
        required: true,
    },
    discountTypes: {
        type: Object,
        default: null,
    },
    assemblyProductTypeStatic: {
        type: Object,
        required: true,
    },
    packageTypes: {
        type: Object,
        default: () => {},
    },
    defaultTypeStatic: {
        type: Object,
        required: true,
    },
    isDraftProduct: {
        type: Boolean,
        default: false,
    },
    productId: {
        type: Number,
        default: 0
    },
    templates: {
        type: Array,
        required: true,
    },
    variantTemplates: {
        type: Array,
        required: true,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        default: () => {},
    },
    creatorCanApproveDraftProduct: {
        type: Boolean,
        default: false,
    },
    updateUnitOfMeasure: {
        type: Boolean,
        default: false,
    },
    tags: {
        type: Object,
        default: () => {},
    },
    saleChannels: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    variantAttributes: [],
    categoryModalShow: false,
    productVariantModalShow: false,
    images: [],
    uploadImageUrls: [],
    videos: [],
    uploadVideoUrls: [],
    displayVideoPlayModal: false,
    videoUrl: null,
    editorConfig: {
        toolbar: {
            items: [
                "heading",
                "|",
                "bold",
                "italic",
                "link",
                "|",
                "bulletedList",
                "numberedList",
                "|",
                "outdent",
                "indent",
                "|",
                "blockQuote",
                "undo",
                "redo",
            ],
        },
    },
    unitOfMeasureName: null,
    item_variants: [],
    variantData: [],
    variantDataIndex: null,
});

const masterProductForm = useForm({
    _method: props.masterProduct ? "put" : "post",
    name: null,
    description: "",
    code: null,
    vendor_id: null,
    department_id: null,
    unit_of_measure_id: null,
    brand_id: null,
    category_ids: [],
    categories: [],
    article_number: null,
    type_id: props.defaultTypeStatic.regularProduct,
    has_batch: false,
    images: [],
    uploaded_images: [],
    videos: [],
    thumbnail: null,
    thumbnail_url: null,
    uploaded_videos: [],
    is_non_inventory: false,
    is_non_selling_item: false,
    watchEnabled: true,
    custom_field_values: [],
    attached_templates: null,
    original_created_at: null,
    variant_template_id: null,
    variants: [],
    assembly_child_master_products: [
        {
            child_master_product_id: null,
            units: null,
        },
    ],
    tag_ids: [],
    tags: [],
});

const saveMasterProduct = () => {
    masterProductForm.watchEnabled = false;

    prepareMasterProductFormDetails();
    removeLocalStorage("master_product");
    if (props.masterProduct) {
        if (props.isDraftProduct === true) {
            masterProductForm.post(route('admin.draft_products.update_master_product', props.masterProduct.id));
            return;
        }

        masterProductForm.post(route('admin.master_products.update', props.masterProduct.id), {
            preserveScroll: true,
            onError: () => showErrorNotification('There are input errors. Please fill out the required form fields on all tabs and try again.'),
        });
        return;
    }

    masterProductForm.post(route('admin.master_products.store'), {
        preserveScroll: true,
        onError: () => showErrorNotification('There are input errors. Please fill out the required form fields on all tabs and try again.'),
    });
};

const uploadImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.images.push(selectedImage.target.files[index]);
        masterProductForm.images = state.images;
        state.uploadImageUrls.push(
            URL.createObjectURL(selectedImage.target.files[index])
        );
    }
};

const uploadVideo = (selectedVideo) => {
    for (let index = 0; index < selectedVideo.target.files.length; index++) {
        state.videos.push(selectedVideo.target.files[index]);
        masterProductForm.videos = state.videos;
        state.uploadVideoUrls.push(
            URL.createObjectURL(selectedVideo.target.files[index])
        );
    }
};

const prepareMasterProductFormDetails = () => {
    masterProductForm.category_ids = masterProductForm.categories.map((category) => {
        return category.id;
    });

    masterProductForm.attached_templates = masterProductForm.custom_field_values.map(
        (template) => {
            return { template_id: template.id };
        }
    );

    masterProductForm.tag_ids = masterProductForm.tags.map((tag) => {
        return tag.id;
    });
};

const hideCategoryModal = () => {
    state.categoryModalShow = false;
};

const updateCategories = (categories) => {
    masterProductForm.categories = categories;
};

onMounted(() => {
    if (props.masterProduct) {
        removeLocalStorage("master_product");
        Object.assign(masterProductForm, JSON.parse(JSON.stringify(props.masterProduct)));

        masterProductForm.description = masterProductForm.description ?? "";
        masterProductForm.image_urls = props.masterProduct.image_urls
            ? props.masterProduct.image_urls
            : null;
        const unitOfMeasure = props.unitOfMeasures.find(
            (unitOfMeasure) =>
                unitOfMeasure.id === props.masterProduct.unit_of_measure_id
        );
        state.unitOfMeasureName = unitOfMeasure ? unitOfMeasure.name : null;
    } else {
        setLocalStorage("master_product", masterProductForm);
    }

    if (masterProductForm.variant_template_id) {
        getVariantAttribute(masterProductForm.variant_template_id);
    }

});

const checkSaveLocalStorage = () => {
    if (!props.masterProduct) {
        saveLocalStorage("master_product", masterProductForm);
    }
};

const clearFormData = () => {
    const unitOfMeasureId = masterProductForm.unit_of_measure_id;
    masterProductForm.reset();

    if (props.masterProduct) {
        masterProductForm.unit_of_measure_id = unitOfMeasureId;
    }
};

watch(
    masterProductForm,
    () => {
        if (masterProductForm.watchEnabled) {
            checkSaveLocalStorage();
        }
    },
    { deep: true }
);

const removeMasterProductImage = (index, mediaId, masterProductId) => {
    masterProductForm.uploaded_images.splice(index, 1);

    if (masterProductId) {
        axios.get(route("admin.master_products.remove_master_product_image", [masterProductId, mediaId]));
    }
};

const removeMasterProductVideo = (index, mediaId, masterProductId) => {
    masterProductForm.uploaded_videos.splice(index, 1);

    if (masterProductId) {
        axios.get(route("admin.master_products.remove_master_product_video", [masterProductId, mediaId]));
    }
};

const removeUploadMasterProductImage = (index) => {
    state.uploadImageUrls.splice(index, 1);
    state.images.splice(index, 1);
    masterProductForm.images = state.images;
};

const removeUploadMasterProductVideo = (index) => {
    state.uploadVideoUrls.splice(index, 1);
    state.videos.splice(index, 1);
    masterProductForm.videos = state.videos;
};

const openVideoPlayModal = (data) => {
    state.displayVideoPlayModal = true;
    state.videoUrl = data;
};

const closeModal = () => {
    state.displayVideoPlayModal = false;
};

const uploadThumbnailImage = (selectedImage) => {
    masterProductForm.thumbnail_url = URL.createObjectURL(selectedImage);
};

const removeMasterProductThumbnail = (masterProductId) => {
    masterProductForm.thumbnail = null;
    masterProductForm.thumbnail_url = null;

    if (masterProductId) {
        axios.get(route("admin.master_products.remove_master_product_thumbnail", masterProductId));
    }
};

const updateCustomFieldValues = (customFieldValues) => {
    masterProductForm.custom_field_values = customFieldValues;
};


const addVariant = () => {
    state.productVariantModalShow = true;
};

const hideProductVariantModal = () => {
    state.variantData = [];
    state.variantDataIndex = null;
    state.productVariantModalShow = false;
};

const newItemVariant = (itemVariant) => {
    if (state.variantDataIndex !== null && state.variantDataIndex >= 0) {
        masterProductForm.variants.splice(state.variantDataIndex, 1);
        masterProductForm.variants.push(itemVariant);
        state.variantData = null;
        state.variantDataIndex = null;
        return;
    }

    masterProductForm.variants.push(itemVariant);
};

const getVariantDetails = (variant) => {
    state.variantData = variant.data;
    state.variantDataIndex = variant.index;
    state.productVariantModalShow = true;
};

const removeVariant = (key) => {
    masterProductForm.variants.splice(key, 1);
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    masterProductForm[columnName] = details.value;
};

const addNewTierAssemblyItemDetails = () => {
    masterProductForm.assembly_child_master_products.push({
        child_master_product_id: null,
        units: null,
    });
};

const updateTierAssemblyItemValueDetails = (details) => {
    masterProductForm.assembly_child_master_products[details.key][details.column_name] =
            details.value;
};

const removeTierAssemblyItemDetailsOf = (key) => {
    masterProductForm.assembly_child_master_products.splice(key, 1);
};

const updateProductType = (itemType) => {
    masterProductForm.type_id = itemType;
    if (itemType === props.assemblyProductTypeStatic.assemblyProduct) {
        masterProductForm.variants = [];
    }
    masterProductForm.assembly_child_master_products = [];
};

const saveNewTag = (tagName) => {
    axios
        .post(route("admin.tags.store"), {
            name: tagName,
        })
        .then((response) => {
            updateTagList(response.data);
        });
};

const updateTagList = (newTag) => {
    masterProductForm.tags.push(newTag);
};

const variantTemplateSelected = async (addedTemplateId) => {
    if (masterProductForm.variant_template_id && masterProductForm.variants.length > 0) {
        const message = 'Do you want to clear the selected variant template?';
        confirmDialogBox(message, () => {
            if (props.masterProduct.id) {
                router.post(route('admin.master_products.remove_master_product_variants', [props.masterProduct.id]), {}, {
                    onSuccess: () => {
                        showSuccessNotification('The variants have been removed successfully.');
                    }
                });
            }
            masterProductForm.variants = [];
        });
    }
    getVariantAttribute(addedTemplateId);
};

const getVariantAttribute = async (addedTemplateId) => {
    if (addedTemplateId) {
        const fetchedTemplate = (await axios.post(route('admin.custom_field_values.fetch'), { templateId: addedTemplateId }));
        masterProductForm.variant_template_id = addedTemplateId;
        state.variantAttributes = fetchedTemplate.data.template.attributes;
    }
};

const draftProductForm = useForm({
    selectedRecords: [],
});

const showApproveButton = () => {
    if (props.isDraftProduct && props.masterProduct) {
        if (props.creatorCanApproveDraftProduct) {
            return props.creatorCanApproveDraftProduct;
        }

        return props.user.id !== props.masterProduct.created_by_id && props.user.type === props.masterProduct.created_by_type;
    }
    return false;
};

const approveProduct = () => {
    const message = 'Are you sure you want to approved this product variant?';
    draftProductForm.selectedRecords = [props.productId];
    confirmDialogBox(message, () => {
        draftProductForm.post(route('admin.draft_products.approved'), {
            onSuccess: () => draftProductForm.get(route('admin.draft_products.index'))
        });
    });
};
</script>
