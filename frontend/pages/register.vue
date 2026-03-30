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
const password = ref('');

async function register(): Promise<void> {
  loading.value = true;
  error.value = '';
  try {
    const response = await fetch(`${apiBaseUrl}/api/auth/register`, {
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
      throw new Error(payload?.message ?? 'Não foi possível cadastrar.');
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
    <div class="hidden lg:flex card p-8 flex-col justify-between bg-gradient-to-br from-emerald-600 to-emerald-800 text-white border-emerald-600">
      <div>
        <p class="uppercase text-xs tracking-widest text-emerald-100">Cadastro</p>
        <h1 class="mt-4 text-3xl font-bold">Crie seu espaço financeiro</h1>
        <p class="mt-3 text-emerald-100">Cadastre-se para começar a organizar cofres, movimentações e colaboração entre espaços.</p>
      </div>
      <p class="text-sm text-emerald-100">Fluxo dedicado de autenticação e cadastro.</p>
    </div>

    <div class="card p-6 md:p-8 self-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Criar conta</h1>
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-6">Preencha os dados para iniciar seu acesso.</p>

      <div class="grid grid-cols-1 gap-4">
        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</span>
          <input v-model="userName" placeholder="Nome completo" class="input-control" />
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
        <button type="button" class="action-primary" @click="register" :disabled="loading">
          {{ loading ? 'Cadastrando...' : 'Cadastrar' }}
        </button>
        <RouterLink to="/login" class="action-secondary">Já tenho conta</RouterLink>
      </div>

      <p v-if="error" class="mt-4 text-sm font-medium text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
  </section>
</template>
