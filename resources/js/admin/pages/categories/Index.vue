<template>
    <PageTitle title="Categories" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Categories
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <ExportDropDown
                class="mr-3"
                :allow-csv-export="true"
                :allow-excel-export="true"
                @update:export-csv-file="exportCsvRecord"
                @update:export-excel-file="exportExcelRecord"
            />

            <Link :href="route('admin.categories.create')">
                <PrimaryButton
                    text="Add New Category"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div class="w-1/4 ml-auto">
        <FormInput
            :input-value="state.search"
            type="search"
            placeholder="Search...."
            input-name="search"
            @update:input-value="filterCategories($event)"
        />
    </div>
    
    <div class="w-full grid grid-cols-12 gap-x-2 sm:gap-x-10 md:gap-x-14 mt-6">
        <div class="col-span-12 md:col-span-6 cursor-pointer">
            <ChildCategories
                :categories="state.categories[0]"
            />
        </div>
        <div class="col-span-12 md:col-span-6 cursor-pointer">
            <ChildCategories
                :categories="state.categories[1]"
            />
        </div>
    </div>
</template>

<script setup>
import ChildCategories from '@adminPages/categories/ChildCategories.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive, onMounted } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import ExportDropDown from '@commonComponents/ExportDropDown.vue';
import { exportRecords } from '@commonServices/helper';
import FormInput from '@commonComponents/FormInput.vue';
import { RefreshCw } from 'lucide-vue-next';
import { showSuccessNotification } from '@commonServices/notifier';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    categories: [],
    mainCategories: [],
    search: '',
    disableRefreshButton: props.hasPendingSyncTransaction,
});

const fetchCategories = () => {
    axios.get(route('admin.categories.fetch')).then((response) => {
        state.mainCategories = response.data.data;
        setDataOfCategories(state.mainCategories);
    });
};

const exportCsvRecord = (params) => {
    return exportRecords(
        'export-categories/',
        'categories.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecord = (params) => {
    return exportRecords(
        'export-categories/',
        'categories.xlsx',
        params,
        props.exportPermission
    );
};

const filterCategories = (searchValue) => {
    state.search = searchValue;
    state.categories = [];

    if (searchValue === '') {
        setDataOfCategories(state.mainCategories);
    }

    const categories = searchCategories(state.mainCategories, searchValue);
    setDataOfCategories(categories);
};

const setDataOfCategories = (allCategories) => {
    const sectionsCount = 2;
    const categoriesPerSection = Math.ceil(allCategories.length / sectionsCount);
    for (let i = 0; i < allCategories.length; i += categoriesPerSection) {
        state.categories.push(allCategories.slice(i, i + categoriesPerSection));
    }
};

const searchCategories = (categories, searchText) => {
    let filterCategories = [];

    for (const category of categories) {
        if (category.name.toLowerCase().includes(searchText.toLowerCase())) {
            filterCategories.push(category);
        }

        if (category.children && category.children.length > 0) {
            const childResults = searchCategories(category.children, searchText);
            filterCategories = filterCategories.concat(childResults);
        }
    }

    return filterCategories;
};

onMounted(() => {
    fetchCategories();
});

const syncData = (id, dismiss) => {
    axios.get(route('admin.categories.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};
</script>
