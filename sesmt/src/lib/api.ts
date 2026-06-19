import { getToken } from './auth';

const API_BASE = import.meta.env.VITE_API_BASE || '/api';

type ApiOptions = {
  method?: string;
  body?: unknown;
  auth?: boolean;
};

export async function apiFetch<T>(path: string, options: ApiOptions = {}): Promise<T> {
  const { method = 'GET', body, auth = true } = options;
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
  };

  if (auth) {
    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    credentials: 'include',
  });

  const text = await response.text();
  let data: T & { success?: boolean; message?: string };
  try {
    data = (text ? JSON.parse(text) : {}) as T & { success?: boolean; message?: string };
  } catch {
    throw new Error(
      text && text.length < 200 ? text : `Resposta inválida do servidor (${response.status}).`
    );
  }

  if (!response.ok) {
    throw new Error(data.message || `Erro ${response.status}`);
  }

  return data;
}

export async function apiFetchBlob(path: string): Promise<Blob> {
  const headers: Record<string, string> = {};
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const response = await fetch(`${API_BASE}${path}`, { headers, credentials: 'include' });
  if (!response.ok) {
    throw new Error(`Erro ao carregar arquivo (${response.status})`);
  }
  return response.blob();
}

export async function apiUploadForm<T>(path: string, formData: FormData): Promise<T> {
  const headers: Record<string, string> = {};
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const response = await fetch(`${API_BASE}${path}`, {
    method: 'POST',
    headers,
    body: formData,
    credentials: 'include',
  });

  const text = await response.text();
  let data: T & { success?: boolean; message?: string };
  try {
    data = (text ? JSON.parse(text) : {}) as T & { success?: boolean; message?: string };
  } catch {
    throw new Error(
      text && text.length < 200 ? text : `Resposta inválida do servidor (${response.status}).`
    );
  }
  if (!response.ok) {
    throw new Error(data.message || `Erro ${response.status}`);
  }
  return data;
}
