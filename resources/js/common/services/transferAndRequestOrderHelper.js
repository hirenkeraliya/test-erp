import { Repeat, X, Truck, XCircle, XSquare, FileSignature, PackageCheck, PackageOpen, CheckCircle2, GitMerge, ArrowRightFromLine, ArrowLeftFromLine } from 'lucide-vue-next';

export const getTransferAndRequestOrderIcons = (name, stockTransferStatuses) => {
    if (name === stockTransferStatuses.closed) {
        return X;
    }

    if (name === stockTransferStatuses.discrepancy) {
        return Truck;
    }

    if (name === stockTransferStatuses.received) {
        return XCircle;
    }

    if (name === stockTransferStatuses.rejected) {
        return XSquare;
    }

    if (name === stockTransferStatuses.cancelled) {
        return Repeat;
    }

    if (name === stockTransferStatuses.shipped) {
        return FileSignature;
    }

    if (name === stockTransferStatuses.draft) {
        return PackageCheck;
    }

    if (name === stockTransferStatuses.open) {
        return PackageOpen;
    }

    if (name === stockTransferStatuses.approved) {
        return CheckCircle2;
    }

    if (name === stockTransferStatuses.transit) {
        return GitMerge;
    }

    if (name === stockTransferStatuses.transitOut) {
        return ArrowRightFromLine;
    }

    if (name === stockTransferStatuses.transitIn) {
        return ArrowLeftFromLine;
    }

    return 'X';
};

export const getTransferAndRequestOrderIconColors = (name, stockTransferStatuses) => {
    if (name === stockTransferStatuses.closed) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
    }

    if (name === stockTransferStatuses.discrepancy) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-red-700';
    }

    if (name === stockTransferStatuses.received) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-yellow-700';
    }

    if (name === stockTransferStatuses.rejected) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-green-700';
    }

    if (name === stockTransferStatuses.cancelled) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-pink-700';
    }

    if (name === stockTransferStatuses.shipped) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-sky-700';
    }

    if (name === stockTransferStatuses.draft) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-fuchsia-700';
    }

    if (name === stockTransferStatuses.open) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-orange-700';
    }

    if (name === stockTransferStatuses.approved) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-purple-700';
    }

    if (name === stockTransferStatuses.transit) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
    }

    if (name === stockTransferStatuses.transitIn) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-green-700';
    }

    if (name === stockTransferStatuses.transitOut) {
        return 'w-4 h-4 lg:h-5 lg:w-5 text-fuchsia-700';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};

export const getBackgroundColorForTransferAndRequestOrder = (name, stockTransferStatuses) => {
    if (name === stockTransferStatuses.closed) {
        return 'bg-indigo-50 border-indigo-100';
    }

    if (name === stockTransferStatuses.discrepancy) {
        return 'bg-red-50 border-red-100';
    }

    if (name === stockTransferStatuses.received) {
        return 'bg-yellow-50 border-yellow-100';
    }

    if (name === stockTransferStatuses.rejected) {
        return 'bg-green-50 border-green-100';
    }

    if (name === stockTransferStatuses.cancelled) {
        return 'bg-pink-50 border-pink-100';
    }

    if (name === stockTransferStatuses.shipped) {
        return 'bg-sky-50 border-sky-100';
    }

    if (name === stockTransferStatuses.draft) {
        return 'bg-fuchsia-50 border-fuchsia-100';
    }

    if (name === stockTransferStatuses.open) {
        return 'bg-orange-50 border-orange-100';
    }

    if (name === stockTransferStatuses.transit) {
        return 'bg-orange-50 border-indigo-100';
    }

    if (name === stockTransferStatuses.transitIn) {
        return 'bg-orange-50 border-green-100';
    }

    if (name === stockTransferStatuses.transitOut) {
        return 'bg-orange-50 border-fuchsia-100';
    }

    return 'w-4 h-4 lg:h-5 lg:w-5 text-indigo-700';
};
