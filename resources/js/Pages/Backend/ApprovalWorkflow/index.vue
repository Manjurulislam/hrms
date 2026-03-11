<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
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
    });
};

const toggleStatus = (item) => {
    axios.post(route('approval-workflows.toggle-status', item.id))
        .then(() => toast('Workflow status updated.'));
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

                    <v-card-text class="pb-0">
                        <v-row>
                            <v-col cols="12" md="4">
                                <v-select
                                    v-model="state.filters.company_id"
                                    :items="props.companies"
                                    clearable
                                    density="compact"
                                    item-title="name"
                                    item-value="id"
                                    label="Company"
                                    variant="outlined"
                                    @update:model-value="handleSearch"
                                />
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="state.filters.search"
                                    clearable
                                    density="compact"
                                    label="Search"
                                    prepend-inner-icon="mdi-magnify"
                                    variant="outlined"
                                    @keyup.enter="handleSearch"
                                    @click:clear="handleSearch"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>

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
                                <v-chip v-if="item.company" color="primary" size="x-small" variant="tonal">
                                    {{ item.company.name }}
                                </v-chip>
                                <span v-else>-</span>
                            </template>
                            <template v-slot:item.steps_count="{item}">
                                <v-chip color="info" size="x-small" variant="tonal">
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
                                    color="bg-darkprimary"
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
