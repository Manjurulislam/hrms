<script setup>
import axios from 'axios';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {computed, reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useDisplay} from 'vuetify';

const {smAndDown} = useDisplay();

const allHeaders = [
    {title: 'SL', align: 'start', sortable: false, key: 'id', mobile: true},
    {title: 'Title', key: 'title', mobile: true},
    {title: 'Department', key: 'department', mobile: false},
    {title: 'Published', key: 'published_at', mobile: true},
    {title: 'Expires', key: 'expired_at', mobile: false},
    {title: 'Actions', key: 'actions', sortable: false, width: '5%', mobile: true},
];

const headers = computed(() =>
    smAndDown.value ? allHeaders.filter(h => h.mobile) : allHeaders
);

const state = reactive({
    search: '',
    serverItems: [],
    loading: true,
});

const loadData = () => {
    state.loading = true;
    axios.get(route('emp-notices.get', {search: state.search})).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
    }).catch(() => {
        state.loading = false;
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
                            :headers="headers"
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
