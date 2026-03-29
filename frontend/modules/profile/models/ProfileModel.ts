export class ProfileModel {
  constructor(
    readonly name: string,
    readonly email: string,
    readonly theme: 'light' | 'dark',
  ) {}

  get avatarInitials(): string {
    return this.name
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((chunk) => chunk.charAt(0).toUpperCase())
      .join('');
  }
}
