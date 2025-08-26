<script lang="ts" setup>
import {ref} from 'vue';
import {useTheme} from 'vuetify';
import {useCustomizerStore} from '@/Service/stores/customizer';
import {Icon} from '@iconify/vue';

const theme = useTheme();
const customizer = useCustomizerStore();

// themes color options
const themeColors = ref([
    {
        name: 'BLUE_THEME',
        bg: 'togglethemeBlue'
    },
    {
        name: 'DARK_BLUE_THEME',
        bg: 'togglethemeDarkBlue'
    }
]);
</script>

<template>
    <div class="position-relative">
        <v-item-group v-model="customizer.actTheme" class="d-flex" mandatory>
            <div v-for="theme in themeColors" :key="theme.name">
                <v-item v-slot="{toggle }" :value="theme.name">
                    <v-sheet class="cursor-pointer text-center hover-btns" elevation="0" rounded="circle"
                             @click="toggle">
                        <v-btn :class="theme.bg" class="custom-hover-primary" color="primary" icon size="small"
                               variant="text">
                            <Icon v-if="theme.bg == 'togglethemeBlue'" :class="theme.bg" height="22"
                                  icon="solar:sun-2-line-duotone"/>
                            <Icon v-if="theme.bg == 'togglethemeDarkBlue'" :class="theme.bg"
                                  height="22" icon="solar:moon-line-duotone"/>
                        </v-btn>
                    </v-sheet>
                </v-item>
            </div>
        </v-item-group>
    </div>
</template>
