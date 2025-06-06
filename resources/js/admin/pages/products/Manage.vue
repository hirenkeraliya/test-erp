<template>
    <PageTitle :title="product ? 'Edit Product' : 'Add Product'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Products
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="product">Edit Product</span>
                        <span v-else>Add Product</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveProduct();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                <label>
                                    Category
                                    <span class="text-danger">*</span>
                                </label>

                                <div
                                    v-if="productForm.categories.length === 0"
                                    class="flex flex-col sm:flex-row mt-2"
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
                                    class="flex flex-col sm:flex-row mt-2"
                                >
                                    <div
                                        v-for="(category, index) in productForm.categories"
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
                                                @click="state.categoryModalShow = true"
                                            />
                                        </label>

                                        <label
                                            class="form-check-label"
                                            for="checkbox-switch-5"
                                        >
                                            <X
                                                class="w-6 h-6 text-slate-400"
                                                @click="productForm.categories = []"
                                            />
                                        </label>
                                    </div>
                                </div>
                                <ValidationError :validation-field-name="'category_ids'" />
                            </div>
                            <div
                                v-if="retailPlanningServiceConfigured"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                            >
                                <label>
                                    Merchandise Hierarchy
                                    <span class="text-danger">*</span>
                                </label>

                                <div
                                    v-if="productForm.selected_retail_planning_hierarchies_path.length === 0"
                                    class="flex flex-col sm:flex-row mt-2"
                                >
                                    <div class="form-check mr-2">
                                        <OutlinePrimaryButton
                                            text="Select Hierarchy"
                                            class="inline-block mr-1 mb-2"
                                            @click="state.showHierarchyModal = true"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-col sm:flex-row mt-2"
                                >
                                    <div
                                        v-for="(hierarchy, index) in productForm.selected_retail_planning_hierarchies_path"
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
                                            {{ hierarchy.name }}
                                        </label>
                                    </div>

                                    <div class="form-check mr-2 mt-2 sm:mt-0">
                                        <label
                                            class="form-check-label"
                                            for="checkbox-switch-5"
                                        >
                                            <Edit2
                                                class="w-4 h-5 text-slate-400"
                                                @click="state.showHierarchyModal = true"
                                            />
                                        </label>

                                        <label
                                            class="form-check-label"
                                            for="checkbox-switch-5"
                                        >
                                            <X
                                                class="w-6 h-6 text-slate-400"
                                                @click="productForm.selected_retail_planning_hierarchies_path = []"
                                            />
                                        </label>
                                    </div>
                                </div>
                                <ValidationError :validation-field-name="'retail_planning_hierarchy_id'" />
                            </div>

                            <CategoryModal
                                v-if="state.categoryModalShow"
                                :records="categories"
                                :category-modal-show="state.categoryModalShow"
                                @update:selected-record="updateCategories"
                                @update:hide-category-modal="hideCategoryModal"
                            />

                            <StyleModal
                                v-if="state.styleModalShow"
                                :style-modal-show="state.styleModalShow"
                                @update:hide-style-modal="hideStyleModal"
                                @new:record="newStyle"
                            />

                            <ColorModal
                                v-if="state.colorModalShow"
                                :color-modal-show="state.colorModalShow"
                                @update:hide-color-modal="hideColorModal"
                                @new:record="newColor"
                            />

                            <SizeModal
                                v-if="state.sizeModalShow"
                                :records="sizes"
                                :size-modal-show="state.sizeModalShow"
                                @update:hide-size-modal="hideSizeModal"
                                @new:record="newSize"
                            />

                            <SeasonModal
                                v-if="state.seasonModalShow"
                                :season-modal-show="state.seasonModalShow"
                                @update:hide-season-modal="hideSeasonModal"
                                @new:record="newSeason"
                            />

                            <DepartmentModal
                                v-if="state.departmentModalShow"
                                :discount-types="discountTypes"
                                :company="company"
                                :commission-types="commissionTypes"
                                :department-modal-show="state.departmentModalShow"
                                @update:hide-department-modal="hideDepartmentModal"
                                @new:record="newDepartment"
                            />

                            <div v-if="retailPlanningServiceConfigured">
                                <HierarchyModal
                                    v-if="state.showHierarchyModal"
                                    :records="retailPlanningHierarchies"
                                    :show-hierarchy-modal="state.showHierarchyModal"
                                    @update:selected-record="updateHierarchies"
                                    @update:hide-hierarchy-modal="hideHierarchyModal"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <label class="form-label mt-3">
                                    Description
                                </label>

                                <ckeditor
                                    v-model="productForm.description"
                                    :editor="ClassicEditor"
                                    :config="state.editorConfig"
                                    tag-name="textarea"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div
                                    v-if="state.unitOfMeasureName"
                                    class="block sm:flex items-center"
                                >
                                    <div v-if="updateUnitOfMeasure">
                                        <FormSelectBox
                                            v-model:selected-record="productForm.unit_of_measure_id"
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
                                    v-model:selected-record="productForm.unit_of_measure_id"
                                    :records="unitOfMeasures"
                                    input-label="Unit Of Measure"
                                    validation-field-name="unit_of_measure_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="block sm:flex items-center">
                                    <FormSelectBox
                                        v-model:selected-record="productForm.season_id"
                                        :records="seasons"
                                        input-label="Season"
                                        validation-field-name="season_id"
                                        class="w-full"
                                    />
                                    <PrimaryButton
                                        text="+"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        @click="addNewSeason"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="block sm:flex items-center">
                                    <FormSelectBox
                                        v-model:selected-record="productForm.department_id"
                                        :records="departments"
                                        input-label="Department"
                                        validation-field-name="department_id"
                                        class="w-full"
                                    />
                                    <PrimaryButton
                                        text="+"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        @click="addNewDepartment"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="productForm.sub_department_id"
                                    :records="subDepartments"
                                    input-label="Sub Department"
                                    validation-field-name="sub_department_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="block sm:flex items-center">
                                    <FormSelectBox
                                        v-model:selected-record="productForm.color_id"
                                        :records="colors"
                                        input-label="Color"
                                        validation-field-name="color_id"
                                        class="w-full"
                                    />
                                    <PrimaryButton
                                        text="+"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        @click="addNewColor"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="block sm:flex items-center">
                                    <FormSelectBox
                                        v-model:selected-record="productForm.size_id"
                                        :records="sizes"
                                        input-label="Size"
                                        validation-field-name="size_id"
                                        class="w-full"
                                    />
                                    <PrimaryButton
                                        text="+"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        @click="addNewSize"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="productForm.brand_id"
                                    :records="brands"
                                    input-label="Brand"
                                    validation-field-name="brand_id"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="block sm:flex items-center">
                                    <FormSelectBox
                                        v-model:selected-record="productForm.style_id"
                                        :records="state.styles"
                                        input-label="Style"
                                        validation-field-name="style_id"
                                        class="w-full"
                                    />
                                    <PrimaryButton
                                        text="+"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        @click="addNewStyle"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="productForm.tags"
                                    :records="tags"
                                    input-label="Tags (Type & Enter to create new tag)"
                                    :option-create="saveNewTag"
                                    :taggable="true"
                                    validation-field-name="tag_ids"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div
                                    v-if="product && productForm.upc"
                                    class="block sm:flex items-center"
                                >
                                    <div class="mt-3 w-full">
                                        <label
                                            for="upc"
                                            class="form-label"
                                        >UPC</label>
                                        <p class="text-lg mt-1">
                                            {{ productForm.upc }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="block sm:flex items-center"
                                >
                                    <FormInput
                                        v-model:input-value="productForm.upc"
                                        input-name="upc"
                                        input-label="UPC"
                                        :required="true"
                                        class="w-full"
                                    />

                                    <PrimaryButton
                                        text="Generate"
                                        class="shadow-md ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                                        type="button"
                                        @click="autoGenerateUpc()"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div
                                    v-if="product && product.verification_qr_code"
                                    class="block sm:flex items-center"
                                >
                                    <div class="mt-3 w-full">
                                        <label
                                            for="verification_qr_code"
                                            class="form-label"
                                        >Verification Qr Code</label>
                                        <p class="text-lg mt-1">
                                            {{ product.verification_qr_code }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="block sm:flex items-center"
                                >
                                    <FormInput
                                        v-model:input-value="productForm.verification_qr_code"
                                        input-name="verification_qr_code"
                                        input-label="Verification Qr Code"
                                        :required="false"
                                        class="w-full"
                                    />
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.ean"
                                    input-name="ean"
                                    input-label="Ean"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.custom_sku"
                                    input-name="custom_sku"
                                    input-label="Custom Sku"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.manufacturer_sku"
                                    input-name="manufacturer_sku"
                                    input-label="Manufacturer Sku"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.article_number"
                                    input-name="article_number"
                                    input-label="Article Number"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="productForm.vendor_id"
                                    :records="vendors"
                                    input-label="Vendor"
                                    validation-field-name="vendor_id"
                                    class="w-full"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="intro-y box p-5 mb-5">
                        <div
                            class="flex items-center pb-5 text-base font-medium border-b border-slate-200/60 dark:border-darkmode-400 mb-5"
                        >
                            Details
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.width"
                                    input-name="product_variant_width"
                                    input-label="Width (In CM)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.height"
                                    input-name="product_variant_height"
                                    input-label="Height (In CM)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productForm.weight"
                                    input-name="product_variant_weight"
                                    input-label="Weight (In KG)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="grid grid-cols-12 gap-0 sm:gap-6 p-5">
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormSelectBox
                                v-if="!product"
                                v-model:selected-record="productForm.type_id"
                                :records="types"
                                input-label="Product Type"
                                validation-field-name="type_id"
                                :title="productForm.type_id !== defaultTypeStatic.regularProduct ?
                                    'Dream Price, Price Override, and complimentary are eligible for regular, bundle and assembly products only.' : null"
                            />

                            <div
                                v-else
                                class="mt-3"
                            >
                                <div class="input-group">
                                    <label>
                                        Product Type:
                                    </label>
                                </div>
                                <div class="font-medium">
                                    {{ product.type_name }}
                                </div>
                            </div>
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.retail_price"
                                input-name="retail_price"
                                input-label="Retail Price"
                                :input-group-prefix="currencySymbol"
                                :required="props.defaultTypeStatic.assemblyProduct === productForm.type_id"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormInput
                                v-model:input-value="productForm.franchise_price_1"
                                input-name="franchise_price_1"
                                input-label="Franchise Price 1"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.franchise_price_2"
                                input-name="franchise_price_2"
                                input-label="Franchise Price 2"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.franchise_price_3"
                                input-name="franchise_price_3"
                                input-label="Franchise Price 3"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.wholesale_price"
                                input-name="wholesale_price"
                                input-label="Wholesale Price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.company_or_tender_price"
                                input-name="company_or_tender_price"
                                input-label="Company Or Tender price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.branch_price"
                                input-name="branch_price"
                                input-label="Branch price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.staff_price"
                                input-name="staff_price"
                                input-label="Staff Price (Minimum selling price for employee)"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.original_capital_price"
                                input-name="original_capital_price"
                                input-label="Original Capital Price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.capital_price"
                                input-name="capital_price"
                                input-label="Capital Price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.minimum_price"
                                input-name="minimum_price"
                                input-label="Minimum Price"
                                :input-group-prefix="currencySymbol"
                                :required="canAddPrice()"
                                :title="canAddPrice() ? 'Minimum price is required when the selected product type is non-regular product.': ''"
                            />
                        </div>

                        <div
                            v-if="checkPurchaseCostPermission()"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <FormInput
                                v-model:input-value="productForm.purchase_cost"
                                input-name="purchase_cost"
                                input-label="Cost Price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <FormInput
                                v-model:input-value="productForm.online_price"
                                input-name="online_price"
                                input-label="Online Price"
                                :input-group-prefix="currencySymbol"
                                :readonly="canAddPrice()"
                            />
                        </div>

                        <div
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                        >
                            <JDateTimePicker
                                v-model:input-value="productForm.original_created_at"
                                input-label="Original Created At"
                                validation-field-name="original_created_at"
                            />
                        </div>
                        <div
                            v-if="productForm.type_id === props.defaultTypeStatic.serialProduct"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6"
                        >
                            <JSwitch
                                v-model:is-checked="productForm.is_warranty"
                                input-label="Warranty"
                                class="mt-3"
                                @update:is-checked="isWarranty($event)"
                            />
                        </div>
                        <div
                            v-if="productForm.is_warranty"
                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-6"
                        >
                            <FormInput
                                v-model:input-value="productForm.warranty_month"
                                input-name="warranty_month"
                                input-label="Default Warranty (Months)"
                                :required="true"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div>
                        <MembershipLoyaltyPointTiers
                            :tiers="productForm.tiers"
                            :memberships="memberships"
                            get-value-input-label="Loyalty Point"
                            @update:column-details="updateColumnDetails"
                            @update:tier-value-details="updateTierValueDetails"
                            @add:new-tier-details="addNewTierDetails"
                            @remove:tier-details-of="removeTierDetailsOf"
                        />
                    </div>
                    <span v-if="assemblyProductTypeStatic.assemblyProduct === productForm.type_id">
                        <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />
                        <div>
                            <AssemblyProductTiers
                                :assembly-child-products="productForm.assembly_child_products"
                                @update:column-details="updateColumnDetails"
                                @update:tier-assembly-product-value-details="updateTierAssemblyProductValueDetails"
                                @add:new-tier-assembly-product-details="addNewTierAssemblyProductDetails"
                                @remove:tier-assembly-product-details-of="removeTierAssemblyProductDetailsOf"
                            />
                        </div>
                    </span>

                    <span v-if="defaultTypeStatic.regularProduct === productForm.type_id || defaultTypeStatic.serialProduct === productForm.type_id">
                        <div
                            class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                        />

                        <h2 class="font-medium text-base ml-5 mt-2">
                            Box Product Details
                        </h2>

                        <div>
                            <BoxProductTiers
                                :boxes="productForm.boxes"
                                :package-types="packageTypes"
                                :memberships="memberships"
                                @update:column-details="updateColumnDetails"
                                @update:tier-box-value-details="updateTierBoxValueDetails"
                                @add:new-tier-box-details="addNewTierBoxDetails"
                                @add:new-nested-tier-box-details="addNewNestedTierBoxDetails($event)"
                                @remove:tier-box-details-of="removeTierBoxDetailsOf"
                                @remove:nested-tier-box-details-of="removeNestedTierBoxDetailsOf($event, $event)"
                                @update:nested-tier-box-value-details="nestedTierBoxValueDetails($event)"
                            />
                        </div>
                    </span>

                    <span v-if="defaultTypeStatic.regularProduct === productForm.type_id && ! product">
                        <CustomFieldValues
                            :custom-field-values="product?.custom_field_values"
                            :templates="props.templates"
                            :field-types="props.fieldTypes"
                            @update:custom-field-values="updateCustomFieldValues($event)"
                        />
                    </span>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="productForm.is_temporarily_unavailable"
                                    input-label="Temporarily Unavailable"
                                    class="mt-3"
                                />
                            </div>

                            <div
                                v-if="assemblyProductTypeStatic.assemblyProduct !== productForm.type_id && defaultTypeStatic.serialProduct !== productForm.type_id"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-model:is-checked="productForm.is_non_inventory"
                                    input-label="Non-Inventory"
                                    class="mt-3"
                                />
                            </div>

                            <div
                                v-if="defaultTypeStatic.serialProduct !== productForm.type_id"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-model:is-checked="productForm.has_batch"
                                    input-label="Batch Product"
                                    class="mt-3"
                                    title="Providing Batch number and expiry date is compulsory for the products that have batch numbers in the inventory related modules like GRN, transfers, adjustments, etc."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="productForm.is_non_selling_item"
                                    input-label="Non-Selling"
                                    class="mt-3"
                                />
                            </div>

                            <div
                                v-if="productForm.is_non_selling_item === false"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-model:is-checked="productForm.is_available_in_pos"
                                    input-label="Available In-Location"
                                    class="mt-3"
                                />
                            </div>

                            <div
                                v-if="productForm.is_non_selling_item === false && defaultTypeStatic.serialProduct !== productForm.type_id"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    input-label="Available Online"
                                    :is-checked="productForm.is_available_in_ecommerce"
                                    class="mt-3"
                                    @update:is-checked="updateIsAvailableInEcommerce('is_available_in_ecommerce', $event)"
                                />

                                <div
                                    v-if="productForm.is_available_in_ecommerce"
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                >
                                    <JMultiSelect
                                        v-model:selected-records="productForm.sale_channels"
                                        :records="saleChannels"
                                        input-label="Sale Channels"
                                        :required="true"
                                        validation-field-name="sale_channel_ids"
                                        class="w-full"
                                    />
                                </div>
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-model:is-checked="productForm.is_sold_as_single_item"
                                    input-label="Is Sold As Single Item"
                                    class="mt-3"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JSwitch
                                    v-model:is-checked="productForm.sell_item_via_derivative"
                                    input-label="Sell Item Via Derivative"
                                    class="mt-3"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-3">
                                <JFileCropUpload
                                    v-model:input-file="productForm.thumbnail"
                                    input-label="Thumbnail (343px X 260px)"
                                    validation-field-name="thumbnail"
                                    :max-width="343"
                                    :max-height="260"
                                    @update:input-file="uploadThumbnailImage"
                                />

                                <div
                                    v-if="productForm.thumbnail_url"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <Tippy
                                        tag="div"
                                        content="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeProductThumbnail(product?.id)"
                                    >
                                        <X class="w-4 h-4" />
                                    </Tippy>
                                    <img
                                        :src="productForm.thumbnail_url"
                                        :alt="productForm.thumbnail_url"
                                        width="100"
                                        class="mt-2"
                                    >
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileUpload
                                    input-label="Images (500px X 500px)"
                                    validation-field-name="images"
                                    :is-multiple="true"
                                    class="mt-3"
                                    @change="uploadImage"
                                />

                                <div
                                    v-for="(uploadedImage, index) in productForm.uploaded_images"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <Tippy
                                        tag="div"
                                        content="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeProductImage(index, uploadedImage.id, product?.id)"
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
                                    v-for="(imageUrl, index) in state.uploadImageUrls"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <div
                                        title="Remove this image?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeUploadProductImage(index)"
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

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileUpload
                                    input-label="Videos"
                                    validation-field-name="videos"
                                    :is-multiple="true"
                                    class="mt-3"
                                    @change="uploadVideo"
                                />

                                <div
                                    v-for="(uploadedVideo, index) in productForm.uploaded_videos"
                                    :key="index"
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <div
                                        title="Remove this video?"
                                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger ml-20"
                                        @click="removeProductVideo(index, uploadedVideo.id, product?.id)"
                                    >
                                        <X class="w-4 h-4" />
                                    </div>

                                    <span
                                        title="Video Play"
                                        class="cursor-pointer flex justify-center w-12 h-12"
                                        @click="openVideoPlayModal(uploadedVideo.url)"
                                    >
                                        <PlayCircle class="text-indigo-900 w-14 h-14" />
                                    </span>
                                </div>

                                <div
                                    v-for="(videoUrl, index) in state.uploadVideoUrls"
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
                                        @click="openVideoPlayModal(videoUrl)"
                                    >
                                        <PlayCircle class="text-indigo-900 w-14 h-14" />
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <SecondaryButton
                                type="button"
                                text="Clear"
                                class="w-24 mr-1"
                                @click="clearFormData"
                            />
                            <Link :href="(props.isDraftProduct === true) ? route('admin.draft_products.index') : route('admin.products.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="product ? 'Update' : 'Submit'"
                                class="w-24"
                            />

                            <PrimaryButton
                                v-if="showApproveButton()"
                                type="button"
                                text="Approve"
                                class="w-24 ms-1"
                                @click="approveProduct()"
                            />

                            <PrimaryButton
                                v-if="showDeleteDraftButton()"
                                type="button"
                                text="Delete"
                                class="w-24 ms-1"
                                @click="deleteDraftProduct()"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <VideoPlay
        v-if="state.displayVideoPlayModal"
        :modal-show="state.displayVideoPlayModal"
        :video-url="state.videoUrl"
        @close-modal="closeModal"
    />
</template>

<script setup>
import CategoryModal from '@adminComponents/CategoryModal.vue';
import VideoPlay from '@adminPages/pos_advertisement/partials/VideoPlay.vue';
import AssemblyProductTiers from '@adminPages/products/AssemblyProductTiers.vue';
import MembershipLoyaltyPointTiers from '@adminPages/products/MembershipLoyaltyPointTiers.vue';
import BoxProductTiers from '@adminPages/products/BoxProductTiers.vue';
import ColorModal from '@adminPages/products/partials/ColorModal.vue';
import DepartmentModal from '@adminPages/products/partials/DepartmentModal.vue';
import SeasonModal from '@adminPages/products/partials/SeasonModal.vue';
import SizeModal from '@adminPages/products/partials/SizeModal.vue';
import StyleModal from '@adminPages/products/partials/StyleModal.vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { component as ckeditor } from '@ckeditor/ckeditor5-vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import { removeLocalStorage, saveLocalStorage, setLocalStorage } from '@commonServices/helper';
import { confirmDialogBox } from '@commonServices/notifier';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ChevronRight, Edit2, PlayCircle, X } from 'lucide-vue-next';
import { computed, onMounted, reactive, watch } from 'vue';
import { route } from 'ziggy';
import CustomFieldValues from './CustomFieldValues.vue';
import HierarchyModal from '@adminPages/products/partials/HierarchyModal.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';

const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
    purchaseCost: {
        type: String,
        required: true,
    },
    unitOfMeasures: {
        type: Object,
        default: () => {},
    },
    seasons: {
        type: Object,
        default: () => {},
    },
    departments: {
        type: Object,
        default: () => {},
    },
    subDepartments: {
        type: Object,
        required: true,
    },
    colors: {
        type: Object,
        default: () => {},
    },
    sizes: {
        type: Object,
        default: () => {},
    },
    brands: {
        type: Object,
        required: true,
    },
    styles: {
        type: Object,
        default: () => {},
    },
    categories: {
        type: Object,
        required: true,
    },
    vendors: {
        type: Object,
        required: true,
    },
    tags: {
        type: Object,
        default: () => {},
    },
    types: {
        type: Object,
        required: true,
    },
    memberships: {
        type: Object,
        default: () => {},
    },
    discountTypes: {
        type: Object,
        default: null
    },
    company: {
        type: Object,
        default: null,
    },
    commissionTypes: {
        type: Object,
        default: null
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
    templates: {
        type: Array,
        required: true
    },
    fieldTypes: {
        type: Object,
        required: true
    },
    user: {
        type: Object,
        default: () => {}
    },
    creatorCanApproveDraftProduct: {
        type: Boolean,
        default: false,
    },
    retailPlanningServiceConfigured: {
        type: Boolean,
        default: false,
    },
    updateUnitOfMeasure: {
        type: Boolean,
        default: false,
    },
    retailPlanningHierarchies: {
        type: Array,
        required: false,
        default: () => [],
    },
    saleChannels: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    categoryModalShow: false,
    styleModalShow: false,
    colorModalShow: false,
    sizeModalShow: false,
    seasonModalShow: false,
    departmentModalShow: false,
    styles: props.styles,
    colors: props.colors,
    sizes: props.sizes,
    seasons: props.seasons,
    departments: props.departments,
    images: [],
    uploadImageUrls: [],
    videos: [],
    uploadVideoUrls: [],
    displayVideoPlayModal: false,
    videoUrl: null,
    editorConfig: {
        toolbar: {
            items: [
                'heading',
                '|', 'bold', 'italic', 'link',
                '|', 'bulletedList', 'numberedList',
                '|', 'outdent', 'indent',
                '|', 'blockQuote', 'undo', 'redo'
            ],
        },
    },
    unitOfMeasureName: null,
    showHierarchyModal: false,
});

