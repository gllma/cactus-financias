<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import { useDemoSession } from '../src/useDemoSession';

type Space = {
  id: number;
  name: string;
  role: string;
  membership_status: string;
  owner_email: string;
};

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const session = useDemoSession();
const currentTheme = ref<'light' | 'dark'>('light');
const spaces = ref<Space[]>([]);
const newSpaceName = ref('');
const inviteEmail = ref('');
const message = ref('');

async function authedFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${session.authToken.value}`,
      ...(options.headers ?? {}),
    },
  });

  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message ?? 'Falha na operação.');
  }
  return payload as T;
}

async function loadSpaces(): Promise<void> {
  const response = await authedFetch<{ data: Space[] }>('/api/spaces');
  spaces.value = response.data;
}

async function createSpace(): Promise<void> {
  const response = await authedFetch<{ data: Space }>('/api/spaces', {
    method: 'POST',
    body: JSON.stringify({ name: newSpaceName.value }),
  });
  newSpaceName.value = '';
  session.setActiveSpace(response.data.id);
  await loadSpaces();
  message.value = 'Espaço criado e ativado.';
}

async function activateSpace(spaceId: number): Promise<void> {
  await authedFetch('/api/auth/switch-space', {
    method: 'POST',
    body: JSON.stringify({ space_id: spaceId }),
  });
  session.setActiveSpace(spaceId);
  message.value = 'Espaço ativo atualizado.';
}

async function invite(spaceId: number): Promise<void> {
  await authedFetch(`/api/spaces/${spaceId}/invite`, {
    method: 'POST',
    body: JSON.stringify({ email: inviteEmail.value, role: 'member' }),
  });
  inviteEmail.value = '';
  message.value = 'Convite enviado.';
}

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
  localStorage.setItem('cactus_theme_preference', currentTheme.value);
  document.documentElement.setAttribute('data-theme', currentTheme.value);
}

onMounted(async () => {
  const cachedTheme = localStorage.getItem('cactus_theme_preference');
  if (cachedTheme === 'light' || cachedTheme === 'dark') currentTheme.value = cachedTheme;
  document.documentElement.setAttribute('data-theme', currentTheme.value);
  await loadSpaces();
});
</script>

<template>
  <AppHeader :user-name="session.userName" :current-theme="currentTheme" @toggleTheme="toggleTheme" />
  <section>
    <h1>Espaços</h1>
    <p class="muted">Crie seu espaço e participe de espaços compartilhados.</p>
    <div class="toolbar">
      <input v-model="newSpaceName" placeholder="Nome do novo espaço" />
      <button type="button" class="btn btn-primary" @click="createSpace">Criar espaço</button>
    </div>

    <div class="list">
      <article v-for="space in spaces" :key="space.id" class="item">
        <p><strong>{{ space.name }}</strong> · {{ space.role }} · {{ space.membership_status }}</p>
        <p class="muted">Proprietário: {{ space.owner_email }}</p>
        <div class="toolbar">
          <button type="button" class="btn btn-secondary" @click="activateSpace(space.id)">
            Ativar espaço
          </button>
          <input v-model="inviteEmail" placeholder="E-mail para convidar" />
          <button type="button" class="btn btn-primary" @click="invite(space.id)">Convidar</button>
        </div>
      </article>
    </div>

    <p v-if="message" class="muted">{{ message }}</p>
  </section>
</template>

<style scoped>
.toolbar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
}

.list {
  display: grid;
  gap: 10px;
  margin-top: 12px;
}

.item {
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 12px;
}

.muted {
  color: var(--muted-color);
}
</style>
