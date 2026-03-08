<script setup>
import {Head, Link} from '@inertiajs/vue3';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';

const props = defineProps({
    notice: Object,
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
};
</script>

<template>
    <DefaultLayout>
        <Head :title="notice.title"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'emp-notices.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-clipboard-text-outline"
                        title="Notice Details"
                    />

                    <v-card-text class="pa-6">
                        <h2 class="text-h5 font-weight-bold mb-4">{{ notice.title }}</h2>

                        <v-row class="mb-4">
                            <v-col cols="auto">
                                <v-chip color="primary" size="small" variant="tonal">
                                    {{ notice.company?.name }}
                                </v-chip>
                            </v-col>
                            <v-col v-if="notice.department" cols="auto">
                                <v-chip color="info" size="small" variant="tonal">
                                    {{ notice.department.name }}
                                </v-chip>
                            </v-col>
                            <v-col cols="auto">
                                <v-chip color="success" size="small" variant="tonal">
                                    Published: {{ formatDate(notice.published_at) }}
                                </v-chip>
                            </v-col>
                            <v-col v-if="notice.expired_at" cols="auto">
                                <v-chip color="warning" size="small" variant="tonal">
                                    Expires: {{ formatDate(notice.expired_at) }}
                                </v-chip>
                            </v-col>
                        </v-row>

                        <v-divider class="mb-4"/>

                        <div class="text-body-1" style="white-space: pre-wrap;">{{ notice.description }}</div>

                        <v-divider class="my-4"/>

                        <div class="text-caption text-grey">
                            Posted by: {{ notice.creator?.name || '-' }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
