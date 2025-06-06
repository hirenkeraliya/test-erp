<template>
    <PageTitle title="Edit Site Configuration" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Site Configurations
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Edit Site Configuration</span>
                    </h2>
                </div>

                <form @submit.prevent="saveSiteConfiguration();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="siteConfigurationForm.type_id"
                                    :records="siteConfigurationEnum"
                                    input-label="Configuration"
                                    validation-field-name="type_id"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.theme"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.theme"
                                    v-model:selected-record="siteConfigurationForm.theme_color"
                                    :records="themeColors"
                                    input-label="Theme Colors"
                                    validation-field-name="theme_color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.theme"
                                    :display-background-colors="true"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.favicon_icon"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.favicon_icon"
                                    v-model:input-file="siteConfigurationForm.favicon_icon"
                                    input-label="Upload Fav Icon (50px X 50px)"
                                    validation-field-name="favicon_icon"
                                    :max-width="50"
                                    :max-height="50"
                                    @update:input-file="uploadFaviconImage"
                                />

                                <img
                                    :src="siteConfigurationForm.favicon_icon_url"
                                    :alt="siteConfigurationForm.favicon_icon_url"
                                    width="100"
                                    class="mt-2"
                                >
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.login_page_logo"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.login_page_logo"
                                    v-model:input-file="siteConfigurationForm.login_page_logo"
                                    input-label="Upload Logo (500px X 500px)"
                                    validation-field-name="login_page_logo"
                                    :max-width="500"
                                    :max-height="500"
                                    @update:input-file="uploadLoginPageLogo"
                                />

                                <img
                                    :src="siteConfigurationForm.login_page_logo_url"
                                    :alt="siteConfigurationForm.login_page_logo_url"
                                    width="100"
                                    class="mt-2"
                                >
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.login_page_tagline"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.login_page_tagline"
                                    v-model:input-value="siteConfigurationForm.login_page_tagline"
                                    input-name="login_page_tagline"
                                    input-label="Login Page Tagline"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.login_page_sub_tagline"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.login_page_sub_tagline"
                                    v-model:input-value="siteConfigurationForm.login_page_sub_tagline"
                                    input-name="login_page_sub_tagline"
                                    input-label="Login Page Sub tagline"
                                    :required="true"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.navbar_logo"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.navbar_logo"
                                    v-model:input-file="siteConfigurationForm.upload_navbar_logo"
                                    input-label="Upload Navbar Logo (200px X 200px)"
                                    validation-field-name="upload_navbar_logo"
                                    :max-width="200"
                                    :max-height="200"
                                    @update:input-file="uploadNavbarLogo"
                                />

                                <img
                                    :src="siteConfigurationForm.upload_navbar_logo_url"
                                    :alt="siteConfigurationForm.upload_navbar_logo_url"
                                    width="100"
                                    class="mt-2"
                                >
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.default_company"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.default_company"
                                    v-model:selected-record="siteConfigurationForm.default_company"
                                    :records="companies"
                                    title="Members who register outside of the ERP system will be assigned to the default company, and they will receive the benefits associated with that company."
                                    input-label="Default Company"
                                    validation-field-name="default_company"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.default_company"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_type"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_type"
                                    v-model:selected-record="siteConfigurationForm.ecommerce_type"
                                    :records="ecommerceType"
                                    input-label="Ecommerce Mode"
                                    validation-field-name="ecommerce_type"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_type"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_theme_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_theme_color"
                                    v-model:input-value="siteConfigurationForm.app_theme_color"
                                    type="color"
                                    input-name="app_theme_color"
                                    input-label="App Theme Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_theme_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_button_text_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_button_text_color"
                                    v-model:input-value="siteConfigurationForm.app_button_text_color"
                                    type="color"
                                    input-name="app_button_text_color"
                                    input-label="App Button Text Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_button_text_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_title_bar_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_title_bar_color"
                                    v-model:input-value="siteConfigurationForm.app_title_bar_color"
                                    type="color"
                                    input-name="app_title_bar_color"
                                    input-label="App Title Bar Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_title_bar_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_complete_text"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_complete_text"
                                    v-model:input-value="siteConfigurationForm.app_complete_text"
                                    type="color"
                                    input-name="app_complete_text"
                                    input-label="App Complete Text"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_complete_text"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_complete_text_background"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_complete_text_background"
                                    v-model:input-value="siteConfigurationForm.app_complete_text_background"
                                    type="color"
                                    input-name="app_complete_text_background"
                                    input-label="App Complete Text Background"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_complete_text_background"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_text_hint_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_text_hint_color"
                                    v-model:input-value="siteConfigurationForm.app_text_hint_color"
                                    type="color"
                                    input-name="app_text_hint_color"
                                    input-label="App Text Hint Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_text_hint_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_text_change_due"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_text_change_due"
                                    v-model:input-value="siteConfigurationForm.app_text_change_due"
                                    type="color"
                                    input-name="app_text_change_due"
                                    input-label="App Text Change Due"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_text_change_due"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_all_text_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_all_text_color"
                                    v-model:input-value="siteConfigurationForm.app_all_text_color"
                                    type="color"
                                    input-name="app_all_text_color"
                                    input-label="App All Text Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_all_text_color"
                                />
                            </div>

                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_label_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_label_color"
                                    v-model:input-value="siteConfigurationForm.app_label_color"
                                    type="color"
                                    input-name="app_label_color"
                                    input-label="App Label Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_label_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_button_background_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_button_background_color"
                                    v-model:input-value="siteConfigurationForm.app_button_background_color"
                                    type="color"
                                    input-name="app_button_background_color"
                                    input-label="App Button Background Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_button_background_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_all_sub_tittle_text_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_all_sub_tittle_text_color"
                                    v-model:input-value="siteConfigurationForm.app_all_sub_tittle_text_color"
                                    type="color"
                                    input-name="app_all_sub_tittle_text_color"
                                    input-label="App All Sub Title Text Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_all_sub_tittle_text_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_switch_on_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_switch_on_color"
                                    v-model:input-value="siteConfigurationForm.app_switch_on_color"
                                    type="color"
                                    input-name="app_switch_on_color"
                                    input-label="App Switch On Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_switch_on_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_checkbox_fill_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_checkbox_fill_color"
                                    v-model:input-value="siteConfigurationForm.app_checkbox_fill_color"
                                    type="color"
                                    input-name="app_checkbox_fill_color"
                                    input-label="App Checkbox fill Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_checkbox_fill_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section1_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section1_color"
                                    v-model:input-value="siteConfigurationForm.app_dashboard_section1_color"
                                    type="color"
                                    input-name="app_dashboard_section1_color"
                                    input-label="App Dashboard Section1 Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section1_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section2_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section2_color"
                                    v-model:input-value="siteConfigurationForm.app_dashboard_section2_color"
                                    type="color"
                                    input-name="app_dashboard_section2_color"
                                    input-label="App Dashboard Section2 Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section2_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section3_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section3_color"
                                    v-model:input-value="siteConfigurationForm.app_dashboard_section3_color"
                                    type="color"
                                    input-name="app_dashboard_section3_color"
                                    input-label="App Dashboard Section3 Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section3_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section4_color"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section4_color"
                                    v-model:input-value="siteConfigurationForm.app_dashboard_section4_color"
                                    type="color"
                                    input-name="app_dashboard_section4_color"
                                    input-label="App Dashboard Section4 Color"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_dashboard_section4_color"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_first_gradient"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_first_gradient"
                                    v-model:input-value="siteConfigurationForm.app_scaffold_background_color_first_gradient"
                                    type="color"
                                    input-name="app_scaffold_background_color_first_gradient"
                                    input-label="App Scaffold Background Color First Gradient"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_first_gradient"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_second_gradient"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_second_gradient"
                                    v-model:input-value="siteConfigurationForm.app_scaffold_background_color_second_gradient"
                                    type="color"
                                    input-name="app_scaffold_background_color_second_gradient"
                                    input-label="App Scaffold Background Color Second Gradient"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_second_gradient"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_third_gradient"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_third_gradient"
                                    v-model:input-value="siteConfigurationForm.app_scaffold_background_color_third_gradient"
                                    type="color"
                                    input-name="app_scaffold_background_color_third_gradient"
                                    input-label="App Scaffold Background Color Third Gradient"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.app_scaffold_background_color_third_gradient"
                                />
                            </div>
                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_favicon"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_favicon"
                                    v-model:input-file="siteConfigurationForm.ecommerce_favicon"
                                    input-label="Upload Fav Icon (45 X 40px)"
                                    validation-field-name="ecommerce_favicon"
                                    :max-width="45"
                                    :max-height="40"
                                    @update:input-file="uploadEcommerceFaviconImage"
                                />

                                <img
                                    :src="siteConfigurationForm.ecommerce_favicon_icon_url"
                                    :alt="siteConfigurationForm.ecommerce_favicon_icon_url"
                                    width="50"
                                    class="mt-2"
                                >
                            </div>

                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_company_name"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_company_name"
                                    v-model:input-value="siteConfigurationForm.ecommerce_company_name"
                                    type="text"
                                    input-name="ecommerce_company_name"
                                    input-label="Ecommerce Company Name"
                                    :required="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_company_name"
                                />
                            </div>

                            <div
                                v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_company_logo"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JFileCropUpload
                                    v-if="siteConfigurationForm.type_id == siteConfigurationValues.ecommerce_company_logo"
                                    v-model:input-file="siteConfigurationForm.ecommerce_company_logo"
                                    input-label="Upload Company Logo (204 X 40px)"
                                    validation-field-name="ecommerce_company_logo"
                                    :max-width="204"
                                    :max-height="40"
                                    @update:input-file="uploadEcommerceCompanyLogo"
                                />

                                <img
                                    :src="siteConfigurationForm.ecommerce_company_logo_url"
                                    :alt="siteConfigurationForm.ecommerce_company_logo_url"
                                    width="50"
                                    class="mt-2"
                                >
                            </div>
                        </div>
                        <div class="mt-5">
                            <Link :href="route('super_admin.site_configurations.index')">
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { route } from 'ziggy';
import { onMounted } from 'vue';
import JFileCropUpload from '@commonComponents/JFileCropUpload.vue';