const productForm = useForm({
    _method: props.product ? 'put' : 'post',
    name: null,
    description: '',
    code: null,
    vendor_id: null,
    unit_of_measure_id: null,
    season_id: null,
    department_id: null,
    sub_department_id: null,
    color_id: null,
    size_id: null,
    brand_id: null,
    style_id: null,
    retail_planning_hierarchy_id: null,
    tag_ids: [],
    tags: [],
    category_ids: [],
    categories: [],
    upc: null,
    verification_qr_code: null,
    ean: null,
    custom_sku: null,
    manufacturer_sku: null,
    article_number: null,
    type_id: props.defaultTypeStatic.regularProduct,
    retail_price: null,
    franchise_price_1: null,
    franchise_price_2: null,
    franchise_price_3: null,
    wholesale_price: null,
    company_or_tender_price: null,
    branch_price: null,
    minimum_price: null,
    original_capital_price: null,
    capital_price: null,
    purchase_cost: null,
    staff_price: 0,
    online_price: null,
    is_temporarily_unavailable: false,
    has_batch: false,
    images: [],
    uploaded_images: [],
    videos: [],
    thumbnail: null,
    thumbnail_url: null,
    uploaded_videos: [],
    is_non_inventory: false,
    is_non_selling_item: false,
    is_available_in_pos: true,
    is_available_in_ecommerce: false,
    is_sold_as_single_item: true,
    sell_item_via_derivative: true,
    tiers: [
        {
            membership_id: null,
            points: null,
        }
    ],
    assembly_child_products: [
        {
            child_product_id: null,
            units: null,
        }
    ],
    boxes: [],
    watchEnabled: true,
    custom_field_values: [],
    attached_templates: null,
    selected_retail_planning_hierarchies_path: [],
    original_created_at: null,
    warranty_month: null,
    is_warranty: false,
    sale_channels: [],
    sale_channel_ids: [],
    width: 0,
    height: 0,
    weight: 0,
});

