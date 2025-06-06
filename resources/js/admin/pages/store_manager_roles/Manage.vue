<template>
    <PageTitle title="Roles &amp; Permission" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Store Manager Permission
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <form @submit.prevent="saveRole">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="roleForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                    validation-field-name="name"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mt-5">
                            <div class="intro-y col-span-12 md:col-span-12 lg:col-span-8 xl:col-span-6 2xl:col-span-4">
                                <div class="intro-y bg-white border-[1px] border-slate-200">
                                    <div
                                        class="flex justify-between items-center w-full px-4 py-3 text-sm font-medium text-left bg-slate-200 focus:outline-none focus-visible:ring-opacity-75"
                                    >
                                        <div class="w-9/12 sm:w-11/12">
                                            Module
                                        </div>
                                        <div class="w-full">
                                            <FormInput
                                                v-model:input-value="state.searchText"
                                                class="rounded -mt-1"
                                                placeholder="Search..."
                                                input-name="search"
                                                @update:input-value="filterByText()"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        id="basic-accordion"
                                        class="p-3 sm:p-5"
                                    >
                                        <div class="preview">
                                            <div
                                                id="faq-accordion-1"
                                                class="accordion"
                                            >
                                                <div
                                                    v-for="(defaultPermission, index) in state.permissions"
                                                    :key="index"
                                                    class="accordion-item"
                                                >
                                                    <div
                                                        id="faq-accordion-content-1"
                                                        class="accordion-header bg-slate-100 px-4 py-2"
                                                    >
                                                        <div class="flex items-center">
                                                            <div class="w-9/12 sm:w-11/12">
                                                                <button
                                                                    class="accordion-button"
                                                                    style="font-size: 0.9rem;"
                                                                    type="button"
                                                                    data-tw-toggle="collapse"
                                                                    data-tw-target="#faq-accordion-collapse-1"
                                                                    @click="expandContents(defaultPermission)"
                                                                >
                                                                    {{ defaultPermission.name }}
                                                                </button>
                                                            </div>

                                                            <div class="w-3/12 sm:w-1/12 mr-2">
                                                                <div class="float-right">
                                                                    <JSwitch
                                                                        input-class="ml-0 mt-0"
                                                                        class="mt-[0px]"
                                                                        :is-checked="defaultPermission.action"
                                                                        @update:is-checked="allPermission($event, defaultPermission.id)"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div
                                                        v-for="(childPermission, childIndex) in defaultPermission.children"
                                                        id="faq-accordion-collapse-1"
                                                        :key="childIndex"
                                                        class="accordion-collapse collapse"
                                                        :class="defaultPermission.is_open ? 'show' : ''"
                                                    >
                                                        <div class="flex">
                                                            <div class="w-9/12 sm:w-11/12 ml-4">
                                                                <div class="accordion-body text-slate-600 leading-relaxed">
                                                                    {{ childPermission.name }}
                                                                </div>
                                                            </div>

                                                            <div class="w-3/12 sm:w-1/12 mr-5">
                                                                <div class="float-right">
                                                                    <JSwitch
                                                                        input-class="ml-0 mt-0"
                                                                        class="mt-[0px]"
                                                                        :is-checked="childPermission.action"
                                                                        @update:is-checked="updatePermission($event, childPermission.id, defaultPermission.id)"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    v-if="Object.keys(state.permissions).length <= 0"
                                                    class="text-center bg-slate-200 p-2 rounded"
                                                >
                                                    There are no records to show.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="intro-y col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-6 2xl:col-span-8"
                            >
                                <div class="intro-y bg-white border-[1px] border-slate-200">
                                    <div
                                        class="flex justify-between items-center w-full px-4 py-3 text-sm font-medium text-left bg-slate-200 focus:outline-none focus-visible:ring-opacity-75"
                                    >
                                        <div class="w-9/12 sm:w-11/12">
                                            Enabled Permissions
                                        </div>
                                        <div class="w-full">
                                            <FormInput
                                                v-model:input-value="state.searchTextEnabledPermissions"
                                                class="rounded -mt-1"
                                                placeholder="Search..."
                                                input-name="search"
                                                @update:input-value="filterByTextEnabledPermission()"
                                            />
                                        </div>
                                    </div>
                                    <div
                                        class="p-3 sm:p-5"
                                    >
                                        <table class="table-auto w-full border">
                                            <thead>
                                                <tr>
                                                    <th class="border px-4 py-2 w-1/2">
                                                        Name
                                                    </th>
                                                    <th class="border px-4 py-2 w-1/2">
                                                        Permission
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="(defaultPermission, index) in state.enabledPermissions"
                                                    :key="index"
                                                >
                                                    <td class="border px-4 py-2">
                                                        {{ defaultPermission.name }}
                                                    </td>
                                                    <td class="border px-4 py-2">
                                                        {{ commaSeparate(defaultPermission.children) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.store_manager_roles.index')">
                                <SecondaryButton
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="role ? 'Update' : 'Submit'"
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
import JSwitch from '@commonComponents/JSwitch.vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { route } from 'ziggy';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted, reactive } from 'vue';

const props = defineProps({
    permissions: {
        type: Object,
        required: true,
    },
    role: {
        type: Object,
        default: null,
    },
    cloneRole: {
        type: Object,
        default: null,
    },
});

const state = reactive({
    permissions: [],
    enabledPermissions: [],
    constEnabledPermissions: [],
    selectedPermissions: [],
    searchText: '',
    searchTextEnabledPermissions: '',
});

const roleForm = useForm({
    name: null,
    permissions: [],
});

const commaSeparate = (permissions) => {
    return permissions.filter(child => child.action).map(child => child.name).join(', ');
};

const allPermission = (element, moduleName) => {
    if (element === true) {
        addParentChildPermission(moduleName);
        removeAllDisabledPermission();
        return false;
    }

    removeParenChildPermission(moduleName);
    removeAllDisabledPermission();
};

const addParentChildPermission = (moduleName) => {
    for (const key in state.permissions) {
        if (state.permissions[key].id === moduleName) {
            state.permissions[key].action = true;
            Object.values(state.permissions[key].children).every((children, childrenKey) => {
                if (!state.selectedPermissions.includes(state.permissions[key].children[childrenKey].id)) {
                    state.selectedPermissions.push(state.permissions[key].children[childrenKey].id);
                    children.action = true;
                    return children;
                }
                return children;
            });

            return true;
        }
    }
};

const removeParenChildPermission = (moduleName) => {
    for (const key in state.permissions) {
        if (state.permissions[key].id === moduleName) {
            for (const childrenKey in state.permissions[key].children) {
                state.permissions[key].children[childrenKey].action = false;
                state.selectedPermissions = state.selectedPermissions.filter(function (e) { return e !== state.permissions[key].children[childrenKey].id; });
            }

            state.permissions[key].action = false;

            return false;
        }
    }
};

const updatePermission = (element, permission, parentPermissionName) => {
    if (element === true) {
        updateChildPermission(permission, parentPermissionName);
        removeAllDisabledPermission();
        return;
    }

    for (const key in state.permissions) {
        if (state.permissions[key].id === parentPermissionName && state.permissions[key].action === true) {
            state.permissions[key].action = false;
        }

        for (const childrenKey in state.permissions[key].children) {
            const permissionId = state.permissions[key].children[childrenKey].id;
            const childAction = state.permissions[key].children[childrenKey].action;

            const hasWriteRecord = permissionId.includes('write_record') && childAction === true;
            const hasModifyRecord = permissionId.includes('modify_record') && childAction === true;
            const hasExportRecord = permissionId.includes('export_record') && childAction === true;

            if (
                state.permissions[key].id === parentPermissionName &&
                permission.includes('read_record')
            ) {
                if (hasWriteRecord || hasModifyRecord || hasExportRecord) {
                    state.permissions[key].children[childrenKey].action = false;

                    const notFound = -1;

                    const writeRecordIndex = state.selectedPermissions.indexOf(permissionId);
                    if (writeRecordIndex > notFound) {
                        state.selectedPermissions.splice(writeRecordIndex, 1);
                    }

                    const permissionIndex = state.selectedPermissions.indexOf(permission);
                    if (permissionIndex > notFound) {
                        state.selectedPermissions.splice(permissionIndex, 1);
                    }
                }
            }

            if (state.permissions[key].children[childrenKey].id === permission) {
                state.permissions[key].children[childrenKey].action = false;
            }
        }
    }

    if (state.selectedPermissions.includes(permission)) {
        state.selectedPermissions.splice(state.selectedPermissions.indexOf(permission), 1);
    }
    removeAllDisabledPermission();
};

const updateChildPermission = (permission, parentPermissionName) => {
    for (const key in state.permissions) {
        if (state.permissions[key].id === parentPermissionName) {
            for (const childrenKey in state.permissions[key].children) {
                const childPermissionId = state.permissions[key].children[childrenKey].id;

                const includesWriteRecord = permission.includes('write_record');
                const includesModifyRecord = permission.includes('modify_record');
                const includesExportRecord = permission.includes('export_record');
                const includesReadRecord = childPermissionId.includes('read_record');

                if ((includesWriteRecord || includesModifyRecord || includesExportRecord) && includesReadRecord) {
                    state.permissions[key].children[childrenKey].action = true;

                    if (!state.selectedPermissions.includes(childPermissionId)) {
                        state.selectedPermissions.push(childPermissionId);
                    }
                }

                if (state.permissions[key].children[childrenKey].id === permission) {
                    state.permissions[key].children[childrenKey].action = true;
                }
            }

            parentEnableIfAllChildEnable(key);
        }
    }

    state.selectedPermissions.push(permission);
};

const parentEnableIfAllChildEnable = (key) => {
    const childEnable = Object.values(state.permissions[key].children).every((children) => {
        return children.action === true;
    });

    if (childEnable) {
        state.permissions[key].action = true;
    }
};

const expandContents = (permission) => {
    permission.is_open = !permission.is_open;
};

const saveRole = () => {
    roleForm.permissions = state.selectedPermissions;
    if (props.role) {
        roleForm.post(route('admin.store_manager_roles.update_roles_permissions', props.role.id));
        return;
    }

    roleForm.post(route('admin.store_manager_roles.store'));
};

const preparedSelectedPermission = () => {
    for (const key in roleForm.permissions) {
        for (const childrenKey in roleForm.permissions[key].children) {
            if (roleForm.permissions[key].children[childrenKey].action === true) {
                state.selectedPermissions.push(roleForm.permissions[key].children[childrenKey].id);
            }
        }
    }
};

onMounted(() => {
    state.permissions = props.permissions;

    if (props.role || props.cloneRole) {
        const sourceRole = props.role || props.cloneRole;
        Object.assign(roleForm, sourceRole);
        state.permissions = roleForm.permissions;

        preparedSelectedPermission();
    }
    removeAllDisabledPermission();
});

const removeAllDisabledPermission = () => {
    const permissions = (props.role || props.cloneRole) ? roleForm.permissions : props.permissions;
    state.enabledPermissions = permissions.filter(permission => {
        let action = true;
        permission.children.forEach(child => {
            if (child.action) {
                action = false;
            }
        });
        return !action;
    });
    state.constEnabledPermissions = state.enabledPermissions;
    filterByTextEnabledPermission();
};

const filterByText = () => {
    const permissions = props.role ? roleForm.permissions : props.permissions;

    if (state.searchText === '') {
        state.permissions = permissions;
        return;
    }

    state.permissions = permissions.filter((record) => {
        return JSON.stringify(record.name).toLowerCase().includes(state.searchText.toLowerCase());
    });
};

const filterByTextEnabledPermission = () => {
    const enabledPermissions = state.constEnabledPermissions;

    if (state.searchTextEnabledPermissions === '') {
        state.enabledPermissions = enabledPermissions;
        return;
    }

    state.enabledPermissions = enabledPermissions.filter((record) => {
        return JSON.stringify(record.name).toLowerCase().includes(state.searchTextEnabledPermissions.toLowerCase());
    });
};
</script>
