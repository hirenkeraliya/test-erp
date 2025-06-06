<template>
    <Dropdown
        class="intro-x mr-4 sm:mr-4"
    >
        <DropdownToggle
            tag="div"
            role="button"
            class="notification cursor-pointer"
        >
            <History
                class="notification__icon text-white"
            />
        </DropdownToggle>

        <DropdownMenu class="notification-content notification-content-transform pt-[18px]">
            <DropdownContent
                tag="div"
                class="notification-content__box"
            >
                <div class="notification-content__title w-full text-base font-medium bg-slate-100 p-1 text-dark rounded-md text-center flex items-center justify-center">
                    Recently Visited Pages
                </div>

                <div
                    v-if="state.recentlyVisitedPages.length"
                    :class="state.recentlyVisitedPages.length > 3 ? 'overflow-y-scroll h-72' : ''"
                    class="mb-4 pr-3"
                >
                    <div
                        v-for="(page, index) in state.recentlyVisitedPages"
                        :key="index"
                        class="cursor-pointer relative"
                        :class="{ index }"
                    >
                        <div class="flex items-start group relative w-full py-1 hover:bg-slate-50 rounded-lg">
                            <div class="flex-none flex items-center justify-center text-primary w-8 h-8 rounded-full">
                                <CornerDownRight class="w-4 h-4" />
                            </div>

                            <div class="w-full">
                                <a
                                    class="flex items-center justify-between mb-0.5 text-sm text-primary font-medium mt-2"
                                    :href="page.url"
                                >
                                    {{ page.title }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <p class="text-sm text-center text-slate-700 mb-3">
                        No Last Visited Pages at this time.
                    </p>
                </div>
            </DropdownContent>
        </DropdownMenu>
    </Dropdown>
</template>

<script setup>
import { History, CornerDownRight } from 'lucide-vue-next';
import { reactive } from 'vue';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
} from '@commonVendor/dropdown';

const props = defineProps({
    localStorageName: {
        type: String,
        required: true
    },
});

const state = reactive({
    recentlyVisitedPages: [],
});

const refreshInterval = 2000;

setInterval(() => {
    const pages = JSON.parse(localStorage.getItem(props.localStorageName));
    pages.reverse();

    state.recentlyVisitedPages = pages;
}, refreshInterval);
</script>
