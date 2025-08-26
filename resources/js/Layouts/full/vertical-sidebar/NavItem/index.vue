<script setup>
import {Icon} from '@iconify/vue';
import {Link} from "@inertiajs/vue3";

const props = defineProps({item: Object, level: Number});
</script>

<template>
    <!---Single Item-->
    <v-list-item
        v-scroll-to="{ el: '#top' }"
        :disabled="item.disabled"
        :href="item.type === 'external' ? item.to : ''"
        :target="item.type === 'external' ? '_blank' : ''"
        :to="item.type === 'external' ? '' : item.to"
        class="mb-1" rounded>
        <!---If icon-->
        <template v-slot:prepend>
            <Icon :class="'text-' + item.BgColor" :icon="'solar:' + item.icon" :level="level" class="dot" height="18"
                  width="18"/>
        </template>
        <v-list-item-title>
            <Link v-if="item.to" :href="route(item.to)">{{ item.title }}</Link>
            <Link v-else>{{ item.title }}</Link>
        </v-list-item-title>
        <!---If Caption-->
        <v-list-item-subtitle v-if="item.subCaption" class="text-caption mt-n1 hide-menu">
            {{ item.subCaption }}
        </v-list-item-subtitle>
        <!---If any chip or label-->
        <template v-if="item.chip" v-slot:append>
            <v-chip
                :class="'sidebarchip hide-menu bg-' + item.chipBgColor"
                :color="item.chipColor"
                :prepend-icon="item.chipIcon"
                :size="item.chipIcon ? 'small' : 'small'"
                :variant="item.chipVariant"
            >
                {{ item.chip }}
            </v-chip>
        </template>
    </v-list-item>
</template>
