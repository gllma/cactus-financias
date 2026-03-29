<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useDemoSession } from '../src/useDemoSession';

type Vault = {
  id: number;
  name: string;
  target_amount: number;
  balance: number;
};

type VaultTransaction = {
  id: number;
  vault_name?: string;
  type: 'deposit' | 'withdraw';
  amount: number;
  category?: string;
  description: string;
  created_at: string;
};

type VaultInsights = {
  total_balance: number;
  total_target: number;
  progress_percent: number;
  monthly_deposits: number;
  monthly_withdrawals: number;
  monthly_net: number;
};

const session = useDemoSession();
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const vaults = ref<Vault[]>([]);
const selectedVault = ref<Vault | null>(null);
const transactions = ref<VaultTransaction[]>([]);
const recentTransactions = ref<VaultTransaction[]>([]);
const insights = ref<VaultInsights | null>(null);
const message = ref('');

const newVaultName = ref('');
const newVaultTarget = ref<number | null>(null);
const txType = ref<'deposit' | 'withdraw'>('deposit');
const txAmount = ref<number | null>(null);
const txDescription = ref('');
const txCategory = ref('geral');

async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'X-User-Email': session.userEmail.value,
      'X-User-Name': session.userName.value,
      Authorization: `Bearer ${session.authToken.value}`,
      ...(session.activeSpaceId.value ? { 'X-Space-Id': String(session.activeSpaceId.value) } : {}),
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

async function loadInsights(): Promise<void> {
  const response = await api<{ data: VaultInsights }>('/api/vaults/insights');
  insights.value = response.data;
}

async function loadRecentTransactions(): Promise<void> {
  const response = await api<{ data: VaultTransaction[] }>('/api/transactions/recent?limit=8');
  recentTransactions.value = response.data;
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
      category: txCategory.value,
      description: txDescription.value,
    }),
  });

  txAmount.value = null;
  txDescription.value = '';
  txCategory.value = 'geral';
  message.value = 'Movimentação registrada com sucesso.';
  await loadVaults(selectedVault.value.id);
  await loadInsights();
  await loadRecentTransactions();
}

onMounted(async () => {
  try {
    await loadVaults();
    await loadInsights();
    await loadRecentTransactions();
  } catch (error) {
    message.value = (error as Error).message;
  }
});
</script>

<template>
  <section class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Cofres e Movimentações</h1>
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Organize objetivos e registre depósitos/saques.</p>

    <div v-if="insights" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo total</span>
        <strong class="text-3xl font-bold text-gray-900 dark:text-white mt-2">R$ {{ insights.total_balance.toFixed(2) }}</strong>
      </article>
      <article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta total</span>
        <strong class="text-3xl font-bold text-gray-900 dark:text-white mt-2">R$ {{ insights.total_target.toFixed(2) }}</strong>
      </article>
      <article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Progresso das metas</span>
        <strong class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ insights.progress_percent.toFixed(1) }}%</strong>
      </article>
      <article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Fluxo do mês</span>
        <strong class="text-3xl font-bold mt-2" :style="{ color: insights.monthly_net >= 0 ? '#16a34a' : '#dc2626' }">R$ {{ insights.monthly_net.toFixed(2) }}</strong>
      </article>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <input v-model="newVaultName" placeholder="Nome do cofre" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500" />
      <input v-model.number="newVaultTarget" type="number" min="0" step="0.01" placeholder="Meta (opcional)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500" />
      <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900" @click="createVault">Criar cofre</button>
    </div>

    <div class="vault-grid">
      <button
        v-for="vault in vaults"
        :key="vault.id"
        type="button"
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 text-left"
        @click="loadTransactions(vault.id)"
      >
        {{ vault.name }} · Saldo: R$ {{ vault.balance.toFixed(2) }} · Meta: R$ {{ Number(vault.target_amount || 0).toFixed(2) }}
      </button>
    </div>

    <div v-if="selectedVault" class="transaction-panel">
      <h2>{{ selectedVault.name }}</h2>
      <p>Saldo atual: R$ {{ selectedVault.balance.toFixed(2) }}</p>
      <div class="toolbar">
        <select v-model="txType" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500">
          <option value="deposit">Depósito</option>
          <option value="withdraw">Saque</option>
        </select>
        <select v-model="txCategory" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500">
          <option value="geral">Geral</option>
          <option value="reserva">Reserva</option>
          <option value="lazer">Lazer</option>
          <option value="moradia">Moradia</option>
          <option value="investimento">Investimento</option>
        </select>
        <input v-model.number="txAmount" type="number" min="0.01" step="0.01" placeholder="Valor" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500" />
        <input v-model="txDescription" placeholder="Descrição" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500" />
        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900" @click="addTransaction">Registrar movimentação</button>
      </div>
      <ul class="transaction-list">
        <li v-for="transaction in transactions" :key="transaction.id" class="transaction-item">
          {{ transaction.type === 'deposit' ? 'Depósito' : 'Saque' }} · R$ {{ Number(transaction.amount).toFixed(2) }}
          <span v-if="transaction.category">· {{ transaction.category }}</span>
          <span v-if="transaction.description">· {{ transaction.description }}</span>
        </li>
      </ul>
    </div>

    <div v-if="recentTransactions.length" class="transaction-panel">
      <h2>Movimentações recentes (geral)</h2>
      <ul class="transaction-list">
        <li v-for="transaction in recentTransactions" :key="`recent-${transaction.id}`" class="transaction-item">
          {{ transaction.vault_name }} · {{ transaction.type === 'deposit' ? 'Depósito' : 'Saque' }}
          · R$ {{ Number(transaction.amount).toFixed(2) }}
          <span v-if="transaction.category">· {{ transaction.category }}</span>
        </li>
      </ul>
    </div>

    <p v-if="message" class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ message }}</p>
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
  border-color: color-mix(in oklab, var(--primary-color), var(--border-color) 60%);
}

.kpi-card {
  border: 1px solid color-mix(in oklab, var(--primary-color), var(--border-color) 70%);
  border-radius: 12px;
  padding: 12px;
  background: color-mix(in oklab, var(--card-color), white 6%);
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.kpi-card span {
  color: var(--muted-color);
}

.transaction-panel {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed color-mix(in oklab, var(--primary-color), var(--border-color) 55%);
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
