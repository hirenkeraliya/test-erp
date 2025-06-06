<template>
    <PageTitle title="Import Records" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Import Records
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        Add new import record
                    </h2>
                </div>

                <form @submit.prevent="saveImportRecord();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="state.groupTypeId"
                                    :records="groupImportTypes"
                                    input-label="Import Types"
                                    :required="true"
                                    @update:selected-record="updateGroupImportType"
                                />
                            </div>
                            <div
                                v-if="state.groupTypeId"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="importRecordForm.type_id"
                                    :records="state.importTypes"
                                    input-label="Import"
                                    validation-field-name="type_id"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <FormSelectBox
                                    v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_image_upload"
                                    :selected-record="importRecordForm.product_upload_type_id"
                                    :records="productUploadTypes"
                                    input-label="Product Upload Type"
                                    validation-field-name="product_upload_type_id"
                                    @update:selected-record="updateProductUploadType"
                                />
                            </div>
                        </div>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.products"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal">
                                <p>
                                    New imported product(s) will be display on this
                                    <Link
                                        :href="route('admin.draft_products.index')"
                                        class="underline text-primary"
                                    >
                                        link.
                                    </Link>
                                </p>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.members_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing members' details by importing them from a file.
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the members and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the members.
                                </li>
                                <li>
                                    The date_of_joining format must be in the d-m-Y (1-1-2023) format.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update UPC, Unit of measure, and type of the product.
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.member_address"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are currently importing new member addresses, which will replace the existing ones.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the product information by downloading it from this
                                <Link
                                    :href="route('admin.products.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_price_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    Please specify UPC of the product along with the new prices.
                                </li>
                                <li>
                                    When you export, the columns linked to your permissions will be visible. This means that only the columns you have permission for will be included in the exported file.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.employees"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    Ensure that the mobile numbers are unique, both in your new uploads and in the existing records.
                                </li>
                                <li>
                                    date_of_joining format must follow the d-m-Y (1-1-2023) format.
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.employees_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing employees' details by importing them from a file.
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the employees and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the employees.
                                </li>
                                <li>
                                    The date_of_joining format must be in the d-m-Y (1-1-2023) format.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.payment_type_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing payment types' details by importing them from a file.
                                </li>
                                <li>
                                    We are not allowing to update Name of the payment type.
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the payment types and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the payment types.
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashier_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing cashiers' details by importing them from a file.
                                </li>
                                <li>
                                    We are not allowing to update first name and mobile number of the cashier.
                                </li>
                                <li>
                                    specified locations name must be comma separated(like: abc,xyz)
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the cashiers and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the cashiers.
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.store_manager_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing store managers' details by importing them from a file.
                                </li>
                                <li>
                                    We are not allowing to update first name and mobile number of the employee.
                                </li>
                                <li>
                                    specified locations name must be comma separated(like: abc,xyz)
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the store managers and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the store managers.
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.promoter_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing promoters' details by importing them from a file.
                                </li>
                                <li>
                                    We are not allowing to update Employee(first_name, mobile_number) of the promoter.
                                </li>
                                <li>
                                    specified locations name must be comma separated(like: abc,xyz)
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the promoters and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the promoters.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.category_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    This feature allows for the editing of existing categories' details by importing them from a file.
                                </li>
                                <li>
                                    We are not allowing to update name of the category.
                                </li>
                                <li>
                                    The "Download Latest Data" button provides you with all the categories and their latest details, but you only need to upload the records that have modifications. Please remove any extra rows to quickly perform a bulk update of the categories.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.counter_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the counter.
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the counters.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashier_groups_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the cashier group.
                                </li>
                                <li>
                                    specified permissions name must be comma separated(like: abc,xyz).
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the cashier groups.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.location_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Phone and Type of the location.
                                </li>
                                <li>
                                    specified brands name must be comma separated(like: abc,xyz)
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the locations.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.location_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the location information by downloading it from this
                                <Link
                                    :href="route('admin.locations.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.color_group_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the color group.
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the color groups.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.color_group_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the color group information by downloading it from this
                                <Link
                                    :href="route('admin.color_groups.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.counter_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the counter information by downloading it from this
                                <Link
                                    :href="route('admin.counters.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashier_groups_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the cashier groups information by downloading it from this
                                <Link
                                    :href="route('admin.cashier_groups.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashier_groups"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    specified permissions name must be comma separated(like: abc,xyz).
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.size_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the size.
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the sizes.
                                </li>
                            </ul>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.size_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the size information by downloading it from this
                                <Link
                                    :href="route('admin.sizes.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.color_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the color.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.color_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the color information by downloading it from this
                                <Link
                                    :href="route('admin.colors.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.size_group_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the size group.
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the size groups.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.size_group_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the size group information by downloading it from this
                                <Link
                                    :href="route('admin.size_groups.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>

                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.vendor_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update phone of the vendor.
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the vendors.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.vendor_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the vendors information by downloading it from this
                                <Link
                                    :href="route('admin.vendors.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.regions_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    We are not allowing to update Name of the regions.
                                </li>
                                <li>
                                    Please remove any extra rows to quickly perform a bulk update of the regions.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.regions_bulk_update"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Get the vendors information by downloading it from this
                                <Link
                                    :href="route('admin.regions.index')"
                                    class="underline text-primary"
                                >
                                    link.
                                </Link>
                            </h2>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.members"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    The date_of_birth format must be d-m-Y (e.g., 1-1-2023).
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.bulk_product_merge"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    The Old UPC Product Is Going To Merge With New UPC Product. So The Old UPC Product Is Going To Be Deleted.
                                </li>
                                <li>
                                    Same Product type only can be merge. Like Regular v/s Regular or Bundle v/s Bundle etc...
                                </li>
                                <li>
                                    Either Article number must be same or non-article number means no article numbers for bot products.
                                </li>
                            </ul>
                        </InfoAlert>
                        <InfoAlert
                            v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_image_upload && importRecordForm.product_upload_type_id"
                            color="danger"
                            class="my-3"
                        >
                            <h2 class="text-lg font-medium mr-auto">
                                Important Instructions
                            </h2>
                            <ul class="list-decimal ml-4">
                                <li>
                                    Accept only ZIP file.
                                </li>
                                <span v-if="importRecordForm.product_upload_type_id === productUploadTypeDetails.thumbnail">
                                    <li>
                                        Product thumbnail images name according to their UPCs.
                                    </li>
                                    <li>
                                        Product images size 343*260.
                                    </li>
                                    <li>
                                        If there is an existing image for any product, it will be replaced with a new one.
                                    </li>
                                </span>
                                <span v-if="importRecordForm.product_upload_type_id === productUploadTypeDetails.images">
                                    <li>
                                        Ensure all images in this zip follow the naming convention {UPC}_{sequence_number}.image_extension.
                                    </li>
                                    <li>
                                        All product images should have dimensions of 500x500 pixels.
                                    </li>
                                </span>
                                <span v-if="importRecordForm.product_upload_type_id === productUploadTypeDetails.videos">
                                    <li>
                                        Ensure all videos in this zip follow the naming convention {UPC}_{sequence_number}.video_extension.
                                    </li>
                                    <li>
                                        All product videos must adhere to a maximum file size of 15MB.
                                    </li>
                                </span>
                            </ul>
                        </InfoAlert>
                        <div
                            v-if="importRecordForm.type_id"
                            class="grid grid-cols-12 gap-6"
                        >
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-3">
                                <JFileUpload
                                    v-if="parseInt(importRecordForm.type_id) !== importTypeDetails.product_bulk_image_upload"
                                    v-model:input-file="importRecordForm.upload_file"
                                    :accept="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_image_upload ? '.zip' : '.xlsx, .xls, .ods'"
                                    input-label="Upload File Records"
                                    validation-field-name="upload_file"
                                    :required="true"
                                />
                                <JFileUpload
                                    v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_image_upload && importRecordForm.product_upload_type_id"
                                    v-model:input-file="importRecordForm.upload_file"
                                    :accept="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_image_upload ? '.zip' : '.xlsx, .xls, .ods'"
                                    input-label="Upload File Records"
                                    validation-field-name="upload_file"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.products"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-products-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.bulk_product_merge"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-bulk_product_merge-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_bulk_image_upload && importRecordForm.product_upload_type_id"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/product-image-bulk-upload.zip"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.members_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkMembers"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.members"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-members-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.product_price_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <button
                                    class="btn btn-outline-warning mt-4"
                                    type="button"
                                    @click="exportProductPriceBulkUpdate"
                                >
                                    <div class="flex justify-center items-center">
                                        <Download class="w-4 h-4 mr-1" />
                                        Download
                                    </div>
                                </button>
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.employees"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-employees-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.employees_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkEmployees"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.payment_type_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkPaymentTypes"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashier_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkCashiers"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.category_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkCategories"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.store_manager_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkStoreManagers"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.promoter_bulk_update"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <div class="mt-6">
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        @click="exportBulkPromoters"
                                    >
                                        Download Latest Data
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.payment_types"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-payment-types-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.counters"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-counter-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.update_member_loyalty_points"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-update-member-loyalty-points-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.add_member_loyalty_points"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-add-member-loyalty-points-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.promoters"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    v-if="promoterCommissionType === commissionType"
                                    file-path="/files/import-promoters-commission-by-promoter-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                                <JFileDownload
                                    v-else
                                    file-path="/files/import-promoters-commission-by-department-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.regions"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-regions-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.color_groups"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-color-groups-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.vendors"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-vendors-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.sizes"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-sizes-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.colors"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-colors-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.size_groups"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-size-groups-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashiers"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-cashiers-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.cashier_groups"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-cashier-groups-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.locations"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-locations-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.store_managers"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-store-managers-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.categories"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-categories-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.set_product_loyalty_points"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-set-product-loyalty-points-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.set_product_box_units"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-set-product-box-units-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>

                            <div
                                v-if="parseInt(importRecordForm.type_id) === importTypeDetails.member_address"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                            >
                                <JFileDownload
                                    file-path="/files/import-member-address-sample-file.xlsx"
                                    input-label="Download Sample File"
                                />
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('admin.import_records.index')">
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
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JFileDownload from '@commonComponents/JFileDownload.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { route } from 'ziggy';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import axios from 'axios';
import { exportRecords } from '@commonServices/helper';
import { Download } from 'lucide-vue-next';
import { reactive } from 'vue';
import { showErrorNotification } from '@commonServices/notifier';

