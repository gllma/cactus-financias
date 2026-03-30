<script setup lang="ts">
import { onMounted, ref } from 'vue';
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

onMounted(async () => {
  await loadSpaces();
});
</script>

<template>
  <section class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Espaços</h1>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6 space-y-4">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Crie seu espaço e participe de espaços compartilhados.</p>
      <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4">
        <input v-model="newSpaceName" placeholder="Nome do novo espaço" class="input-control" />
        <button type="button" class="action-primary" @click="createSpace">Criar espaço</button>
      </div>
    </div>

    <div class="grid gap-4">
      <article v-for="space in spaces" :key="space.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
        <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ space.name }}</p>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ space.role }} · {{ space.membership_status }}</p>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Proprietário: {{ space.owner_email }}</p>
        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr_auto] gap-3 mt-4">
          <button type="button" class="action-secondary" @click="activateSpace(space.id)">
            Ativar espaço
          </button>
          <input v-model="inviteEmail" placeholder="E-mail para convidar" class="input-control" />
          <button type="button" class="action-primary" @click="invite(space.id)">Convidar</button>
        </div>
      </article>
    </div>

    <p v-if="message" class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ message }}</p>
  </section>
</template>
