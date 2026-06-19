import { useCallback, useEffect, useState } from 'react';
import {
  Users,
  Settings,
  Shield,
  LogOut,
  X,
  Bell,
  ChevronDown,
  Menu,
  LayoutDashboard,
  Calendar,
  TrendingUp,
  Repeat,
  Wallet,
  Tags,
  CalendarDays,
  Inbox,
} from 'lucide-react';
import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';
import FinAnoPanel from './finance/FinAnoPanel';
import FinCategoriasPanel from './finance/FinCategoriasPanel';
import FinMesPanel from './finance/FinMesPanel';
import FinProjecaoPanel from './finance/FinProjecaoPanel';
import FinRecorrenciasPanel from './finance/FinRecorrenciasPanel';
import FinSaldoPanel from './finance/FinSaldoPanel';
import ProjBacklogPanel from './projetos/ProjBacklogPanel';
import ProjSemanaPanel from './projetos/ProjSemanaPanel';

type PanelSection =
  | 'fin-ano'
  | 'fin-mes'
  | 'fin-projecao'
  | 'fin-recorrencias'
  | 'fin-saldo'
  | 'fin-categorias'
  | 'proj-semana'
  | 'proj-backlog'
  | 'users'
  | 'settings';

const sectionTitles: Record<PanelSection, string> = {
  'fin-ano': 'Finanças — Dashboard',
  'fin-mes': 'Finanças — Mês',
  'fin-projecao': 'Finanças — Projeção',
  'fin-recorrencias': 'Finanças — Recorrências',
  'fin-saldo': 'Finanças — Saldo inicial',
  'fin-categorias': 'Finanças — Categorias',
  'proj-semana': 'Projetos — Semana',
  'proj-backlog': 'Projetos — Backlog',
  users: 'Usuários',
  settings: 'Configurações',
};

