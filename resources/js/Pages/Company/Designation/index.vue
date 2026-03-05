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
        {title: 'Title', key: 'title'},
        {title: 'Level', key: 'level', align: 'center'},
        {title: 'Parent', key: 'parent'},
        {title: 'Description', key: 'description', sortable: false},
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
    axios.get(route('company.designations.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.post(route('company.designations.toggle-status', item.id))
        .then(() => toast('Designation status has been updated.'));
};

const truncateText = (text, length = 40) => {
    if (!text) return '-';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <DefaultLayout>
        <Head title="Designations"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'company.designations.create'}"
                        icon="mdi-plus"
                        title="Designations"
                    />

                    <v-card-text>
                        <v-text-field
                            v-model="state.filters.search"
                            class="mb-4"
                            clearable
                            density="compact"
                            hide-details
                            label="Search designations..."
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
                            item-value="title"
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
                            <template v-slot:item.level="{ item }">
                                <v-chip
                                    class="font-weight-medium"
                                    color="secondary"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ item.level }}
                                </v-chip>
                            </template>
                            <template v-slot:item.parent="{ item }">
                                <v-chip
                                    v-if="item.parent"
                                    class="font-weight-regular"
                                    color="info"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ item.parent.title }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.description="{ item }">
                                <span
                                    v-if="item.description"
                                    :title="item.description"
                                    class="text-body-2"
                                >
                                    {{ truncateText(item.description, 50) }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('company.designations.edit', item.id)"
                                    color="bg-darkprimary"
                                    icon="mdi-pencil"/>

                                <btn-delete
                                    :route="route('company.designations.destroy', item.id)"
                                    color="bg-red-darken-2"
                                    icon="mdi-delete"
                                    size="small"
                                    title="Delete Designation"
                                />
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
