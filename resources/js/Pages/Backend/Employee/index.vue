<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import FilterWithoutTrash from '@/Components/common/filter/FilterWithoutTrash.vue';
import {useToast} from 'vue-toastification';

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Emp ID', key: 'id_no'},
        {title: 'Name', key: 'full_name'},
        {title: 'Email', key: 'email'},
        {title: 'Phone', key: 'phone'},
        {title: 'Department', key: 'department'},
        {title: 'Designations', key: 'designations', sortable: false},
        {title: 'Joining Date', key: 'joining_date'},
        {title: 'Status', key: 'status', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'}
    ],
    pagination: {
        itemsPerPage: 10,
        totalItems: 0
    },
    filters: {
        search: '',
        dateSearch: null,
        isChecked: false,
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
    axios.get(route('employees.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.get(route('employees.toggle-status', item.id), {}, {preserveScroll: true})
        .then(() => toast('Employee status has been updated.'));
};

const handleSearch = (filters) => {
    state.filters = filters;
    state.loading = true;
    getData(state.filters);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getJoiningDateColor = (date) => {
    if (!date) return 'default';
    const today = new Date();
    const joiningDate = new Date(date);
    const diffTime = today.getTime() - joiningDate.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 30) return 'success'; // New employee (less than 1 month)
    if (diffDays < 180) return 'info'; // Recent employee (less than 6 months)
    if (diffDays < 365) return 'warning'; // Mid-tenure (less than 1 year)
    return 'primary'; // Senior employee (1+ year)
};

const getFullName = (item) => {
    return `${item.first_name || ''} ${item.last_name || ''}`.trim() || '-';
};

const truncateEmail = (email, length = 25) => {
    if (!email) return '-';
    return email.length > length ? email.substring(0, length) + '...' : email;
};
</script>

<template>
    <DefaultLayout>
        <Head title="Employees"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'employees.create'}"
                        icon="mdi-plus"
                        title="Employees"
                    />

                    <FilterWithoutTrash :dateSearch="true" :filters="state.filters" @handleFilter="handleSearch"/>
                    <v-card-text>
                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            :search="state.searchParam"
                            density="compact"
                            item-value="id"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
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
                            <template v-slot:item.id_no="{ item }">
                                <v-chip
                                    class="font-weight-medium"
                                    color="info"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ item.id_no || '-' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.full_name="{ item }">
                                <div class="d-flex align-center">
                                    <v-avatar
                                        class="me-2"
                                        color="primary"
                                        size="32"
                                    >
                                        <span class="text-white font-weight-medium">
                                            {{ (item.first_name?.[0] || '') + (item.last_name?.[0] || '') }}
                                        </span>
                                    </v-avatar>
                                    <div>
                                        <div class="font-weight-medium">{{ getFullName(item) }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ item.gender || '-' }}</div>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:item.email="{ item }">
                                <span
                                    v-if="item.email"
                                    :title="item.email"
                                    class="text-body-2"
                                >
                                    {{ truncateEmail(item.email) }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.phone="{ item }">
                                <div v-if="item.phone" class="text-body-2">
                                    <div>{{ item.phone }}</div>
                                    <div v-if="item.sec_phone" class="text-caption text-medium-emphasis">
                                        {{ item.sec_phone }}
                                    </div>
                                </div>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.department="{ item }">
                                <v-chip
                                    v-if="item.department"
                                    class="font-weight-regular"
                                    color="primary"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ item.department.name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.designations="{ item }">
                                <div v-if="item.designations && item.designations.length > 0"
                                     class="d-flex flex-wrap ga-1">
                                    <v-chip
                                        v-for="designation in item.designations.slice(0, 2)"
                                        :key="designation.id"
                                        class="font-weight-regular"
                                        color="secondary"
                                        size="x-small"
                                        variant="tonal"
                                    >
                                        {{ designation.title }}
                                    </v-chip>
                                    <v-chip
                                        v-if="item.designations.length > 2"
                                        class="font-weight-regular"
                                        color="default"
                                        size="x-small"
                                        variant="tonal"
                                    >
                                        +{{ item.designations.length - 2 }}
                                    </v-chip>
                                </div>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.joining_date="{ item }">
                                <v-chip
                                    :color="getJoiningDateColor(item.joining_date)"
                                    class="font-weight-regular"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ formatDate(item.joining_date) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <div class="d-flex ga-1">
                                    <btn-link
                                        :route="route('employees.edit', item.id)"
                                        color="bg-darkprimary"
                                        icon="mdi-pencil"
                                        size="small"
                                    />
                                </div>
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
