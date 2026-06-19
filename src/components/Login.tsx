import { useState } from 'react';
import { ArrowLeft, LogIn, Shield } from 'lucide-react';
import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  type AuthApiResponse = {
    success?: boolean;
    message?: string;
    token?: string;
    role?: string;
    name?: string;
    email?: string;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');

    try {
      const apiPaths = [
        '/api/auth.php',
        'https://mvlopes.com.br/api/auth.php',
        './api/auth.php',
      ];

      let response: Response | null = null;
      let data: AuthApiResponse | null = null;
      let lastError: Error | null = null;

      for (const apiPath of apiPaths) {
        try {
          response = await fetch(apiPath, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password }),
            credentials: 'include',
          });

          if (response.ok) {
            data = (await response.json()) as AuthApiResponse;
            break;
          } else {
            try {
              data = (await response.json()) as AuthApiResponse;
            } catch {
              /* continua */
            }
          }
        } catch (err) {
          lastError = err instanceof Error ? err : new Error('Erro de conexão');
          continue;
        }
      }

      if (!response || !data) {
        throw lastError || new Error('Não foi possível conectar ao servidor. Verifique sua conexão.');
      }

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Credenciais inválidas');
      }

      if (!data.token || !data.role) {
        throw new Error('Resposta da API incompleta. Tente novamente.');
      }

      const isLocalStorageAvailable = (() => {
        try {
          const test = '__localStorage_test__';
          localStorage.setItem(test, test);
          localStorage.removeItem(test);
          return true;
        } catch {
          return false;
        }
      })();

      const storage = isLocalStorageAvailable ? localStorage : sessionStorage;
      storage.setItem('auth_token', String(data.token));
      storage.setItem('user_role', String(data.role));
      storage.setItem('user_name', String(data.name || 'Usuário'));

      const savedToken = storage.getItem('auth_token');
      const savedRole = storage.getItem('user_role');

      if (!savedToken || !savedRole) {
        throw new Error('Falha ao salvar dados de autenticação.');
      }

      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 300);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Erro ao fazer login';
      setError(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#F8F9FB] flex">
      <div className="hidden lg:flex lg:w-[42%] xl:w-[38%] bg-[#1A1D26] relative overflow-hidden items-center justify-center p-12">
        <div
          className="absolute inset-0 opacity-40"
          style={{
            background:
              'radial-gradient(circle at 20% 30%, rgba(16,82,224,0.45) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(182,146,246,0.35) 0%, transparent 45%)',
          }}
        />
        <div className="relative z-10 max-w-sm text-center">
          <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur flex items-center justify-center mx-auto mb-8 p-3">
            <img src={logoMvlBranco} alt="MVLopes" className="w-full h-full object-contain" />
          </div>
          <h2 className="text-2xl font-bold text-white mb-3">Painel MVLopes</h2>
          <p className="text-slate-400 text-sm leading-relaxed">
            Acompanhe métricas do site, gerencie usuários e configure o sistema em um ambiente seguro.
          </p>
        </div>
      </div>

      <div className="flex-1 flex items-center justify-center p-6 sm:p-10">
        <div className="w-full max-w-md">
          <a
            href="/"
            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-8 transition-colors"
          >
            <ArrowLeft size={16} />
            Voltar ao site
          </a>

          <div className="panel-card p-8 sm:p-10">
            <div className="flex items-center gap-3 mb-8">
              <div className="w-11 h-11 rounded-xl bg-[#1A1D26] flex items-center justify-center shrink-0">
                <Shield className="w-5 h-5 text-white" strokeWidth={1.75} />
              </div>
              <div>
                <h1 className="text-xl font-bold text-foreground">Acesso ao sistema</h1>
                <p className="text-sm text-muted-foreground">Entre com suas credenciais</p>
              </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-5">
              {error && (
                <div className="text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                  {error}
                </div>
              )}

              <div>
                <label htmlFor="email" className="block text-sm font-medium text-foreground mb-1.5">
                  E-mail
                </label>
                <input
                  type="email"
                  id="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  className="panel-input"
                  placeholder="seu@email.com"
                />
              </div>

              <div>
                <label htmlFor="password" className="block text-sm font-medium text-foreground mb-1.5">
                  Senha
                </label>
                <input
                  type="password"
                  id="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  className="panel-input"
                  placeholder="••••••••"
                />
              </div>

              <button type="submit" disabled={isLoading} className="panel-btn-primary w-full py-3">
                {isLoading ? 'Entrando…' : 'Entrar'}
                <LogIn size={18} />
              </button>
            </form>
          </div>

          <p className="text-center text-xs text-muted-foreground mt-6">
            © {new Date().getFullYear()} MVLopes. Acesso restrito.
          </p>
        </div>
      </div>
    </div>
  );
}
