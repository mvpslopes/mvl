/** Base do app (ex.: '' ou '/financas') — sem barra final */
export const basePath = import.meta.env.BASE_URL.replace(/\/$/, '') || '';

export function appUrl(path: string): string {
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${basePath}${p}` || p;
}

export function apiUrl(path: string): string {
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${basePath}/api${p}`;
}
