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
  <main class="app-shell">
    <section class="session-bar card">
      <input v-model="session.userName" placeholder="Nome" />
      <input v-model="session.userEmail" placeholder="E-mail" />
      <button type="button" class="btn btn-primary" @click="session.save">Salvar sessão</button>
    </section>
    <nav class="main-nav">
      <router-link to="/profile-preferences">Perfil</router-link>
      <router-link to="/vaults">Cofres</router-link>
      <router-link to="/observability-dashboard">Observabilidade</router-link>
    </nav>
    <router-view class="page-content" />
  </main>
</template>

<style>
:root {
  color-scheme: light;
  --bg-color: #f8fafc;
  --card-color: #ffffff;
  --text-color: #0f172a;
  --muted-color: #475569;
  --border-color: #cbd5e1;
  --primary-color: #0f766e;
  --primary-hover-color: #0d5f59;
  --shadow-color: rgba(15, 23, 42, 0.08);
}

:root[data-theme='dark'] {
  color-scheme: dark;
  --bg-color: #020617;
  --card-color: #0f172a;
  --text-color: #e2e8f0;
  --muted-color: #94a3b8;
  --border-color: #334155;
  --primary-color: #14b8a6;
  --primary-hover-color: #0f988a;
  --shadow-color: rgba(2, 6, 23, 0.45);
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

.app-shell {
  min-height: 100vh;
  width: min(1100px, calc(100% - 32px));
  margin: 0 auto;
  padding: 16px 0 24px;
}

.session-bar {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-top: 0;
}

section {
  margin: 16px;
  padding: 16px;
  border: 1px solid color-mix(in oklab, var(--border-color), transparent 20%);
  border-radius: 14px;
  background: var(--card-color);
  box-shadow: 0 10px 30px -18px var(--shadow-color);
}

input,
button,
select {
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 8px 10px;
  background: var(--card-color);
  color: var(--text-color);
  width: 100%;
}

input:focus,
button:focus,
select:focus {
  outline: 2px solid color-mix(in oklab, var(--primary-color), white 35%);
  outline-offset: 1px;
}

a {
  color: inherit;
}

.btn {
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
}

.btn-primary {
  background: var(--primary-color);
  color: #fff;
}

.btn-primary:hover {
  background: var(--primary-hover-color);
}

.btn-secondary {
  border-color: var(--border-color);
}

.main-nav {
  display: flex;
  gap: 8px;
  padding: 6px;
  margin: 16px;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: var(--card-color);
}

.main-nav a {
  padding: 10px 14px;
  border-radius: 10px;
  text-decoration: none;
  color: var(--muted-color);
  font-weight: 600;
}

.main-nav a.router-link-active {
  background: color-mix(in oklab, var(--primary-color), transparent 86%);
  color: var(--primary-color);
}

.page-content {
  display: block;
}

.card {
  margin: 16px;
}

@media (max-width: 820px) {
  .session-bar {
    grid-template-columns: 1fr;
  }

  .main-nav {
    overflow-x: auto;
  }
}
</style>
