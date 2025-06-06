<template>
    <h2 class="font-medium text-base">
        Collection Filter Types
    </h2>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6"
    >
        <div :class="['input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 w-60', staticDetails.attribute === tier.filter_type_id ? 'xl:col-span-2' : 'xl:col-span-3']">
            <FormSelectBox
                v-model:selected-record="tier.filter_type_id"
                :records="state.filterTypes"
                input-label="Filter Type"
                :validation-field-name="'collection_filter_types.' + index + '.filter_type_id'"
                :required="true"
                @update:selected-record="updateTierValueDetails($event, index, 'filter_type_id')"
            />
        </div>

        <div
            v-if="staticDetails.attribute === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 w-100 ml-10"
        >
            <div
                v-for="(attributeTier, index2) in tier.attributes"
                :key="index2"
                class="grid grid-cols-12 gap-0 sm:gap-6"
            >
                <div
                    class="col-span-3 w-60"
                >
                    <FormSelectBox
                        v-model:selected-record="attributeTier.attribute"
                        :records="attributes"
                        input-label="Attribute"
                        :validation-field-name="'collection_filter_types.' + index + '.attribute.'+ index2 + '.attribute_id'"
                        :required="true"
                        @update:selected-record="selectAttribute($event, index, index2, 'attributes')"
                    />
                </div>

                <div class="col-span-3 w-60">
                    <JMultiSelect
                        v-model:selected-records="attributeTier.attribute_selected_values"
                        :records="attributeTier.attribute_values"
                        input-label="Values"
                        label-class="block"
                        :validation-field-name="'collection_filter_types.' + index + '.attribute.'+ index2 + '.attribute_values'"
                        :required="true"
                        @update:selected-records="selectAttributeValues($event, index, index2, 'attributes')"
                    />
                </div>
                <div
                    v-if="index2 == 0"
                    class="col-span-3 w-60 mt-8"
                >
                    <OutlinePrimaryButton
                        text="+ Add New"
                        type="button"
                        class="border-dashed w-full"
                        @click="addNewAttributes(index, 'attributes')"
                    />
                </div>
                <div
                    v-else
                    class="col-span-3 w-60 mt-8"
                >
                    <DeleteButton
                        type="button"
                        class="w-12 h-4"
                        @click="removeAttributes(index2, index,'attributes')"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="staticDetails.name === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormInput
                class="mr-1"
                type="text"
                input-label="Product Name"
                :input-value="tier.name"
                :validation-field-name="'collection_filter_types.' + index + '.name'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'name')"
            />
        </div>

        <div
            v-if="staticDetails.created_by === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JDatePicker
                v-model:input-value="tier.created_by"
                input-label="Created By"
                :validation-field-name="'collection_filter_types.' + index + '.created_by'"
                :required="true"
                label-class="text-primary-p3"
                @update:input-value="updateTierValueDetails($event, index, 'created_by')"
            />
        </div>

        <div
            v-if="staticDetails.price === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormInput
                class="mr-1"
                type="number"
                input-label="Enter Price"
                :input-value="tier.price"
                :validation-field-name="'collection_filter_types.' + index + '.price'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'price')"
            />
        </div>

        <div
            v-if="staticDetails.category === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.categories"
                :records="categories"
                input-label="Categories"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.category_ids'"
                :required="true"
                @update:selected-records="selectCategories($event, index, 'categories')"
            />
        </div>

        <div
            v-if="staticDetails.season === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.seasons"
                :records="seasons"
                input-label="Seasons"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.season_ids'"
                :required="true"
                @update:selected-records="selectSeasons($event, index, 'seasons')"
            />
        </div>

        <div
            v-if="staticDetails.department === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.departments"
                :records="departments"
                input-label="Departments"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.department_ids'"
                :required="true"
                @update:selected-records="selectDepartments($event, index, 'departments')"
            />
        </div>

        <div
            v-if="staticDetails.color === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.colors"
                :records="colors"
                input-label="Colors"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.color_ids'"
                :required="true"
                @update:selected-records="selectColors($event, index, 'colors')"
            />
        </div>

        <div
            v-if="staticDetails.size === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.sizes"
                :records="sizes"
                input-label="Sizes"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.size_ids'"
                :required="true"
                @update:selected-records="selectSizes($event, index, 'sizes')"
            />
        </div>

        <div
            v-if="staticDetails.brand === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.brands"
                :records="brands"
                input-label="Brands"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.brand_ids'"
                :required="true"
                @update:selected-records="selectBrands($event, index, 'brands')"
            />
        </div>

        <div
            v-if="staticDetails.style === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.styles"
                :records="styles"
                input-label="Styles"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.style_ids'"
                :required="true"
                @update:selected-records="selectStyles($event, index, 'styles')"
            />
        </div>

        <div
            v-if="staticDetails.tags === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.tags"
                :records="tags"
                input-label="Tags"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.tag_ids'"
                :required="true"
                @update:selected-records="selectTags($event, index, 'tags')"
            />
        </div>

        <div
            v-if="staticDetails.type === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <JMultiSelect
                :selected-records="tier.types"
                :records="types"
                input-label="Types"
                label-class="block"
                :validation-field-name="'collection_filter_types.' + index + '.type_ids'"
                :required="true"
                @update:selected-records="selectTypes($event, index, 'types')"
            />
        </div>

        <div
            v-if="staticDetails.is_available_in_pos === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormSelectBox
                v-model:selected-record="tier.is_available_in_pos"
                :records="state.isAvailableOption"
                input-label="Is Available"
                :validation-field-name="'collection_filter_types.' + index + '.is_available_in_pos'"
                :required="true"
                @update:selected-record="updateTierValueDetails($event, index, 'is_available_in_pos')"
            />
        </div>

        <div
            v-if="staticDetails.sale_unit_sold === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormInput
                class="mr-1"
                type="number"
                input-label="Enter Sold Unit"
                title="We are consider unit sold by Sale Quantity minus Sale Return Quantity"
                :input-value="tier.sale_unit_sold"
                :validation-field-name="'collection_filter_types.' + index + '.sale_unit_sold'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'sale_unit_sold')"
            />
        </div>

        <div
            v-if="staticDetails.sale_amount === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormInput
                class="mr-1"
                type="number"
                input-label="Enter Sale"
                title="We are consider sale price by of Sale minus Sale Return"
                :input-value="tier.sale_amount"
                :validation-field-name="'collection_filter_types.' + index + '.sale_amount'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'sale_amount')"
            />
        </div>

        <div
            v-if="staticDetails.order_unit_sold === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormInput
                class="mr-1"
                type="number"
                input-label="Enter Order Unit"
                title="We are consider unit sold by Order Quantity minus Order Return Quantity"
                :input-value="tier.order_unit_sold"
                :validation-field-name="'collection_filter_types.' + index + '.order_unit_sold'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'order_unit_sold')"
            />
        </div>

        <div
            v-if="staticDetails.order_amount === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormInput
                class="mr-1"
                type="number"
                input-label="Enter Order"
                title="We are consider order price by of Order minus Order Return"
                :input-value="tier.order_amount"
                :validation-field-name="'collection_filter_types.' + index + '.order_amount'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'order_amount')"
            />
        </div>

        <div
            v-if="staticDetails.is_available_in_ecommerce === tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        >
            <FormSelectBox
                v-model:selected-record="tier.is_available_in_ecommerce"
                :records="state.isAvailableOption"
                input-label="Is Available"
                :validation-field-name="'collection_filter_types.' + index + '.is_available_in_ecommerce'"
                :required="true"
                @update:selected-record="updateTierValueDetails($event, index, 'is_available_in_ecommerce')"
            />
        </div>

        <div
            v-if="!tier.filter_type_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
        />

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60">
            <FormSelectBox
                v-if="tier.filter_type_id && (staticDetails.name === tier.filter_type_id || staticDetails.price === tier.filter_type_id || staticDetails.created_by === tier.filter_type_id || staticDetails.sale_unit_sold === tier.filter_type_id || staticDetails.sale_amount === tier.filter_type_id || staticDetails.order_unit_sold === tier.filter_type_id || staticDetails.order_amount === tier.filter_type_id)"
                v-model:selected-record="tier.condition_operator_id"
                :records="getConditionOperatorTypes(tier.filter_type_id)"
                input-label="Condition Operator"
                :validation-field-name="'collection_filter_types.' + index + '.condition_operator_id'"
                :required="true"
                @update:selected-record="updateTierValueDetails($event, index, 'condition_operator_id')"
            />

            <div
                v-if="tier.filter_type_id && (staticDetails.name !== tier.filter_type_id && staticDetails.price !== tier.filter_type_id && staticDetails.created_by !== tier.filter_type_id && staticDetails.sale_unit_sold !== tier.filter_type_id && staticDetails.sale_amount !== tier.filter_type_id && staticDetails.order_unit_sold !== tier.filter_type_id && staticDetails.order_amount !== tier.filter_type_id)"
                class="mt-3"
            >
                <div class="input-group">
                    <label>
                        Condition Operator:
                    </label>
                </div>
                <div class="font-medium mt-2">
                    N/A
                </div>
            </div>

            <div
                v-else
                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 w-60"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 p-5 pb-0 px-0">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
            <OutlinePrimaryButton
                text="+ Add New Tier"
                type="button"
                class="border-dashed w-full"
                @click="addNewTierDetails()"
            />
        </div>
    </div>
