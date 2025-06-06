<template>
    <PageTitle :title="productCollection ? 'Edit Collection' : 'Add Collection'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Product Collection
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="productCollection">Edit Collection</span>
                        <span v-else>Add Collection</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveProductCollection();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="productCollectionForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="productCollectionForm.logical_connector_type_id"
                                    :records="logicalConnectorTypes"
                                    input-label="Logical Connector Type"
                                    validation-field-name="logical_connector_type_id"
                                    :required="true"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    input-label="Available Online"
                                    :is-checked="productCollectionForm.is_available_in_ecommerce"
                                    class="mt-3"
                                    @update:is-checked="updateIsAvailableInEcommerce"
                                />

                                <div
                                    v-if="productCollectionForm.is_available_in_ecommerce"
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                >
                                    <JMultiSelect
                                        v-model:selected-records="productCollectionForm.sale_channels"
                                        :records="saleChannels"
                                        input-label="Sale Channels"
                                        :required="true"
                                        validation-field-name="sale_channel_ids"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60">
                            <div>
                                <CollectionFilterTiers
                                    :tiers="productCollectionForm.collection_filter_types"
                                    :filter-types="filterTypes"
                                    :condition-operator-types="conditionOperatorTypes"
                                    :static-details="staticDetails"
                                    :categories="categories"
                                    :seasons="seasons"
                                    :departments="departments"
                                    :colors="colors"
                                    :sizes="sizes"
                                    :brands="brands"
                                    :styles="styles"
                                    :tags="tags"
                                    :types="types"
                                    :attributes="attributes"
                                    @update:column-details="updateColumnDetails"
                                    @update:tier-value-details="updateTierValueDetails"
                                    @update:attribute-tier-details="updateAttributeTierDetails"
                                    @update:attribute-tier-value-details="updateAttributeTierValueDetails"
                                    @add:new-tier-details="addNewTierDetails"
                                    @add:new-attribute-tier-details="addNewAttributeTierDetails"
                                    @remove:tier-details-of="removeTierDetailsOf"
                                    @remove:attribute-tier-details-of="removeAttributeTierDetailsOf"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.product_collections.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="productCollection ? 'Update' : 'Submit'"
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
import { onMounted, watch, computed } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import CollectionFilterTiers from '@adminPages/product_collection/CollectionFilterTiers.vue';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
const pageProps = computed(() => usePage().props);
import JSwitch from '@commonComponents/JSwitch.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    productCollection: {
        type: Object,
        default: null,
    },
    logicalConnectorTypes: {
        type: Array,
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
        default: null,
    },
    departments: {
        type: Object,
        required: true,
    },
    colors: {
        type: Object,
        default: null,
    },
    sizes: {
        type: Object,
        default: null,
    },
    brands: {
        type: Object,
        required: true,
    },
    styles: {
        type: Object,
        default: null,
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
    attributeTiers: {
        type: Object,
        default: null,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
});

const productCollectionForm = useForm({
    _method: props.productCollection ? 'put' : 'post',
    name: null,
    logical_connector_type_id: null,
    collection_filter_types: [
        {
            filter_type_id: null,
            condition_operator_id: null,
        },
    ],
    watchEnabled: true,
    is_available_in_ecommerce: false,
    sale_channels: [],
    sale_channel_ids: [],
});

const saveProductCollection = () => {
    prepareProductCollectionFormDetails();

    removeLocalStorage('productCollection');
    productCollectionForm.watchEnabled = false;

    if (productCollectionForm.sale_channels) {
        productCollectionForm.sale_channel_ids = productCollectionForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }

    if (props.productCollection) {
        const message = 'Are you sure? After updating this collection it will rebuild the matched products.';
        confirmDialogBox(message, () => {
            productCollectionForm.put(route('admin.product_collections.update', props.productCollection.id));
        });
        return;
    }
    productCollectionForm.post(route('admin.product_collections.store'), {
        preserveScroll: true,
        onError: () => {
            const errors = pageProps.value.errors;

            const errorMessage = Object.keys(errors)
                .filter(key => key.startsWith('collection_filter_types.') && key.endsWith('.attributes'))
                .map(key => errors[key]).join(', ');

            if (errorMessage) {
                showErrorNotification(errorMessage);
            }
        }
    });
};

