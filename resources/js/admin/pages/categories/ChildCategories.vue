<template v-if="categories">
    <template
        v-for="(category, index) in categories"
        :key="index"
    >
        <template v-if="category.children.length">
            <div class="py-1">
                <div
                    class="rounded-lg bg-white mt-2"
                    :class="{
                        'pl-4 pr-3 py-2 mt-0' : category.parent_category_id === null
                    }"
                >
                    <div
                        class="flex flex-row items-center justify-between rounded-lg"
                        :class="{
                            'bg-slate-100 pl-4 pr-3 py-2' : category.parent_category_id !== null,
                            'bg-white': category.parent_category_id === null
                        }"
                        @click="toggleChildCategories(category)"
                    >
                        <div
                            class="flex flex-row items-center"
                        >
                            <p class="font-medium text-base text-gray-900">
                                {{ category.name }} ({{ category.children.length }})
                            </p>
                        </div>
                        <div
                            class="flex flex-row text-primary"
                        >
                            <Link :href="route('admin.categories.create', category.id)">
                                <Plus class="w-5 h-5" />
                            </Link>
                            <Link
                                :href="route('admin.categories.edit', category.id)"
                                class="ml-4 mr-4"
                            >
                                <Edit class="w-5 h-5" />
                            </Link>
                            <ChevronDown
                                v-if="!category.is_open"
                                class="w-6 h-6 text-gray-900"
                            />
                            <ChevronUp
                                v-if="category.is_open"
                                class="folder-arrow"
                            />
                        </div>
                    </div>
                    <div
                        :class="{ 'hidden': !category.is_open }"
                        class="edit-folder-wrapper transition duration-500 px-2"
                    >
                        <ChildCategories
                            :categories="category.children"
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
                        'bg-slate-100' : category.parent_category_id !== null,
                        'bg-white': category.parent_category_id === null,
                    }"
                >
                    <div class="flex flex-row items-center">
                        <p class="font-medium text-base text-black-60">
                            {{ category.name }}
                        </p>
                    </div>

                    <div
                        class="flex flex-row text-primary"
                    >
                        <Link :href="route('admin.categories.create', category.id)">
                            <Plus class="w-5 h-5" />
                        </Link>

                        <Link
                            :href="route('admin.categories.edit', category.id)"
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
import ChildCategories from '@adminPages/categories/ChildCategories.vue';
import { ChevronUp, Plus, Edit, ChevronDown } from 'lucide-vue-next';
import { route } from 'ziggy';

defineProps({
    categories: {
        type: Array,
        default: null,
    },
});

const toggleChildCategories = (category) => {
    category.is_open = !category.is_open;
};
</script>
