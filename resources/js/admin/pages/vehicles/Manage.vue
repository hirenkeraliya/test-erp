<template>
    <PageTitle :title="vehicle ? 'Edit Vehicle' : 'Add Vehicle'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Vehicle
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="vehicle">Edit Vehicle</span>
                        <span v-else>Add Vehicle</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveVehicle();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <FormInput
                                    v-model:input-value="vehicleForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <FormInput
                                    v-model:input-value="vehicleForm.plate_no"
                                    input-name="plate_no"
                                    input-label="Plate No"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <FormInput
                                    v-model:input-value="vehicleForm.type_of_vehicle"
                                    input-name="type_of_vehicle"
                                    input-label="Type of Vehicle"
                                    :required="false"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4">
                                <JSwitch
                                    input-label="Status"
                                    :is-checked="vehicleForm.status"
                                    class="mt-3"
                                    @update:is-checked="vehicleForm.status = $event"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-right">
                                <Link :href="route('admin.vehicles.index')">
                                    <SecondaryButton
                                        type="button"
                                        text="Cancel"
                                        class="w-24 mr-1"
                                    />
                                </Link>
                                <PrimaryButton
                                    type="submit"
                                    :text="vehicle ? 'Update' : 'Save'"
                                    :disabled="vehicleForm.processing"
                                    class="w-24"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { route } from 'ziggy';
import { Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import JSwitch from '@commonComponents/JSwitch.vue';

const props = defineProps({
    vehicle: {
        type: Object,
        default: null
    }
});

const vehicleForm = useForm({
    name: '',
    plate_no: '',
    type_of_vehicle: '',
    status: true
});

const clearFormData = () => {
    vehicleForm.reset();
    vehicleForm.clearErrors();
};

const saveVehicle = () => {
    if (props.vehicle) {
        vehicleForm.put(route('admin.vehicles.update', props.vehicle.data.id), {
            onSuccess: () => {
                clearFormData();
            }
        });
    } else {
        vehicleForm.post(route('admin.vehicles.store'), {
            onSuccess: () => {
                clearFormData();
            }
        });
    }
};

onMounted(() => {
    if (props.vehicle) {
        vehicleForm.name = props.vehicle.data.name;
        vehicleForm.plate_no = props.vehicle.data.plate_no;
        vehicleForm.type_of_vehicle = props.vehicle.data.type_of_vehicle;
        vehicleForm.status = props.vehicle.data.status;
    }
});
</script>
