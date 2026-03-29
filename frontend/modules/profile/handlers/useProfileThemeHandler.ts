import { ref } from 'vue';
import type { UpdateThemePreferenceDTO } from '../dtos/UpdateThemePreferenceDTO';
import { ProfileService } from '../services/profileService';

export function useProfileThemeHandler(profileService: ProfileService) {
  const loading = ref(false);
  const errorMessage = ref<string | null>(null);
  const currentTheme = ref<'light' | 'dark'>('light');

  async function loadPersistedTheme(): Promise<'light' | 'dark'> {
    const response = await profileService.getThemePreference();
    currentTheme.value = response.theme;

    return currentTheme.value;
  }

  async function updateTheme(payload: UpdateThemePreferenceDTO): Promise<void> {
    loading.value = true;
    errorMessage.value = null;

    try {
      const response = await profileService.updateThemePreference(payload);
      currentTheme.value = response.theme;
    } catch {
      errorMessage.value = 'Não foi possível salvar a preferência de tema.';
      throw errorMessage.value;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    errorMessage,
    currentTheme,
    loadPersistedTheme,
    updateTheme,
  };
}
