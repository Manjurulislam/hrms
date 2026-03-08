<script setup>
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive, ref} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import EmployeeFilter from '@/Components/common/filter/EmployeeFilter.vue';
import {useToast} from 'vue-toastification';
import ImportEmployeeDialog from "@/Components/modules/employee/ImportEmployeeDialog.vue";

const props = defineProps({
    companies: Array,
    departments: Array,
    empStatusOptions: Array,
    defaultCompanyId: Number,
})

const showImportDialog = ref(false);
const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'full_name'},
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
        itemsPerPage: 50,
        totalItems: 0
    },
    filters: {
        search: '',
        company_id: props.defaultCompanyId,
        department_id: null,
        status: null,
        emp_status: null,
        per_page: 50
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
    axios.post(route('employees.toggle-status', item.id))
        .then(() => toast('Employee status has been updated.'));
};

const handleSearch = () => {
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

    if (diffDays < 30) return 'success';
    if (diffDays < 180) return 'info';
    if (diffDays < 365) return 'warning';
    return 'primary';
};

const getFullName = (item) => {
    return `${item.first_name || ''} ${item.last_name || ''}`.trim() || '-';
};

const truncateEmail = (email, length = 25) => {
    if (!email) return '-';
    return email.length > length ? email.substring(0, length) + '...' : email;
};

const handleImportSuccess = () => {
    state.loading = true;
    getData(state.filters);
    showImportDialog.value = false;
};
</script>

<template>
    <DefaultLayout>
        <Head title="Employees"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <v-toolbar :rounded="true" class="border-b pr-3" color="#FFFFFF" density="compact">
                        <v-toolbar-title class="text-uppercase text-subtitle-2 font-weight-bold">
                            Employees
                        </v-toolbar-title>
                        <v-spacer></v-spacer>

                        <v-btn
                            class="bg-surface-variant ml-2"
                            prepend-icon="mdi-file-import"
                            size="small"
                            variant="elevated"
                            @click="showImportDialog = true"
                        >
                            Import
                        </v-btn>

                        <v-btn
                            :href="route('employees.create')"
                            class="ml-2"
                            color="darkgray"
                            size="small"
                            variant="elevated"
                        >
                            <v-icon icon="mdi-plus"></v-icon>
                            Add New
                        </v-btn>
                    </v-toolbar>

                    <EmployeeFilter
                        :filters="state.filters"
                        :companies="props.companies"
                        :departments="props.departments"
                        :empStatusOptions="props.empStatusOptions"
                        @handleFilter="handleSearch"
                    />
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
                                    color="primary"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.id_no || '-' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.full_name="{ item }">
                                <div class="d-flex align-center ga-2">
                                    <v-avatar size="30" color="primary" variant="tonal">
                                        <v-img v-if="item.avatar_url" :src="item.avatar_url" cover/>
                                        <span v-else class="text-caption text-uppercase">
                                            {{ item.first_name?.charAt(0) }}{{ item.last_name?.charAt(0) }}
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
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.department.name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.designation="{ item }">
                                <v-chip
                                    v-if="item.designation"
                                    class="font-weight-regular"
                                    color="secondary"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.designation.title }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.manager="{ item }">
                                <v-chip
                                    v-if="item.manager"
                                    class="font-weight-regular"
                                    color="info"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.manager.first_name }} {{ item.manager.last_name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.joining_date="{ item }">
                                <v-chip
                                    :color="getJoiningDateColor(item.joining_date)"
                                    class="font-weight-regular"
                                    size="x-small"
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

        <ImportEmployeeDialog
            v-model="showImportDialog"
            :companies="companies"
            :departments="departments"
            @success="handleImportSuccess"
        />
    </DefaultLayout>
</template>
