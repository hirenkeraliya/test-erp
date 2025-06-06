<template>
    <PageTitle :title="company ? 'Edit Company' : 'Add Company'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Companies
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="company">Edit Company</span>
                        <span v-else>Add Company</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="saveCompany()"
                >
                    <div class=" p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.legal_name"
                                    input-name="legal_name"
                                    input-label="Legal Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.employer_identification_number"
                                    placeholder="Enter Employer Identification Number"
                                    input-name="employer_identification_number"
                                    input-label="Employer Identification Number (EIN)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.social_security_number"
                                    placeholder="Enter Social Security Number"
                                    input-name="social_security_number"
                                    input-label="Social Security Number or Tax ID Number (SSN or TIN)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="flex items-center">
                                    <FormInput
                                        v-model:input-value="companyForm.email"
                                        input-name="email"
                                        input-label="Email"
                                        :required="true"
                                    />
                                    <Tippy
                                        v-if="company ? ! company.is_email_verified && companyForm.email : companyForm.email"
                                        :content="'Your email will require verification.'"
                                    >
                                        <TriangleAlert
                                            class="text-red-400 ml-2 mt-7"
                                            :size="20"
                                        />
                                    </Tippy>
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.website"
                                    type="url"
                                    input-name="website"
                                    input-label="Website"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.fax"
                                    input-name="fax"
                                    input-label="Fax"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextArea
                                    v-model:input-value="companyForm.address"
                                    input-name="address"
                                    input-label="Address"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileCropUpload
                                    v-model:input-file="companyForm.light_logo"
                                    input-label="Light Logo (200px X 200px)"
                                    validation-field-name="light_logo"
                                    :max-width="200"
                                    :max-height="200"
                                    @update:input-file="uploadImageLight"
                                />
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <img
                                        :src="companyForm.light_logo_url"
                                        :alt="companyForm.name"
                                        width="100"
                                        class="bg-gray-300 mt-2"
                                    >
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileCropUpload
                                    v-model:input-file="companyForm.dark_logo"
                                    input-label="Dark Logo (200px X 200px)"
                                    validation-field-name="dark_logo"
                                    :max-width="200"
                                    :max-height="200"
                                    @update:input-file="uploadImageDark"
                                />
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <img
                                        :src="companyForm.dark_logo_url"
                                        :alt="companyForm.name"
                                        width="100"
                                        class="bg-gray-300 mt-2"
                                    >
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JFileCropUpload
                                    v-model:input-file="companyForm.email_footer_logo"
                                    input-label="Email Footer Logo (1280px X 720px)"
                                    validation-field-name="email_footer_logo"
                                    :max-width="1280"
                                    :max-height="720"
                                    @update:input-file="uploadImageEmailFooter"
                                />
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-3"
                                >
                                    <img
                                        :src="companyForm.email_footer_logo_url"
                                        :alt="companyForm.name"
                                        width="100"
                                        class="bg-gray-300 mt-2"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="companyForm.brands"
                                    :records="brands"
                                    input-label="Brands"
                                    :required="true"
                                    validation-field-name="brand_ids"
                                    title="1) Locations of the company can select only the brands that you select here.
                                    2) Admins can select only the brands of the company that they belong to when managing products."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.grn_format"
                                    input-name="grn_format"
                                    input-label="GRN Format"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.void_sale_number_prefix"
                                    input-name="void_sale_number_prefix"
                                    input-label="Void Sale Prefix Number"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.order_picking_list_prefix"
                                    input-name="order_picking_list_prefix"
                                    input-label="Order Picking List Prefix"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="companyForm.commission_type_id"
                                    :records="commissionTypes"
                                    :required="true"
                                    input-label="Promoter Commission Type"
                                    validation-field-name="commission_type_id"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.min_promoters_per_item"
                                    placeholder="Enter minimum required promoters per sale item. Enter any number between 0 and 255."
                                    input-name="min_promoters_per_item"
                                    validation-field-name="min_promoters_per_item"
                                    input-label="Minimum promoters per sale item."
                                    title="For example, if you set this to 2, each sale item must have at least 2 promoters attached for the successful sale. Set to zero(0) for no restrictions."
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.new_member_free_loyalty_points"
                                    placeholder="Enter New Member Free Loyalty Points"
                                    input-name="new_member_free_loyalty_points"
                                    input-label="New Member Free Loyalty Points"
                                    title="Set Zero (0) if you don't want to give free points to the newly registered member."
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.loyalty_point_expiration_days"
                                    input-name="loyalty_point_expiration_days"
                                    input-label="Loyalty Points Expiration Days"
                                    title="1) Set Zero (0) if you don't want to set a limit. 2) Cannot be used after expiry."
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.number_of_receipts"
                                    placeholder="Number Of Receipts"
                                    input-name="number_of_receipts"
                                    input-label="Number Of Receipts"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="companyForm.yearly_target"
                                    input-name="yearly_target"
                                    input-label="Yearly Target"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="companyForm.discount_applicable_type"
                                    :records="discountApplicableTypes"
                                    input-label="Discount Applicable Type"
                                    validation-field-name="discount_applicable_type"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="companyForm.booking_payment_use_type"
                                    :records="bookingPaymentUseTypes"
                                    input-label="Booking Payment Use Type"
                                    validation-field-name="booking_payment_use_type"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="companyForm.booking_payment_refund_type"
                                    :records="bookingPaymentRefundTypes"
                                    input-label="Booking Payment Refund Type"
                                    validation-field-name="booking_payment_refund_type"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="companyForm.countries"
                                    :records="countries"
                                    input-label="Country"
                                    :required="true"
                                    validation-field-name="country_ids"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="companyForm.default_country_id"
                                    :records="companyForm.countries"
                                    input-label="Default Country"
                                    :placeholder="companyForm.countries.length === 0 ? 'Please Select Country First' : 'Select Default Country'"
                                    validation-field-name="default_country_id"
                                    :disabled="companyForm.countries.length === 0"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="props.company"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="companyForm.location_assignment_type"
                                    :records="locationAssignmentTypes"
                                    title="Default Location Assignment for members registering outside of the ERP system offers three options: Manual Assignment: Administrators can manually set a specific location for a member from the admin panel. Default Location: A pre-selected location will be automatically assigned to new members registering outside the ERP system. Based on First Purchase: Location assignment will be determined by the location where members make their first purchase."
                                    input-label="Location Assignment Type"
                                    validation-field-name="location_assignment_type"
                                />
                            </div>
                            <div
                                v-if="companyForm.location_assignment_type === locationAssignmentStaticDetails.defaultLocation && props.company"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="companyForm.default_location_id"
                                    :records="locations"
                                    input-label="Locations"
                                    validation-field-name="default_location_id"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.send_sale_email_to_member"
                                    input-label="Send Sale Email to Member?"
                                    validation-field-name="send_sale_email_to_member"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.is_bill_reference_number_mandatory"
                                    input-label="Bill Reference Number Mandatory?"
                                    validation-field-name="is_bill_reference_number_mandatory"
                                    :required="true"
                                    title="When this option is set to true, bill reference number is compulsory when adding new Sales and Booking Payments from POS"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_exchange_to_different_store"
                                    input-label="Allow Exchange Different Location?"
                                    validation-field-name="allow_exchange_to_different_store"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_price_override_cart_level"
                                    input-label="Allow Price Override Cart Level?"
                                    validation-field-name="allow_price_override_cart_level"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_negative_inventory"
                                    input-label="Allow Negative Inventory?"
                                    validation-field-name="allow_negative_inventory"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.is_employee_booking_payment_allowed"
                                    input-label="Is Employee Booking Payment Allowed?"
                                    validation-field-name="allow_price_override_cart_level"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_only_return"
                                    input-label="Is Allow only return?"
                                    validation-field-name="allow_only_return"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.auto_birthday_voucher_generation"
                                    input-label="Auto Birthday Voucher Generation?"
                                    validation-field-name="auto_birthday_voucher_generation"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_credit_sale"
                                    input-label="Is Allow Credit Sale?"
                                    validation-field-name="allow_credit_sale"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_employee_credit_sale"
                                    input-label="Is Employee Allow Credit Sale?"
                                    validation-field-name="allow_employee_credit_sale"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.enable_ioi_city_mall_integration"
                                    input-label="Allow IOI City Mall Integration?"
                                    validation-field-name="enable_ioi_city_mall_integration"
                                    title="If any locations are located in IOI City Mall, please enable the IOI City Mall Integration here. Then, the admin can enable the integration for the specific location(s) by specifying the Machine ID provided by the mall."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.enable_trx_mall_integration"
                                    input-label="Allow TRX Mall Integration?"
                                    validation-field-name="enable_trx_mall_integration"
                                    :required="true"
                                    title="If any locations are located in TRX Mall, please enable the TRX Mall Integration here. Then, the admin can enable the integration for the specific location(s) by specifying the Machine ID provided by the mall."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.allow_happy_hour_discount"
                                    input-label="Allow Happy Hour Discount?"
                                    validation-field-name="allow_happy_hour_discount"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.auto_include_in_collections"
                                    input-label="Auto Include In Collection?"
                                    validation-field-name="auto_include_in_collections"
                                    :required="true"
                                    title="New/Updated Product(s) based on criteria will be automatically added in product collections."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.auto_include_in_member_group"
                                    input-label="Auto Include In Member Group?"
                                    validation-field-name="auto_include_in_member_group"
                                    :required="true"
                                    title="Member is on criteria will be automatically added in smart member group."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.creator_can_approve_draft_product"
                                    input-label="Creator Can Approve Draft Product?"
                                    validation-field-name="creator_can_approve_draft_product"
                                    :required="true"
                                    title="Creators can approve their own product(s) when this option is enabled."
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="companyForm.enable_e_invoice"
                                    input-label="Allow E-Invoice Generate"
                                    validation-field-name="enable_e_invoice"
                                    :required="true"
                                    title="If enabled, E-invoice form can be submit on respective Sales/Orders/Booking payments/Sale returns/Order returns/Credit notes report"
                                />

                                <span v-if="companyForm.enable_e_invoice">
                                    <JSwitch
                                        v-model:is-checked="companyForm.show_e_invoice_qr_on_receipt"
                                        input-label="Do you want to show the E-Invoice QR code on the receipt?"
                                        validation-field-name="show_e_invoice_qr_on_receipt"
                                        :required="true"
                                        title="If enabled, E-invoice generation QR code will be displayed on Sales/Booking payments/Sale returns/Credit notes receipts."
                                    />
                                </span>
                            </div>
                        </div>

                        <CompanySettingToggles
                            v-if="isSettingDataPrepared"
                            :company-data="companyForm.company_setting"
                            @selected-toggles="selectedToggles"
                        />

                        <div class="mt-5">
                            <Link :href="route('super_admin.companies.index')">
                                <SecondaryButton
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="company ? 'Update' : 'Submit'"
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
import { useForm, router } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import FormTextArea from '@commonComponents/FormTextarea.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import CompanySettingToggles from '@superAdminComponents/CompanySettingsToggles.vue';
import { onMounted, ref } from 'vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { route } from 'ziggy';
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    company: {
        type: Object,
        default: null,
    },
    commissionTypes: {
        type: Array,
        required: true,
    },
    brands: {
        type: Object,
        required: true,
    },
    discountApplicableTypes: {
        type: Array,
        required: true,
    },
    bookingPaymentUseTypes: {
        type: Array,
        required: true,
    },
    additionalDiscount: {
        type: Number,
        required: true,
    },
    partiallyPayment: {
        type: Number,
        required: true,
    },
    locationAssignmentTypes: {
        type: Array,
        required: true,
    },
    manualAssignment: {
        type: Number,
        required: true,
    },
    locationAssignmentStaticDetails: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        default: null,
    },
    countries: {
        type: Object,
        required: true,
    },
    bookingPaymentRefundTypes: {
        type: Array,
        required: true,
    },
    partiallyRefundPayment: {
        type: Number,
        required: true,
    },
});