const isWarranty = () => {
    productForm.warranty_month = null;
};

const saveProduct = () => {
    productForm.watchEnabled = false;

    prepareProductFormDetails();
    removeLocalStorage('product');

    if (productForm.staff_price === null || productForm.staff_price === '') {
        productForm.staff_price = 0;
    }

    if (props.product && props.isDraftProduct === true) {
        productForm.post(route('admin.draft_products.update', props.product.id));
        return;
    }

    if (props.product) {
        productForm.post(route('admin.products.update', props.product.id));
        return;
    }

    productForm.post(route('admin.products.store'));
};

const uploadImage = (selectedImage) => {
    for (let index = 0; index < selectedImage.target.files.length; index++) {
        state.images.push(selectedImage.target.files[index]);
        productForm.images = state.images;
        state.uploadImageUrls.push(URL.createObjectURL(selectedImage.target.files[index]));
    }
};

const uploadVideo = (selectedVideo) => {
    for (let index = 0; index < selectedVideo.target.files.length; index++) {
        state.videos.push(selectedVideo.target.files[index]);
        productForm.videos = state.videos;
        state.uploadVideoUrls.push(URL.createObjectURL(selectedVideo.target.files[index]));
    }
};

const prepareProductFormDetails = () => {
    productForm.tag_ids = productForm.tags.map((tag) => {
        return tag.id;
    });
    productForm.category_ids = productForm.categories.map((category) => {
        return category.id;
    });

    productForm.tiers = productForm.tiers.filter((tier) => {
        return tier.membership_id && tier.points;
    });

    productForm.attached_templates = productForm.custom_field_values.map((template) => {
        return { template_id: template.id };
    });

    if (productForm.sale_channels) {
        productForm.sale_channel_ids = productForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }
};

