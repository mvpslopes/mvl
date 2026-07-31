type FetchOpts = {
  method?: string;
  body?: unknown;
};

function getToken(): string | null {
  return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
}

const OVERRIDE = new Set(['PUT', 'PATCH', 'DELETE']);

export async function ideiasFetch<T>(path: string, opts: FetchOpts = {}): Promise<T> {
  const token = getToken();
  const requested = (opts.method ?? 'GET').toUpperCase();
  const useOverride = OVERRIDE.has(requested);

  const headers: Record<string, string> = { 'Content-Type': 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (useOverride) headers['X-HTTP-Method-Override'] = requested;

  const res = await fetch(`/api/ideias${path}`, {
    method: useOverride ? 'POST' : requested,
    headers,
    credentials: 'include',
    body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
  });

  const raw = await res.text();
  let data: (T & { message?: string }) | null = null;
  if (raw) {
    try {
      data = JSON.parse(raw) as T & { message?: string };
    } catch {
      throw new Error(
        res.status === 403
          ? 'O servidor bloqueou a operação. Atualize o site (deploy) e tente novamente.'
          : `Resposta inválida do servidor (${res.status}).`
      );
    }
  }
  if (!res.ok) {
    throw new Error(data?.message || `Erro ${res.status}`);
  }
  return (data ?? {}) as T;
}
