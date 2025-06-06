<template>
    <div class="relative ml-0 mr-auto md:mr-0 md:ml-auto w-full md:w-auto">
        <div class="relative mr-3 intro-x sm:mr-6">
            <div class="relative block">
                <input
                    ref="searchBoxRef"
                    v-model="state.searchInput"
                    type="text"
                    class="border-transparent w-full md:w-56 shadow-none rounded bg-slate-200 pr-8 transition-[width] duration-300 ease-in-out focus:border-transparent focus:w-full md:focus:w-72 dark:bg-darkmode-400 pl-8"
                    placeholder="Search menu..."
                    @focus="showSearchDropdown"
                    @keyup="buildFilteredNavigation"
                >

                <span
                    v-if="!state.searchDropdown"
                    class="absolute inset-y-0 right-0 h-6 pt-1 pb-1 my-auto mr-5 text-slate-600 dark:text-slate-500"
                >
                    <kbd class="flex gap-3 font-sans font-semibold hover:text-primary-50 text-slate-300 dark:text-slate-500">
                        <Tippy
                            tag="abbr"
                            content="Control"
                            class="text-sm font-normal no-underline text-slate-400 dark:text-slate-500"
                        >
                            Ctrl K
                        </Tippy>
                    </kbd>
                </span>

                <span
                    v-else
                    class="absolute inset-y-0 right-0 h-6 pt-1 pb-1 my-auto mr-5 text-slate-600 dark:text-slate-500"
                >
                    <p>Esc</p>
                </span>

                <Search
                    class="absolute inset-y-0 left-3 w-4 h-4 my-auto text-slate-500"
                />
            </div>
        </div>

        <div
            class="search-result"
            :class="{ show: state.searchDropdown }"
        >
            <div
                v-if="searchIsEmpty"
                class="search-result__content"
            >
                <span class="flex items-center">
                    Type something to start searching.
                </span>
            </div>

            <div
                v-else
                class="search-result__content"
            >
                <div class="search-result__content__title">
                    Search Results
                </div>

                <div
                    v-if="state.filtered.length"
                    ref="searchBoxResultRef"
                    class="mb-1"
                >
                    <a
                        v-for="(result, index) in state.filtered"
                        :key="'search-result-'+ index"
                        :href="route(result.route)"
                        class="group flex items-center p-1 rounded-md hover:bg-slate-100 hover:font-medium focus:bg-slate-100 focus:font-medium hover:text-primary focus:text-primary"
                        @click.prevent="redirectToRoute(result)"
                    >
                        <div
                            class="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 dark:bg-primary/20 text-primary/80 group-hover:bg-primary/30 group-focus:bg-primary/30 bg-gray-200"
                        >
                            <component
                                :is="menuIcons[result.icon]"
                                class="w-4 h-4 group-hover:text-primary group-focus:text-primary"
                            />
                        </div>
                        <div class="ml-3">
                            {{ result.parent }}
                            <ChevronRight
                                class="w-5 h-5 text-slate-500 inline-block"
                            />
                            {{ result.text }}
                        </div>
                    </a>
                    <span
                        v-if="state.tooMany"
                        class="flex items-center mt-5"
                    >
                        Too many results. Please type more.
                    </span>
                </div>

                <div
                    v-else
                    class="flex items-center mt-5"
                >
                    No result found. Please type correct word.
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy';
import { onMounted, reactive, computed, ref, onBeforeUnmount } from 'vue';
import { menuIcons } from '@commonServices/menuIcons';
import { Search, ChevronRight } from 'lucide-vue-next';
import { filterMenusByPermissions } from '@commonServices/helper.js';

const searchIsEmpty = computed(() => state.searchInput === '');

const props = defineProps({
    menu: {
        type: Object,
        default: null,
    },
});

const state = reactive({
    tooMany: false,
    searchDropdown: false,
    searchInput: '',
    menus: [],
    filtered: [],
    resultLimit: 10,
    currentFocussedElement: null,
});

const searchBoxRef = ref(null);
const searchBoxResultRef = ref(null);

const buildFilteredNavigation = () => {
    state.filtered = [];
    state.tooMany = false;
    let menus = state.menus;

    if (!state.searchIsEmpty) {
        menus = state.menus.filter((item) => {
            return item.text.toLowerCase().includes(state.searchInput.toLowerCase());
        });
    }

    if (menus.length >= state.resultLimit) {
        state.tooMany = true;
        state.filtered = menus.slice(0, state.resultLimit);
        return;
    }

    state.filtered = menus;
};

const showSearchDropdown = () => {
    searchBoxRef.value.focus();
    state.currentFocussedElement = null;
    state.searchDropdown = true;
};

const hideSearchDropdown = () => {
    state.searchDropdown = false;
    searchBoxRef.value.blur();
};

const flattenNavigation = (array, title) => {
    if (!array) return;
    let menus = [];
    array.forEach((item) => {
        if (item.route_name !== '') {
            menus.push({
                text: item.title,
                route: item.route_name,
                icon: item.icon,
                permission: item.permission,
                parent: title,
            });
        }

        if (Array.isArray(item.subMenu)) {
            menus = menus.concat(flattenNavigation(item.subMenu, item.title));
        }

        if (Array.isArray(item.subSubMenu)) {
            menus = menus.concat(flattenNavigation(item.subSubMenu, item.title));
        }

        if (Array.isArray(item.subSubSubMenu)) {
            menus = menus.concat(flattenNavigation(item.subSubSubMenu, item.title));
        }
    });

    return filterMenusByPermissions(menus);
};

const redirectToRoute = (menu) => {
    state.searchInput = '';
    if (menu.route) {
        router.get(route(menu.route));
        hideSearchDropdown();
    }
};

const focusSearchBox = (event) => {
    const hasCtrlK = event.ctrlKey && event.key === 'k';
    const isEscape = event.key === 'Escape';
    const isArrowDown = event.key === 'ArrowDown';
    const isArrowUp = event.key === 'ArrowUp';
    const isEnter = event.key === 'Enter';

    if (hasCtrlK) {
        if (state.searchDropdown) {
            hideSearchDropdown();
        } else {
            showSearchDropdown();
        }
        event.preventDefault();
        return;
    }

    if (isEscape) {
        hideSearchDropdown();
        state.searchInput = '';
        return;
    }

    if (!state.searchInput || !searchBoxResultRef.value) {
        return;
    }

    const results = searchBoxResultRef.value.querySelectorAll('a');
    const hasResults = results.length > 0;

    if (hasResults && (isArrowDown || isArrowUp)) {
        event.preventDefault();

        if (state.currentFocussedElement === null) {
            state.currentFocussedElement = isArrowDown ? 0 : results.length - 1;
        } else {
            state.currentFocussedElement = isArrowDown
                ? Math.min(results.length - 1, state.currentFocussedElement + 1)
                : Math.max(0, state.currentFocussedElement - 1);
        }

        results[state.currentFocussedElement].focus();
        return;
    }

    if (isEnter && state.searchDropdown) {
        hideSearchDropdown();
    }
};

const handleOutsideClick = (event) => {
    if (
        state.searchDropdown &&
        searchBoxRef.value &&
        !searchBoxRef.value.contains(event.target)
    ) {
        hideSearchDropdown();
    }
};

onMounted(() => {
    state.menus = flattenNavigation(props.menu, props.title);
    // Listen for clicks outside the search box
    document.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', focusSearchBox);
});

onBeforeUnmount(() => {
    // Remove event listeners when the component is unmounted
    document.removeEventListener('click', handleOutsideClick);
    document.removeEventListener('keydown', focusSearchBox);
});

</script>
