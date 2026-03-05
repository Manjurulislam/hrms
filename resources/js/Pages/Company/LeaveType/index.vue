<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';
import BtnDelete from "@/Components/common/utility/BtnDelete.vue";

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'name'},
        {title: 'Max Days/Year', key: 'max_per_year', align: 'center'},
        {title: 'Status', key: 'status', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'}
    ],
    pagination: {
        itemsPerPage: 10,
        totalItems: 0
    },
    filters: {
        search: '',
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
    axios.get(route('company.leave-types.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.post(route('company.leave-types.toggle-status', item.id))
        .then(() => toast('Leave type status has been updated.'));
};

const getDaysColor = (days) => {
    if (days <= 5) return 'error';
    if (days <= 15) return 'warning';
    if (days <= 30) return 'info';
    return 'success';
};

const getDaysLabel = (days) => {
    if (days === 1) return '1 Day';
    return `${days} Days`;
};
</script>

<template>
    <DefaultLayout>
        <Head title="Leave Types"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'company.leave-types.create'}"
                        icon="mdi-plus"
                        title="Leave Types"
                    />

                    <v-card-text>
                        <v-text-field
                            v-model="state.filters.search"
                            class="mb-4"
                            clearable
                            density="compact"
                            hide-details
                            label="Search leave types..."
                            prepend-inner-icon="mdi-magnify"
                            variant="outlined"
                            @keyup.enter="getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []})"
                        />

                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            :search="state.filters.search"
                            density="compact"
                            item-value="name"
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
                            <template v-slot:item.max_per_year="{ item }">
                                <v-chip
                                    :color="getDaysColor(item.max_per_year)"
                                    class="font-weight-medium"
                                    size="small"
                                    variant="flat"
                                >
                                    <v-icon size="small" start>mdi-calendar-clock</v-icon>
                                    {{ getDaysLabel(item.max_per_year) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('company.leave-types.edit', item.id)"
                                    color="bg-darkprimary"
                                    icon="mdi-pencil"/>

                                <btn-delete
                                    :route="route('company.leave-types.destroy', item.id)"
                                    color="bg-red-darken-2"
                                    icon="mdi-delete"
                                    size="small"
                                    title="Delete Leave Type"
                                />
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
