export default {
    get (storageKey) {
        const storageDetails = localStorage.getItem(storageKey);

        if (storageDetails === null) {
            return false;
        }

        return JSON.parse(storageDetails);
    },

    save (storageKey, storageDetails) {
        localStorage.setItem(storageKey, JSON.stringify(storageDetails));
    },

    remove (storageKey) {
        localStorage.removeItem(storageKey);
    },
};
