<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';
import BtnDelete from "@/Components/common/utility/BtnDelete.vue";

const props = defineProps({
    departments: Array,
    empStatusOptions: Array,
});

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'name'},
        {title: 'Emp ID', key: 'id_no'},
        {title: 'Email', key: 'email'},
        {title: 'Phone', key: 'phone'},
        {title: 'Department', key: 'department'},
        {title: 'Designation', key: 'designation'},
        {title: 'Manager', key: 'manager'},
        {title: 'Joined', key: 'joining_date'},
        {title: 'Status', key: 'status', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'}
    ],
    pagination: {
        itemsPerPage: 10,
        totalItems: 0
    },
    filters: {
        search: '',
        department_id: null,
        status: null,
        emp_status: null,
        per_page: 10
    },
    serverItems: [],
    loading: true
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    axios.get(route('company.employees.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.post(route('company.employees.toggle-status', item.id))
        .then(() => toast('Employee status has been updated.'));
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Employees"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'company.employees.create'}"
                        icon="mdi-plus"
                        title="Employees"
                    />

                    <v-card-text>
                        <v-row class="mb-4" dense>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="state.filters.search"
                                    clearable
                                    density="compact"
                                    hide-details
                                    label="Search employees..."
                                    prepend-inner-icon="mdi-magnify"
                                    variant="outlined"
                                    @keyup.enter="getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []})"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="state.filters.department_id"
                                    :items="departments"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="name"
                                    item-value="id"
                                    label="Department"
                                    variant="outlined"
                                    @update:model-value="getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []})"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select
                                    v-model="state.filters.status"
                                    :items="[{title: 'Active', value: 1}, {title: 'Inactive', value: 0}]"
                                    clearable
                                    density="compact"
                                    hide-details
                                    label="Status"
                                    variant="outlined"
                                    @update:model-value="getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []})"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="state.filters.emp_status"
                                    :items="empStatusOptions"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="label"
                                    item-value="value"
                                    label="Emp. Status"
                                    variant="outlined"
                                    @update:model-value="getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []})"
                                />
                            </v-col>
                        </v-row>

                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            :search="state.filters.search"
                            density="compact"
                            item-value="id"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.name="{ item }">
                                {{ item.first_name }} {{ item.last_name }}
                            </template>
                            <template v-slot:item.department="{ item }">
                                <v-chip v-if="item.department" class="font-weight-regular" color="primary" size="small" variant="tonal">
                                    {{ item.department.name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.designation="{ item }">
                                <v-chip v-if="item.designation" class="font-weight-regular" color="secondary" size="small" variant="tonal">
                                    {{ item.designation.title }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.manager="{ item }">
                                <span v-if="item.manager">{{ item.manager.first_name }} {{ item.manager.last_name }}</span>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.joining_date="{ item }">
                                {{ formatDate(item.joining_date) }}
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-switch
                                    v-model="item.status"
                                    color="success"
                                    density="compact"
                                    hide-details
                                    @change="() => toggleStatus(item)"
                                />
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('company.employees.edit', item.id)"
                                    color="bg-darkprimary"
                                    icon="mdi-pencil"/>

                                <btn-delete
                                    :route="route('company.employees.destroy', item.id)"
                                    color="bg-red-darken-2"
                                    icon="mdi-delete"
                                    size="small"
                                    title="Delete Employee"
                                />
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