const addNewStyle = () => {
    state.styleModalShow = true;
};

const addNewColor = () => {
    state.colorModalShow = true;
};

const addNewSize = () => {
    state.sizeModalShow = true;
};

const addNewSeason = () => {
    state.seasonModalShow = true;
};

const addNewDepartment = () => {
    state.departmentModalShow = true;
};

const hideStyleModal = () => {
    state.styleModalShow = false;
};

const hideColorModal = () => {
    state.colorModalShow = false;
};

const hideSizeModal = () => {
    state.sizeModalShow = false;
};

const hideSeasonModal = () => {
    state.seasonModalShow = false;
};

const hideDepartmentModal = () => {
    state.departmentModalShow = false;
};

const hideCategoryModal = () => {
    state.categoryModalShow = false;
};

const hideHierarchyModal = () => {
    state.showHierarchyModal = false;
};

const updateCategories = (categories) => {
    productForm.categories = categories;
};

const updateHierarchies = (retailPlanningHierarchies) => {
    productForm.selected_retail_planning_hierarchies_path = retailPlanningHierarchies.hierarchyPath;
    productForm.retail_planning_hierarchy_id = retailPlanningHierarchies.selectedHierarchyId;
};

const newStyle = (style) => {
    state.styles.push(style);
    productForm.style_id = style.id;
};

