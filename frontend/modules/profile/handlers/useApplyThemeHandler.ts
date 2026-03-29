import { watch } from 'vue';
import type { Ref } from 'vue';

export function useApplyThemeHandler(currentTheme: Ref<'light' | 'dark'>): void {
  watch(
    currentTheme,
    (theme) => {
      document.documentElement.setAttribute('data-theme', theme);
    },
    { immediate: true },
  );
}
