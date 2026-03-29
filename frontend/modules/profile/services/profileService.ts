import { profileRoutes } from '../routes/profileRoutes';
import type { UpdateThemePreferenceDTO } from '../dtos/UpdateThemePreferenceDTO';

export interface HttpClient {
  patch<T>(url: string, payload: unknown): Promise<T>;
}

export class ProfileService {
  constructor(private readonly httpClient: HttpClient) {}

  async updateThemePreference(payload: UpdateThemePreferenceDTO): Promise<{ theme: 'light' | 'dark' }> {
    return this.httpClient.patch<{ theme: 'light' | 'dark' }>(profileRoutes.updateTheme, payload);
  }
}
