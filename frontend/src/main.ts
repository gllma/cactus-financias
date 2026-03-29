import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';

import ProfilePreferencesPage from '../pages/profile-preferences.vue';
import ObservabilityDashboardPage from '../pages/observability-dashboard.vue';
import VaultsPage from '../pages/vaults.vue';
import LoginPage from '../pages/login.vue';
import SpacesPage from '../pages/spaces.vue';
import { useDemoSession } from './useDemoSession';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginPage },
    { path: '/profile-preferences', component: ProfilePreferencesPage },
    { path: '/spaces', component: SpacesPage },
    { path: '/vaults', component: VaultsPage },
    { path: '/observability-dashboard', component: ObservabilityDashboardPage },
  ],
});

router.beforeEach((to) => {
  const session = useDemoSession();
  session.load();

  if (!session.isAuthenticated.value && to.path !== '/login') {
    return '/login';
  }

  if (session.isAuthenticated.value && to.path === '/login') {
    return '/vaults';
  }

  return true;
});

createApp(App).use(router).mount('#app');