</template>
<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { reactive, onMounted } from 'vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';

const props = defineProps({
    tiers: {
        type: Object,
        required: true,
    },
    filterTypes: {
        type: Array,
        required: true,
    },
    conditionOperatorTypes: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        required: true,
    },
    seasons: {
        type: Object,
        default: () => {},
    },
    departments: {
        type: Object,
        required: true,
    },
    colors: {
        type: Object,
        default: () => {},
    },
    sizes: {
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
    tags: {
        type: Object,
        required: true,
    },
    types: {
        type: Object,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => {},
    },
});

const state = reactive({
    filterTypes: [],
    isAvailableOption: [
        {
            id: '1',
            name: 'Yes',
        },
        {
            id: '0',
            name: 'No',
        },
    ],
    values: [],
    attributes: [],
    attributeTiers: [
        {
            attribute: null,
            attribute_selected_values: [],
            attribute_values: [],
        }
    ]
});

const selectCategories = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectAttribute = (event, itemIndex, subIndex, columnName) => {
    if (!event) {
        return;
    }

    let attribute = props.attributes.find(attribute => attribute.id === event);
    let attributeOptions = Object.values(attribute.options).map(value => ({
        id: value,
        name: value
    }));

    emits('update:attribute-tier-details', {
        parent_key: itemIndex,
        key: subIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
        attribute_options: attributeOptions
    });
};

