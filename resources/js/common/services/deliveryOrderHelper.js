import { X, Truck, XSquare, FileSignature, PackageCheck, } from 'lucide-vue-next';

export const getDeliveryOrderIcons = (name, fulfillmentStatuses) => {
    if (name === fulfillmentStatuses.draft) {
        return PackageCheck;
    }

    if (name === fulfillmentStatuses.shipped) {
        return Truck;
    }

    if (name === fulfillmentStatuses.received) {
        return FileSignature;
    }

    if (name === fulfillmentStatuses.discrepancy) {
        return Truck;
    }

    if (name === fulfillmentStatuses.closed) {
        return X;
    }

    if (name === fulfillmentStatuses.cancelled) {
        return XSquare;
    }

    return X;
};

export const getDeliveryOrderIconColors = (name, fulfillmentStatuses) => {
    if (name === fulfillmentStatuses.draft) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-fuchsia-700';
    }

    if (name === fulfillmentStatuses.shipped) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-sky-700';
    }

    if (name === fulfillmentStatuses.received) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-yellow-700';
    }

    if (name === fulfillmentStatuses.discrepancy) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-red-700';
    }

    if (name === fulfillmentStatuses.closed) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
    }

    if (name === fulfillmentStatuses.cancelled) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};

export const getBackgroundColorForDeliveryOrder = (name, fulfillmentStatuses) => {
    if (name === fulfillmentStatuses.draft) {
        return 'bg-fuchsia-50 border-fuchsia-100';
    }

    if (name === fulfillmentStatuses.shipped) {
        return 'bg-sky-50 border-sky-100';
    }

    if (name === fulfillmentStatuses.received) {
        return 'bg-yellow-50 border-yellow-100';
    }

    if (name === fulfillmentStatuses.discrepancy) {
        return 'bg-red-50 border-red-100';
    }

    if (name === fulfillmentStatuses.closed) {
        return 'bg-indigo-50 border-indigo-100';
    }

    if (name === fulfillmentStatuses.cancelled) {
        return 'bg-indigo-50 border-indigo-100';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};
