import { watch } from 'vue';
import type { Ref } from 'vue';

const themeStorageKey = 'cactus_theme_preference';

export function useApplyThemeHandler(currentTheme: Ref<'light' | 'dark'>): void {
  const cachedTheme = localStorage.getItem(themeStorageKey);
  if (cachedTheme === 'light' || cachedTheme === 'dark') {
    currentTheme.value = cachedTheme;
  }

  watch(
    currentTheme,
    (theme) => {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem(themeStorageKey, theme);
    },
    { immediate: true },
  );
}
