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

async function toggleTheme() {
  await setTheme(handler.currentTheme === 'light' ? 'dark' : 'light');
}
</script>

<template>
  <AppHeader :user-name="session.userName" :current-theme="handler.currentTheme" @toggleTheme="toggleTheme" />
  <section class="profile-card">
    <div class="profile-top">
      <div>
        <h1>Preferências de Perfil</h1>
        <p class="muted">Personalize sua experiência visual no sistema.</p>
      </div>
      <AvatarInitials :name="session.userName" />
    </div>

    <p><strong>Tema atual:</strong> {{ handler.currentTheme }}</p>
    <div class="theme-actions">
      <button type="button" class="btn btn-secondary" @click="setTheme('light')">Tema Claro</button>
      <button type="button" class="btn btn-primary" @click="setTheme('dark')">Tema Escuro</button>
    </div>

    <p v-if="handler.loading" class="muted">Salvando...</p>
    <p v-if="handler.errorMessage" class="error">{{ handler.errorMessage }}</p>
  </section>
</template>

<style scoped>
.profile-card {
  position: relative;
}

.profile-card::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  padding: 1px;
  background: linear-gradient(130deg, rgba(45, 212, 191, 0.45), rgba(59, 130, 246, 0.2));
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

.profile-top {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  margin-bottom: 10px;
}

.theme-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.muted {
  color: var(--muted-color);
}

.error {
  color: #dc2626;
  font-weight: 600;
}
</style>