const isSettingDataPrepared = ref(false);

const companyForm = useForm({
    _method: props.company ? 'put' : 'post',
    name: null,
    code: null,
    brand_ids: [],
    grn_format: 'GRN/',
    light_logo: null,
    dark_logo: null,
    email_footer_logo: null,
    legal_name: null,
    website: null,
    email: null,
    fax: null,
    address: null,
    employer_identification_number: null,
    social_security_number: null,
    void_sale_number_prefix: 'VS',
    send_sale_email_to_member: false,
    new_member_free_loyalty_points: 0,
    loyalty_point_expiration_days: 0,
    commission_type_id: null,
    min_promoters_per_item: null,
    allow_exchange_to_different_store: false,
    allow_price_override_cart_level: false,
    allow_negative_inventory: true,
    is_bill_reference_number_mandatory: false,
    is_employee_booking_payment_allowed: false,
    allow_only_return: false,
    allow_credit_sale: false,
    allow_employee_credit_sale: false,
    yearly_target: null,
    light_logo_url: null,
    dark_logo_url: null,
    email_footer_logo_url: null,
    brands: [],
    discount_applicable_type: props.additionalDiscount,
    booking_payment_use_type: props.partiallyPayment,
    booking_payment_refund_type: props.partiallyRefundPayment,
    auto_birthday_voucher_generation: false,
    enable_ioi_city_mall_integration: false,
    enable_trx_mall_integration: false,
    allow_happy_hour_discount: true,
    location_assignment_type: props.manualAssignment,
    default_location_id: null,
    auto_include_in_collections: true,
    auto_include_in_member_group: true,
    creator_can_approve_draft_product: false,
    enable_e_invoice: true,
    show_e_invoice_qr_on_receipt: false,
    countries: [],
    country_ids: [],
    default_country_id: null,
    order_picking_list_prefix: 'OP/',
    number_of_receipts: 2,
    company_setting: {
        credit_sale_use_cashback: true,
        credit_sale_redeem_loyalty_points: true,
        credit_sale_earn_loyalty_points: true,
        credit_sale_redeem_vouchers: true,
        credit_sale_generate_vouchers: true,
        credit_sale_cart_wide_automatic_promotions: true,
        credit_sale_cart_wide_manual_promotions: true,
        credit_sale_item_wise_automatic_promotions: true,
        credit_sale_item_wise_manual_promotions: true,
        credit_sale_complimentary_item: true,
        credit_sale_manual_cart_discount: true,
        credit_sale_manual_item_discount: true,
        credit_sale_happy_hour_discount: true,
        credit_sale_allow_multi_currency_in_payment: true,

        layaway_sale_use_cashback: true,
        layaway_sale_redeem_loyalty_points: true,
        layaway_sale_earn_loyalty_points: true,
        layaway_sale_redeem_vouchers: true,
        layaway_sale_generate_vouchers: true,
        layaway_sale_cart_wide_automatic_promotions: true,
        layaway_sale_cart_wide_manual_promotions: true,
        layaway_sale_item_wise_automatic_promotions: true,
        layaway_sale_item_wise_manual_promotions: true,
        layaway_sale_complimentary_item: true,
        layaway_sale_manual_cart_discount: true,
        layaway_sale_manual_item_discount: true,
        layaway_sale_happy_hour_discount: true,
        layaway_sale_allow_multi_currency_in_payment: true,

        booking_payment_allow_multi_currency_in_payment: true,
    }
});

