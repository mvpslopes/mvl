import { useEffect, useState } from 'react';
import { LogIn, Shield } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { apiFetch } from '../lib/api';
import { isAuthenticated, saveSession, type UserRole } from '../lib/auth';

type AuthResponse = {
  success: boolean;
  token: string;
  name: string;
  username: string;
  role: UserRole;
  message?: string;
};

export default function LoginPage() {
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isAuthenticated()) {
      navigate('/', { replace: true });
    }
  }, [navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const data = await apiFetch<AuthResponse>('/auth.php', {
        method: 'POST',
        auth: false,
        body: { username: username.trim(), password },
      });

      if (!data.token || !data.role) {
        throw new Error('Resposta inválida do servidor.');
      }

      saveSession({
        token: data.token,
        role: data.role,
        name: data.name,
        username: data.username,
      });

      navigate('/', { replace: true });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha no login.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-6 bg-sesmt-page">
      <div className="w-full max-w-md sesmt-card">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-12 h-12 rounded-xl bg-sesmt-forest flex items-center justify-center">
            <Shield className="w-6 h-6 text-white" />
          </div>
          <div>
            <h1 className="text-xl font-bold text-sesmt-forest">SESMT</h1>
            <p className="text-sm text-sesmt-forest/60">Acesso ao sistema</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="sesmt-label" htmlFor="username">
              Usuário
            </label>
            <input
              id="username"
              className="sesmt-input"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              autoComplete="username"
              required
            />
          </div>
          <div>
            <label className="sesmt-label" htmlFor="password">
              Senha
            </label>
            <input
              id="password"
              type="password"
              className="sesmt-input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="current-password"
              required
            />
          </div>

          {error && (
            <p className="text-sm text-sesmt-forest bg-amber-50 border border-amber-200/80 rounded-[10px] px-3 py-2">
              {error}
            </p>
          )}

          <button type="submit" disabled={loading} className="sesmt-btn-primary w-full">
            <LogIn size={18} />
            {loading ? 'Entrando…' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  );
}
