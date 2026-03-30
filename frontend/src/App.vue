<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDemoSession } from './useDemoSession';

const session = useDemoSession();
const route = useRoute();
const router = useRouter();
const mobileMenuOpen = ref(false);
const currentTheme = ref<'light' | 'dark'>('light');
const isBooting = ref(true);
const showLoginSplash = ref(false);

const routeTitle = computed(() => {
  if (route.path.includes('spaces')) return 'Espaços';
  if (route.path.includes('vaults')) return 'Cofres';
  if (route.path.includes('observability')) return 'Observabilidade';
  if (route.path.includes('profile')) return 'Perfil';
  return 'Login';
});

const userInitials = computed(() =>
  session.userName.value
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((chunk) => chunk.charAt(0).toUpperCase())
    .join(''),
);

async function runLoginSplashIfNeeded(): Promise<void> {
  if (!session.isAuthenticated.value) return;
  if (sessionStorage.getItem('cactus_show_login_splash') !== '1') return;

  showLoginSplash.value = true;
  sessionStorage.removeItem('cactus_show_login_splash');

  await new Promise((resolve) => setTimeout(resolve, 1300));
  showLoginSplash.value = false;
}

onMounted(async () => {
  session.load();

  const cachedTheme = localStorage.getItem('cactus_theme_preference');
  if (cachedTheme === 'light' || cachedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', cachedTheme);
    currentTheme.value = cachedTheme;
  } else {
    document.documentElement.setAttribute('data-theme', currentTheme.value);
  }

  isBooting.value = false;
  await runLoginSplashIfNeeded();
});

watch(
  () => route.fullPath,
  () => {
    mobileMenuOpen.value = false;
  },
);

function logout(): void {
  session.logout();
  router.push('/login');
}

function toggleTheme(): void {
  currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
  localStorage.setItem('cactus_theme_preference', currentTheme.value);
  document.documentElement.setAttribute('data-theme', currentTheme.value);
}
</script>

<template>
  <main class="h-screen flex overflow-hidden bg-gray-50 dark:bg-gray-900">
    <div v-if="isBooting" class="fixed inset-0 z-[70] grid place-items-center bg-gray-950/70 backdrop-blur-sm">
      <div class="card p-8 text-center">
        <p class="text-4xl mb-2">🌵</p>
        <p class="text-base font-semibold text-gray-900 dark:text-white">Carregando Cactus Financias...</p>
      </div>
    </div>

    <div v-if="showLoginSplash" class="fixed inset-0 z-[80] grid place-items-center bg-emerald-950/85 backdrop-blur-sm">
      <div class="text-center text-white">
        <div class="mx-auto mb-4 grid place-items-center w-24 h-24 rounded-full bg-emerald-500/20 border border-emerald-300/40 animate-pulse">
          <span class="text-5xl">🌵</span>
        </div>
        <p class="text-xl font-semibold">Preparando seu espaço...</p>
      </div>
    </div>

    <template v-if="session.isAuthenticated">
      <div
        v-if="mobileMenuOpen"
        class="fixed inset-0 z-20 bg-gray-900/40 md:hidden"
        @click="mobileMenuOpen = false"
      ></div>

      <aside
        :class="[
          'fixed inset-y-0 left-0 z-30 w-64 flex-shrink-0 transform bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 transition-transform duration-200 md:translate-x-0 md:static',
          mobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
        ]"
      >
        <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700">
          <span class="text-lg font-semibold text-gray-900 dark:text-white">Cactus Financias</span>
        </div>

        <nav class="px-3 py-4">
          <router-link
            to="/vaults"
            class="flex items-center px-4 py-2 mt-2 rounded-lg text-sm font-medium transition-colors text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            active-class="bg-blue-50 text-blue-700 dark:bg-gray-700 dark:text-blue-400"
          >
            <svg class="w-5 h-5 mr-3 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M6 3h12l3 4H3l3-4Zm-1 4h14l-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7Z"/></svg>
            Cofres
          </router-link>
          <router-link
            to="/spaces"
            class="flex items-center px-4 py-2 mt-2 rounded-lg text-sm font-medium transition-colors text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            active-class="bg-blue-50 text-blue-700 dark:bg-gray-700 dark:text-blue-400"
          >
            <svg class="w-5 h-5 mr-3 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 2a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 21a6 6 0 1 1 12 0H2Zm12 0a5 5 0 0 1 8 0h-8Z"/></svg>
            Espaços
          </router-link>
          <router-link
            to="/profile-preferences"
            class="flex items-center px-4 py-2 mt-2 rounded-lg text-sm font-medium transition-colors text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            active-class="bg-blue-50 text-blue-700 dark:bg-gray-700 dark:text-blue-400"
          >
            <svg class="w-5 h-5 mr-3 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm8 10a8 8 0 1 0-16 0h16Z"/></svg>
            Perfil
          </router-link>
          <router-link
            to="/observability-dashboard"
            class="flex items-center px-4 py-2 mt-2 rounded-lg text-sm font-medium transition-colors text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            active-class="bg-blue-50 text-blue-700 dark:bg-gray-700 dark:text-blue-400"
          >
            <svg class="w-5 h-5 mr-3 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16M7 16V8m5 8V4m5 12v-6"/></svg>
            Observabilidade
          </router-link>
        </nav>
      </aside>

      <section class="flex-1 min-w-0 flex flex-col">
        <header
          class="h-16 flex justify-between items-center px-6 sticky top-0 z-10 bg-white/90 backdrop-blur-sm border-b border-gray-200 dark:bg-gray-800/90 dark:border-gray-700"
        >
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
              @click="mobileMenuOpen = !mobileMenuOpen"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <p class="text-sm text-gray-500 dark:text-gray-400">Dashboard / {{ routeTitle }}</p>
          </div>

          <div class="flex items-center space-x-4">
            <button
              type="button"
              class="inline-flex items-center justify-center w-11 h-11 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
              @click="toggleTheme"
            >
              <span v-if="currentTheme === 'light'">🌙</span>
              <span v-else>☀️</span>
            </button>
            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>
            <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-200">{{ session.userName }}</span>
            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">{{ userInitials }}</div>
            <button
              type="button"
              class="action-secondary"
              @click="logout"
            >
              Sair
            </button>
          </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-gray-50 dark:bg-gray-900">
          <router-view v-slot="{ Component, route: slotRoute }">
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-1"
              mode="out-in"
            >
              <component :is="Component" :key="slotRoute.fullPath" />
            </Transition>
          </router-view>
        </div>
      </section>
    </template>

    <section v-else class="flex-1 min-w-0 overflow-y-auto p-6 md:p-8 bg-gray-50 dark:bg-gray-900">
      <router-view v-slot="{ Component, route: slotRoute }">
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 scale-[0.98]"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-[0.98]"
          mode="out-in"
        >
          <component :is="Component" :key="slotRoute.fullPath" />
        </Transition>
      </router-view>
    </section>
  </main>
</template>
