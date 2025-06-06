import { Repeat, X, XSquare, FileSignature, PackageCheck, PackageOpen, CheckCircle2, } from 'lucide-vue-next';

export const getPurchaseOrderIcons = (name, purchaseOrderStatuses) => {
    if (name === purchaseOrderStatuses.draft) {
        return PackageCheck;
    }

    if (name === purchaseOrderStatuses.opened) {
        return PackageOpen;
    }

    if (name === purchaseOrderStatuses.approved) {
        return CheckCircle2;
    }

    if (name === purchaseOrderStatuses.rejected) {
        return XSquare;
    }

    if (name === purchaseOrderStatuses.cancelled) {
        return Repeat;
    }

    if (name === purchaseOrderStatuses.closed) {
        return X;
    }

    if (name === purchaseOrderStatuses.partialFulfillment) {
        return FileSignature;
    }

    if (name === purchaseOrderStatuses.fulfillmentCompleted) {
        return FileSignature;
    }

    return X;
};

export const getPurchaseOrderIconColors = (name, purchaseOrderStatuses) => {
    if (name === purchaseOrderStatuses.draft) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-fuchsia-700';
    }

    if (name === purchaseOrderStatuses.opened) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-orange-700';
    }

    if (name === purchaseOrderStatuses.approved) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-purple-700';
    }

    if (name === purchaseOrderStatuses.rejected) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-green-700';
    }

    if (name === purchaseOrderStatuses.cancelled) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-pink-700';
    }

    if (name === purchaseOrderStatuses.closed) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
    }

    if (name === purchaseOrderStatuses.partialFulfillment) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-sky-700';
    }

    if (name === purchaseOrderStatuses.fulfillmentCompleted) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-sky-700';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};

export const getBackgroundColorForPurchaseOrder = (name, purchaseOrderStatuses) => {
    if (name === purchaseOrderStatuses.draft) {
        return 'bg-fuchsia-50 border-fuchsia-100';
    }

    if (name === purchaseOrderStatuses.opened) {
        return 'bg-orange-50 border-orange-100';
    }

    if (name === purchaseOrderStatuses.approved) {
        return 'bg-purple-50 border-purple-100';
    }

    if (name === purchaseOrderStatuses.rejected) {
        return 'bg-green-50 border-green-100';
    }

    if (name === purchaseOrderStatuses.cancelled) {
        return 'bg-pink-50 border-pink-100';
    }

    if (name === purchaseOrderStatuses.closed) {
        return 'bg-indigo-50 border-indigo-100';
    }

    if (name === purchaseOrderStatuses.partialFulfillment) {
        return 'bg-sky-50 border-sky-100';
    }

    if (name === purchaseOrderStatuses.fulfillmentCompleted) {
        return 'bg-sky-50 border-sky-100';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};
