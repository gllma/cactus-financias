<script setup lang="ts">
import { onMounted } from 'vue';
import AvatarInitials from '../components/AvatarInitials.vue';
import AppHeader from '../components/AppHeader.vue';
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

async function toggleTheme() {
  await setTheme(handler.currentTheme === 'light' ? 'dark' : 'light');
}
</script>

<template>
  <AppHeader :user-name="session.userName" :current-theme="handler.currentTheme" @toggleTheme="toggleTheme" />
  <section>
    <h1>Preferências de Perfil</h1>
    <AvatarInitials :name="session.userName" />

    <p>Tema atual: {{ handler.currentTheme }}</p>
    <button type="button" @click="setTheme('light')">Tema Claro</button>
    <button type="button" @click="setTheme('dark')">Tema Escuro</button>

    <p v-if="handler.loading">Salvando...</p>
    <p v-if="handler.errorMessage">{{ handler.errorMessage }}</p>
  </section>
</template>
