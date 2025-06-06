<template>
    <PageTitle :title="department ? 'Edit Department' : 'Add Department'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Departments
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="department">Edit Department</span>
                        <span v-else>Add Department</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form
                    @submit.prevent="saveDepartment();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-3 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="departmentForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-3 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="departmentForm.code"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6">
                                <div class="mt-3">
                                    <Tabs
                                        :records="state.discountTypes"
                                        :selected-record="departmentForm.discount_type"
                                        :required="true"
                                        input-label="Commission Type"
                                        return-selected-record="id"
                                        class="max-w-full"
                                        @update:selected-record="updateDiscountType"
                                    >
                                        <TabPanel
                                            :v-if="departmentForm.discount_type === discountTypes.percentage"
                                        >
                                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                                <FormInput
                                                    v-if="company.commission_type_id === commissionTypes.by_department"
                                                    v-model:input-value="departmentForm.commission_percentage"
                                                    type="number"
                                                    input-name="commission_percentage"
                                                    input-label="Promoter Commission Percentage"
                                                    input-group-suffix="%"
                                                    title="Promoter will receive this percent of item amount as commission."
                                                />
                                            </div>
                                        </TabPanel>

                                        <TabPanel
                                            :v-if="departmentForm.discount_type === discountTypes.flat"
                                        >
                                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
                                                <FormInput
                                                    v-if="company.commission_type_id === commissionTypes.by_department"
                                                    v-model:input-value="departmentForm.flat_commission"
                                                    type="number"
                                                    input-name="flat_commission"
                                                    input-label="Promoter Flat Commission"
                                                    :input-group-prefix="currencySymbol"
                                                    title="Promoter will receive this flat amount of item amount as commission."
                                                />
                                            </div>
                                        </TabPanel>
                                    </Tabs>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.departments.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="department ? 'Update' : 'Submit'"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { computed, onMounted, reactive, watch } from 'vue';
import { route } from 'ziggy';
import Tabs from '@commonComponents/Tabs.vue';
import { TabPanel } from '@headlessui/vue';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
    commissionTypes: {
        type: Object,
        default: null
    },

    discountTypes: {
        type: Object,
        default: null
    },

    department: {
        type: Object,
        default: null,
    },

    company: {
        type: Object,
        default: null,
    }
});

const state = reactive({
    discountTypes: [
        { id: props.discountTypes.percentage, name: 'Percentage' },
        { id: props.discountTypes.flat, name: 'Flat' },
    ],
});

const updateDiscountType = (discountType) => {
    departmentForm.discount_type = discountType;
};

const departmentForm = useForm({
    name: null,
    code: null,
    commission_percentage: null,
    flat_commission: null,
    discount_type: props.discountTypes.percentage,
    watchEnabled: true,
});

const saveDepartment = () => {
    departmentForm.watchEnabled = false;
    removeLocalStorage('department');

    if (props.department) {
        departmentForm.put(route('admin.departments.update', props.department.id));
        return;
    }

    departmentForm.post(route('admin.departments.store'));
};

onMounted(() => {
    if (props.department) {
        removeLocalStorage('department');
        Object.assign(departmentForm, props.department);
    } else {
        setLocalStorage('department', departmentForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.department) {
        saveLocalStorage('department', departmentForm);
    }
};

const clearFormData = () => {
    departmentForm.reset();
};

watch(departmentForm, () => {
    if (departmentForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });
</script>
