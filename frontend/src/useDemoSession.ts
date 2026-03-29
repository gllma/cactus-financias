import { ref } from 'vue';

const storageKey = 'cactus_demo_session';

const userEmail = ref('admin@cactus.com');
const userName = ref('Maria Silva');
const authToken = ref('');
const activeSpaceId = ref<number | null>(null);
const isAuthenticated = ref(false);

export function useDemoSession() {
  function load(): void {
    const raw = localStorage.getItem(storageKey);
    if (!raw) return;

    try {
      const parsed = JSON.parse(raw) as { email?: string; name?: string; token?: string; activeSpaceId?: number | null };
      if (parsed.email) userEmail.value = parsed.email;
      if (parsed.name) userName.value = parsed.name;
      if (parsed.token) {
        authToken.value = parsed.token;
        isAuthenticated.value = true;
      }
      if (typeof parsed.activeSpaceId === 'number') activeSpaceId.value = parsed.activeSpaceId;
    } catch {
      // ignore invalid local cache
    }
  }

  function save(): void {
    localStorage.setItem(storageKey, JSON.stringify({
      email: userEmail.value,
      name: userName.value,
      token: authToken.value,
      activeSpaceId: activeSpaceId.value,
    }));
  }

  function setAuth(token: string): void {
    authToken.value = token;
    isAuthenticated.value = true;
    save();
  }

  function setActiveSpace(spaceId: number): void {
    activeSpaceId.value = spaceId;
    save();
  }

  function logout(): void {
    authToken.value = '';
    activeSpaceId.value = null;
    isAuthenticated.value = false;
    save();
  }

  return {
    userEmail,
    userName,
    authToken,
    activeSpaceId,
    isAuthenticated,
    load,
    save,
    setAuth,
    setActiveSpace,
    logout,
  };
}
