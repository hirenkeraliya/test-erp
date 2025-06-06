<template>
    <PageTitle :title="happyHour ? 'Edit Happy Hours' : 'Add Happy Hours'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Happy Hours
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="happyHour">Edit Happy Hours</span>
                        <span v-else>Add Hours</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveHappyHours();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="happyHourForm.name"
                                    input-name="name"
                                    input-label="Offer Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="happyHourForm.new_price"
                                    input-name="new_price"
                                    input-label="New Price For Selected Products"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="happyHourForm.location_id"
                                    :records="locations"
                                    input-label="Location"
                                    validation-field-name="location_id"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="happyHourForm.product_type_id"
                                    :records="productTypes"
                                    input-label="Product Type"
                                    validation-field-name="product_type_id"
                                    :required="true"
                                    @update:selected-record="updateProductType"
                                />
                            </div>
                            <div
                                v-if="happyHourForm.product_type_id === staticProductTypes.brand"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="happyHourForm.brands"
                                    :records="brands"
                                    input-label="Brands"
                                    validation-field-name="brand_ids"
                                />
                            </div>
                            <div
                                v-if="happyHourForm.product_type_id === staticProductTypes.departments"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="happyHourForm.departments"
                                    :records="departments"
                                    input-label="Departments"
                                    validation-field-name="department_ids"
                                />
                            </div>
                            <div
                                v-if="happyHourForm.product_type_id === staticProductTypes.style"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="happyHourForm.styles"
                                    :records="styles"
                                    input-label="Styles"
                                    validation-field-name="style_ids"
                                />
                            </div>
                            <div
                                v-if="happyHourForm.product_type_id === staticProductTypes.category"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JMultiSelect
                                    v-model:selected-records="happyHourForm.categories"
                                    :records="categories"
                                    input-label="Categories"
                                    validation-field-name="category_ids"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JDateTimePicker
                                    v-model:input-value="happyHourForm.start_date"
                                    input-label="Start Date"
                                    validation-field-name="start_date"
                                    :required="true"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JDateTimePicker
                                    v-model:input-value="happyHourForm.end_date"
                                    input-label="End Date"
                                    validation-field-name="end_date"
                                    :required="true"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="mt-5">
                            <Link :href="route('admin.happy_hours.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="happyHour ? 'Update' : 'Submit'"
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
import { useForm, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { computed, onMounted, watch } from 'vue';
import { route } from 'ziggy';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    happyHour: {
        type: Object,
        default: null,
    },
    departments: {
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
    productTypes: {
        type: Object,
        required: true,
    },
    locations: {
        type: Object,
        required: true,
    },
    staticProductTypes: {
        type: Object,
        required: true,
    },
});

const happyHourForm = useForm({
    location_id: null,
    product_type_id: null,
    name: null,
    new_price: null,
    department_ids: [],
    brand_ids: [],
    style_ids: [],
    category_ids: [],
    departments: [],
    categories: [],
    brands: [],
    styles: [],
    start_date: null,
    end_date: null,
    watchEnabled: true,
});

const saveHappyHours = () => {
    happyHourForm.watchEnabled = false;

    prepareHappyHourFormDetails();
    removeLocalStorage('happyHours');

    if (props.happyHour) {
        happyHourForm.put(route('admin.happy_hours.update', props.happyHour.id));
        return;
    }

    happyHourForm.post(route('admin.happy_hours.store'));
};

const updateProductType = (productType) => {
    happyHourForm.product_type_id = productType;
    if (productType === props.staticProductTypes.category) {
        happyHourForm.brands = [];
        happyHourForm.styles = [];
        happyHourForm.departments = [];
    }

    if (productType === props.staticProductTypes.brand) {
        happyHourForm.categories = [];
        happyHourForm.styles = [];
        happyHourForm.departments = [];
    }

    if (productType === props.staticProductTypes.departments) {
        happyHourForm.brands = [];
        happyHourForm.styles = [];
        happyHourForm.categories = [];
    }

    if (productType === props.staticProductTypes.style) {
        happyHourForm.brands = [];
        happyHourForm.categories = [];
        happyHourForm.departments = [];
    }

    happyHourForm.brands = [];
    happyHourForm.categories = [];
    happyHourForm.departments = [];
    happyHourForm.styles = [];
};

const prepareHappyHourFormDetails = () => {
    happyHourForm.brand_ids = happyHourForm.brands.map((brand) => {
        return brand.id;
    });
    happyHourForm.category_ids = happyHourForm.categories.map((category) => {
        return category.id;
    });
    happyHourForm.style_ids = happyHourForm.styles.map((style) => {
        return style.id;
    });
    happyHourForm.department_ids = happyHourForm.departments.map((department) => {
        return department.id;
    });
};

onMounted(() => {
    if (props.happyHour) {
        removeLocalStorage('happyHours');
        Object.assign(happyHourForm, JSON.parse(JSON.stringify(props.happyHour)));
    } else {
        setLocalStorage('happyHours', happyHourForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.happyHour) {
        saveLocalStorage('happyHours', happyHourForm);
    }
};

const clearFormData = () => {
    happyHourForm.reset();
};

watch(happyHourForm, () => {
    if (happyHourForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

</script>
