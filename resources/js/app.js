import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { MotionPlugin } from '@vueuse/motion';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.use(MotionPlugin);
app.use(Toast, { position: 'top-right', timeout: 2500 });

// hydrate auth on first paint
const auth = useAuthStore();
auth.bootstrap().finally(() => app.mount('#app'));