const selectedToggles = toggleValue => {
    companyForm.company_setting[toggleValue.name] = toggleValue.val;
};

const saveCompany = () => {
    prepareCompanyFormDetails();

    if (props.company) {
        companyForm.post(route('super_admin.companies.update_company', props.company.id));
        return;
    }

    router.post(route('super_admin.companies.store_company'), companyForm);
};

const prepareCompanyFormDetails = () => {
    companyForm.brand_ids = companyForm.brands.map((brand) => {
        return brand.id;
    });

    companyForm.country_ids = companyForm.countries.map((country) => {
        return country.id;
    });
};

const uploadImageLight = (selectedImage) => {
    companyForm.light_logo_url = URL.createObjectURL(selectedImage);
};

const uploadImageDark = (selectedImage) => {
    companyForm.dark_logo_url = URL.createObjectURL(selectedImage);
};

const uploadImageEmailFooter = (selectedImage) => {
    companyForm.email_footer_logo_url = URL.createObjectURL(selectedImage);
};

onMounted(() => {
    if (props.company) {
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(companyForm, JSON.parse(JSON.stringify(props.company)));

        companyForm.light_logo_url = props.company.light_logo_url;
        companyForm.dark_logo_url = props.company.dark_logo_url;
        companyForm.email_footer_logo_url = props.company.email_footer_logo_url;
    }
    isSettingDataPrepared.value = true;
});
</script>
