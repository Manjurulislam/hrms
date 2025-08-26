<template>
    <v-card-title class="border-b">
        <form @submit.prevent="form.get(route)">
            <v-row>
                <v-col cols="10">
                    <el-input v-model="form.search" style="width: 100%" placeholder="Search..." />
                </v-col>
                <v-col v-if="dateSearch" cols="5">
                    <el-date-picker
                        v-model="form.search_date"
                        end-placeholder="End date"
                        range-separator="To"
                        size="default"
                        start-placeholder="Start date"
                        style="width: 100%"
                        type="daterange"
                        value-format="YYYY-MM-DD"
                    />
                </v-col>

                <v-col cols="2">
                    <v-btn class="bg-secondary" color="darkText" size="small" density="default" type="submit">
                        <i aria-hidden="true" class="mdi mdi-magnify"></i>
                    </v-btn>

<!--                    <v-btn color="primary" size="small" class="ml-1" density="default" @click="toggleCheckbox">-->
<!--                        <i aria-hidden="true" class="mdi mdi-delete"></i>-->
<!--                    </v-btn>-->

                    <Link :href="clear"
                          class="v-btn v-btn--elevated
                          v-theme--light bg-error
                          v-btn--density-default
                          v-btn--size-small
                          v-btn--variant-elevated ml-1"
                    >
                        <i aria-hidden="true" class="mdi mdi-brush-outline"></i>
                    </Link>
                </v-col>
            </v-row>
        </form>
    </v-card-title>
</template>

<script setup>
import {Link, useForm} from '@inertiajs/vue3';

const props = defineProps({
    clear: String,
    route: String,
    filters: Object,
    label: String,
    dateSearch: false
});

const form = useForm({
    search: props.filters.serach,
    search_date: props.filters.search_date,
    isChecked: props.filters.isChecked,
})


const toggleCheckbox = () => {
    form.isChecked = !form.isChecked;
};
</script>

<style scoped>

</style>
