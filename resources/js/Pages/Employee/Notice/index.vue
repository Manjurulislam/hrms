<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Title', key: 'title'},
        {title: 'Department', key: 'department'},
        {title: 'Published', key: 'published_at'},
        {title: 'Expires', key: 'expired_at'},
        {title: 'Actions', key: 'actions', sortable: false, width: '5%'},
    ],
    search: '',
    serverItems: [],
    loading: true,
});

const loadData = () => {
    state.loading = true;
    axios.get(route('emp-notices.get', {search: state.search})).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
    });
};

loadData();

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
};

const handleSearch = () => {
    loadData();
};
</script>

<template>
    <DefaultLayout>
        <Head title="Notice Board"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle icon="mdi-clipboard-text-outline" title="Notice Board"/>

                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="4">
                                <el-input
                                    v-model="state.search"
                                    clearable
                                    placeholder="Search notices..."
                                    style="width: 100%"
                                    @clear="handleSearch"
                                    @keyup.enter="handleSearch"
                                >
                                    <template #prefix>
                                        <v-icon size="small">mdi-magnify</v-icon>
                                    </template>
                                </el-input>
                            </v-col>
                        </v-row>

                        <v-data-table
                            :headers="state.headers"
                            :items="state.serverItems"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.department="{ item }">
                                <v-chip
                                    v-if="item.department"
                                    class="font-weight-regular"
                                    color="info"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.department.name }}
                                </v-chip>
                                <span v-else class="text-grey">General</span>
                            </template>
                            <template v-slot:item.published_at="{ item }">
                                {{ formatDate(item.published_at) }}
                            </template>
                            <template v-slot:item.expired_at="{ item }">
                                {{ item.expired_at ? formatDate(item.expired_at) : 'No Expiry' }}
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <Link :href="route('emp-notices.show', item.id)">
                                    <v-btn
                                        color="primary"
                                        icon="mdi-eye"
                                        size="x-small"
                                        variant="text"
                                    />
                                </Link>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
