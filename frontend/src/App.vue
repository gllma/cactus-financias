<script setup lang="ts">
import { onMounted } from 'vue';
import { useDemoSession } from './useDemoSession';

const session = useDemoSession();

onMounted(() => {
  session.load();
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
