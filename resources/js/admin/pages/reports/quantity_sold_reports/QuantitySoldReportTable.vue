<template>
    <div class="intro-y col-span-12 overflow-auto">
        <table :class="tableClasses">
            <thead>
                <tr>
                    <th
                        v-for="column in columns"
                        :key="'header-' + column.key"
                        :class="column.headerClass ?? 'text-center whitespace-nowrap'"
                    >
                        <div
                            :class="column.sortable ? 'flex cursor-pointer' : ''"
                            @click="column.sortable ? sortRecords(column) : ''"
                        >
                            <div :class="column.sortable ? 'text-left mr-auto inline-block' : ''">
                                {{ prepareColumnLabel(column) }}
                            </div>

                            <div
                                v-if=" column.sortable && records.length !== 0"
                                class="text-right ml-auto inline-block"
                                :class="column.key === sortBy ? 'text-gray-900' : 'text-gray-400'"
                            >
                                <ChevronUp
                                    v-if="sortDirection === 'asc' && column.key === sortBy"
                                    class="w-4 h-4"
                                />

                                <ChevronDown
                                    v-else
                                    class="w-4 h-4"
                                />
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody v-if="isDataFetching">
                <tr
                    v-for="n in perPage"
                    :key="'loading-table-content-' + n"
                >
                    <td
                        :colspan="columns.length"
                        class="cp"
                    >
                        <div class="animated-background" />
                    </td>
                </tr>
            </tbody>
            <tbody v-else>
                <tr
                    v-for="(record, index) in filteredRecords"
                    :key="'record-' + record.id"
                    :class="rowClasses"
                >
                    <td
                        v-for="column in columns"
                        :key="'body-' + column.key"
                        :class="column.bodyClass ?? ''"
                    >
                        <slot
                            v-if="!column.hidden"
                            :name="`${column.key}`"
                            :item="record"
                            :index="currentPage === 1 ?
                                index :
                                (index + (perPage * (currentPage -1)))"
                        >
                            {{ record[column.key] }}
                        </slot>
                    </td>
                </tr>

                <tr
                    v-if="records.length === 0"
                    class="intro-x"
                >
                    <td
                        :colspan="columns.length"
                        class="w-40 text-center"
                    >
                        There are no records to show.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    ChevronUp,
    ChevronDown
} from 'lucide-vue-next';

const props = defineProps({
    records: {
        type: Array,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    rowClasses: {
        type: String,
        default: 'intro-x'
    },
    tableClasses: {
        type: String,
        default: 'table table-report -mt-2'
    },
    sortDirection: {
        type: String,
        default: 'asc'
    },
    sortBy: {
        type: String,
        default: 'id'
    },
    currentPage: {
        type: Number,
        default: 1
    },
    perPage: {
        type: Number,
        default: 10
    },
    isDataFetching: {
        type: Boolean,
        default: false
    }
});

const filteredRecords = computed(() => {
    const records = props.records;
    return records;
});

const emits = defineEmits([
    'update:sort-by'
]);

const sortRecords = (column) => {
    emits('update:sort-by', column.key);
};

const prepareColumnLabel = (column) => {
    if (column.label) {
        return column.label;
    }

    return column.key.split('_')
        .map((word) => {
            return word[0].toUpperCase() + word.substr(1).toLowerCase();
        }).join(' ');
};

</script>
