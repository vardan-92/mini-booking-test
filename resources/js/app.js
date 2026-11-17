import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';
import '../css/app.css';
import App from './App.vue';

const root = document.getElementById('app');

const theme = definePreset(Aura, {
    semantic: {
        colorScheme: {
            light: {
                primary: {
                    color: '{emerald.700}',
                    contrastColor: '#ffffff',
                    hoverColor: '{emerald.800}',
                    activeColor: '{emerald.900}',
                },
            },
        },
    },
});

createApp(App, { timezone: root.dataset.timezone, today: root.dataset.today })
    .use(PrimeVue, { theme: { preset: theme, options: { darkModeSelector: false } } })
    .mount(root);
