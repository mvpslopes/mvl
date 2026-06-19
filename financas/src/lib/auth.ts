const TOKEN_KEY = 'fin_auth_token';
const NAME_KEY = 'fin_user_name';

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY);
}

export function saveSession(token: string, name: string) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(NAME_KEY, name);
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(NAME_KEY);
  sessionStorage.removeItem(TOKEN_KEY);
  sessionStorage.removeItem(NAME_KEY);
}

export function isAuthenticated(): boolean {
  return !!getToken();
}

export function getUserName(): string {
  return localStorage.getItem(NAME_KEY) || sessionStorage.getItem(NAME_KEY) || 'Usuário';
}
