<template>
  <main>
    <nav style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #ddd;">
      <router-link to="/profile-preferences">Perfil</router-link>
      <router-link to="/observability-dashboard">Observabilidade</router-link>
    </nav>
    <router-view />
  </main>
</template>
