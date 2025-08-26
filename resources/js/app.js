import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {createPinia} from 'pinia'
import {ZiggyVue} from '../../vendor/tightenco/ziggy';

//========================================
import Toast from "vue-toastification";
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
//========================================
import vuetify from '@/Service/plugins/vuetify';
import '../css/scss/style.scss';
import "vue-toastification/dist/index.css";
//========================================
import {PerfectScrollbarPlugin} from 'vue3-perfect-scrollbar';
import VueTablerIcons from 'vue-tabler-icons';
import VueScrollTo from 'vue-scrollto';
//========================================

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(Toast)
            .use(ElementPlus)
            .use(vuetify)
            .use(VueTablerIcons)
            .use(VueScrollTo)
            .use(pinia)
            .use(PerfectScrollbarPlugin)
            .use(ZiggyVue)
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});
