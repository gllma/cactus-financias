<script setup lang="ts">
import { onMounted } from 'vue';
import AvatarInitials from '../components/AvatarInitials.vue';
import { ProfileService } from '../modules/profile/services/profileService';
import { useProfileThemeHandler } from '../modules/profile/handlers/useProfileThemeHandler';
import { useApplyThemeHandler } from '../modules/profile/handlers/useApplyThemeHandler';
import { useDemoSession } from '../src/useDemoSession';

const session = useDemoSession();
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

const httpClient = {
  get: async <T>(url: string): Promise<T> => {
    const response = await fetch(`${apiBaseUrl}${url}`, {
      credentials: 'include',
      headers: {
        'X-User-Email': session.userEmail.value,
        'X-User-Name': session.userName.value,
        Authorization: `Bearer ${session.authToken.value}`,
        ...(session.activeSpaceId.value ? { 'X-Space-Id': String(session.activeSpaceId.value) } : {}),
      },
    });
    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload?.message ?? 'Falha ao buscar preferência de tema.');
    }

    return payload;
  },
  patch: async <T>(url: string, payload: unknown): Promise<T> => {
    const response = await fetch(`${apiBaseUrl}${url}`, {
      method: 'PATCH',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-User-Email': session.userEmail.value,
        'X-User-Name': session.userName.value,
        Authorization: `Bearer ${session.authToken.value}`,
        ...(session.activeSpaceId.value ? { 'X-Space-Id': String(session.activeSpaceId.value) } : {}),
      },
      body: JSON.stringify(payload),
    });

    const body = await response.json();
    if (!response.ok) {
      throw new Error(body?.message ?? 'Falha ao salvar preferência de tema.');
    }

    return body;
  },
};

const handler = useProfileThemeHandler(new ProfileService(httpClient));
useApplyThemeHandler(handler.currentTheme);

onMounted(async () => {
  await handler.loadPersistedTheme();
});

async function setTheme(theme: 'light' | 'dark') {
  await handler.updateTheme({ theme });
}

</script>

<template>
  <section class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
      <div class="flex items-center justify-between gap-4 mb-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Preferências de Perfil</h1>
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Personalize sua experiência visual no sistema.</p>
        </div>
        <AvatarInitials :name="session.userName" />
      </div>

      <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Tema atual: <span class="text-gray-900 dark:text-white">{{ handler.currentTheme }}</span></p>
      <div class="flex flex-wrap gap-3">
        <button type="button" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" @click="setTheme('light')">Tema Claro</button>
        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900" @click="setTheme('dark')">Tema Escuro</button>
      </div>
    </div>

    <p v-if="handler.loading" class="text-sm font-medium text-gray-500 dark:text-gray-400">Salvando...</p>
    <p v-if="handler.errorMessage" class="text-sm font-medium text-red-600 dark:text-red-400">{{ handler.errorMessage }}</p>
  </section>
</template>
