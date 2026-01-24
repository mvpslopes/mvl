import { useState } from 'react';
import { LogIn, X } from 'lucide-react';
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
      // Tentar diferentes caminhos da API
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
            credentials: 'include', // Incluir cookies de sessão
          });

          if (response.ok) {
            data = (await response.json()) as AuthApiResponse;
            break;
          } else {
            // Tentar ler a resposta mesmo em caso de erro
            try {
              data = (await response.json()) as AuthApiResponse;
            } catch {
              // Se não conseguir parsear JSON, continuar para próximo caminho
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

      console.log('📥 Resposta bruta da API:', {
        status: response.status,
        ok: response.ok,
        statusText: response.statusText,
        headers: Object.fromEntries(response.headers.entries()),
        data: data,
        dataString: JSON.stringify(data)
      });

      if (!response.ok || !data.success) {
        console.error('❌ Erro na resposta da API:', {
          status: response.status,
          data: data
        });
        throw new Error(data.message || 'Credenciais inválidas');
      }

      console.log('📥 Dados recebidos da API:', {
        success: data.success,
        hasToken: !!data.token,
        hasRole: !!data.role,
        hasName: !!data.name,
        tokenType: typeof data.token,
        roleType: typeof data.role,
        fullData: data
      });

      // Verificar se os dados necessários estão presentes
      if (!data.token || !data.role) {
        console.error('❌ Dados incompletos da API:', {
          token: data.token,
          role: data.role,
          name: data.name,
          email: data.email,
          fullResponse: data
        });
        throw new Error(`Resposta da API incompleta. Token: ${data.token ? 'OK' : 'FALTANDO'}, Role: ${data.role ? 'OK' : 'FALTANDO'}`);
      }

      // Verificar se localStorage está disponível
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

      console.log('💾 localStorage disponível?', isLocalStorageAvailable);

      // Tentar salvar no localStorage com tratamento de erro
      try {
        const storage = isLocalStorageAvailable ? localStorage : sessionStorage;
        const storageName = isLocalStorageAvailable ? 'localStorage' : 'sessionStorage';
        
        console.log(`💾 Usando ${storageName} para salvar dados...`);
        
        storage.setItem('auth_token', String(data.token));
        storage.setItem('user_role', String(data.role));
        storage.setItem('user_name', String(data.name || 'Usuário'));
        
        // Forçar sincronização - ler imediatamente após salvar
        const savedToken = storage.getItem('auth_token');
        const savedRole = storage.getItem('user_role');
        
        console.log('✅ Dados salvos, verificando...', {
          tokenSalvo: !!savedToken,
          roleSalvo: !!savedRole,
          tokenMatch: savedToken === String(data.token),
          roleMatch: savedRole === String(data.role)
        });
        
        if (!savedToken || !savedRole) {
          throw new Error(`Falha ao salvar dados no ${storageName}`);
        }
        
        // Dados já foram verificados acima, então estão salvos
        console.log(`✅ Dados salvos com sucesso no ${storageName}`);
        
        console.log('✅ Login realizado com sucesso:', { 
          token: data.token ? `Token salvo (${data.token.length} caracteres)` : 'Sem token',
          role: data.role,
          name: data.name,
          storage: storageName,
          fullData: data
        });

        // Pequeno delay para garantir que o localStorage seja salvo
        setTimeout(() => {
          console.log('🔄 Redirecionando para dashboard...');
          // Forçar recarregamento completo da página para garantir que o App.tsx detecte a mudança
          window.location.href = '/dashboard';
        }, 300);
        
      } catch (storageError) {
        console.error('❌ Erro ao salvar no localStorage:', storageError);
        setError('Erro ao salvar dados de autenticação. Verifique se o navegador permite armazenamento local.');
        throw storageError;
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Erro ao fazer login';
      setError(errorMessage);
      console.error('Erro no login:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleCancel = () => {
    window.location.href = '/';
  };

  return (
    <div className="min-h-screen bg-background text-foreground flex items-center justify-center px-4">
      <div className="max-w-md w-full">
        <div className="bg-card border border-white/10 rounded-2xl shadow-[0_20px_80px_rgba(0,0,0,0.45)] p-8 relative overflow-hidden">
          <div
            className="absolute -top-24 -right-24 h-72 w-72 rounded-full"
            style={{
              background:
                'radial-gradient(circle at center, rgba(16,82,224,0.35) 0%, rgba(16,82,224,0.08) 40%, rgba(16,82,224,0) 70%)',
            }}
          />
          <div className="text-center mb-8">
            <img
              src={logoMvlBranco}
              alt="MVLopes"
              className="h-10 w-auto mx-auto mb-5 relative"
            />
            <h1 className="text-2xl font-extrabold text-foreground">Acesso ao Sistema</h1>
            <p className="text-muted-foreground mt-2">Entre com suas credenciais para acessar o painel.</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {error}
              </div>
            )}

            <div>
              <label htmlFor="email" className="block text-sm font-semibold text-foreground mb-2">
                E-mail
              </label>
              <input
                type="email"
                id="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="w-full px-4 py-3 border border-white/10 rounded-lg bg-black/20 text-foreground placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-0 transition-shadow"
                placeholder="seu@email.com"
              />
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-semibold text-foreground mb-2">
                Senha
              </label>
              <input
                type="password"
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                className="w-full px-4 py-3 border border-white/10 rounded-lg bg-black/20 text-foreground placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-0 transition-shadow"
                placeholder="••••••••"
              />
            </div>

            <div className="flex gap-3">
              <button
                type="button"
                onClick={handleCancel}
                disabled={isLoading}
                className="flex-1 px-4 py-3 border border-white/10 rounded-lg text-foreground/80 hover:bg-white/5 transition-colors flex items-center justify-center gap-2"
              >
                <X size={20} />
                Cancelar
              </button>
              <button
                type="submit"
                disabled={isLoading}
                className="flex-1 btn-brand flex items-center justify-center gap-2"
              >
                {isLoading ? 'Entrando...' : 'Entrar'}
                <LogIn size={20} />
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

