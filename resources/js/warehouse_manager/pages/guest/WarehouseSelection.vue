<template>
    <PageTitle title="Location Selection" />
    <div class="block xl:grid grid-cols-2 gap-4">
        <GuestSidebar
            :first-message="null"
            :second-message="null"
        />

        <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
            <div class="my-auto mx-auto xl:ml-20 bg-white xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                    Select Location
                </h2>

                <div class="intro-x mt-8">
                    <FormSelectBox
                        :selected-record="locationSelection.location_id"
                        :records="locations"
                        input-label="Locations"
                        validation-field-name="location_id"
                        @update:selected-record="saveSelectedLocation"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { useForm } from '@inertiajs/vue3';
import ObjectStorage from '@commonServices/storage.js';
import GuestSidebar from '@commonComponents/GuestSidebar.vue';
import { onMounted } from 'vue';
import { recordExistsInList } from '@commonServices/helper';
import { route } from 'ziggy';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
});

const locationSelection = useForm({
    location_id: null,
});

const saveSelectedLocation = (selectedLocation) => {
    locationSelection.location_id = selectedLocation;

    ObjectStorage.save('warehouse-manager-warehouse-id', parseInt(selectedLocation));

    locationSelection.post(route('warehouse_manager.set_selected_warehouse'), {
        onError: () => ObjectStorage.remove('warehouse-manager-warehouse-id'),
    });
};

onMounted(() => {
    if (props.locations.length === 1) {
        saveSelectedLocation(props.locations[0].id);

        return;
    }

    if (ObjectStorage.get('warehouse-manager-warehouse-id')) {
        if (recordExistsInList(props.locations, ObjectStorage.get('warehouse-manager-warehouse-id'))) {
            saveSelectedLocation(ObjectStorage.get('warehouse-manager-warehouse-id'));
            return;
        }
        ObjectStorage.remove('warehouse-manager-warehouse-id');
    }
});
</script>