export default function Dashboard() {
  const [mounted, setMounted] = useState(false);
  const [authError, setAuthError] = useState('');
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [activeSection, setActiveSection] = useState<PanelSection>('fin-ano');
  const [finMes, setFinMes] = useState(new Date().toISOString().slice(0, 7));
  const [users, setUsers] = useState<
    Array<{ id: number; nome: string; email: string; perfis: string[]; isRoot: boolean }>
  >([]);
  const [loadingUsers, setLoadingUsers] = useState(false);
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  const checkAuth = useCallback(() => {
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    const role = localStorage.getItem('user_role') || sessionStorage.getItem('user_role');

    if (!token || role !== 'root') {
      setAuthError('Você não está autenticado ou não tem permissão para acessar esta página.');
      setTimeout(() => {
        window.location.href = '/login';
      }, 2500);
      return false;
    }
    return true;
  }, []);

  useEffect(() => {
    setMounted(true);
    checkAuth();
  }, [checkAuth]);

  useEffect(() => {
    const mq = window.matchMedia('(max-width: 1023px)');
    const apply = () => {
      const mobile = mq.matches;
      setIsMobile(mobile);
      if (mobile) {
        setSidebarOpen(true);
        setMobileNavOpen(false);
      }
    };
    apply();
    mq.addEventListener('change', apply);
    return () => mq.removeEventListener('change', apply);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_role');
    localStorage.removeItem('user_name');
    sessionStorage.removeItem('auth_token');
    sessionStorage.removeItem('user_role');
    sessionStorage.removeItem('user_name');
    window.location.href = '/login';
  };

  const loadUsers = async () => {
    setLoadingUsers(true);
    setPasswordError('');
    setPasswordSuccess('');

    try {
      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
      const response = await fetch('/api/users.php', {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        credentials: 'include',
      });

      const data = await response.json();

      if (data.success) {
        setUsers(data.users || []);
      } else {
        setPasswordError(data.message || 'Erro ao carregar usuários.');
      }
    } catch {
      setPasswordError('Erro ao carregar usuários.');
    } finally {
      setLoadingUsers(false);
    }
  };

  const handleChangePassword = async () => {
    setPasswordError('');
    setPasswordSuccess('');

    if (!selectedUserId) {
      setPasswordError('Selecione um usuário.');
      return;
    }

    if (!newPassword || newPassword.length < 6) {
      setPasswordError('A senha deve ter pelo menos 6 caracteres.');
      return;
    }

    if (newPassword !== confirmPassword) {
      setPasswordError('As senhas não coincidem.');
      return;
    }

    try {
      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
      const response = await fetch('/api/change-password.php', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ userId: selectedUserId, newPassword }),
      });

      const data = await response.json();

      if (data.success) {
        setPasswordSuccess('Senha alterada com sucesso!');
        setNewPassword('');
        setConfirmPassword('');
        setSelectedUserId(null);
      } else {
        setPasswordError(data.message || 'Erro ao alterar senha.');
      }
    } catch {
      setPasswordError('Erro ao alterar senha.');
    }
  };

  useEffect(() => {
    if (activeSection === 'settings') {
      loadUsers();
    }
  }, [activeSection]);

  const userName =
    localStorage.getItem('user_name') || sessionStorage.getItem('user_name') || 'Usuário';

  const navItemClass = (section: PanelSection) =>
    `panel-nav-item ${activeSection === section ? 'panel-nav-item-active' : ''}`;

  const goFinMes = (ym: string) => {
    setFinMes(ym);
    setActiveSection('fin-mes');
    setMobileNavOpen(false);
  };

  const selectSection = (section: PanelSection) => {
    setActiveSection(section);
    setMobileNavOpen(false);
  };

  const navExpanded = isMobile ? true : sidebarOpen;

  if (!mounted) {
    return (
      <div className="min-h-screen bg-[#F8F9FB] flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-10 w-10 border-2 border-slate-200 border-t-[#1A1D26] mx-auto mb-3" />
          <p className="text-sm text-slate-500">Carregando painel…</p>
        </div>
      </div>
    );
  }

  if (authError) {
    return (
      <div className="min-h-screen bg-[#F8F9FB] flex items-center justify-center p-6">
        <div className="panel-card max-w-md text-center">
          <p className="text-red-700 text-sm">{authError}</p>
          <p className="text-slate-500 text-xs mt-2">Redirecionando para o login…</p>
        </div>
      </div>
    );
  }

  const financeNav: { id: PanelSection; icon: typeof Wallet; label: string }[] = [
    { id: 'fin-ano', icon: LayoutDashboard, label: 'Dashboard' },
    { id: 'fin-mes', icon: Calendar, label: 'Mês' },
    { id: 'fin-projecao', icon: TrendingUp, label: 'Projeção' },
    { id: 'fin-recorrencias', icon: Repeat, label: 'Recorrências' },
    { id: 'fin-categorias', icon: Tags, label: 'Categorias' },
    { id: 'fin-saldo', icon: Wallet, label: 'Saldo inicial' },
  ];

  const projetosNav: { id: PanelSection; icon: typeof CalendarDays; label: string }[] = [
    { id: 'proj-semana', icon: CalendarDays, label: 'Semana' },
    { id: 'proj-backlog', icon: Inbox, label: 'Backlog' },
  ];

  return (
    <div className="min-h-screen bg-[#F8F9FB] text-[#1A1D26] flex">
      {mobileNavOpen && (
        <button
          type="button"
          className="fixed inset-0 z-30 bg-black/40 lg:hidden"
          aria-label="Fechar menu"
          onClick={() => setMobileNavOpen(false)}
        />
      )}

      <aside
        className={`
          fixed lg:static inset-y-0 left-0 z-40 flex flex-col shrink-0 bg-white border-r border-slate-200
          transition-transform duration-300 lg:transition-[width]
          ${mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
          ${navExpanded ? 'w-64' : 'w-[72px]'}
        `}
      >
        <div className="p-4 sm:p-5 border-b border-slate-100">
          <div className={`flex items-center ${navExpanded ? 'justify-between' : 'justify-center'}`}>
            <div className={`flex items-center gap-3 ${!navExpanded ? 'justify-center' : ''}`}>
              <div className="w-10 h-10 rounded-xl bg-[#1A1D26] flex items-center justify-center p-1.5 shrink-0">
                <img src={logoMvlBranco} alt="MVLopes" className="w-full h-full object-contain" />
              </div>
              {navExpanded && (
                <div>
                  <p className="text-sm font-bold leading-tight">MVLopes</p>
                  <p className="text-[11px] text-slate-500">Painel interno</p>
                </div>
              )}
            </div>
            {navExpanded && (
              <button
                type="button"
                onClick={() => (isMobile ? setMobileNavOpen(false) : setSidebarOpen(false))}
                className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                aria-label="Fechar menu"
              >
                <X size={18} />
              </button>
            )}
          </div>
          {!navExpanded && !isMobile && (
            <div className="mt-3 flex justify-center">
              <button
                type="button"
                onClick={() => setSidebarOpen(true)}
                className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                aria-label="Expandir menu"
              >
                <Menu size={18} />
              </button>
            </div>
          )}
        </div>

        <nav className="flex-1 px-3 py-4 sm:py-5 space-y-1 overflow-y-auto">
          {navExpanded && (
            <p className="px-3.5 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
              Finanças
            </p>
          )}
          {financeNav.map(({ id, icon: Icon, label }) => (
            <button key={id} type="button" onClick={() => selectSection(id)} className={navItemClass(id)}>
              <Icon size={18} strokeWidth={1.75} />
              {navExpanded && <span>{label}</span>}
            </button>
          ))}

          {navExpanded && (
            <p className="px-3.5 mt-6 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
              Projetos
            </p>
          )}
          {projetosNav.map(({ id, icon: Icon, label }) => (
            <button key={id} type="button" onClick={() => selectSection(id)} className={navItemClass(id)}>
              <Icon size={18} strokeWidth={1.75} />
              {navExpanded && <span>{label}</span>}
            </button>
          ))}

          {navExpanded && (
            <p className="px-3.5 mt-6 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
              Sistema
            </p>
          )}
          <button type="button" onClick={() => selectSection('users')} className={navItemClass('users')}>
            <Users size={18} strokeWidth={1.75} />
            {navExpanded && <span>Usuários</span>}
          </button>
          <button type="button" onClick={() => selectSection('settings')} className={navItemClass('settings')}>
            <Settings size={18} strokeWidth={1.75} />
            {navExpanded && <span>Configurações</span>}
          </button>
        </nav>

        <div className="p-3 border-t border-slate-100 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
          <button
            type="button"
            onClick={handleLogout}
            className={`panel-nav-item text-red-600 hover:bg-red-50 hover:text-red-700 ${!navExpanded ? 'justify-center' : ''}`}
          >
            <LogOut size={18} strokeWidth={1.75} />
            {navExpanded && <span>Sair</span>}
          </button>
        </div>
      </aside>

      <main className="flex-1 min-w-0 flex flex-col w-full lg:w-auto">
        <header className="sticky top-0 z-20 bg-[#F8F9FB]/95 backdrop-blur border-b border-slate-200 px-4 sm:px-6 py-3 sm:py-4">
          <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-3 min-w-0">
              <button
                type="button"
                onClick={() => setMobileNavOpen(true)}
                className="lg:hidden p-2 -ml-1 rounded-xl text-slate-600 hover:bg-white border border-transparent hover:border-slate-200"
                aria-label="Abrir menu"
              >
                <Menu size={20} />
              </button>
              <div className="min-w-0">
                <h1 className="text-base sm:text-xl font-bold truncate">{sectionTitles[activeSection]}</h1>
                <p className="text-xs sm:text-sm text-slate-500 truncate">Olá, {userName}</p>
              </div>
            </div>
            <div className="flex items-center gap-2 sm:gap-3 shrink-0">
              <button
                type="button"
                className="hidden sm:flex p-2 rounded-xl text-slate-500 hover:bg-white border border-transparent hover:border-slate-200 transition-colors relative"
                aria-label="Notificações"
              >
                <Bell size={18} />
              </button>
              <div className="flex items-center gap-2 sm:gap-3 sm:pl-3 sm:border-l sm:border-slate-200">
                <div className="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center text-sm font-semibold">
                  {userName.charAt(0).toUpperCase()}
                </div>
                <div className="hidden sm:block">
                  <p className="text-sm font-semibold leading-tight">{userName}</p>
                  <p className="text-xs text-slate-500">Root</p>
                </div>
                <ChevronDown size={16} className="text-slate-400 hidden sm:block" />
              </div>
            </div>
          </div>
        </header>

        <div className="p-4 sm:p-6 flex-1 pb-[max(1rem,env(safe-area-inset-bottom))]">
          {activeSection === 'fin-ano' && <FinAnoPanel onOpenMes={goFinMes} />}
          {activeSection === 'fin-mes' && <FinMesPanel mes={finMes} onMesChange={setFinMes} />}
          {activeSection === 'fin-projecao' && <FinProjecaoPanel />}
          {activeSection === 'fin-recorrencias' && <FinRecorrenciasPanel />}
          {activeSection === 'fin-categorias' && <FinCategoriasPanel />}
          {activeSection === 'fin-saldo' && <FinSaldoPanel />}
          {activeSection === 'proj-semana' && <ProjSemanaPanel />}
          {activeSection === 'proj-backlog' && <ProjBacklogPanel />}

          {activeSection === 'users' && (
            <div className="panel-card max-w-3xl">
              <h2 className="text-lg font-semibold mb-2">Gerenciamento de usuários</h2>
              <p className="text-sm text-slate-500">Em breve. Use Configurações para alterar senhas.</p>
            </div>
          )}

          {activeSection === 'settings' && (
            <div className="panel-card max-w-3xl">
              <h2 className="text-lg font-semibold mb-1 flex items-center gap-2">
                <Shield size={20} />
                Alterar senhas
              </h2>
              <p className="text-sm text-slate-500 mb-6">Selecione um usuário e defina uma nova senha.</p>

              {passwordError && (
                <div className="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-red-700 text-sm">
                  {passwordError}
                </div>
              )}
              {passwordSuccess && (
                <div className="mb-4 p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-700 text-sm">
                  {passwordSuccess}
                </div>
              )}

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-1.5">Selecionar usuário</label>
                  {loadingUsers ? (
                    <p className="text-sm text-slate-500">Carregando…</p>
                  ) : (
                    <select
                      value={selectedUserId || ''}
                      onChange={(e) =>
                        setSelectedUserId(e.target.value ? parseInt(e.target.value, 10) : null)
                      }
                      className="panel-input"
                    >
                      <option value="">Selecione um usuário</option>
                      {users.map((user) => (
                        <option key={user.id} value={user.id}>
                          {user.nome} ({user.email}){user.isRoot ? ' — Root' : ''}
                        </option>
                      ))}
                    </select>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1.5">Nova senha</label>
                  <input
                    type="password"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    className="panel-input"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1.5">Confirmar senha</label>
                  <input
                    type="password"
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    className="panel-input"
                  />
                </div>
                <button
                  type="button"
                  onClick={handleChangePassword}
                  disabled={!selectedUserId || !newPassword || !confirmPassword || loadingUsers}
                  className="panel-btn-primary w-full py-2.5"
                >
                  Alterar senha
                </button>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
