import type { ReactNode } from 'react';
import { useEffect } from 'react';

type ThemeScopeProps = {
  theme: 'light' | 'dark';
  children: ReactNode;
};

export default function ThemeScope({ theme, children }: ThemeScopeProps) {
  useEffect(() => {
    const el = document.documentElement;
    const prev = el.getAttribute('data-theme');

    if (theme === 'light') {
      el.removeAttribute('data-theme');
    } else {
      el.setAttribute('data-theme', 'dark');
    }

    return () => {
      if (prev === null) el.removeAttribute('data-theme');
      else el.setAttribute('data-theme', prev);
    };
  }, [theme]);

  return <>{children}</>;
}