const props = defineProps({
    importTypeDetails: {
        type: Object,
        default: null,
    },
    productUploadTypeDetails: {
        type: Object,
        required: true,
    },
    promoterCommissionType: {
        type: Number,
        required: true,
    },

    commissionType: {
        type: Number,
        required: true,
    },

    exportPermission: {
        type: String,
        required: true,
    },
    productUploadTypes: {
        type: Array,
        required: true,
    },

    groupImportTypes: {
        type: Object,
        default: null,
    },
    groupImportTypeDetails: {
        type: Object,
        default: null,
    },
    productImportTypes: {
        type: Object,
        default: null,
    },
    memberImportTypes: {
        type: Object,
        default: null,
    },
    employeeImportTypes: {
        type: Object,
        default: null,
    },
    counterImportTypes: {
        type: Object,
        default: null,
    },
    paymentTypeImportTypes: {
        type: Object,
        default: null,
    },
    promoterImportTypes: {
        type: Object,
        default: null,
    },
    colorGroupImportTypes: {
        type: Object,
        default: null,
    },
    regionImportTypes: {
        type: Object,
        default: null,
    },
    vendorImportTypes: {
        type: Object,
        default: null,
    },
    sizeGroupImportTypes: {
        type: Object,
        default: null,
    },
    sizeImportTypes: {
        type: Object,
        default: null,
    },
    colorImportTypes: {
        type: Object,
        default: null,
    },
    cashierImportTypes: {
        type: Object,
        default: null,
    },
    storeManagerImportTypes: {
        type: Object,
        default: null,
    },
    categoryImportTypes: {
        type: Object,
        default: null,
    },
    cashierGroupImportTypes: {
        type: Object,
        default: null,
    },
    locationImportTypes: {
        type: Object,
        default: null,
    },
});

