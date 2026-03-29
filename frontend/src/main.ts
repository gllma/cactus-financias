import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';

import ProfilePreferencesPage from '../pages/profile-preferences.vue';
import ObservabilityDashboardPage from '../pages/observability-dashboard.vue';
import VaultsPage from '../pages/vaults.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/profile-preferences' },
    { path: '/profile-preferences', component: ProfilePreferencesPage },
    { path: '/vaults', component: VaultsPage },
    { path: '/observability-dashboard', component: ObservabilityDashboardPage },
  ],
});

createApp(App).use(router).mount('#app');
