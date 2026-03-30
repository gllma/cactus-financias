import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import './styles.css';

import ProfilePreferencesPage from '../pages/profile-preferences.vue';
import ObservabilityDashboardPage from '../pages/observability-dashboard.vue';
import VaultsPage from '../pages/vaults.vue';
import LoginPage from '../pages/login.vue';
import RegisterPage from '../pages/register.vue';
import SpacesPage from '../pages/spaces.vue';
import { useDemoSession } from './useDemoSession';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginPage },
    { path: '/register', component: RegisterPage },
    { path: '/profile-preferences', component: ProfilePreferencesPage },
    { path: '/spaces', component: SpacesPage },
    { path: '/vaults', component: VaultsPage },
    { path: '/observability-dashboard', component: ObservabilityDashboardPage },
  ],
});

router.beforeEach((to) => {
  const session = useDemoSession();
  session.load();

  if (!session.isAuthenticated.value && !['/login', '/register'].includes(to.path)) {
    return '/login';
  }

  if (session.isAuthenticated.value && ['/login', '/register'].includes(to.path)) {
    return '/vaults';
  }

  return true;
});

createApp(App).use(router).mount('#app');
