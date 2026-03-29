import { ref } from 'vue';
import type { UpdateThemePreferenceDTO } from '../dtos/UpdateThemePreferenceDTO';
import { ProfileService } from '../services/profileService';

export function useProfileThemeHandler(profileService: ProfileService) {
  const loading = ref(false);
  const errorMessage = ref<string | null>(null);

  async function updateTheme(payload: UpdateThemePreferenceDTO): Promise<void> {
    loading.value = true;
    errorMessage.value = null;

    try {
      await profileService.updateThemePreference(payload);
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
    updateTheme,
  };
}
