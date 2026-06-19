const TOKEN_KEY = 'sesmt_auth_token';
const ROLE_KEY = 'sesmt_user_role';
const NAME_KEY = 'sesmt_user_name';
const USERNAME_KEY = 'sesmt_username';

export type UserRole = 'root' | 'admin';

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function getRole(): UserRole | null {
  const role = localStorage.getItem(ROLE_KEY);
  if (role === 'root' || role === 'admin') return role;
  return null;
}

export function getUserName(): string {
  return localStorage.getItem(NAME_KEY) || 'Usuário';
}

export function getUsername(): string {
  return localStorage.getItem(USERNAME_KEY) || '';
}

export function saveSession(data: {
  token: string;
  role: UserRole;
  name: string;
  username: string;
}) {
  localStorage.setItem(TOKEN_KEY, data.token);
  localStorage.setItem(ROLE_KEY, data.role);
  localStorage.setItem(NAME_KEY, data.name);
  localStorage.setItem(USERNAME_KEY, data.username);
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(ROLE_KEY);
  localStorage.removeItem(NAME_KEY);
  localStorage.removeItem(USERNAME_KEY);
}

export function isAuthenticated(): boolean {
  return Boolean(getToken());
}

export function isRoot(): boolean {
  return getRole() === 'root';
}
