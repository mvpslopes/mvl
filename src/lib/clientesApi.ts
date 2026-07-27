type FetchOpts = {
  method?: string;
  body?: BodyInit | null;
  json?: unknown;
  formData?: FormData;
};

function getToken(): string | null {
  return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
}

const OVERRIDE = new Set(['PUT', 'PATCH', 'DELETE']);

export async function clientesAdminFetch<T>(path: string, opts: FetchOpts = {}): Promise<T> {
  const token = getToken();
  const requested = (opts.method ?? 'GET').toUpperCase();
  const useOverride = OVERRIDE.has(requested);
  const headers: Record<string, string> = {};
  if (token) headers.Authorization = `Bearer ${token}`;
  if (useOverride) headers['X-HTTP-Method-Override'] = requested;

  let body: BodyInit | undefined;
  if (opts.formData) {
    body = opts.formData;
  } else if (opts.json !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(opts.json);
  } else if (opts.body != null) {
    body = opts.body;
  }

  const res = await fetch(`/api/clientes${path}`, {
    method: useOverride ? 'POST' : requested,
    headers,
    credentials: 'include',
    body,
  });

  const raw = await res.text();
  let data: (T & { message?: string }) | null = null;
  if (raw) {
    try {
      data = JSON.parse(raw) as T & { message?: string };
    } catch {
      throw new Error(res.status === 403 ? 'Servidor bloqueou a operação.' : `Resposta inválida (${res.status}).`);
    }
  }
  if (!res.ok) {
    throw new Error(data?.message || `Erro ${res.status}`);
  }
  return data as T;
}

export async function fetchClientesPublic(): Promise<
  Array<{ id: string; name: string; url: string; bg_color: string; logo_url: string | null }>
> {
  const res = await fetch('/api/clientes/lista.php', { credentials: 'omit' });
  if (!res.ok) throw new Error('Falha ao carregar clientes');
  const data = (await res.json()) as {
    success?: boolean;
    clientes?: Array<{ id: string; name: string; url: string; bg_color: string; logo_url: string | null }>;
  };
  return Array.isArray(data.clientes) ? data.clientes : [];
}

export function clienteLogoUrl(id: string): string {
  return `/api/clientes/file.php?id=${encodeURIComponent(id)}`;
}