const selectAttributeValues = (event, itemIndex, subIndex, columnName) => {
    emits('update:attribute-tier-value-details', {
        parent_key: itemIndex,
        key: subIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectSeasons = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectDepartments = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectColors = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectSizes = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectBrands = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectStyles = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectTags = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const selectTypes = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: JSON.parse(JSON.stringify(event)),
        column_name: columnName,
    });
};

const emits = defineEmits([
    'update:tier-value-details',
    'add:new-tier-details',
    'add:new-attribute-tier-details',
    'remove:tier-details-of',
    'remove:attribute-tier-details-of',
    'update:attribute-tier-details',
    'update:attribute-tier-value-details',
]);

const updateTierValueDetails = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: event,
        column_name: columnName,
    });
};

const addNewTierDetails = () => {
    emits('add:new-tier-details');
};

const addNewAttributes = (index, columnName) => {
    emits('add:new-attribute-tier-details', index, columnName);
};

const removeAttributes = (index2, index, columnName) => {
    state.attributeTiers.splice(index2, 1);
    emits('remove:attribute-tier-details-of', index2, index, columnName);
};

const removeTierDetailsOf = (index) => {
    emits('remove:tier-details-of', index);
};

onMounted(() => {
    state.filterTypes = props.filterTypes;
});

const getConditionOperatorTypes = (filterTypeId) => {
    const conditionOperatorTypes = [];

    if (filterTypeId === props.staticDetails.name || filterTypeId === props.staticDetails.category || filterTypeId === props.staticDetails.season || filterTypeId === props.staticDetails.department || filterTypeId === props.staticDetails.color || filterTypeId === props.staticDetails.size || filterTypeId === props.staticDetails.brand || filterTypeId === props.staticDetails.style || filterTypeId === props.staticDetails.tag
    ) {
        conditionOperatorTypes.push(props.conditionOperatorTypes.contains);
        conditionOperatorTypes.push(props.conditionOperatorTypes.equal);
        return conditionOperatorTypes;
    }

    if (filterTypeId === props.staticDetails.price || filterTypeId === props.staticDetails.created_by || filterTypeId === props.staticDetails.sale_unit_sold || filterTypeId === props.staticDetails.sale_amount || filterTypeId === props.staticDetails.order_unit_sold || filterTypeId === props.staticDetails.order_amount) {
        conditionOperatorTypes.push(props.conditionOperatorTypes.lessThan);
        conditionOperatorTypes.push(props.conditionOperatorTypes.greaterThan);
        conditionOperatorTypes.push(props.conditionOperatorTypes.equal);
        return conditionOperatorTypes;
    }

    return conditionOperatorTypes;
};
</script>
