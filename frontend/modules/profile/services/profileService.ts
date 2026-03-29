import { profileRoutes } from '../routes/profileRoutes';
import type { UpdateThemePreferenceDTO } from '../dtos/UpdateThemePreferenceDTO';

export interface HttpClient {
  get<T>(url: string): Promise<T>;
  patch<T>(url: string, payload: unknown): Promise<T>;
}

export class ProfileService {
  constructor(private readonly httpClient: HttpClient) {}

  async getThemePreference(): Promise<{ theme: 'light' | 'dark' }> {
    return this.httpClient.get<{ theme: 'light' | 'dark' }>(profileRoutes.getTheme);
  }

  async updateThemePreference(payload: UpdateThemePreferenceDTO): Promise<{ theme: 'light' | 'dark' }> {
    return this.httpClient.patch<{ theme: 'light' | 'dark' }>(profileRoutes.updateTheme, payload);
  }
}
