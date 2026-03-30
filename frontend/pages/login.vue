<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useDemoSession } from '../src/useDemoSession';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const session = useDemoSession();
const userName = computed({
  get: () => session.userName.value,
  set: (value: string) => {
    session.userName.value = value;
    session.save();
  },
});
const userEmail = computed({
  get: () => session.userEmail.value,
  set: (value: string) => {
    session.userEmail.value = value;
    session.save();
  },
});

const router = useRouter();
const loading = ref(false);
const error = ref('');
const password = ref('123456');

async function login(): Promise<void> {
  loading.value = true;
  error.value = '';
  try {
    const response = await fetch(`${apiBaseUrl}/api/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: userEmail.value,
        name: userName.value,
        password: password.value,
      }),
    });

    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload?.message ?? 'Não foi possível entrar.');
    }

    sessionStorage.setItem('cactus_show_login_splash', '1');
    session.setAuth(payload.data.token);
    if (payload.data.active_space_id) {
      session.setActiveSpace(payload.data.active_space_id);
    }
    await router.push('/vaults');
  } catch (err) {
    error.value = (err as Error).message;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="min-h-full grid lg:grid-cols-2 gap-6 items-stretch">
    <div class="hidden lg:flex card p-8 flex-col justify-between bg-gradient-to-br from-blue-600 to-blue-800 text-white border-blue-600">
      <div>
        <p class="uppercase text-xs tracking-widest text-blue-100">Cactus Financias</p>
        <h1 class="mt-4 text-3xl font-bold">Gestão financeira inteligente para seus espaços</h1>
        <p class="mt-3 text-blue-100">Controle cofres, acompanhe movimentações e monitore a saúde operacional em um painel moderno.</p>
      </div>
      <p class="text-sm text-blue-100">Padrão SaaS com foco em clareza, produtividade e segurança.</p>
    </div>

    <div class="card p-6 md:p-8 self-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Entrar</h1>
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-6">Acesse sua conta para gerenciar seus espaços e finanças compartilhadas.</p>

      <div class="grid grid-cols-1 gap-4">
        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</span>
          <input v-model="userName" placeholder="Nome" class="input-control" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">E-mail</span>
          <input v-model="userEmail" placeholder="E-mail" class="input-control" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Senha</span>
          <input v-model="password" placeholder="Senha" type="password" class="input-control" />
        </label>
      </div>

      <div class="mt-6 flex items-center gap-3">
        <button type="button" class="action-primary" @click="login" :disabled="loading">
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
        <RouterLink to="/register" class="action-secondary">Criar conta</RouterLink>
      </div>

      <p v-if="error" class="mt-4 text-sm font-medium text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
  </section>
</template>
