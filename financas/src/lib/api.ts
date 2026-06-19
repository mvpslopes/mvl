import { getToken } from './auth';
import { apiUrl } from './paths';

type FetchOpts = {
  method?: string;
  body?: unknown;
  auth?: boolean;
};

export async function apiFetch<T>(path: string, opts: FetchOpts = {}): Promise<T> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
  };

  if (opts.auth !== false) {
    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  const res = await fetch(apiUrl(path), {
    method: opts.method ?? 'GET',
    headers,
    credentials: 'include',
    body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
  });

  const data = (await res.json()) as T & { message?: string; success?: boolean };

  if (!res.ok) {
    throw new Error(data.message || `Erro ${res.status}`);
  }

  return data;
}
