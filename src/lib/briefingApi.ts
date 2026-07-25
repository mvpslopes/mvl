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

export async function briefingAdminFetch<T>(path: string, opts: FetchOpts = {}): Promise<T> {
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

  const res = await fetch(`/api/briefing${path}`, {
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
  return (data ?? {}) as T;
}

export async function briefingSubmit(formData: FormData): Promise<{ ok: boolean; id?: string; message?: string }> {
  const res = await fetch('/api/briefing/enviar.php', {
    method: 'POST',
    body: formData,
  });
  const raw = await res.text();
  let data: { ok?: boolean; id?: string; message?: string } = {};
  try {
    data = JSON.parse(raw);
  } catch {
    throw new Error('Resposta inválida do servidor.');
  }
  if (!res.ok || !data.ok) {
    throw new Error(data.message || `Erro ${res.status}`);
  }
  return { ok: true, id: data.id };
}

export function briefingFileUrl(id: string, file: 'logo' | 'brand'): string {
  return `/api/briefing/file.php?id=${encodeURIComponent(id)}&file=${file}`;
}

export async function downloadBriefingFile(id: string, file: 'logo' | 'brand', filename: string): Promise<void> {
  const token = getToken();
  const res = await fetch(briefingFileUrl(id, file), {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
    credentials: 'include',
  });
  if (!res.ok) {
    throw new Error('Não foi possível baixar o arquivo.');
  }
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}
