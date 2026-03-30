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
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Cofres e Movimentações</h1>
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Organize objetivos e acompanhe entradas/saídas por espaço.</p>
    </div>

    <div v-if="insights" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <article class="card p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo total</span>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">R$ {{ insights.total_balance.toFixed(2) }}</p>
      </article>
      <article class="card p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta total</span>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">R$ {{ insights.total_target.toFixed(2) }}</p>
      </article>
      <article class="card p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Progresso das metas</span>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ insights.progress_percent.toFixed(1) }}%</p>
      </article>
      <article class="card p-5">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Fluxo do mês</span>
        <p class="text-3xl font-bold mt-2" :class="insights.monthly_net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
          R$ {{ insights.monthly_net.toFixed(2) }}
        </p>
      </article>
    </div>

    <div class="card p-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Criar novo cofre</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</span>
          <input v-model="newVaultName" placeholder="Nome do cofre" class="input-control" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta (opcional)</span>
          <input v-model.number="newVaultTarget" type="number" min="0" step="0.01" placeholder="Ex: 15000" class="input-control" />
        </label>
        <div class="flex items-end">
          <button type="button" class="action-primary" @click="createVault">Criar cofre</button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="card p-5 xl:col-span-1">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Meus cofres</h2>
        <div class="space-y-2">
          <button
            v-for="vault in vaults"
            :key="vault.id"
            type="button"
            class="w-full text-left action-secondary"
            @click="loadTransactions(vault.id)"
          >
            <span>
              {{ vault.name }}
              <span class="block text-xs text-gray-500 dark:text-gray-400">Saldo: R$ {{ vault.balance.toFixed(2) }} · Meta: R$ {{ Number(vault.target_amount || 0).toFixed(2) }}</span>
            </span>
          </button>
        </div>
      </div>

      <div v-if="selectedVault" class="card p-6 xl:col-span-2 space-y-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ selectedVault.name }}</h2>
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo atual: R$ {{ selectedVault.balance.toFixed(2) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
          <select v-model="txType" class="input-control">
            <option value="deposit">Depósito</option>
            <option value="withdraw">Saque</option>
          </select>
          <select v-model="txCategory" class="input-control">
            <option value="geral">Geral</option>
            <option value="reserva">Reserva</option>
            <option value="lazer">Lazer</option>
            <option value="moradia">Moradia</option>
            <option value="investimento">Investimento</option>
          </select>
          <input v-model.number="txAmount" type="number" min="0.01" step="0.01" placeholder="Valor" class="input-control" />
          <input v-model="txDescription" placeholder="Descrição" class="input-control" />
          <button type="button" class="action-primary" @click="addTransaction">Registrar</button>
        </div>

        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
          <li v-for="transaction in transactions" :key="transaction.id" class="py-3 text-sm text-gray-700 dark:text-gray-300">
            {{ transaction.type === 'deposit' ? 'Depósito' : 'Saque' }} · R$ {{ Number(transaction.amount).toFixed(2) }}
            <span v-if="transaction.category"> · {{ transaction.category }}</span>
            <span v-if="transaction.description"> · {{ transaction.description }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div v-if="recentTransactions.length" class="card p-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3">Movimentações recentes (geral)</h2>
      <ul class="divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="transaction in recentTransactions" :key="`recent-${transaction.id}`" class="py-3 text-sm text-gray-700 dark:text-gray-300">
          {{ transaction.vault_name }} · {{ transaction.type === 'deposit' ? 'Depósito' : 'Saque' }}
          · R$ {{ Number(transaction.amount).toFixed(2) }}
          <span v-if="transaction.category"> · {{ transaction.category }}</span>
        </li>
      </ul>
    </div>

    <p v-if="message" class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ message }}</p>
  </section>
</template>