const prepareProductCollectionFormDetails = () => {
    productCollectionForm.collection_filter_types.forEach((filterType, index) => {
        if (Object.getOwnPropertyDescriptor(filterType, 'categories')) {
            productCollectionForm.collection_filter_types[index].category_ids = filterType.categories.map((category) => {
                return category.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'seasons')) {
            productCollectionForm.collection_filter_types[index].season_ids = filterType.seasons.map((season) => {
                return season.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'departments')) {
            productCollectionForm.collection_filter_types[index].department_ids = filterType.departments.map((department) => {
                return department.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'colors')) {
            productCollectionForm.collection_filter_types[index].color_ids = filterType.colors.map((color) => {
                return color.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'sizes')) {
            productCollectionForm.collection_filter_types[index].size_ids = filterType.sizes.map((size) => {
                return size.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'brands')) {
            productCollectionForm.collection_filter_types[index].brand_ids = filterType.brands.map((brand) => {
                return brand.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'styles')) {
            productCollectionForm.collection_filter_types[index].style_ids = filterType.styles.map((style) => {
                return style.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'tags')) {
            productCollectionForm.collection_filter_types[index].tag_ids = filterType.tags.map((tag) => {
                return tag.id;
            });
        }
        if (Object.getOwnPropertyDescriptor(filterType, 'types')) {
            productCollectionForm.collection_filter_types[index].type_ids = filterType.types.map((type) => {
                return type.id;
            });
        }
    });
};

const checkSaveLocalStorage = () => {
    if (!props.productCollection) {
        saveLocalStorage('productCollection', productCollectionForm);
    }
};

const clearFormData = () => {
    productCollectionForm.reset();
};

watch(productCollectionForm, () => {
    if (productCollectionForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

onMounted(() => {
    if (props.productCollection) {
        removeLocalStorage('productCollection');
        Object.assign(productCollectionForm, props.productCollection);
    } else {
        setLocalStorage('productCollection', productCollectionForm);
    }
});

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    productCollectionForm[columnName] = details.value;
};

const updateTierValueDetails = (details) => {
    if (details.value === props.staticDetails.attribute) {

        productCollectionForm.collection_filter_types[details.key][details.column_name] = details.value;
        productCollectionForm.collection_filter_types[details.key]['attributes'] = [];
        addNewAttributeTierDetails(details.key, 'attributes');
        return;
    }

    productCollectionForm.collection_filter_types[details.key][details.column_name] = details.value;
};

const updateAttributeTierDetails = (details) => {

    if (!productCollectionForm.collection_filter_types[details.parent_key][details.column_name]) {
        productCollectionForm.collection_filter_types[details.parent_key][details.column_name] = {};
    }

    if (!productCollectionForm.collection_filter_types[details.parent_key][details.column_name][details.key]) {
        productCollectionForm.collection_filter_types[details.parent_key][details.column_name][details.key] = {
            attribute: null,
            attribute_selected_values: [],
            attribute_values: [],
        };
    }

    productCollectionForm.collection_filter_types[details.parent_key][details.column_name][details.key].attribute = details.value;
    productCollectionForm.collection_filter_types[details.parent_key][details.column_name][details.key].attribute_values = details.attribute_options;
};

const updateAttributeTierValueDetails = (details) => {
    productCollectionForm.collection_filter_types[details.parent_key][details.column_name][details.key].attribute_selected_values = details.value;
};

const addNewTierDetails = () => {
    productCollectionForm.collection_filter_types.push({ filter_type_id: null, condition_operator_id: null });
};

const addNewAttributeTierDetails = (parent_key, column_name) => {

    if (!productCollectionForm.collection_filter_types[parent_key][column_name]) {
        productCollectionForm.collection_filter_types[parent_key][column_name] = {};
    }

    productCollectionForm.collection_filter_types[parent_key][column_name].push({
        attribute: null,
        attribute_selected_values: [],
        attribute_values: [],
    });
};

const removeTierDetailsOf = (key) => {
    productCollectionForm.collection_filter_types.splice(key, 1);
};

const removeAttributeTierDetailsOf = (index2, index, column_name) => {
    const obj = productCollectionForm.collection_filter_types[index][column_name];

    if (obj[index2]) {
        obj.splice(index2, 1);
    }
};

const updateIsAvailableInEcommerce = (data) => {
    productCollectionForm.sale_channels = [];
    productCollectionForm.is_available_in_ecommerce = data;
};
</script>
