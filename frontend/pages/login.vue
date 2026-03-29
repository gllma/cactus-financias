<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useDemoSession } from '../src/useDemoSession';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const session = useDemoSession();
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
        email: session.userEmail.value,
        name: session.userName.value,
        password: password.value,
      }),
    });

    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload?.message ?? 'Não foi possível entrar.');
    }

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
  <section>
    <h1>Entrar</h1>
    <p class="muted">Acesse sua conta para gerenciar seus espaços e finanças compartilhadas.</p>
    <div class="toolbar">
      <input v-model="session.userName" placeholder="Nome" />
      <input v-model="session.userEmail" placeholder="E-mail" />
      <input v-model="password" placeholder="Senha" type="password" />
      <button type="button" class="btn btn-primary" @click="login" :disabled="loading">
        {{ loading ? 'Entrando...' : 'Entrar' }}
      </button>
    </div>
    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

<style scoped>
.toolbar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.muted {
  color: var(--muted-color);
}

.error {
  color: #dc2626;
}
</style>
