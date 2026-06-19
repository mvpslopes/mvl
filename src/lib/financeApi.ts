type FetchOpts = {
  method?: string;
  body?: unknown;
};

function getToken(): string | null {
  return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
}

export async function finFetch<T>(path: string, opts: FetchOpts = {}): Promise<T> {
  const token = getToken();
  const headers: Record<string, string> = { 'Content-Type': 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`/api/financas${path}`, {
    method: opts.method ?? 'GET',
    headers,
    credentials: 'include',
    body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
  });

  const data = (await res.json()) as T & { message?: string };
  if (!res.ok) {
    throw new Error(data.message || `Erro ${res.status}`);
  }
  return data;
}
