<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import { useDemoSession } from '../src/useDemoSession';

type Vault = {
  id: number;
  name: string;
  target_amount: number;
  balance: number;
};

type VaultTransaction = {
  id: number;
  type: 'deposit' | 'withdraw';
  amount: number;
  description: string;
  created_at: string;
};

const session = useDemoSession();
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const currentTheme = ref<'light' | 'dark'>('light');
const vaults = ref<Vault[]>([]);
const selectedVault = ref<Vault | null>(null);
const transactions = ref<VaultTransaction[]>([]);
const message = ref('');

const newVaultName = ref('');
const newVaultTarget = ref<number | null>(null);
const txType = ref<'deposit' | 'withdraw'>('deposit');
const txAmount = ref<number | null>(null);
const txDescription = ref('');

async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'X-User-Email': session.userEmail.value,
      'X-User-Name': session.userName.value,
      ...(options.headers ?? {}),
    },
  });
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message ?? 'Falha na operação.');
  }

  return payload as T;
}

async function loadVaults(selectId?: number): Promise<void> {
  const response = await api<{ data: Vault[] }>('/api/vaults');
  vaults.value = response.data;
  const firstId = selectId ?? response.data[0]?.id;

  if (firstId) {
    await loadTransactions(firstId);
  } else {
    selectedVault.value = null;
    transactions.value = [];
  }
}

async function loadTransactions(vaultId: number): Promise<void> {
  const response = await api<{ data: { vault: Vault; transactions: VaultTransaction[] } }>(`/api/vaults/${vaultId}/transactions`);
  selectedVault.value = response.data.vault;
  transactions.value = response.data.transactions;
}

async function createVault(): Promise<void> {
  const response = await api<{ data: Vault }>('/api/vaults', {
    method: 'POST',
    body: JSON.stringify({
      name: newVaultName.value,
      target_amount: newVaultTarget.value ?? 0,
    }),
  });

  newVaultName.value = '';
  newVaultTarget.value = null;
  message.value = 'Cofre criado com sucesso.';
  await loadVaults(response.data.id);
}

async function addTransaction(): Promise<void> {
  if (!selectedVault.value) return;

  await api(`/api/vaults/${selectedVault.value.id}/transactions`, {
    method: 'POST',
    body: JSON.stringify({
      type: txType.value,
      amount: txAmount.value,
      description: txDescription.value,
    }),
  });

  txAmount.value = null;
  txDescription.value = '';
  message.value = 'Movimentação registrada com sucesso.';
  await loadVaults(selectedVault.value.id);
}

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
  localStorage.setItem('cactus_theme_preference', currentTheme.value);
  document.documentElement.setAttribute('data-theme', currentTheme.value);
}

onMounted(async () => {
  const cachedTheme = localStorage.getItem('cactus_theme_preference');
  if (cachedTheme === 'light' || cachedTheme === 'dark') {
    currentTheme.value = cachedTheme;
  }
  document.documentElement.setAttribute('data-theme', currentTheme.value);

  try {
    await loadVaults();
  } catch (error) {
    message.value = (error as Error).message;
  }
});
</script>

<template>
  <AppHeader :user-name="session.userName" :current-theme="currentTheme" @toggleTheme="toggleTheme" />
  <section>
    <h1>Cofres e Movimentações</h1>
    <p class="muted">Organize objetivos e registre depósitos/saques.</p>

    <div class="toolbar">
      <input v-model="newVaultName" placeholder="Nome do cofre" />
      <input v-model.number="newVaultTarget" type="number" min="0" step="0.01" placeholder="Meta (opcional)" />
      <button type="button" class="btn btn-primary" @click="createVault">Criar cofre</button>
    </div>

    <div class="vault-grid">
      <button
        v-for="vault in vaults"
        :key="vault.id"
        type="button"
        class="btn btn-secondary vault-item"
        @click="loadTransactions(vault.id)"
      >
        {{ vault.name }} · Saldo: R$ {{ vault.balance.toFixed(2) }} · Meta: R$ {{ Number(vault.target_amount || 0).toFixed(2) }}
      </button>
    </div>

    <div v-if="selectedVault" class="transaction-panel">
      <h2>{{ selectedVault.name }}</h2>
      <p>Saldo atual: R$ {{ selectedVault.balance.toFixed(2) }}</p>
      <div class="toolbar">
        <select v-model="txType">
          <option value="deposit">Depósito</option>
          <option value="withdraw">Saque</option>
        </select>
        <input v-model.number="txAmount" type="number" min="0.01" step="0.01" placeholder="Valor" />
        <input v-model="txDescription" placeholder="Descrição" />
        <button type="button" class="btn btn-primary" @click="addTransaction">Registrar movimentação</button>
      </div>
      <ul class="transaction-list">
        <li v-for="transaction in transactions" :key="transaction.id" class="transaction-item">
          {{ transaction.type === 'deposit' ? 'Depósito' : 'Saque' }} · R$ {{ Number(transaction.amount).toFixed(2) }}
          <span v-if="transaction.description">· {{ transaction.description }}</span>
        </li>
      </ul>
    </div>

    <p v-if="message" class="muted">{{ message }}</p>
  </section>
</template>

<style scoped>
.toolbar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
  margin-bottom: 14px;
}

.vault-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 10px;
  margin-bottom: 12px;
}

.vault-item {
  text-align: left;
  min-height: 54px;
}

.transaction-panel {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--border-color);
}

.transaction-list {
  list-style: none;
  margin: 12px 0 0;
  padding: 0;
}

.transaction-item {
  padding: 10px 0;
  border-bottom: 1px solid var(--border-color);
}

.muted {
  color: var(--muted-color);
}
</style>