const props = defineProps({
    siteConfiguration: {
        type: Object,
        default: null,
    },
    siteConfigurationEnum: {
        type: Object,
        default: null,
    },
    siteConfigurationValues: {
        type: Object,
        default: null,
    },
    themeColors: {
        type: Object,
        default: null,
    },
    companies: {
        type: Object,
        default: null,
    },
    ecommerceType: {
        type: Object,
        default: null,
    },
    appThemeColor: {
        type: Object,
        default: null,
    },
    appButtonTextColor: {
        type: Object,
        default: null,
    },
    appTitleBarColor: {
        type: Object,
        default: null,
    },
    appCompleteText: {
        type: Object,
        default: null,
    },
    appCompleteTextBackground: {
        type: Object,
        default: null,
    },
    appTextHintColor: {
        type: Object,
        default: null,
    },
    appTextChangeDue: {
        type: Object,
        default: null,
    },
    appAllTextColor: {
        type: Object,
        default: null,
    },
    appLabelColor: {
        type: Object,
        default: null,
    },
});

const siteConfigurationForm = useForm({
    type_id: null,
    theme_color: null,
    favicon_icon: null,
    favicon_icon_url: null,
    login_page_logo: null,
    login_page_logo_url: null,
    login_page_tagline: null,
    login_page_sub_tagline: null,
    upload_navbar_logo: null,
    upload_navbar_logo_url: null,
    default_company: null,
    ecommerce_type: null,
    app_theme_color: null,
    app_button_text_color: null,
    app_title_bar_color: null,
    app_complete_text: null,
    app_complete_text_background: null,
    app_text_hint_color: null,
    app_text_change_due: null,
    app_all_text_color: null,
    ecommerce_favicon: null,
    ecommerce_favicon_icon_url: null,
    ecommerce_company_name: null,
    ecommerce_company_logo: null,
    ecommerce_company_logo_url: null,
    app_label_color: null,
    app_button_background_color: null,
    app_all_sub_tittle_text_color: null,
    app_switch_on_color: null,
    app_checkbox_fill_color: null,
    app_dashboard_section1_color: null,
    app_dashboard_section2_color: null,
    app_dashboard_section3_color: null,
    app_dashboard_section4_color: null,
    app_scaffold_background_color_first_gradient: null,
    app_scaffold_background_color_second_gradient: null,
    app_scaffold_background_color_third_gradient: null,
});

const saveSiteConfiguration = () => {
    siteConfigurationForm.post(route('super_admin.site_configurations.update', props.siteConfiguration.id));
};

const uploadNavbarLogo = (selectedImage) => {
    siteConfigurationForm.upload_navbar_logo_url = URL.createObjectURL(selectedImage);
};

const uploadFaviconImage = (selectedImage) => {
    siteConfigurationForm.favicon_icon_url = URL.createObjectURL(selectedImage);
};

const uploadEcommerceFaviconImage = (selectedImage) => {
    siteConfigurationForm.ecommerce_favicon_icon_url = URL.createObjectURL(selectedImage);
};

const uploadEcommerceCompanyLogo = (selectedImage) => {
    siteConfigurationForm.ecommerce_company_logo_url = URL.createObjectURL(selectedImage);
};

const uploadLoginPageLogo = (selectedImage) => {
    siteConfigurationForm.login_page_logo_url = URL.createObjectURL(selectedImage);
};

onMounted(() => {
    Object.assign(siteConfigurationForm, props.siteConfiguration);
});
</script>