const newColor = (color) => {
    state.colors.push(color);
    productForm.color_id = color.id;
};

const newSize = (size) => {
    state.sizes.push(size);
    productForm.size_id = size.id;
};

const newSeason = (season) => {
    state.seasons.push(season);
    productForm.season_id = season.id;
};

const newDepartment = (department) => {
    state.departments.push(department);
    productForm.department_id = department.id;
};

const updateTagList = (newTag) => {
    productForm.tags.push(newTag);
};

const saveNewTag = (tagName) => {
    axios.post(route('admin.tags.store'), {
        name: tagName
    }).then((response) => {
        updateTagList(response.data);
    });
};

const addNewTierDetails = () => {
    productForm.tiers.push({ membership_id: null, points: null });
};

const updateTierValueDetails = (details) => {
    productForm.tiers[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    productForm.tiers.splice(key, 1);
};

const addNewTierAssemblyProductDetails = () => {
    productForm.assembly_child_products.push({ child_product_id: null, units: null });
};

const updateTierAssemblyProductValueDetails = (details) => {
    productForm.assembly_child_products[details.key][details.column_name] = details.value;
};

const removeTierAssemblyProductDetailsOf = (key) => {
    productForm.assembly_child_products.splice(key, 1);
};

const addNewTierBoxDetails = () => {
    productForm.boxes.push({ package_type_id: null, units: null, retail_price: null, minimum_price: null, staff_price: null, box_product_loyalty_points: [{ membership_id: null, points: null }] });
};

const addNewNestedTierBoxDetails = (index) => {
    productForm.boxes[index].box_product_loyalty_points.push({ membership_id: null, points: null });
};

const updateTierBoxValueDetails = (details) => {
    productForm.boxes[details.key][details.column_name] = details.value;
};

const removeTierBoxDetailsOf = (key) => {
    productForm.boxes.splice(key, 1);
};

const removeNestedTierBoxDetailsOf = (data) => {
    productForm.boxes[data.mainIndex].box_product_loyalty_points.splice(data.key, 1);
};

const nestedTierBoxValueDetails = (details) => {
    productForm.boxes[details.main_index].box_product_loyalty_points[details.key][details.column_name] = details.value;
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    productForm[columnName] = details.value;
};

const updateIsAvailableInEcommerce = (columnName, data) => {
    productForm.sale_channels = [];
    productForm[columnName] = data;
};

const autoGenerateUpc = () => {
    const alphabetBase = 36;
    const minNumber = 1111;
    const maxNumber = 9999;
    const stringStartIndex = 2;
    const stringEndIndex = 6;

    const randomLetters = Math.random().toString(alphabetBase).substring(stringStartIndex, stringEndIndex).toUpperCase();
    const randomNumber = Math.floor(Math.random() * (maxNumber - minNumber + 1)) + minNumber;
    const generatedUPC = randomLetters + randomNumber + Math.random().toString(alphabetBase).substring(stringStartIndex, stringEndIndex).toUpperCase();

    axios.get(route('admin.products.exists_product_upc', generatedUPC))
        .then((response) => {
            if (response.data.status === true) {
                autoGenerateUpc();
            }
            productForm.upc = generatedUPC;
        });
};

onMounted(() => {
    if (props.product) {
        removeLocalStorage('product');
        Object.assign(productForm, JSON.parse(JSON.stringify(props.product)));
        productForm.description = productForm.description ?? '';
        productForm.image_urls = props.product.image_urls ? props.product.image_urls : null;
        const unitOfMeasure = props.unitOfMeasures.find(unitOfMeasure => unitOfMeasure.id === props.product.unit_of_measure_id);
        state.unitOfMeasureName = unitOfMeasure ? unitOfMeasure.name : null;
    } else {
        setLocalStorage('product', productForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.product) {
        saveLocalStorage('product', productForm);
    }
};

const clearFormData = () => {
    const unitOfMeasureId = productForm.unit_of_measure_id;
    const upc = productForm.upc;
    productForm.reset();

    if (props.product) {
        productForm.unit_of_measure_id = unitOfMeasureId;
        productForm.upc = upc;
    }
};

watch(productForm, () => {
    if (productForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

const checkPurchaseCostPermission = () => {
    if (pageProps.value.permissions === null) {
        return true;
    }

    return pageProps.value.permissions.find(record => record.includes(props.purchaseCost));
};

const canAddPrice = () => {
    return !!((props.defaultTypeStatic.assemblyProduct !== productForm.type_id &&
        props.defaultTypeStatic.regularProduct !== productForm.type_id && props.defaultTypeStatic.serialProduct !== productForm.type_id));
};

const removeProductImage = (index, mediaId, productId) => {
    productForm.uploaded_images.splice(index, 1);

    if (productId) {
        axios.get(route('admin.products.remove_product_image', [productId, mediaId]));
    }
};

const removeProductVideo = (index, mediaId, productId) => {
    productForm.uploaded_videos.splice(index, 1);

    if (productId) {
        axios.get(route('admin.products.remove_product_video', [productId, mediaId]));
    }
};

const removeUploadProductImage = (index) => {
    state.uploadImageUrls.splice(index, 1);
    state.images.splice(index, 1);
    productForm.images = state.images;
};

const removeUploadProductVideo = (index) => {
    state.uploadVideoUrls.splice(index, 1);
    state.videos.splice(index, 1);
    productForm.videos = state.videos;
};

const openVideoPlayModal = (data) => {
    state.displayVideoPlayModal = true;
    state.videoUrl = data;
};

const closeModal = () => {
    state.displayVideoPlayModal = false;
};

const uploadThumbnailImage = (selectedImage) => {
    productForm.thumbnail_url = URL.createObjectURL(selectedImage);
};

const removeProductThumbnail = (productId) => {
    productForm.thumbnail = null;
    productForm.thumbnail_url = null;

    if (productId) {
        axios.get(route('admin.products.remove_product_thumbnail', productId));
    }
};

const updateCustomFieldValues = (customFieldValues) => {
    productForm.custom_field_values = customFieldValues;
};

const showApproveButton = () => {
    if (props.isDraftProduct && props.product) {
        if (props.creatorCanApproveDraftProduct) {
            return props.creatorCanApproveDraftProduct;
        }

        return props.user.id !== props.product.created_by_id && props.user.type === props.product.created_by_type;
    }
    return false;
};

const showDeleteDraftButton = () => {
    if (props.isDraftProduct && props.product) {
        return true;
    }
    return false;
};

const draftProductForm = useForm({
    selectedRecords: [],
});

const approveProduct = () => {
    const message = 'Are you sure you want to approved this product?';
    draftProductForm.selectedRecords = [props.product.id];
    confirmDialogBox(message, () => {
        draftProductForm.post(route('admin.draft_products.approved'), {
            onSuccess: () => draftProductForm.get(route('admin.draft_products.index'))
        });
    });
};

const deleteDraftProduct = () => {
    const message = 'Are you sure you want to delete this product?';
    draftProductForm.selectedRecords = [props.product.id];
    confirmDialogBox(message, () => {
        draftProductForm.post(route('admin.draft_products.delete'), {
            onSuccess: () => draftProductForm.get(route('admin.draft_products.index'))
        });
    });
};

</script>
