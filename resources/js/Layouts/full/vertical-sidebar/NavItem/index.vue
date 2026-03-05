<script setup>
import {Icon} from '@iconify/vue';
import {router, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';

const props = defineProps({item: Object, level: Number});

const page = usePage();

const currentUrl = computed(() => page.url);

const isActive = computed(() => {
    if (!props.item.to) return false;

    try {
        const currentPath = currentUrl.value.split('?')[0];
        const targetPath = new URL(route(props.item.to)).pathname;

        // Exact match
        if (currentPath === targetPath) {
            return true;
        }

        // Prefix match (e.g. /employees matches /employees/create)
        if (targetPath !== '/' && currentPath.startsWith(targetPath + '/')) {
            return true;
        }
    } catch (error) {
        // Route doesn't exist
    }

    return false;
});

const navigate = () => {
    if (props.item.disabled || !props.item.to) return;
    if (props.item.type === 'external') return;
    router.visit(route(props.item.to));
};
</script>

<template>
    <!-- External Links -->
    <v-list-item
        v-if="item.type === 'external'"
        :active="isActive"
        :disabled="item.disabled"
        :href="item.to"
        rounded
        class="mb-1"
        target="_blank"
    >
        <template v-slot:prepend>
            <Icon :class="'text-' + item.BgColor" :icon="'solar:' + item.icon" :level="level" class="dot" height="18"
                  width="18"/>
        </template>
        <v-list-item-title>{{ item.title }}</v-list-item-title>
        <v-list-item-subtitle v-if="item.subCaption" class="text-caption mt-n1 hide-menu">
            {{ item.subCaption }}
        </v-list-item-subtitle>
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

    <!-- Internal Links -->
    <v-list-item
        v-else
        :active="isActive"
        :disabled="item.disabled"
        rounded
        class="mb-1"
        @click="navigate"
    >
        <template v-slot:prepend>
            <Icon :class="'text-' + item.BgColor" :icon="'solar:' + item.icon" :level="level" class="dot"
                  height="18" width="18"/>
        </template>
        <v-list-item-title>{{ item.title }}</v-list-item-title>
        <v-list-item-subtitle v-if="item.subCaption" class="text-caption mt-n1 hide-menu">
            {{ item.subCaption }}
        </v-list-item-subtitle>
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
