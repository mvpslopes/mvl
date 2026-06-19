import { useEffect, useState } from 'react';
import { LogIn, Wallet } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { apiFetch } from '../lib/api';
import { isAuthenticated, saveSession } from '../lib/auth';

export default function LoginPage() {
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isAuthenticated()) navigate('/', { replace: true });
  }, [navigate]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const data = await apiFetch<{
        success: boolean;
        token: string;
        name: string;
        message?: string;
      }>('/auth.php', {
        method: 'POST',
        auth: false,
        body: { username: username.trim(), password },
      });
      if (!data.token) throw new Error('Resposta inválida.');
      saveSession(data.token, data.name);
      navigate('/', { replace: true });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro no login.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-page flex items-center justify-center p-6">
      <div className="w-full max-w-md fin-card">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-11 h-11 rounded-xl bg-ink flex items-center justify-center">
            <Wallet className="w-5 h-5 text-white" />
          </div>
          <div>
            <h1 className="text-xl font-bold">Finanças pessoais</h1>
            <p className="text-sm text-slate-500">Controle de receitas e despesas</p>
          </div>
        </div>
        <form onSubmit={submit} className="space-y-4">
          {error && (
            <p className="text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl px-3 py-2">{error}</p>
          )}
          <div>
            <label className="fin-label" htmlFor="user">
              Usuário
            </label>
            <input
              id="user"
              className="fin-input"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="fin-label" htmlFor="pass">
              Senha
            </label>
            <input
              id="pass"
              type="password"
              className="fin-input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          <button type="submit" disabled={loading} className="fin-btn w-full">
            {loading ? 'Entrando…' : 'Entrar'}
            <LogIn size={18} />
          </button>
        </form>
      </div>
    </div>
  );
}
