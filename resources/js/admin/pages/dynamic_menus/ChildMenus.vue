<template v-if="dynamicMenus">
    <template
        v-for="(menu, index) in dynamicMenus"
        :key="index"
    >
        <template v-if="menu.children.length">
            <div class="py-1">
                <div
                    class="rounded-lg bg-white mt-2"
                    :class="{
                        'pl-4 pr-3 py-2 mt-0' : menu.parent_id === null
                    }"
                >
                    <div
                        class="flex flex-row items-center justify-between rounded-lg"
                        :class="{
                            'bg-slate-100 pl-4 pr-3 py-2' : menu.parent_id !== null,
                            'bg-white': menu.parent_id === null
                        }"
                        @click="toggleChildCategories(menu)"
                    >
                        <div
                            class="flex flex-row items-center"
                        >
                            <p class="font-medium text-base text-gray-900">
                                {{ menu.title }} ({{ menu.children.length }})
                            </p>
                        </div>
                        <div
                            class="flex flex-row text-primary"
                        >
                            <Link
                                v-if="menu.type !== staticMenuTypes.static_page"
                                :href="route('admin.dynamic_menus.create', menu.id)"
                            >
                                <Plus class="w-5 h-5" />
                            </Link>
                            <Link
                                :href="route('admin.dynamic_menus.edit', menu.id)"
                                class="ml-4 mr-4"
                            >
                                <Edit class="w-5 h-5" />
                            </Link>
                            <ChevronDown
                                v-if="!menu.is_open"
                                class="w-6 h-6 text-gray-900"
                            />
                            <ChevronUp
                                v-if="menu.is_open"
                                class="folder-arrow"
                            />
                        </div>
                    </div>
                    <div
                        :class="{ 'hidden': !menu.is_open }"
                        class="edit-folder-wrapper transition duration-500 px-2"
                    >
                        <ChildMenus
                            :dynamic-menus="menu.children"
                            :static-menu-types="staticMenuTypes"
                        />
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="py-1">
                <div
                    class="flex flex-row items-center justify-between px-4 py-2 rounded-lg mt-2"
                    :class="{
                        'bg-slate-100' : menu.parent_id !== null,
                        'bg-white': menu.parent_id === null,
                    }"
                >
                    <div class="flex flex-row items-center">
                        <p class="font-medium text-base text-black-60">
                            {{ menu.title }}
                        </p>
                    </div>

                    <div
                        class="flex flex-row text-primary"
                    >
                        <Link
                            v-if="menu.type !== '4'"
                            :href="route('admin.dynamic_menus.create', menu.id)"
                        >
                            <Plus class="w-5 h-5" />
                        </Link>

                        <Link
                            :href="route('admin.dynamic_menus.edit', menu.id)"
                            class="ml-4 mr-4"
                        >
                            <Edit class="w-5 h-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </template>
    </template>
</template>

<script setup>
import ChildMenus from "@adminPages/dynamic_menus/ChildMenus.vue";
import { ChevronUp, Plus, Edit, ChevronDown } from 'lucide-vue-next';
import { route } from 'ziggy';

defineProps({
    dynamicMenus: {
        type: Array,
        default: null,
    },
    staticMenuTypes: {
        type: Array,
        required: true,
    },
});

const toggleChildCategories = (menu) => {
    menu.is_open = !menu.is_open;
};
</script>