const importRecordForm = useForm({
    upload_file: null,
    type_id: null,
    product_upload_type_id: null,
});

const state = reactive({
    groupTypeId: null,
    importTypes: [],
});

const saveImportRecord = () => {
    if (state.groupTypeId === null) {
        showErrorNotification('Please select a group import type');
        return false;
    }

    importRecordForm.post(route('admin.import_records.store'));
};

const updateProductUploadType = (typeId) => {
    importRecordForm.product_upload_type_id = typeId;
};

const exportBulkMembers = () => {
    axios.get(route('admin.members.export_existing_members'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'members-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const exportBulkEmployees = () => {
    axios.get(route('admin.employees.export_existing_employees'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'employees-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const exportBulkPaymentTypes = () => {
    axios.get(route('admin.payment_types.export_bulk_update_payment_types'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'payment-type-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const exportBulkCashiers = () => {
    axios.get(route('admin.cashiers.export_bulk_update_cashiers'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'cashiers-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const exportBulkCategories = () => {
    axios.get(route('admin.categories.export_bulk_update_categories'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'category-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const exportBulkStoreManagers = () => {
    axios.get(route('admin.store_managers.export_bulk_update_store_managers'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'store-manager-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const exportProductPriceBulkUpdate = () => {
    return exportRecords(
        'export-product-price-update/',
        'product-price-bulk-update.xlsx',
        [],
        props.exportPermission,
    );
};

const exportBulkPromoters = () => {
    axios.get(route('admin.promoters.export_existing_promoters'), {
        responseType: 'arraybuffer'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'promoter-bulk-update.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
};

const updateGroupImportType = (groupTypeId) => {
    importRecordForm.type_id = null;
    if (groupTypeId === props.groupImportTypeDetails.products) {
        state.importTypes = props.productImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.members) {
        state.importTypes = props.memberImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.employees) {
        state.importTypes = props.employeeImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.counters) {
        state.importTypes = props.counterImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.payment_types) {
        state.importTypes = props.paymentTypeImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.promoters) {
        state.importTypes = props.promoterImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.color_groups) {
        state.importTypes = props.colorGroupImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.regions) {
        state.importTypes = props.regionImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.vendors) {
        state.importTypes = props.vendorImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.size_groups) {
        state.importTypes = props.sizeGroupImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.sizes) {
        state.importTypes = props.sizeImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.colors) {
        state.importTypes = props.colorImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.cashiers) {
        state.importTypes = props.cashierImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.store_managers) {
        state.importTypes = props.storeManagerImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.categories) {
        state.importTypes = props.categoryImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.cashier_groups) {
        state.importTypes = props.cashierGroupImportTypes;
    }

    if (groupTypeId === props.groupImportTypeDetails.locations) {
        state.importTypes = props.locationImportTypes;
    }
};

</script>
