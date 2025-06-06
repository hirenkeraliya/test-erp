import { defineStore } from 'pinia';

export const useHelpCenterStore = defineStore('helpCenter', {
    state: () => ({
        data: null,
    }),
    actions: {
        setHelpData (data) {
            this.data = data;
        },
        getHelpData () {
            return this.data;
        },
    },
});
