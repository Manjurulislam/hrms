<script setup>
import axios from 'axios';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import ApprovalWorkflowFilter from '@/Components/common/filter/ApprovalWorkflowFilter.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';

const props = defineProps({
    companies: Array,
    defaultCompanyId: Number,
});

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'name'},
        {title: 'Company', key: 'company'},
        {title: 'Steps', key: 'steps_count', align: 'center'},
        {title: 'Status', key: 'is_active', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'},
    ],
    pagination: {itemsPerPage: 50, totalItems: 0},
    filters: {
        search: '',
        company_id: props.defaultCompanyId,
        per_page: 50,
    },
    serverItems: [],
    loading: true,
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    axios.get(route('approval-workflows.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data.map(item => ({
            ...item,
            steps_count: item.steps?.length || 0,
        }));
        state.pagination.totalItems = data.total;
    }).catch(() => {
        state.loading = false;
        toast.error('Failed to load workflows.');
    });
};

const toggleStatus = (item) => {
    axios.post(route('approval-workflows.toggle-status', item.id))
        .then(() => toast('Workflow status updated.'))
        .catch(() => toast.error('Failed to update status.'));
};

const handleSearch = () => {
    state.loading = true;
    getData(state.filters);
};
</script>

<template>
    <DefaultLayout>
        <Head title="Approval Workflows"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New', route: 'approval-workflows.create'}"
                        icon="mdi-plus"
                        title="Approval Workflows"
                    />

                    <ApprovalWorkflowFilter
                        :filters="state.filters"
                        :companies="props.companies"
                        @refresh="handleSearch"
                    />

                    <v-card-text>
                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            density="compact"
                            item-value="name"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{index}">{{ index + 1 }}</template>
                            <template v-slot:item.company="{item}">
                                <v-chip v-if="item.company" size="x-small" variant="outlined">
                                    {{ item.company.name }}
                                </v-chip>
                                <span v-else>-</span>
                            </template>
                            <template v-slot:item.steps_count="{item}">
                                <v-chip color="info" size="x-small" variant="outlined">
                                    {{ item.steps_count }} {{ item.steps_count === 1 ? 'step' : 'steps' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.is_active="{item}">
                                <v-switch
                                    v-model="item.is_active"
                                    color="success"
                                    density="compact"
                                    hide-details
                                    @change="() => toggleStatus(item)"
                                />
                            </template>
                            <template v-slot:item.actions="{item}">
                                <btn-link
                                    :route="route('approval-workflows.edit', item.id)"
                                    color="text-primary"
                                    icon="mdi-pencil"
                                />
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
