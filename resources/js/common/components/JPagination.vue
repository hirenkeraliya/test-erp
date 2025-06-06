<template>
    <nav class="w-full sm:w-auto">
        <ul class="pagination">
            <li class="page-item">
                <button
                    type="button"
                    class="page-link"
                    :disabled="currentPage <= 1"
                    @click="gotoPage(currentPage - 1)"
                >
                    <ChevronLeft class="w-4 h-4" />
                </button>
            </li>

            <li
                v-for="(pageNumber, key) in getPageNumbers()"
                :key="'pagination-link-' + key"
                class="page-item"
                :class="{ 'active': currentPage === pageNumber }"
                @click="gotoPage(pageNumber)"
            >
                <button
                    type="button"
                    class="page-link"
                    :disabled="currentPage == pageNumber"
                >
                    {{ pageNumber }}
                </button>
            </li>

            <li class="page-item">
                <button
                    type="button"
                    class="page-link"
                    :disabled="Math.ceil(totalRecords / perPage) <= currentPage"
                    @click="gotoPage(currentPage + 1)"
                >
                    <ChevronRight class="w-4 h-4" />
                </button>
            </li>
        </ul>
    </nav>
</template>

<script setup>
import {
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    currentPage: {
        type: Number,
        default: 1
    },
    perPage: {
        type: Number,
        default: 15
    },
    totalRecords: {
        type: Number,
        default: 0
    },
});

const emits = defineEmits([
    'update:current-page'
]);

const getPageNumbers = () => {
    const pages = [];
    const totalPages = Math.ceil(props.totalRecords / props.perPage);
    const rangeOffset = 3;

    let startPages = props.currentPage - rangeOffset <= 0 ? 1 : props.currentPage - rangeOffset;
    const endPages = props.currentPage + rangeOffset > totalPages ? totalPages : props.currentPage + rangeOffset;

    for (startPages; startPages <= endPages; startPages++) {
        pages.push(startPages);
    }

    return pages;
};

const gotoPage = (pageNumber) => {
    emits('update:current-page', pageNumber);
};
</script>
