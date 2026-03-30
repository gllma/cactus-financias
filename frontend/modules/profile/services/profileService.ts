import { profileRoutes } from '../routes/profileRoutes';
import type { UpdateThemePreferenceDTO } from '../dtos/UpdateThemePreferenceDTO';

export interface HttpClient {
  get<T>(url: string): Promise<T>;
  patch<T>(url: string, payload: unknown): Promise<T>;
}

export class ProfileService {
  constructor(private readonly httpClient: HttpClient) {}

  private extractTheme(response: { theme?: 'light' | 'dark'; data?: { theme?: 'light' | 'dark' } }): 'light' | 'dark' {
    return response.data?.theme ?? response.theme ?? 'light';
  }

  async getThemePreference(): Promise<{ theme: 'light' | 'dark' }> {
    const response = await this.httpClient.get<{ theme?: 'light' | 'dark'; data?: { theme?: 'light' | 'dark' } }>(
      profileRoutes.getTheme,
    );

    return { theme: this.extractTheme(response) };
  }

  async updateThemePreference(payload: UpdateThemePreferenceDTO): Promise<{ theme: 'light' | 'dark' }> {
    const response = await this.httpClient.patch<{ theme?: 'light' | 'dark'; data?: { theme?: 'light' | 'dark' } }>(
      profileRoutes.updateTheme,
      payload,
    );

    return { theme: this.extractTheme(response) };
  }
}
