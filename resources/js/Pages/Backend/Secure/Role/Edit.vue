<script setup>
import TextInput from '@/Components/common/form/TextInput.vue';
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {onMounted, ref, watch} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
    permissions: Object, // Changed from Array to Object since it's grouped
});

let form = useForm({
    name: props.item?.name || '',
    description: props.item?.description || '',
    permission: props.item?.permission || [],
    status: props.item?.status ?? true
});

// Reactive data
const selectedItems = ref([])

// Initialize selectedItems with existing permissions on mount
onMounted(() => {
    if (props.item?.permission && Array.isArray(props.item.permission)) {
        selectedItems.value = [...props.item.permission]
    }
})

// Helper methods for group operations
const getGroupPermissionIds = (groupName) => {
    return props.permissions[groupName]?.map(permission => permission.id) || []
}

const getGroupSelectedCount = (groupName) => {
    const groupIds = getGroupPermissionIds(groupName)
    return selectedItems.value.filter(id => groupIds.includes(id)).length
}

const isGroupSelected = (groupName) => {
    const groupIds = getGroupPermissionIds(groupName)
    return groupIds.length > 0 && groupIds.every(id => selectedItems.value.includes(id))
}

const isGroupIndeterminate = (groupName) => {
    const groupIds = getGroupPermissionIds(groupName)
    const selectedCount = getGroupSelectedCount(groupName)
    return selectedCount > 0 && selectedCount < groupIds.length
}

// Methods
const handleGroupSelectAll = (groupName, value) => {
    const groupIds = getGroupPermissionIds(groupName)

    if (value) {
        // Add all group IDs to selection
        groupIds.forEach(id => {
            if (!selectedItems.value.includes(id)) {
                selectedItems.value.push(id)
            }
        })
    } else {
        // Remove all group IDs from selection
        selectedItems.value = selectedItems.value.filter(id => !groupIds.includes(id))
    }
}

const handleItemToggle = (id) => {
    const index = selectedItems.value.indexOf(id)
    if (index > -1) {
        selectedItems.value.splice(index, 1)
    } else {
        selectedItems.value.push(id)
    }
}

// Watch selectedItems and sync with form.permission
watch(selectedItems, (newSelection) => {
    form.permission = [...newSelection]
}, {deep: true})

const submit = () => {
    form.put(route('core.role.update', props.item.id), {
        onSuccess: () => toast('Data has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Role"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title:'Back', route:'core.role.index'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Role"
                    />
                    <form @submit.prevent="submit">
                        <v-card-item>
                            <v-row justify="center">
                                <v-col class="mt-5" cols="8">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name"/>

                                    <TextInput
                                        v-model="form.description"
                                        :error-messages="form.errors.description"
                                        label="Description"/>
                                </v-col>
                            </v-row>


                            <v-row>
                                <v-col v-for="(permissionList, groupName) in permissions" :key="groupName" cols="3"
                                       md="3">
                                    <v-card>
                                        <v-card-title class="bg-grey-lighten-5">
                                            <v-row align="center" no-gutters>
                                                <v-col cols="auto">
                                                    <v-checkbox
                                                        :indeterminate="isGroupIndeterminate(groupName)"
                                                        :model-value="isGroupSelected(groupName)"
                                                        density="comfortable"
                                                        hide-details
                                                        @update:model-value="handleGroupSelectAll(groupName, $event)"
                                                    ></v-checkbox>
                                                </v-col>
                                                <v-col>
                                <span class="text-subtitle-1 font-weight-medium">
                                    {{ groupName }} ({{ getGroupSelectedCount(groupName) }}/{{
                                        permissionList.length
                                    }})
                                </span>
                                                </v-col>
                                            </v-row>
                                        </v-card-title>

                                        <v-divider></v-divider>

                                        <v-list>
                                            <v-list-item
                                                v-for="permission in permissionList"
                                                :key="permission.id"
                                                :class="{ 'v-list-item--active': selectedItems.includes(permission.id) }"
                                                @click="handleItemToggle(permission.id)"
                                            >
                                                <template v-slot:prepend>
                                                    <v-checkbox
                                                        :model-value="selectedItems.includes(permission.id)"
                                                        density="comfortable"
                                                        hide-details
                                                        @update:model-value="handleItemToggle(permission.id)"
                                                    ></v-checkbox>
                                                </template>

                                                <v-list-item-title>{{ permission.name }}</v-list-item-title>

                                                <template v-slot:append>
                                                    <v-icon
                                                        v-if="selectedItems.includes(permission.id)"
                                                        color="primary"
                                                        icon="mdi-check"
                                                    ></v-icon>
                                                </template>
                                            </v-list-item>
                                        </v-list>
                                    </v-card>
                                </v-col>
                            </v-row>


                        </v-card-item>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>


    </DefaultLayout>
</template>
