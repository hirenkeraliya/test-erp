<template>
    <div class="intro-y bg-slate-50">
        <div class="font-medium text-lg p-5 border-b">
            Effective Dates
        </div>

        <div class="p-5 pt-1">
            <InfoAlert
                color="primary"
                class="my-3"
            >
                <p>
                    Vouchers will be issued/generated only during the selected "Start date" and "End date".
                </p>
            </InfoAlert>
            <div>
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <JDatePicker
                            :input-value="voucherConfigurationForm.start_date"
                            input-label="Start Date"
                            validation-field-name="start_date"
                            :min-date="new Date()"
                            @update:input-value="updateDate('start_date', $event)"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <JDatePicker
                            :input-value="voucherConfigurationForm.end_date"
                            input-label="End Date"
                            validation-field-name="end_date"
                            :min-date="new Date(voucherConfigurationForm.start_date)"
                            @update:input-value="updateDate('end_date', $event)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';

defineProps({
    voucherTypes: {
        type: Array,
        default: () => [],
    },
    voucherConfigurationForm: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:column-details',
    'clear:columns',
]);

const updateDate = (columnName, data) => {
    if (columnName === 'start_date') {
        emits('clear:columns', {
            end_date: null,
        });
    }

    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};
</script>
