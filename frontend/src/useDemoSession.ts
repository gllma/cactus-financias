import { ref } from 'vue';

const storageKey = 'cactus_demo_session';

const userEmail = ref('admin@cactus.com');
const userName = ref('Maria Silva');

export function useDemoSession() {
  function load(): void {
    const raw = localStorage.getItem(storageKey);
    if (!raw) return;

    try {
      const parsed = JSON.parse(raw) as { email?: string; name?: string };
      if (parsed.email) userEmail.value = parsed.email;
      if (parsed.name) userName.value = parsed.name;
    } catch {
      // ignore invalid local cache
    }
  }

  function save(): void {
    localStorage.setItem(storageKey, JSON.stringify({
      email: userEmail.value,
      name: userName.value,
    }));
  }

  return {
    userEmail,
    userName,
    load,
    save,
  };
}
