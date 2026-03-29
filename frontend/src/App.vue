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

function logout(): void {
  session.logout();
}
</script>

<template>
  <main class="app-shell">
    <section v-if="session.isAuthenticated" class="session-bar card">
      <input v-model="session.userName" placeholder="Nome" />
      <input v-model="session.userEmail" placeholder="E-mail" />
      <button type="button" class="btn btn-primary" @click="session.save">Salvar sessão</button>
      <button type="button" class="btn btn-secondary" @click="logout">Sair</button>
    </section>
    <nav v-if="session.isAuthenticated" class="main-nav">
      <router-link to="/spaces">Espaços</router-link>
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
  --bg-color: #042f2e;
  --card-color: rgba(255, 255, 255, 0.92);
  --text-color: #0f172a;
  --muted-color: #5b6b84;
  --border-color: rgba(99, 119, 152, 0.35);
  --primary-color: #0f766e;
  --primary-hover-color: #0b5f5a;
  --shadow-color: rgba(3, 16, 37, 0.28);
  --glass-blur: blur(12px);
}

:root[data-theme='dark'] {
  color-scheme: dark;
  --bg-color: #031b1e;
  --card-color: rgba(9, 22, 42, 0.85);
  --text-color: #e2e8f0;
  --muted-color: #8fa7c3;
  --border-color: rgba(98, 123, 164, 0.4);
  --primary-color: #22c4b4;
  --primary-hover-color: #18a79a;
  --shadow-color: rgba(2, 6, 23, 0.55);
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  background: var(--bg-color);
  color: var(--text-color);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  position: relative;
  overflow-x: hidden;
}

body::before,
body::after {
  content: '';
  position: fixed;
  inset: auto;
  pointer-events: none;
  z-index: -1;
}

body::before {
  width: 540px;
  height: 540px;
  border-radius: 999px;
  background: radial-gradient(circle at center, rgba(45, 212, 191, 0.25), transparent 70%);
  top: -180px;
  left: -100px;
}

body::after {
  width: 420px;
  height: 420px;
  border-radius: 999px;
  background: radial-gradient(circle at center, rgba(59, 130, 246, 0.2), transparent 70%);
  bottom: -140px;
  right: -70px;
}

.app-shell {
  min-height: 100vh;
  width: min(1100px, calc(100% - 32px));
  margin: 0 auto;
  padding: 16px 0 24px;
}

.session-bar {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  margin-top: 0;
}

section {
  margin: 16px;
  padding: 16px;
  border: 1px solid var(--border-color);
  border-radius: 18px;
  background: var(--card-color);
  box-shadow: 0 24px 45px -30px var(--shadow-color), inset 0 1px 0 rgba(255, 255, 255, 0.18);
  backdrop-filter: var(--glass-blur);
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
  transition: all 0.24s ease;
  font-weight: 600;
}

.btn-primary {
  background: var(--primary-color);
  color: #fff;
}

.btn-primary:hover {
  background: var(--primary-hover-color);
  transform: translateY(-1px);
  box-shadow: 0 10px 18px -12px rgba(15, 118, 110, 0.8);
}

.btn-secondary {
  border-color: var(--border-color);
}

.main-nav {
  display: flex;
  gap: 8px;
  padding: 6px;
  margin: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  background: linear-gradient(160deg, rgba(15, 118, 110, 0.9), rgba(6, 78, 59, 0.92));
  box-shadow: 0 20px 30px -22px rgba(6, 78, 59, 0.9);
}

.main-nav a {
  padding: 10px 14px;
  border-radius: 10px;
  text-decoration: none;
  color: rgba(224, 242, 254, 0.92);
  font-weight: 600;
}

.main-nav a.router-link-active {
  background: rgba(255, 255, 255, 0.16);
  color: #ffffff;
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
