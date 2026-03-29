<script setup lang="ts">
import { onMounted } from 'vue';
import { useDemoSession } from './useDemoSession';

const session = useDemoSession();

onMounted(() => {
  session.load();

  const cachedTheme = localStorage.getItem('cactus_theme_preference');
  if (cachedTheme === 'light' || cachedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', cachedTheme);
  }
});
</script>

<template>
  <main>
    <section style="display: flex; gap: 8px; padding: 12px; border-bottom: 1px solid #ddd; align-items: center;">
      <input v-model="session.userName" placeholder="Nome" />
      <input v-model="session.userEmail" placeholder="E-mail" />
      <button type="button" @click="session.save">Salvar sessão</button>
    </section>
    <nav style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #ddd;">
      <router-link to="/profile-preferences">Perfil</router-link>
      <router-link to="/observability-dashboard">Observabilidade</router-link>
    </nav>
    <router-view />
  </main>
</template>
´h
<style>
:root {
  color-scheme: light;
  --bg-color: #f8fafc;
  --card-color: #ffffff;
  --text-color: #0f172a;
  --muted-color: #475569;
  --border-color: #cbd5e1;
}

:root[data-theme='dark'] {
  color-scheme: dark;
  --bg-color: #020617;
  --card-color: #0f172a;
  --text-color: #e2e8f0;
  --muted-color: #94a3b8;
  --border-color: #334155;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  background: var(--bg-color);
  color: var(--text-color);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

main {
  min-height: 100vh;
}

section {
  margin: 16px;
  padding: 16px;
  border: 1px solid var(--border-color);
  border-radius: 10px;
  background: var(--card-color);
}

input,
button {
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 8px 10px;
  background: var(--card-color);
  color: var(--text-color);
}

a {
  color: inherit;
}
</style>
