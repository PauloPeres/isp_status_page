import { Injectable, signal } from '@angular/core';

export type ThemeMode = 'light' | 'dark' | 'system';

@Injectable({ providedIn: 'root' })
export class ThemeService {
  private readonly STORAGE_KEY = 'theme_mode';
  private systemDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

  mode = signal<ThemeMode>(this.getStored());

  constructor() {
    this.applyTheme(this.mode());
    this.systemDarkQuery.addEventListener('change', () => {
      if (this.mode() === 'system') {
        this.setDarkClass(this.systemDarkQuery.matches);
      }
    });
  }

  toggle(): void {
    const current = this.mode();
    const next: ThemeMode = current === 'light' ? 'dark' : current === 'dark' ? 'system' : 'light';
    this.setMode(next);
  }

  setMode(mode: ThemeMode): void {
    this.mode.set(mode);
    localStorage.setItem(this.STORAGE_KEY, mode);
    this.applyTheme(mode);
  }

  isDark(): boolean {
    const m = this.mode();
    if (m === 'system') return this.systemDarkQuery.matches;
    return m === 'dark';
  }

  private applyTheme(mode: ThemeMode): void {
    const dark = mode === 'dark' || (mode === 'system' && this.systemDarkQuery.matches);
    this.setDarkClass(dark);
  }

  private setDarkClass(dark: boolean): void {
    document.body.classList.toggle('dark', dark);
  }

  private getStored(): ThemeMode {
    const stored = localStorage.getItem(this.STORAGE_KEY);
    if (stored === 'light' || stored === 'dark' || stored === 'system') return stored;
    return 'system';
  }
}
