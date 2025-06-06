<template>
    <PageTitle title="Dynamic Menus" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Dynamic Menus
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.dynamic_menus.create')">
                <PrimaryButton
                    text="Add New Menu"
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
            @update:input-value="filterDynamicMenus($event)"
        />
    </div>

    <div class="w-full grid grid-cols-12 gap-x-2 sm:gap-x-10 md:gap-x-14 mt-6">
        <div class="col-span-12 md:col-span-6 cursor-pointer">
            <ChildMenus
                :dynamic-menus="state.dynamicMenus[0]"
                :static-menu-types="staticMenuTypes"
            />
        </div>
        <div class="col-span-12 md:col-span-6 cursor-pointer">
            <ChildMenus
                :dynamic-menus="state.dynamicMenus[1]"
                :static-menu-types="staticMenuTypes"
            />
        </div>
    </div>
</template>

<script setup>
import ChildMenus from "@adminPages/dynamic_menus/ChildMenus.vue";
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive, onMounted } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import FormInput from '@commonComponents/FormInput.vue';

defineProps({
    saleChannels: {
        type: Array,
        required: true,
    },
    staticMenuTypes: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    dynamicMenus: [],
    mainMenus: [],
    search: '',
});

const fetchDynamicMenus = () => {
    axios.get(route('admin.dynamic_menus.fetch')).then((response) => {
        state.mainMenus = response.data.data;
        setDataOfDynamicMenus(state.mainMenus);
    });
};

const filterDynamicMenus = (searchValue) => {
    state.search = searchValue;
    state.dynamicMenus = [];

    if (searchValue === '') {
        setDataOfDynamicMenus(state.mainMenus);
    }

    const dynamicMenus = searchDynamicMenus(state.mainMenus, searchValue);
    setDataOfDynamicMenus(dynamicMenus);
};

const setDataOfDynamicMenus = (allCategories) => {
    const sectionsCount = 2;
    const categoriesPerSection = Math.ceil(allCategories.length / sectionsCount);
    for (let i = 0; i < allCategories.length; i += categoriesPerSection) {
        state.dynamicMenus.push(allCategories.slice(i, i + categoriesPerSection));
    }

};

const searchDynamicMenus = (dynamicMenus, searchText) => {
    let filterDynamicMenus = [];

    for (const menu of dynamicMenus) {
        if (menu.name.toLowerCase().includes(searchText.toLowerCase())) {
            filterDynamicMenus.push(menu);
        }

        if (menu.children && menu.children.length > 0) {
            const childResults = searchDynamicMenus(menu.children, searchText);
            filterDynamicMenus = filterDynamicMenus.concat(childResults);
        }
    }

    return filterDynamicMenus;
};

onMounted(() => {
    fetchDynamicMenus();
});
</script>
